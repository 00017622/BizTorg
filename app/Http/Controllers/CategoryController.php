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

        if ($request->has('price_from') || $request->has('price_to')) {
            $currency = $request->input('currency', 'usd');
            if (!$usdRate || $usdRate <= 0) {
                Log::info('USD Rate: USD rate not available');
                $usdRate = 12900; 
            }
        
            $priceFrom = round((float) $request->input('price_from', 0), 2);
            $priceTo = round((float) $request->input('price_to', PHP_INT_MAX), 2);
        
            Log::info('Price From: ' . $priceFrom);
            Log::info('Price To: ' . $priceTo);
        
            $productsQuery->where(function ($query) use ($priceFrom, $priceTo, $currency, $usdRate) {
                $query->where(function ($q) use ($priceFrom, $priceTo, $currency, $usdRate) {
                    if ($currency === 'usd') {
                        $q->where(function ($usdQuery) use ($priceFrom, $priceTo) {
                            $usdQuery->where('currency', 'доллар')
                                     ->whereBetween('price', [$priceFrom, $priceTo]);
                        })->orWhere(function ($uzsQuery) use ($priceFrom, $priceTo, $usdRate) {
                            $uzsQuery->where('currency', 'сум')
                                     ->whereBetween('price', [$priceFrom * $usdRate, $priceTo * $usdRate]);
                        });
                    } elseif ($currency === 'uzs') {
                        $q->where(function ($uzsQuery) use ($priceFrom, $priceTo) {
                            $uzsQuery->where('currency', 'сум')
                                     ->whereBetween('price', [$priceFrom, $priceTo]);
                        })->orWhere(function ($usdQuery) use ($priceFrom, $priceTo, $usdRate) {
                            $usdQuery->where('currency', 'доллар')
                                     ->whereBetween('price', [$priceFrom / $usdRate, $priceTo / $usdRate]);
                        });
                    }
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

        if ($request->has('type')) {
            $productsQuery->where('type', $request->input('type'));
        }

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

        if ($request->has('with_images_only') && $request->input('with_images_only') === 'yes') {
            $productsQuery->whereHas('images');
        }

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

        if ($request->has('region')) {
            $selectedRegion = Region::where('slug', $request->input('region'))->first();
            if ($selectedRegion) {
                $regionIds = array_merge([$selectedRegion->id], $selectedRegion->children()->pluck('id')->toArray());
                $regionChildren = $selectedRegion->children;

                $productsQuery->whereIn('region_id', $regionIds);
            }
        }

        if ($request->has('city')) {
            $selectedCity = Region::where('slug', $request->input('city'))->first();
            if ($selectedCity) {
                $productsQuery->where('region_id', $selectedCity->id);
            }
        }

        $products = $productsQuery->paginate(5);

        return view('category', compact('category', 'user', 'usdRate', 'selectedCity', 'selectedRegion', 'regionChildren', 'products', 'mainRegions', 'selectedSubcategory', 'attributes', 'attributeValues'));
    }

    public function filterProducts($slug, Request $request)
    {
        $usdRate = $this->currencyService->getDollarRate();
        if (!$usdRate || $usdRate <= 0) {
            $usdRate = 12900;
        }

        $category = Category::where('slug', $slug)->with('subcategories')->first();
        $productsQuery = Product::query();

        if ($category) {
            if ($request->has('subcategory')) {
                $selectedSubcategory = $category->subcategories->where('slug', $request->input('subcategory'))->first();
                if ($selectedSubcategory) {
                    $productsQuery->where('subcategory_id', $selectedSubcategory->id);
                } else {
                    $subcategoryIds = $category->subcategories->pluck('id')->toArray();
                    $productsQuery->whereIn('subcategory_id', $subcategoryIds);
                }
            }

            $attributeFilters = Arr::except($request->all(), ['subcategory', 'currency', 'page', 'city', 'region', 'price_from', 'price_to', 'type', 'date_filter', 'with_images_only', 'search']);
            if (!empty($attributeFilters)) {
                foreach ($attributeFilters as $attributeSlug => $valueId) {
                    $productsQuery->whereHas('attributeValues', function ($query) use ($attributeSlug, $valueId) {
                        $query->whereHas('attributes', function ($subQuery) use ($attributeSlug) {
                            $subQuery->where('attributes.slug', $attributeSlug);
                        })->where('attribute_values.id', $valueId);
                    });
                }
            }

            if ($request->has('price_from') || $request->has('price_to')) {
                $currency = $request->input('currency', 'usd');
                $priceFrom = round((float) $request->input('price_from', 0), 2);
                $priceTo = round((float) $request->input('price_to', PHP_INT_MAX), 2);

                $productsQuery->where(function ($query) use ($priceFrom, $priceTo, $currency, $usdRate) {
                    if ($currency === 'usd') {
                        $query->where(function ($usdQuery) use ($priceFrom, $priceTo) {
                            $usdQuery->where('currency', 'доллар')->whereBetween('price', [$priceFrom, $priceTo]);
                        })->orWhere(function ($uzsQuery) use ($priceFrom, $priceTo, $usdRate) {
                            $uzsQuery->where('currency', 'сум')->whereBetween('price', [$priceFrom * $usdRate, $priceTo * $usdRate]);
                        });
                    } elseif ($currency === 'uzs') {
                        $query->where(function ($uzsQuery) use ($priceFrom, $priceTo) {
                            $uzsQuery->where('currency', 'сум')->whereBetween('price', [$priceFrom, $priceTo]);
                        })->orWhere(function ($usdQuery) use ($priceFrom, $priceTo, $usdRate) {
                            $usdQuery->where('currency', 'доллар')->whereBetween('price', [$priceFrom / $usdRate, $priceTo / $usdRate]);
                        });
                    }
                });
            }

            if ($request->has('region')) {
                $selectedRegion = Region::where('slug', $request->input('region'))->first();
                if ($selectedRegion) {
                    $regionIds = array_merge([$selectedRegion->id], $selectedRegion->children()->pluck('id')->toArray());
                    $productsQuery->whereIn('region_id', $regionIds);
                }
            }

            if ($request->has('city')) {
                $selectedCity = Region::where('slug', $request->input('city'))->first();
                if ($selectedCity) {
                    $productsQuery->where('region_id', $selectedCity->id);
                }
            }

            if ($request->has('type')) {
                $productsQuery->where('type', $request->input('type'));
            }

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

            $page = $request->input('page', 5);
            $perPage = 5; // Match your pagination
            $products = $productsQuery->paginate($perPage, ['*'], 'page', $page);

            return response()->json([
                'products' => view('components.card', compact('products', 'usdRate'))->render(), // Added $usdRate
                'next_page' => $products->nextPageUrl() ? $products->currentPage() + 1 : null,
                'has_more' => $products->hasMorePages(),
            ]);
        }

        return response()->json(['error' => 'Category not found'], 404);
    }
}