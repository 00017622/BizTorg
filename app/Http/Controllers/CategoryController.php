<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Region;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use App\Services\CurrencyService;

class CategoryController extends Controller
{
    protected $currencyService;

    public function __construct(CurrencyService $currencyService)
    {
        $this->currencyService = $currencyService;
    }

    public function index($slug, Request $request)
    {
        // Filter out empty query parameters
        $query = array_filter($request->query(), fn($value) => $value !== '' && $value !== null);
        $request->merge($query);

        $usdRate = $this->currencyService->getDollarRate() ?: 12900; // Default to 12900 if rate is invalid
        $user = $request->user();
        $category = Category::where('slug', $slug)->with(['subcategories' => function ($query) {
            $query->with(['products' => function ($query) {
                $query->with('images', 'attributeValues.attributes');
            }]);
        }])->firstOrFail();
        $selectedSubcategory = null;
        $attributes = collect();
        $attributeValues = [];
        $mainRegions = Region::whereNull('parent_id')->get();
        $selectedRegion = null;
        $selectedCity = null;
        $regionChildren = collect();

        $productsQuery = Product::query()->with('images', 'attributeValues.attributes');

        // Subcategory filtering
        if ($request->has('subcategory') && $request->input('subcategory')) {
            $selectedSubcategory = $category->subcategories->where('slug', $request->input('subcategory'))->first();
            if ($selectedSubcategory) {
                $productsQuery->where('subcategory_id', $selectedSubcategory->id);
                $attributes = $selectedSubcategory->attributes()->with('attributeValues')->get();
                Log::info('Subcategory filter applied', ['subcategory_id' => $selectedSubcategory->id, 'slug' => $request->input('subcategory')]);
            } else {
                Log::warning('Subcategory not found', ['slug' => $request->input('subcategory')]);
                $subcategoryIds = $category->subcategories->pluck('id')->toArray();
                $productsQuery->whereIn('subcategory_id', $subcategoryIds);
            }
        } else {
            $subcategoryIds = $category->subcategories->pluck('id')->toArray();
            $productsQuery->whereIn('subcategory_id', $subcategoryIds);
        }

        // Attribute filtering
        foreach ($attributes as $attribute) {
            $values = $attribute->attributeValues()->get();
            $attributeValues[$attribute->id] = $values;
        }

        $attributeFilters = Arr::except($request->query(), [
            'subcategory', 'currency', 'page', 'city', 'region', 'price_from', 'price_to',
            'type', 'date_filter', 'with_images_only', 'search'
        ]);

        if (!empty($attributeFilters)) {
            foreach ($attributeFilters as $attributeSlug => $valueId) {
                if (is_numeric($valueId)) {
                    $productsQuery->whereHas('attributeValues', function ($query) use ($attributeSlug, $valueId) {
                        $query->whereHas('attributes', function ($subQuery) use ($attributeSlug) {
                            $subQuery->where('attributes.slug', $attributeSlug);
                        })->where('attribute_values.id', $valueId);
                    });
                    Log::info('Attribute filter applied', ['attribute' => $attributeSlug, 'value_id' => $valueId]);
                }
            }
        }

        // Price filtering
        $currency = $request->input('currency', 'usd');
        $priceFrom = $request->has('price_from') && $request->input('price_from') !== '' ? (float) $request->input('price_from') : null;
        $priceTo = $request->has('price_to') && $request->input('price_to') !== '' ? (float) $request->input('price_to') : null;

        if ($priceFrom !== null || $priceTo !== null) {
            $productsQuery->where(function ($query) use ($priceFrom, $priceTo, $currency, $usdRate) {
                $priceFrom = $priceFrom ?? 0;
                $priceTo = $priceTo ?? PHP_INT_MAX;
                if ($currency === 'usd') {
                    $query->where(function ($q) use ($priceFrom, $priceTo) {
                        $q->where('currency', 'доллар')->whereBetween('price', [$priceFrom, $priceTo]);
                    })->orWhere(function ($q) use ($priceFrom, $priceTo, $usdRate) {
                        $q->where('currency', 'сум')->whereBetween('price', [$priceFrom * $usdRate, $priceTo * $usdRate]);
                    });
                } elseif ($currency === 'uzs') {
                    $query->where(function ($q) use ($priceFrom, $priceTo) {
                        $q->where('currency', 'сум')->whereBetween('price', [$priceFrom, $priceTo]);
                    })->orWhere(function ($q) use ($priceFrom, $priceTo, $usdRate) {
                        $q->where('currency', 'доллар')->whereBetween('price', [$priceFrom / $usdRate, $priceTo / $usdRate]);
                    });
                }
            });
            Log::info('Price filter applied', ['currency' => $currency, 'price_from' => $priceFrom, 'price_to' => $priceTo]);
        }

        // Region and city filtering
        if ($request->has('region') && $request->input('region') !== 'whole') {
            $selectedRegion = Region::where('slug', $request->input('region'))->first();
            if ($selectedRegion) {
                $regionIds = array_merge([$selectedRegion->id], $selectedRegion->children()->pluck('id')->toArray());
                $regionChildren = $selectedRegion->children;
                $productsQuery->whereIn('region_id', $regionIds);
                Log::info('Region filter applied', ['region_id' => $selectedRegion->id]);
            }
        }

        if ($request->has('city') && $request->input('city')) {
            $selectedCity = Region::where('slug', $request->input('city'))->first();
            if ($selectedCity) {
                $productsQuery->where('region_id', $selectedCity->id);
                Log::info('City filter applied', ['city_id' => $selectedCity->id]);
            }
        }

        // Type filtering
        if ($request->has('type') && in_array($request->input('type'), ['purchase', 'sale'])) {
            $productsQuery->where('type', $request->input('type'));
            Log::info('Type filter applied', ['type' => $request->input('type')]);
        }

        // Date filtering
        if ($request->has('date_filter') && in_array($request->input('date_filter'), ['new', 'expensive', 'cheap'])) {
            switch ($request->input('date_filter')) {
                case 'new':
                    $productsQuery->orderBy('created_at', 'desc');
                    break;
                case 'expensive':
                    $productsQuery->orderBy('price', 'desc');
                    break;
                case 'cheap':
                    $productsQuery->orderBy('price', 'asc');
                    break;
            }
            Log::info('Date filter applied', ['date_filter' => $request->input('date_filter')]);
        }

        // Image filtering
        if ($request->has('with_images_only') && $request->input('with_images_only') === 'yes') {
            $productsQuery->whereHas('images');
            Log::info('Image filter applied');
        }

        // Search filtering
        if ($request->has('search') && $request->input('search')) {
            $input = $request->input('search');
            $inputArray = explode(' ', strtolower(trim($input)));
            $productsQuery->where(function ($query) use ($inputArray) {
                foreach ($inputArray as $word) {
                    $query->where('name', 'LIKE', "%$word%")
                          ->orWhere('description', 'LIKE', "%$word%");
                }
            });
            Log::info('Search filter applied', ['search' => $input]);
        }

        // Paginate and log results
        $products = $productsQuery->paginate(12);
        Log::info('Products retrieved', ['query' => $request->query()]);

        return view('category', compact(
            'category', 'user', 'usdRate', 'selectedCity', 'selectedRegion',
            'regionChildren', 'products', 'mainRegions', 'selectedSubcategory',
            'attributes', 'attributeValues'
        ));
    }
}