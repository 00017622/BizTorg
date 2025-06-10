<?php 

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\PostToSocialMediaJob;
use App\Jobs\RemoveFromSocialMediaJob;
use App\Jobs\SendFcmNotification;

use App\Jobs\UpdateSocialMediaPostsJob;
use App\Models\Attribute;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Region;
use App\Models\ShopProfile;
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
use Storage;

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

    public function getProducts($subcategoryId)
{
    // Get page and per_page from query parameters
    $page = request()->query('page', 1);
    $perPage = request()->query('per_page', 30);

    try {
        $products = Product::where('subcategory_id', $subcategoryId)
            ->with(['images', 'region'])
            ->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'success' => true,
            'products' => $products->items(),
            'pagination' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
            ]
        ], 200);
    } catch (\Exception $e) {
        Log::error('Error fetching products by subcategory: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'error' => 'An error occurred while fetching products: ' . $e->getMessage(),
        ], 500);
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
    
    public function filterProducts(Request $request)
{
    try {
        $usdRate = 12950; // $this->currencyService->getDollarRate() ?? 12900;

        // Get page and per_page from query parameters
        $page = $request->query('page', 1);
        $perPage = $request->query('per_page', 10);

        // Step 1: Perform the search logic (from searchProducts)
        $query = trim($request->query('query', ''));

        Log::info("FilterProducts: Search query received: '$query'");

        $productsQuery = Product::query()->with(['images', 'region.parent']);

        if (!empty($query)) {
            // Step 1.1: Preprocess the query for full-text search
            $words = preg_split('/\s+/', $query, -1, PREG_SPLIT_NO_EMPTY);
            $tsQueryTerms = [];

            foreach ($words as $word) {
                // Escape special characters for to_tsquery
                $word = str_replace(['&', '|', '!', ':', '*'], '', $word);
                // Generate truncated versions of the word (e.g., пожарн -> пожарн:*, пожар:*, пожа:*, ...)
                $truncatedWords = [];
                $minLength = 3; // Minimum length for truncation to avoid overly broad matches
                for ($len = mb_strlen($word); $len >= $minLength; $len--) {
                    $truncated = mb_substr($word, 0, $len);
                    $truncatedWords[] = $truncated . ':*';
                }
                // Combine truncated terms with OR (|)
                if (!empty($truncatedWords)) {
                    $tsQueryTerms[] = '(' . implode(' | ', $truncatedWords) . ')';
                }
            }

            // Combine all words with OR (|)
            $tsQuery = implode(' | ', $tsQueryTerms);
            if (empty($tsQuery)) {
                // If no valid terms after truncation, fall back to original word
                $tsQuery = implode(' | ', array_map(function ($word) {
                    return $word . ':*';
                }, $words));
            }
            Log::info("FilterProducts: Full-text search query: '$tsQuery'");

            // Step 1.2: Perform the full-text search with 'simple' configuration
            $fullTextQuery = Product::query()
                ->whereRaw(
                    "(name_tsvector @@ to_tsquery('simple', ?) OR description_tsvector @@ to_tsquery('simple', ?) OR slug_tsvector @@ to_tsquery('simple', ?))",
                    [$tsQuery, $tsQuery, $tsQuery]
                )
                ->orderByRaw(
                    "(ts_rank(name_tsvector, to_tsquery('simple', ?)) + ts_rank(description_tsvector, to_tsquery('simple', ?)) + ts_rank(slug_tsvector, to_tsquery('simple', ?))) DESC",
                    [$tsQuery, $tsQuery, $tsQuery]
                )
                ->with(['images', 'region.parent']);

            $fullTextProducts = $fullTextQuery->get(); // Fetch all matching products for now

            Log::info("FilterProducts: Full-text search results: " . $fullTextProducts->toJson());

            // Step 1.3: Perform trigram similarity search for typo tolerance
            $similarityThreshold = 0.05; // Lowered for more matches
            $trigramQuery = Product::query();

            $trigramConditions = [];
            $trigramBindings = [];
            $trigramSimilarityExpressions = [];

            foreach ($words as $word) {
                $trigramConditions[] = "(name % ? OR description % ? OR slug % ?)";
                $trigramBindings = array_merge($trigramBindings, [$word, $word, $word]);

                $trigramSimilarityExpressions[] = "GREATEST(similarity(name, ?), similarity(description, ?), similarity(slug, ?))";
                $trigramBindings = array_merge($trigramBindings, [$word, $word, $word]);
            }

            $trigramConditionString = implode(' OR ', $trigramConditions);
            $trigramMaxSimilarityString = "GREATEST(" . implode(', ', $trigramSimilarityExpressions) . ")";

            $trigramOrderBindings = [];
            foreach ($words as $word) {
                $trigramOrderBindings = array_merge($trigramOrderBindings, [$word, $word, $word]);
            }

            $trigramProducts = $trigramQuery->whereRaw("($trigramConditionString)", $trigramBindings)
                ->whereRaw("$trigramMaxSimilarityString >= ?", [$similarityThreshold])
                ->orderByRaw("$trigramMaxSimilarityString DESC", $trigramOrderBindings)
                ->with(['images', 'region.parent'])
                ->get(); // Fetch all matching products for now

            Log::info("FilterProducts: Trigram search results: " . $trigramProducts->toJson());

            // Step 1.4: Combine results, prioritizing full-text matches
            $combinedProducts = $fullTextProducts->merge($trigramProducts)
                ->unique('id') // Remove duplicates by ID
                ->sortByDesc(function ($product) use ($query) {
                    // Sort by similarity for trigram matches, and ts_rank for full-text
                    $tsRank = $product->ts_rank ?? 0; // If ts_rank exists (from full-text)
                    $similarity = max(
                        $this->similarity($product->name, $query),
                        $this->similarity($product->description ?? '', $query),
                        $this->similarity($product->slug ?? '', $query)
                    );
                    return $tsRank + $similarity; // Combine scores for sorting
                })
                ->values(); // Reindex the collection

            Log::info("FilterProducts: Combined search results: " . $combinedProducts->toJson());

            // Step 1.5: Apply the combined product IDs to the main query
            if ($combinedProducts->isNotEmpty()) {
                $productIds = $combinedProducts->pluck('id')->toArray();
                $productsQuery->whereIn('id', $productIds);
            } else {
                // If no products match the search query, return an empty result
                $productsQuery->where('id', 0); // Force no results
            }
        }

        // Step 2: Apply the existing filtering logic from filterProducts
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

        $productsQuery->when($request->filled('parent_region_id'), function ($query) use ($request) {
            $query->whereHas('region', function ($subQuery) use ($request) {
                $subQuery->where('regions.parent_id', $request->input('parent_region_id'));
                if ($request->filled('child_region_id')) {
                    $subQuery->where('regions.id', $request->input('child_region_id'));
                }
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

        // Step 3: Paginate the results after applying all filters
        $products = $productsQuery->paginate($perPage, ['*'], 'page', $page);

        // Step 4: Transform the products to include image_url at the root level from the first image and parent region
        $transformedProducts = collect($products->items())->map(function ($product) {
            $productData = $product->toArray();
            // Set image_url from the first image in the images relationship, or null if no images
            $productData['image_url'] = $product->images->isNotEmpty()
                ? $product->images->first()->image_url
                : null;
                $productData['isFromShop'] = $product->user->isShop;
            // Replace region with parent region name
            $productData['region'] = $product->parentRegion->name ?? $product->region->name ?? null;
            return $productData;
        });

        return response()->json([
            'products' => $transformedProducts,
            'pagination' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
            ],
        ]);

    } catch (Exception $e) {
        Log::error("FilterProducts error: " . $e->getMessage());
        return response()->json([
            'error' => 'Error occurred: ' . $e->getMessage(),
        ], 500);
    }
}

/**
 * Calculate trigram similarity (helper function for sorting)
 */
private function similarity($string1, $string2)
{
    $string1 = strtolower(trim($string1));
    $string2 = strtolower(trim($string2));
    if (empty($string1) || empty($string2)) {
        return 0;
    }
    return DB::selectOne("SELECT similarity(?, ?) as sim", [$string1, $string2])->sim ?? 0;
}

public function createProduct(Request $request)
{
    $validatedData = $request->validate([
        'uuid' => 'required|numeric|exists:users,id',
        'name' => 'required|string|max:255',
        'description' => 'required|string|max:900',
        'subcategory_id' => 'required|exists:subcategories,id',
        'images' => 'nullable|array',
        'images.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
        'latitude' => 'required|numeric|between:-90,90',
        'longitude' => 'required|numeric|between:-180,180',
        'attributes' => 'nullable|array',
        'attributes.*' => 'integer|exists:attribute_values,id',
        'price' => 'required|numeric|min:0',
        'currency' => 'required|string|in:сум,доллар',
        'type' => 'required|string|in:sale,purchase',
        'child_region_id' => 'required|exists:regions,id',
        'showNumber' => 'required|boolean',
        'number' => 'nullable|string|regex:/^\+998\d{9}$/|max:15',
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
                'showNumber' => $validatedData['showNumber'],
                'number' => $validatedData['number'],
            ]);

            $imagePaths = [];

            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) {
                    try {
                        $path = $image->store('product-images', 'public');
                        Log::info("✅ Image uploaded successfully to: $path");

                        ProductImage::create([
                            'product_id' => $product->id,
                            'image_url' => $path,
                        ]);

                        // Add the stored path to the array
                        $imagePaths[] = $path;
                    } catch (\Exception $e) {
                        Log::error("❌ Failed to upload image: " . $e->getMessage());
                    }
                }
            } else {
                Log::warning("⚠️ No images were uploaded.");
            }


            if(isset($validatedData['attributes']) && is_array($validatedData['attributes'])) {
                $product->attributeValues()->sync($validatedData['attributes']);
            } else {
                Log::info("ℹ️ No attributes provided for product ID: {$product->id}");
            }
           



            $user = User::findOrFail($validatedData['uuid']);

       

            if ($user->isShop) {
                $shopProfile = ShopProfile::where('user_id', $user->id)->first();
                if ($shopProfile) {
                    $subscribers = $shopProfile->subscribers()->get();

                    if ($subscribers->isNotEmpty()) {
                        $firstImageUrl = !empty($imagePaths) ? asset("storage/{$imagePaths[0]}") : '';

                        $notificationTitle = "{$shopProfile->shop_name} опубликовал новое объявление";
                        $notificationBody = \Str::limit("{$product->name} - {$product->description}", 300, '...');

                        $senderId = $user->id;
                        $shopName = $shopProfile->shop_name;
                        $productName = $product->name;
                        $productDescription = $product->description;
                        $shopImageString = $user->shopProfile->profile_url;
                        $shopImageMain = asset("storage/{$shopImageString}");

                        foreach ($subscribers as $index => $subscriber) {
                            if ($subscriber->fcm_token) {
                                SendFcmNotification::dispatch(
                                    $product->id,
                                    $notificationTitle,
                                    $notificationBody,
                                    $subscriber->fcm_token,
                                    $firstImageUrl,
                                    $senderId,
                                    $shopName,
                                    $productName,
                                    $productDescription,
                                    $subscriber->id,
                                    $shopImageMain,
                                );

                                \Log::info("✅ Queued FCM notification for subscriber ID: {$subscriber->id}");
                            } else {
                                \Log::warning("⚠️ No FCM token for subscriber ID: {$subscriber->id}");
                            }
                        }
                    } else {
                        \Log::info("ℹ️ No subscribers found for shop user ID: {$user->id}");
                    }
                } else {
                    \Log::warning("⚠️ No ShopProfile found for user ID: {$user->id}");
                }
            }

            // Dispatch social media posting job
            $contactName = $user->isShop ? $user->shopProfile->contact_name : $product->user->name;
            $contactPhone = $user->isShop ? $user->shopProfile->phone : $product->user->profile->phone;


            $isShop = $user->isShop;

            $determineShopName = null;

            if ($user->isShop) {
    $shopProfile = ShopProfile::where('user_id', $user->id)->first();
    if ($shopProfile) {
        $determineShopName = $shopProfile->shop_name;
    }
}

            $images = ProductImage::where('product_id', $product->id)
                ->pluck('image_url')
                ->map(function ($path) {
                    return asset("storage/{$path}");
                })->toArray();

            PostToSocialMediaJob::dispatch($product, $contactName, $contactPhone, $images, $isShop, $determineShopName);
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

public function getProduct(Request $request, $productId)
{
    $cacheKey = 'product_data_' . $productId;

    $data = Cache::remember($cacheKey, now()->addMinutes(1), function () use ($productId, $request) {
        $product = Product::with(['images', 'region.parent'])->where('id', $productId)->firstOrFail();
        $user = $product->user;
        $profile = $product->user->profile;

        $attributes = $product->subcategory->attributes()->with(['attributeValues' => function ($query) use ($product) {
            $query->whereExists(function ($q) use ($product) {
                $q->from('product_attribute_values')
                  ->whereColumn('product_attribute_values.attribute_value_id', 'attribute_values.id')
                  ->where('product_attribute_values.product_id', $product->id);
            });
        }])->get();

        $userProducts = $user->products()
            ->with(['images', 'region.parent'])
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
                    'region' => $product->parentRegion->name ?? $product->region->name ?? null,
                    'type' => $product->type,
                    'name' => $product->name,
                    'created_at' => $product->created_at,
                    'description' => $product->description,
                    'images' => $product->images->map(function ($image) { 
                        return ['image_url' => $image->image_url]; 
                    })
                ];
            });

        $sameProducts = Product::with(['images', 'region.parent'])
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
                    'region' => $product->parentRegion->name ?? $product->region->name ?? null,
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

        $productCount = $user->products()->count();

        $productData = [
            'id' => $product->id,
            'subcategory_id' => $product->subcategory_id,
            'name' => $product->name,
            'slug' => $product->slug,
            'description' => $product->description,
            'price' => $product->price,
            'currency' => $product->currency,
            'created_at' => $product->created_at,
            'updated_at' => $product->updated_at,
            'type' => $product->type,
            'region_id' => $product->region_id,
            'user_id' => $product->user_id,
            'latitude' => $product->latitude,
            'longitude' => $product->longitude,
            'name_tsvector' => $product->name_tsvector,
            'description_tsvector' => $product->description_tsvector,
            'slug_tsvector' => $product->slug_tsvector,
            'images' => $product->images->map(function ($image) {
                return ['image_url' => $image->image_url];
            })->toArray(),
            'region' => $product->parentRegion->name . ' - ' .  $product->region->name,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'email_verified_at' => $user->email_verified_at,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
                'avatar' => $user->avatar,
                'role_id' => $user->role_id,
                'settings' => $user->settings,
                'google_id' => $user->google_id,
                'facebook_id' => $user->facebook_id,
                'telegram_id' => $user->telegram_id,
                'fcm_token' => $user->fcm_token,
                'profile' => $profile ? [
                    'id' => $profile->id,
                    'user_id' => $profile->user_id,
                    'phone' => $profile->phone,
                    'region_id' => $profile->region_id,
                    'address' => $profile->address,
                    'avatar' => $profile->avatar,
                    'created_at' => $profile->created_at,
                    'updated_at' => $profile->updated_at,
                    'latitude' => $profile->latitude,
                    'longitude' => $profile->longitude,
                ] : null,
                'products' => $user->products->map(function ($prod) {
                    return [
                        'id' => $prod->id,
                        'subcategory_id' => $prod->subcategory_id,
                        'name' => $prod->name,
                        'slug' => $prod->slug,
                        'description' => $prod->description,
                        'price' => $prod->price,
                        'currency' => $prod->currency,
                        'created_at' => $prod->created_at,
                        'updated_at' => $prod->updated_at,
                        'type' => $prod->type,
                        'region_id' => $prod->region_id,
                        'user_id' => $prod->user_id,
                        'latitude' => $prod->latitude,
                        'longitude' => $prod->longitude,
                        'name_tsvector' => $prod->name_tsvector,
                        'description_tsvector' => $prod->description_tsvector,
                        'slug_tsvector' => $prod->slug_tsvector,
                    ];
                })->toArray(),
            ],
            'subcategory' => [
                'id' => $product->subcategory->id,
                'category_id' => $product->subcategory->category_id,
                'name' => $product->subcategory->name,
                'slug' => $product->subcategory->slug,
                'created_at' => $product->subcategory->created_at,
                'updated_at' => $product->subcategory->updated_at,
            ],
        ];

        $shopProfile = null;

        if($user->isShop) {
            $shopProfile = $user->shopProfile;
        }

        $accessingUser = $request->query('user_id');

        $isAlreadySubscriber = false;

        $hasAlreadyRated = false;

        if($request->query('user_id') != null && $user->isShop) {
            $isAlreadySubscriber = $user->shopProfile->subscribers()->where('user_id', $request->query('user_id'))->exists();
            $hasAlreadyRated = $user->shopProfile->raters()->where('user_id', $request->query('user_id'))->exists();
        }


        return [
            'shopProfile'     => $shopProfile,
            'isShop'          => $user->isShop ?? false,
            'isAlreadySubscriber' => $isAlreadySubscriber,
            'hasAlreadyRated' => $hasAlreadyRated,
            'product'         => $productData,
            'userProducts'    => $userProducts,
            'sameProducts'    => $sameProducts,
            'user'            => $user,
            'profile'         => $profile,
            'attributes'      => $attributes,
            'userProductCount' => $productCount,
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

public function fetchUserProducts( $userId) {
    $user = User::findOrFail($userId);

    $userProducts = $user->products()->with('images')->get();

    return response()->json(
        [
            'status' => 'success',
            'user_products' => $userProducts,
        ]
        );
}

    public function getFavoritesOfUser(Request $request, $uuid)
    {
        try {
            // Log the token for debugging
            $token = $request->bearerToken();
            Log::info('Authorization token received', ['token' => $token]);

            // Fetch the authenticated user
            $authenticatedUser = $request->user();
            Log::info('Authenticated user', [
                'user_id' => $authenticatedUser ? $authenticatedUser->id : 'null',
                'user_exists' => $authenticatedUser ? true : false,
                'user_type' => $authenticatedUser ? get_class($authenticatedUser) : 'null',
            ]);

            // Check if the user is authenticated
            if (!$authenticatedUser || !($authenticatedUser instanceof \App\Models\User) || !$authenticatedUser->exists || $authenticatedUser->id <= 0) {
                Log::warning('User is not authenticated or invalid', [
                    'user_id' => $authenticatedUser ? $authenticatedUser->id : 'null',
                ]);
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthenticated',
                    'data' => null,
                ], 401);
            }

            // Validate the uuid parameter
            $uuid = (int) $uuid;
            if ($uuid <= 0) {
                Log::warning('Invalid user ID provided', ['uuid' => $uuid]);
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid user ID',
                    'data' => null,
                ], 400);
            }

            // Check if the authenticated user matches the requested uuid
            if ($authenticatedUser->id !== $uuid) {
                Log::warning('User not authorized to access this data', [
                    'authenticated_user_id' => $authenticatedUser->id,
                    'requested_user_id' => $uuid,
                ]);
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized: You can only access your own favorites',
                    'data' => null,
                ], 403);
            }

            // Fetch the user from the database
            Log::info('Attempting to fetch user from database', ['user_id' => $uuid]);
            $user = User::findOrFail($uuid);
            Log::info('User fetched from database', ['user_id' => $user->id]);

            // Fetch the favorited products with their images
            Log::info('Fetching favorite products for user', ['user_id' => $user->id]);
            $favorites = $user->favoriteProducts()->with('images')->get();
            Log::info('Fetched favorite products', ['products' => $favorites->toArray()]);

            return response()->json([
                'status' => 'success',
                'message' => 'Products fetched successfully',
                'products' => $favorites,
            ], 200);

        } catch (\Exception $e) {
            Log::error('Failed to fetch user products for user ID ' . ($uuid ?? 'unknown') . ': ' . $e->getMessage(), [
                'exception' => $e,
                'stack_trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch products',
                'data' => null,
            ], 500);
        }
    }


    public function getUserProducts(Request $request, $uuid)
    {
        try {
            // Log the token for debugging
            $token = $request->bearerToken();
            Log::info('Authorization token received in getUserProducts', ['token' => $token]);

            // Fetch the authenticated user
            $authenticatedUser = $request->user();
            Log::info('Authenticated user in getUserProducts', [
                'user_id' => $authenticatedUser ? $authenticatedUser->id : 'null',
                'user_exists' => $authenticatedUser ? true : false,
                'user_type' => $authenticatedUser ? get_class($authenticatedUser) : 'null',
            ]);

            // Check if the user is authenticated
            if (!$authenticatedUser || !($authenticatedUser instanceof \App\Models\User) || !$authenticatedUser->exists || $authenticatedUser->id <= 0) {
                Log::warning('User is not authenticated or invalid in getUserProducts', [
                    'user_id' => $authenticatedUser ? $authenticatedUser->id : 'null',
                ]);
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthenticated',
                    'data' => null,
                ], 401);
            }

            // Validate the uuid parameter
            $uuid = (int) $uuid;
            if ($uuid <= 0) {
                Log::warning('Invalid user ID provided in getUserProducts', ['uuid' => $uuid]);
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid user ID',
                    'data' => null,
                ], 400);
            }

            // Check if the authenticated user matches the requested uuid
            if ($authenticatedUser->id !== $uuid) {
                Log::warning('User not authorized to access this data in getUserProducts', [
                    'authenticated_user_id' => $authenticatedUser->id,
                    'requested_user_id' => $uuid,
                ]);
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized: You can only access your own products',
                    'data' => null,
                ], 403);
            }

            // Fetch the user from the database
            Log::info('Attempting to fetch user from database in getUserProducts', ['user_id' => $uuid]);
            $user = User::findOrFail($uuid);
            Log::info('User fetched from database in getUserProducts', ['user_id' => $user->id]);

            // Fetch the user's products with their images
            Log::info('Fetching user products for user in getUserProducts', ['user_id' => $user->id]);
            $products = $user->products()->with('images')->get();
            Log::info('Fetched user products in getUserProducts', ['products' => $products->toArray()]);

            return response()->json([
                'status' => 'success',
                'message' => 'Products fetched successfully',
                'products' => $products,
            ], 200);

        } catch (\Exception $e) {
            Log::error('Failed to fetch user products for user ID ' . ($uuid ?? 'unknown') . ': ' . $e->getMessage(), [
                'exception' => $e,
                'stack_trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch products',
                'data' => null,
            ], 500);
        }
    }

