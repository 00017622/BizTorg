<?php 

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attribute;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Region;
use App\Models\Subcategory;
use App\Models\User;
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
use Illuminate\Support\Facades\Auth;

use function PHPUnit\Framework\isEmpty;

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

    public function createProduct(Request $request)
{
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

    Log::info('✅ Validated Data:', $validatedData);

    $slug = Str::slug($validatedData['name'], '-');

    try {
        DB::transaction(function () use ($validatedData, $request, $slug) {
            $product = Product::create([
                'name' => $validatedData['name'],
                'slug' => $slug,
                'subcategory_id' => $validatedData['subcategory_id'],
                'description' => $validatedData['description'],
                'price' => $validatedData['price'],
                'currency' => $validatedData['currency'],
                'latitude' => $validatedData['latitude'],
                'longitude' => $validatedData['longitude'],
                'type' => $validatedData['type'],
                'region_id' => $validatedData['child_region_id'],
                'user_id' => $validatedData['uuid'],
            ]);

            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) { 
                    try {
                        $path = $image->store('product-images', 'public'); 
                        Log::info("✅ Image uploaded successfully to: $path");
            
                        ProductImage::create([
                            'product_id' => $product->id,
                            'image_url' => $path,
                        ]);
                    } catch (\Exception $e) {
                        Log::error("❌ Failed to upload image: " . $e->getMessage());
                    }
                }
            } else {
                Log::warning("⚠️ No images were uploaded.");
            }
            
           
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


               $images = ProductImage::where('product_id', $product->id)->pluck('image_url')->map(function ($path) {
             
                   $url = asset("storage/{$path}");
                   Log::info("Constructed image URL: {$url}");
                   return $url;
               })->toArray();
               

        if (!is_array($images)) {
            throw new \InvalidArgumentException('Images should be an array.');
        }

        try {
            if (count($images) > 1) {
                $media = array_map(function ($image, $index) use ($productInfo) {
                    $mediaItem = [
                        'type' => 'photo',
                        'media' => $image,
                        'parse_mode' => 'HTML',
                    ];
                    if ($index === 0) {
                        $mediaItem['caption'] = $productInfo;
                    }
                    return $mediaItem;
                }, $images, array_keys($images));
                
                $this->telegramService->sendMediaGroup($media);
            } elseif (count($images) === 1) {
                Log::info("Sending single photo to Telegram: " . $images[0]);
                $this->telegramService->sendPhoto($images[0], $productInfo);
            } else {
                $this->telegramService->sendMessage($productInfo);
            }
        } catch (\Exception $e) {
            Log::error("Failed to send Telegram message: " . $e->getMessage());
        }
        
        try {

            $facebookProductInfo = <<<INFO
📢 Объявление: {$product->name}

📝 Описание: {$product->description}

📍 Регион: {$product->region->parent->name}, {$product->region->name}

👤 Контактное лицо: {$product->user->name}

📞 Номер телефона: {$product->user->profile->phone}

🌍 Карта: Местоположение в Google Maps: https://www.google.com/maps?q={$product->latitude},{$product->longitude}

🌍 Карта: Местоположение в Yandex Maps: https://yandex.ru/maps/?ll={$product->longitude},{$product->latitude}&z=17&l=map&pt={$product->longitude},{$product->latitude},pm2rdm

🔗 Подробнее по ссылке: https://biztorg.uz/obyavlenie/{$product->slug}
INFO;

        $imagesForFacebook = ProductImage::where('product_id', $product->id)->get()->map(function ($image) {
            $path = str_replace('\\', '/', $image->image_url);
            return [
                'id' => $image->id,
                'image_url' => asset("storage/{$path}"), 
            ];
        })->toArray();

        
        
        $this->facebookService->createPost($facebookProductInfo, $imagesForFacebook);

            
        } catch (\Exception $e) {
            Log::error("Failed to send Facebook post" . $e->getMessage());
        }

        try {
        
            $productImagesUrls = ProductImage::where('product_id', $product->id)->pluck('image_url');
            $imagesUrls = [];

            foreach ($productImagesUrls as $productImageUrl) {
                $imagesUrls[] = asset("storage/{$productImageUrl}");
            }
        
            $region = $product->region->parent->name ?? 'Unknown Region';
            $subregion = $product->region->name ?? 'Unknown Subregion';
            $phone = $product->user->profile->phone ?? 'No Phone Number Provided';
        
            $instaMessage = "
            📢 Объявление: {$product->name}
        
            📝 Описание: {$product->description}
        
            📍 Регион: {$region}, {$subregion}
        
            👤 Контактное лицо: {$product->user->name}
        
            📞 Номер телефона: {$phone}
        
            🌍 Карта: Местоположение в Google Maps: https://www.google.com/maps?q={$product->latitude},{$product->longitude}
        
            🌍 Карта: Местоположение в Yandex Maps: https://yandex.ru/maps/?ll={$product->longitude},{$product->latitude}&z=17&l=map&pt={$product->longitude},{$product->latitude},pm2rdm
        
            🔗 Подробнее по ссылке: https://biztorg/obyavlenie/{$product->slug}
            ";
        
            $this->instagramService->createCarouselPost($instaMessage, $imagesUrls);
        
        } catch (\Exception $e) {
            Log::error("Failed to send Instagram post: " . $e->getMessage());
        }
        
        
       
    });
        return response()->json([
            'status' => 'success',
            'message' => 'Product is created',
        ], 201);
    } catch (\Exception $e) {
        Log::error('Product creation failed: ' . $e->getMessage());
        return response()->json([
            'status' => 'error',
            'message' => 'Product was not created',
        ], 500);
    }
}

