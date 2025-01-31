<?php 

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attribute;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Region;
use App\Models\Subcategory;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use App\Services\CurrencyService;
use Cache;
use App\Services\FacebookService;
use App\Services\InstagramService;
use App\Services\TelegramService;
use DB;
use Str;

class ProductController extends Controller {
    protected $currencyService;
    protected $telegramService;
    protected $facebookService;
    protected $instagramService;

    public function __construct(CurrencyService $currencyService, TelegramService $telegramService, FacebookService $facebookService, InstagramService $instagramService)
    {
        $this->currencyService = $currencyService;
        $this->telegramService = $telegramService;
        $this->facebookService = $facebookService;
        $this->instagramService = $instagramService;
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

    public function createProduct(Request $request) {

        Log::info("🔥 Incoming Request: ", [
            'headers' => $request->headers->all(),
            'body' => $request->all()
        ]);

        $validatedData = $request->validate([
            'uuid' => 'required|numeric|exists:users,id',
            'name' => 'required|string|max:255',
            'description' => 'required|string|max:900',
            'subcategory_id' => 'required|exists:subcategories,id',
            'images' => 'required|array|min:1',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'attributes' => 'required|array|min:1',
            'attributes.*' => 'integer|exists:attribute_values,id',
            'price' => 'required|numeric|min:0',
            'currency' => 'required|string|in:сум,доллар',
            'type' => 'required|string|in:sale,purchase',
            'child_region_id' => 'required|exists:regions,id',
        ]);
    
        try {
            $slug = Str::slug($validatedData['name'], '-');
    
            DB::transaction(function () use ($validatedData, $request, $slug) {
                $product = Product::create([
                    'name' => $validatedData['name'],
                    'slug' => $slug,
                    'subcategory_id' => $validatedData['subcategory_id'],
                    'description' => $validatedData['description'],
                    'price' => $validatedData['price'],
                    'currency' => $validatedData['currency'],
                    'latitude' => (float) $validatedData['latitude'],
                    'longitude' => (float) $validatedData['longitude'],
                    'type' => $validatedData['type'],
                    'region_id' => $validatedData['child_region_id'],
                    'user_id' => $validatedData['uuid'],
                ]);
    
                // ✅ Handle Images Upload
                $uploadedImages = [];
                foreach ($request->file('images') as $index => $image) {
                    try {
                        $path = $image->store('product-images', 'public');
                        Log::info("Image uploaded successfully to: $path");
    
                        ProductImage::create([
                            'product_id' => $product->id,
                            'image_url' => $path,
                        ]);
    
                        $uploadedImages[] = ['image_url' => asset("storage/$path")];
                    } catch (\Exception $e) {
                        Log::error("Failed to upload image: " . $e->getMessage());
                    }
                }
    
                // ✅ Sync Product Attributes
                $product->attributeValues()->sync($validatedData['attributes']);
    
                $productInfo = <<<INFO
    📢 <b>Объявление:</b> {$product->name}
    
    📝 <b>Описание:</b> {$product->description}
    
    📍 <b>Регион:</b> {$product->region->parent->name}, {$product->region->name}
    
    👤 <b>Контактное лицо:</b> {$product->user->name}
    
    📞 <b>Номер телефона:</b> <a href="tel:{$product->user->profile->phone}">{$product->user->profile->phone}</a>
    
    🌍 <b>Карта:</b> <a href="https://www.google.com/maps?q={$product->latitude},{$product->longitude}">Местоположение в Google Maps</a>
    
    🌍 <b>Карта:</b> <a href="https://yandex.ru/maps/?ll={$product->longitude},{$product->latitude}&z=17&l=map&pt={$product->longitude},{$product->latitude},pm2rdm">Местоположение в Yandex Maps</a>
    
    🔗 <a href="https://biztorg.uz/obyavlenie/{$product->slug}">Подробнее по ссылке</a>
    INFO;
    
                // ✅ Send to Telegram
                try {
                    if (count($uploadedImages) > 1) {
                        $media = array_map(function ($image, $index) use ($productInfo) {
                            return [
                                'type' => 'photo',
                                'media' => $image,
                                'parse_mode' => 'HTML',
                                'caption' => $index === 0 ? $productInfo : null
                            ];
                        }, $uploadedImages, array_keys($uploadedImages));
    
                        $this->telegramService->sendMediaGroup($media);
                    } elseif (count($uploadedImages) === 1) {
                        $this->telegramService->sendPhoto($uploadedImages[0]['image_url'], $productInfo);
                    } else {
                        $this->telegramService->sendMessage($productInfo);
                    }
                } catch (\Exception $e) {
                    Log::error("Failed to send Telegram message: " . $e->getMessage());
                }
    
                // ✅ Send to Facebook
                try {
                    $this->facebookService->createPost($productInfo, $uploadedImages);
                } catch (\Exception $e) {
                    Log::error("Failed to send Facebook post: " . $e->getMessage());
                }
    
                // ✅ Send to Instagram
                try {
                    $imageUrls = array_map(fn($image) => $image['image_url'], $uploadedImages);
                    $this->instagramService->createCarouselPost($productInfo, $imageUrls);
                } catch (\Exception $e) {
                    Log::error("Failed to send Instagram post: " . $e->getMessage());
                }
            });
    
            return response()->json([
                'message' => 'Product created successfully',
                'status' => 'success',
            ], 201);
        } catch (\Exception $e) {
            Log::error('Product creation failed: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error occurred',
                'status' => 'error',
            ], 500);
        }
    }
    
    
}