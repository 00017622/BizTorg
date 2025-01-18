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

class ProductController extends Controller {
    protected $currencyService;

    public function __construct(CurrencyService $currencyService)
    {
        $this->currencyService = $currencyService;
    }

    public function getProducts ($subcategoryId) {
        try {
            $products = Product::where('subcategory_id', $subcategoryId)->with(['images', 'region'])->get();
            return response()->json([
                'products' => $products,
            ]);

        } catch(Exception $e) {
            return response()->json([
                'error' => 'Error occured' . $e,
            ]);
        }
    }

    public function getFilteredProducts ($subcategoryId) {
        try {
            $selectedSubCategory = Subcategory::find($subcategoryId);
            $categoryId = $selectedSubCategory->category->id;
            $categoryName = $selectedSubCategory->category->name;
            $categoryImage = $selectedSubCategory->category->image_url;

            if (!$selectedSubCategory) {
                return response()->json([
                    'error' => 'No such subcategory found',
                ], 404);
            }

            $attributes = $selectedSubCategory->attributes()->with('attributeValues')->get();
            return response()->json([
                'attributes' => $attributes,
                'categoryId' => $categoryId,
                'categoryName' => $categoryName,
                'categoryImage' => $categoryImage
            ], 200);

        } catch(Exception $e) {
            return response()->json([
                'error' => 'Error occured' . $e,
            ], 500);
        };
    }
        public function filterProducts(Request $request) {
            $usdRate = $this->currencyService->getDollarRate();
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
                $attributeValues = $request->input('attribute_values');
        
                if (is_string($attributeValues)) {
                    $attributeValues = explode(',', $attributeValues);
                }
        
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
                if (!$usdRate || $usdRate <= 0) {
                    Log::info('USD Rate: USD rate not available');
                    $usdRate = 12900; // Default USD rate
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
        
            $products = $productsQuery->get();
      
            Log::info('Request Data: ', $request->all());
            Log::info('Final SQL Query:', [$productsQuery->toSql()]);
            Log::info('Query Bindings:', $productsQuery->getBindings());
        
            return response()->json([
                'products' => $products,
            ]);
        }        
}