public function getProduct($productId)
{
    $cacheKey = 'product_data_' . $productId;

    $data = Cache::remember($cacheKey, now()->addMinutes(60), function () use ($productId) {
        $product = Product::with(['images'])->where('id', $productId)->firstOrFail();
        $user = $product->user;

        $attributes = $product->subcategory->attributes()->with(['attributeValues' => function ($query) use ($product) {
            $query->whereExists(function ($q) use ($product) {
                $q->from('product_attribute_values')
                  ->whereColumn('product_attribute_values.attribute_value_id', 'attribute_values.id')
                  ->where('product_attribute_values.product_id', $product->id);
            });
        }])->get();

        $userProducts = $user->products()
            ->with(['images', 'region'])
            ->where('id', '!=', $product->id)
            ->latest()
            ->limit(10)
            ->get()
            ->map(function ($product) { 
                return [
                    'id' => $product->id,
                    'price' => $product->price,
                    'currency' => $product->currency,
                    'latitude' => $product->latitude,
                    'longitude' => $product->longitude,
                    'type' => $product->type,
                    'name' => $product->name,
                    'created_at' => $product->created_at,
                    'description' => $product->description,
                    'images' => $product->images->map(function ($image) { 
                        return ['image_url' => $image->image_url]; 
                    })
                ];
            });

        $sameProducts = Product::with(['images', 'region'])
            ->where('subcategory_id', $product->subcategory->id)
            ->where('id', '!=', $product->id)
            ->whereNotIn('id', $user->products->pluck('id')->toArray())
            ->latest()
            ->limit(10)
            ->get()
            ->map(function ($product) { 
                return [
                    'id' => $product->id,
                    'price' => $product->price,
                    'currency' => $product->currency,
                    'latitude' => $product->latitude,
                    'longitude' => $product->longitude,
                    'type' => $product->type,
                    'name' => $product->name,
                    'created_at' => $product->created_at,
                    'description' => $product->description,
                    'user' => [
                        'user_name' => $product->user->name ?? 'Неизвестный пользователь',
                    ],
                    'images' => $product->images->map(function ($image) { 
                        return ['image_url' => $image->image_url]; 
                    })
                ];
            });

        return [
            'product'         => $product,
            'sameProducts'    => $sameProducts,
            'user'            => $user,
            'attributes'      => $attributes,
            'userProducts'    => $userProducts
        ];
    });

    return response()->json($data);
}

public function toggleFavorites(Request $request)
{

    $user = $request->user();
    $productId = $request->input('product_id');

    if (!$productId || !Product::find($productId)) {
        return response()->json([
            'status' => 'error',
            'message' => 'Invalid product ID',
        ], 400); 
    }

    $user->favoriteProducts()->toggle($productId);

    $isFavorited = $user->favoriteProducts()->where('product_id', $productId)->exists();

    return response()->json([
        'status' => 'success',
        'isFavorited' => $isFavorited,
    ]);
}

public function getFavorite(Request $request) {
    $user = $request->user();
    $favorites = $user->favoriteProducts()->pluck('product_id');

    return response()->json([
        'status' => 'success',
        'favorites' => $favorites,
    ]);
}

}