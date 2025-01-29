<?php 

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attribute;
use App\Models\Product;
use App\Models\Region;
use App\Models\Subcategory;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use App\Services\CurrencyService;
use Cache;

class ProductController extends Controller {
    protected $currencyService;

    public function __construct(CurrencyService $currencyService)
    {
        $this->currencyService = $currencyService;
    }

    public function getProducts ($subcategoryId) {
        $cacheKey = "products_by_{$subcategoryId}";
        $cacheDuration = $cacheDuration = 60 * 30; 
        try {
            $products = Cache::remember($cacheKey, $cacheDuration, function () use($subcategoryId) {
                return Product::where('subcategory_id', $subcategoryId)->with(['images', 'region'])->get();
            });
            return response()->json([
                'products' => $products,
            ]);

        } catch(Exception $e) {
            return response()->json([
                'error' => 'Error occured' . $e,
            ]);
        }
    }

    public function getAttributes($subcategoryId) {
        try {
            $cacheKey = "attributes_{$subcategoryId}";
            $cacheDuration = 60 * 300; 
    
            return Cache::remember($cacheKey, $cacheDuration, function () use ($subcategoryId) {
                $selectedSubCategory = Subcategory::find($subcategoryId);
    
                if (!$selectedSubCategory) {
                    return response()->json([
                        'error' => 'No such subcategory found',
                    ], 404);
                }
    
                $categoryId = $selectedSubCategory->category->id;
                $categoryName = $selectedSubCategory->category->name;
                $categoryImage = $selectedSubCategory->category->image_url;
    
                $attributes = $selectedSubCategory->attributes()->with('attributeValues')->get();
    
                return response()->json([
                    'attributes' => $attributes,
                    'categoryId' => $categoryId,
                    'categoryName' => $categoryName,
                    'categoryImage' => $categoryImage
                ], 200);
            });
    
        } catch (Exception $e) {
            return response()->json([
                'error' => 'Error occurred: ' . $e->getMessage(),
            ], 500);
        }
    }
    
    public function filterProducts(Request $request) {
        try {
            $usdRate = $this->currencyService->getDollarRate() ?? 12900;
    
            $cacheKey = 'filtered_products_' . md5(json_encode($request->all()));
            $cacheDuration = 60 * 10;
    
            return Cache::remember($cacheKey, $cacheDuration, function () use ($request, $usdRate) {
                $productsQuery = Product::query();

                $productsQuery->when($request->filled('subcategory_id'), function ($query) use ($request) {
                    $query->where('subcategory_id', $request->input('subcategory_id'));
                }, function ($query) use ($request) {
                    if ($request->filled('category_id')) {
                        $subcategoryIds = Subcategory::where('category_id', $request->input('category_id'))->pluck('id');
                        $query->whereIn('subcategory_id', $subcategoryIds);
                    }
                });
    
                $productsQuery->when($request->filled('attribute_values'), function ($query) use ($request) {
                    $attributeValues = is_string($request->input('attribute_values'))
                        ? explode(',', $request->input('attribute_values'))
                        : $request->input('attribute_values');
    
                    $query->whereHas('attributeValues', function ($subQuery) use ($attributeValues) {
                        $subQuery->whereIn('attribute_values.id', $attributeValues);
                    });
                });
    
                $productsQuery->when($request->filled('child_region_id') && $request->filled('parent_region_id'), function ($query) use ($request) {
                    $query->whereHas('region', function ($subQuery) use ($request) {
                        $subQuery->where('regions.id', $request->input('child_region_id'))
                                 ->where('regions.parent_id', $request->input('parent_region_id'));
                    });
                });
    
                if ($request->has('price_from') || $request->has('price_to')) {
                    $currency = $request->input('currency', 'usd');
                    $priceFrom = round((float) $request->input('price_from', 0), 2);
                    $priceTo = round((float) $request->input('price_to', PHP_INT_MAX), 2);
    
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
    
                $productsQuery->when($request->filled('sorting_type'), function ($query) use ($request) {
                    switch ($request->input('sorting_type')) {
                        case 'Новые':
                            $query->orderBy('created_at', 'desc');
                            break;
                        case 'Дешевые':
                            $query->orderBy('price', 'asc');
                            break;
                        case 'Дорогие':
                            $query->orderBy('price', 'desc');
                            break;
                    }
                });
    
                return $productsQuery->get();
            });
    
        } catch (Exception $e) {
            return response()->json([
                'error' => 'Error occurred: ' . $e->getMessage(),
            ], 500);
        }
    }
    
}