public function removeProduct(Request $request, $productId)
{
    try {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthenticated',
                'data' => null,
            ], 401);
        }

        $productId = (int) $productId;

        $product = Product::where('id', $productId)
                         ->where('user_id', $user->id)
                         ->first();

        if (!$product) {
            return response()->json([
                'status' => 'error',
                'message' => 'Product not found or you do not have permission to delete it',
                'data' => null,
            ], 404);
        }

       RemoveFromSocialMediaJob::dispatch(
            $product->telegram_post_id,
            $product->facebook_post_id,
            $product->insta_post_id
        );

        $product->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Product deleted successfully',
            'data' => null,
        ], 200);

    } catch (\Exception $e) {
        Log::error('Failed to delete product with ID ' . $productId . ': ' . $e->getMessage(), [
            'exception' => $e,
            'user_id' => $user->id ?? 'unknown',
        ]);
        return response()->json([
            'status' => 'error',
            'message' => 'Failed to delete product',
            'data' => null,
        ], 500);
    }
}

public function fetchSingleProduct($id) {
    $product = Product::with(['region.parent',  'attributes.attributeValues', 'images'])->findOrFail($id);

    $productImages = $product->images->map(function ($image) {
        return ['image_url' => $image->image_url];
    });

    
    $attributes = $product->subcategory->attributes()->with([
        'attributeValues' => function ($query) use ($product) {
            $query->whereExists(function ($q) use ($product) {
                $q->from('product_attribute_values')
                  ->whereColumn('product_attribute_values.attribute_value_id', 'attribute_values.id')
                  ->where('product_attribute_values.product_id', $product->id);
            });
        }
    ])->get();
    
    $productAttributes = $attributes->mapWithKeys(function ($attribute) {
        $selectedValue = $attribute->attributeValues->first();
        return [
            $attribute->id => [
                'id' => $selectedValue->id ?? null,
                'name' => $selectedValue->value ?? 'No value assigned', 
            ],
        ];
    });

    return response()->json([
        'product' => $product,
        'productAttributes' => $productAttributes,
        'productImages' => $productImages,
        
    ]);
}

 public function updateProduct(Request $request, $id)
    {
        $user = $request->user();

        // Log request data safely by excluding files
        Log::info('Incoming Request Data:', [
            'fields' => $request->except(['images']), // Exclude images to avoid serialization
            'headers' => $request->headers->all(),
        ]);

        try {
            $validatedData = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'required|string|max:900',
                'subcategory_id' => 'required|exists:subcategories,id',
                'images' => 'nullable|array',
                'images.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
                'latitude' => 'required|numeric|between:-90,90',
                'longitude' => 'required|numeric|between:-180,180',
                'attributes' => 'nullable|array',
                'attributes.*' => 'integer|exists:attribute_values,id',
                'price' => 'required|numeric|min:0',
                'currency' => 'required|string|in:сум,доллар',
                'type' => 'required|string|in:sale,purchase',
                'child_region_id' => 'required|exists:regions,id',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation failed:', $e->errors());
            return redirect()->back()->withErrors($e->errors());
        }

        // Prepare validated data for logging (exclude images)
        $loggableData = array_diff_key($validatedData, array_flip(['images']));
        Log::info('✅ Validated Data for Update:', $loggableData);

        try {
            return DB::transaction(function () use ($validatedData, $request, $id, $user) {
                $product = Product::where('id', $id)
                    ->where('user_id', $user->id)
                    ->firstOrFail();

                $slug = Str::slug($validatedData['name'], '-');

                $product->update([
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
                    'user_id' => $user->id,
                ]);

                $existingImages = $product->images->map(function ($image) {
    return asset('storage/' . $image->image_url);
})->toArray();

                // Handle new images and store their paths
                $newImagePaths = [];
                if ($request->hasFile('images')) {
                    foreach ($request->file('images') as $image) {
                        try {
                            $path = $image->store('product-images', 'public');
                            $fullUrl = asset('storage/' . $path); // Generate the full URL
                            Log::info("✅ Image uploaded successfully to: $path");
                            ProductImage::create([
                                'product_id' => $product->id,
                                'image_url' => $path,
                            ]);
                            $newImagePaths[] = $fullUrl;
                        } catch (\Exception $e) {
                            Log::error("❌ Failed to upload image: " . $e->getMessage());
                        }
                    }
                } else {
                    Log::info("⚠️ No new images were uploaded.");
                }

$allImages = array_merge($existingImages, $newImagePaths);
 Log::info("All images for update: ", $allImages);

 

                // Update product's attributes
                
            if(isset($validatedData['attributes']) && is_array($validatedData['attributes'])) {
                $product->attributeValues()->sync($validatedData['attributes']);
            } else {
                Log::info("ℹ️ No attributes provided for product ID: {$product->id}");
            }
              

                // Prepare data for the job, including new image URLs
                $jobData = array_merge($validatedData, [
                    'images' => $allImages, // Pass only the URLs, not UploadedFile objects
                ]);

                UpdateSocialMediaPostsJob::dispatch($product->id, $jobData);

                return response()->json([
                    'status' => 'success',
                    'message' => 'Product updated successfully',
                ], 200);
            });
        } catch (\Exception $e) {
            Log::error('Product update failed: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update product: ' . $e->getMessage(),
            ], 500);
        }
    }

