<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Region;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use App\Services\CurrencyService;


class CategoryController extends Controller
{

    protected $currencyService;

    public function __construct(CurrencyService $currencyService) {
        $this->currencyService = $currencyService;
    }

    public function index($slug, Request $request)
{
    $usdRate = $this->currencyService->getDollarRate();
    $user = $request->user();
    $category = Category::where('slug', $slug)->with('subcategories.products')->first();
    $selectedSubcategory = null;
    $attributes = collect();
    $attributeValues = [];
    $mainRegions = Region::whereNull('parent_id')->get();
    $selectedRegion = null;
    $selectedCity = null;
    $regionChildren = collect();

    // Start with a base query
    $productsQuery = Product::query();
    
    if ($category) {
        if ($request->has('subcategory')) {
            $selectedSubcategory = $category->subcategories->where('slug', $request->input('subcategory'))->first();

            if ($selectedSubcategory) {
                $productsQuery->where('subcategory_id', $selectedSubcategory->id);
                $attributes = $selectedSubcategory->attributes()->get();
            }
        } else {
            $subcategoryIds = $category->subcategories->pluck('id')->toArray();
            $productsQuery->whereIn('subcategory_id', $subcategoryIds);
        }
    }

    foreach ($attributes as $attribute) {
        $values = $attribute->attributeValues()->get();
        $attributeValues[$attribute->id] = $values;
    }

    // Apply attribute filters
    $attributeFilters = Arr::except($request->query(), ['subcategory', 'currency', 'page', 'city', 'region', 'price_from', 'price_to', 'type', 'date_filter', 'with_images_only', 'search']);

    if (!empty($attributeFilters)) {
        foreach ($attributeFilters as $attributeSlug => $valueId) {
            $productsQuery->whereHas('attributeValues', function ($query) use ($attributeSlug, $valueId) {
                $query->whereHas('attributes', function ($subQuery) use ($attributeSlug) {
                    $subQuery->where('attributes.slug', $attributeSlug);
                })->where('attribute_values.id', $valueId);
            });
        }
    }

    // Price filters
    if ($request->has('price_from') && $request->input('currency') === 'uzs') {
       $priceFrom = $request->input('price_from');

       $productsQuery->where(function ($query) use ($priceFrom, $usdRate) {
        $query->where(function ($q) use ($priceFrom) {
            $q->where('currency', 'сум')->where('price', '>=', $priceFrom);
        })
        ->orWhere(function ($q) use($usdRate, $priceFrom) {
            $q->where('currency', 'доллар')->where('price', '>=', $priceFrom / $usdRate);
        });
       });
    }

    if ($request->has('price_from') && $request->input('currency') === 'usd') {
        $priceFrom = $request->input('price_from');

        $productsQuery->where(function ($query) use ($usdRate, $priceFrom) {
            $query->where(function ($q) use ($usdRate, $priceFrom) {
                $q->where('currency', 'доллар')->where('price', '>=', $priceFrom);
            }) ->orWhere(function ($q) use($usdRate, $priceFrom) {
                $q->where('currency', 'сум')->where('price', '>=', $priceFrom * $usdRate);
            });
        });
    }


    if ($request->has('price_to') && $request->input('currency') === 'uzs') {
        $priceTo = $request->input('price_to');

        $productsQuery->where(function ($query) use ($priceTo, $usdRate) {
            $query->where(function ($q) use($priceTo, $usdRate) {
                $q->where('currency', 'сум')->where('price', '<=', $priceTo);
            }) ->orWhere(function ($q) use ($priceTo, $usdRate) {
                $q->where('currency', 'доллар')->where('price', '<=', $priceTo / $usdRate);
            });
        });
    }

    if ($request->has('price_to') && $request->input('currency') === 'usd') {
        $priceTo = $request->input('price_to');

        $productsQuery->where(function ($query) use($priceTo, $usdRate) {
            $query->where(function ($q) use($priceTo, $usdRate) {
                $q->where('currency', 'доллар')->where('price', '<=', $priceTo);
            }) ->orWhere(function ($q) use($priceTo, $usdRate) {
                $q->where('currency', 'сум')->where('price', '<=', $priceTo * $usdRate);
            });
        });
    }

    // Type filter
    if ($request->has('type')) {
        $productsQuery->where('type', $request->input('type'));
    }

    // Date filter
    if ($request->has('date_filter')) {
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
    }

    // Filter for products with images only
    if ($request->has('with_images_only') && $request->input('with_images_only') === 'yes') {
        $productsQuery->whereHas('images');
    }

    // Search filter
    if ($request->has('search')) {
        $input = $request->input('search');
        $inputArray = explode(' ', strtolower($input));

        $productsQuery->where(function ($query) use ($inputArray) {
            foreach ($inputArray as $word) {
                $query->orWhere('name', 'LIKE', "%$word%")
                      ->orWhere('description', 'LIKE', "%$word%");
            }
        });
    }

    // Region filter
    if ($request->has('region')) {
        $selectedRegion = Region::where('slug', $request->input('region'))->first();
        if ($selectedRegion) {
            $regionIds = array_merge([$selectedRegion->id], $selectedRegion->children()->pluck('id')->toArray());
            $regionChildren = $selectedRegion->children;

            $productsQuery->whereIn('region_id', $regionIds);
        }
    }

    // City filter
    if ($request->has('city')) {
        $selectedCity = Region::where('slug', $request->input('city'))->first();
        if ($selectedCity) {
            $productsQuery->where('region_id', $selectedCity->id);
        }
    }

    // Paginate the results (10 items per page)
    $products = $productsQuery->paginate(1);

    return view('category', compact('category', 'user', 'usdRate', 'selectedCity', 'selectedRegion', 'regionChildren', 'products', 'mainRegions', 'selectedSubcategory', 'attributes', 'attributeValues'));
}

}