public function deleteImage(Request $request, $id)
{
    $user = $request->user();

    Log::info('Attempting to delete image with ID: ' . $id, ['user_id' => $user->id]);

    try {
        $image = ProductImage::where('id', $id)
            ->whereHas('product', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->firstOrFail();

        $imagePath = $image->image_url;

    
        if (Storage::disk('public')->exists($imagePath)) {
            Storage::disk('public')->delete($imagePath);
            Log::info('Deleted image file from storage: ' . $imagePath);
        } else {
            Log::warning('Image file not found in storage: ' . $imagePath);
        }

        $image->delete();
        Log::info('Deleted image record from database', ['image_id' => $id, 'product_id' => $image->product_id]);

        return response()->json([
            'success' => true,
            'message' => 'Image deleted successfully.',
        ], 200);
    } catch (\Exception $e) {
        Log::error('Error deleting image: ' . $e->getMessage(), ['image_id' => $id, 'user_id' => $user->id]);
        return response()->json([
            'success' => false,
            'message' => 'Failed to delete image: ' . $e->getMessage(),
        ], 500);
    }
}

public function getProductsByCategory($categoryId)
{
    Log::info('Received categoryId is ' . $categoryId);

    // Get page and per_page from query parameters, with defaults
    $page = request()->query('page', 1);
    $perPage = request()->query('per_page', 30);

    try {
        $category = Category::with(['products.images', 'products.region'])
            ->findOrFail($categoryId);

        // Paginate products
        $products = $category->products()
            ->with(['images', 'region'])
            ->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'success' => true,
            'category' => $category->name,
            'products' => $products->items(),
            'pagination' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
            ]
        ], 200);
    } catch (\Exception $e) {
        Log::error('Error fetching products by category: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'error' => 'An error occurred while fetching products',
        ], 500);
    }
}
    
}
