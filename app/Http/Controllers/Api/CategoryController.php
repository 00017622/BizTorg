<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\Subcategory;
use Cache;
use Carbon\Carbon;
use DB;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

class CategoryController extends Controller {
     public function homePage(Request $request)
    {
        try {
            // Log request parameters
            Log::info('homePage called', [
                'categories' => $request->query('categories'),
                'ad_type' => $request->query('ad_type'),
                'page' => $request->query('page', 1),
            ]);

            // Handle categories filter
            $selectedCategoryIds = $request->query('categories', []);
            if (is_string($selectedCategoryIds)) {
                $selectedCategoryIds = array_filter(
                    explode(',', $selectedCategoryIds),
                    fn($id) => is_numeric($id) && $id !== ''
                );
            } elseif (is_array($selectedCategoryIds)) {
                $selectedCategoryIds = array_filter(
                    $selectedCategoryIds,
                    fn($id) => is_numeric($id) && $id !== ''
                );
            } else {
                $selectedCategoryIds = [];
            }

            // Handle ad_type filter
            $adType = $request->query('ad_type', 'all');

            // Handle page parameter
            $page = $request->input('page', 1);
            $perPage = 24; // Match your desired items per page

            // Fetch all categories
            $categories = Category::get();
            Log::info('Categories fetched', ['count' => $categories->count()]);

            // Build product query
            $productQuery = Product::query()
                ->with([
                    'images',
                    'region' => function ($query) {
                        $query->with('parent');
                    },
                    'user',
                ])
                ->orderBy('created_at', 'desc');

            // Apply time filter for ad_type == 'new'
            if ($adType === 'new') {
                $weekAgo = Carbon::now()->subDays(10);
                $productQuery->where('created_at', '>=', $weekAgo);
            }

            // Apply category filter if provided
            if (!empty($selectedCategoryIds)) {
                $productQuery->whereHas('subcategory', function ($query) use ($selectedCategoryIds) {
                    $query->whereIn('category_id', $selectedCategoryIds);
                });
            }

            // Paginate results
            $products = $productQuery->paginate($perPage, ['*'], 'page', $page);

            // Transform products
            $transformedProducts = $products->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'price' => $product->price,
                    'slug' => $product->slug,
                    'currency' => $product->currency,
                    'created_at' => $product->created_at->toISOString(),
                    'region' => $product->region
                        ? ($product->region->parent
                            ? $product->region->parent->name . ', ' . $product->region->name
                            : $product->region->name)
                        : null,
                    'images' => $product->images->map(function ($image) {
                        return ['image_url' => $image->image_url];
                    })->toArray(),
                    'isFromShop' => $product->user ? $product->user->isShop : false,
                ];
            });

            Log::info('Products processed', ['count' => $transformedProducts->count()]);

            // Return paginated response
            return response()->json([
                'categories' => $categories,
                'products' => $transformedProducts->values(),
                'pagination' => [
                    'current_page' => $products->currentPage(),
                    'last_page' => $products->lastPage(),
                    'per_page' => $products->perPage(),
                    'total' => $products->total(),
                    'has_more' => $products->hasMorePages(),
                ],
            ], 200);
        } catch (\Exception $e) {
            Log::error('homePage error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'error' => 'Error fetching data: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function fetchCategories() {
        $categories = Category::get();

        return response()->json([
                'categories' => $categories,
            ], 200);
    }

    public function fetchSubcategories($categoryId) {
        try {
            $cacheKey = 'all_subcategories' . $categoryId;
            $cacheDuration = 60 * 180;
            $subcategories = Cache::remember($cacheKey, $cacheDuration, function () use($categoryId) {
                return Subcategory::where('category_id', $categoryId)->get();
            });

            return response()->json([
                'subcategories' => $subcategories,
            ], 200);
        } catch (Exception $e) {
            Log::error("Error fetching subcategories: " . $e->getMessage());
            return response()->json([
                'error' => "Error fetching subcategories",
            ], 500);
        }
    }

    public function searchRecommendations(Request $request) {
        $foundRecommendations = Subcategory::select('id', 'name')
            ->get();

        return response()->json([
            'data' => $foundRecommendations
        ], 200);
    }

    public function searchProducts(Request $request)
    {
        $query = trim($request->query('query', ''));
    
        Log::info("Search query received: '$query'");
    
        if (empty($query)) {
            Log::info("Empty query, returning empty products");
            return response()->json([
                'success' => true,
                'products' => [],
                'pagination' => [
                    'current_page' => 1,
                    'last_page' => 1,
                    'per_page' => $request->query('per_page', 30),
                    'total' => 0,
                ]
            ], 200);
        }
    
        // Get page and per_page from query parameters
        $page = $request->query('page', 1);
        $perPage = $request->query('per_page', 30);
    
        try {
            Log::info("Total products in DB: " . Product::count());
            Log::info("Product names: " . Product::pluck('name')->toJson());
    
            // Debug TSVector content for all products
            $tsvectorDebug = Product::selectRaw("name, name_tsvector::text, description, description_tsvector::text, slug, slug_tsvector::text")
                ->get();
            Log::info("TSVector debug for all products: " . $tsvectorDebug->toJson());
    
            // Enable query logging
            DB::enableQueryLog();
    
            // Step 1: Preprocess the query for full-text search
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
            Log::info("Full-text search query: '$tsQuery'");
    
            // Step 2: Perform the full-text search with 'simple' configuration
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
    
            $fullTextProducts = $fullTextQuery->paginate($perPage, ['*'], 'page', $page);
    
            Log::info("Full-text search results: " . $fullTextProducts->toJson());
    
            // Step 3: Perform trigram similarity search for typo tolerance
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
                ->paginate($perPage, ['*'], 'page', $page);
    
            Log::info("Trigram search results: " . $trigramProducts->toJson());
    
            // Step 4: Combine results, prioritizing full-text matches
            $combinedProducts = $fullTextProducts->getCollection()->merge($trigramProducts->getCollection())
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
    
            // Create a new paginated response
            $paginatedProducts = new \Illuminate\Pagination\LengthAwarePaginator(
                $combinedProducts->forPage($page, $perPage),
                $combinedProducts->count(),
                $perPage,
                $page,
                ['path' => $request->url()]
            );
    
            // Map products to include parent region name with debug logging
            $productsWithParentRegion = $paginatedProducts->getCollection()->map(function ($product) {
                Log::info("Product ID: " . $product->id . ", Parent Region: " . json_encode($product->parentRegion));
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'price' => $product->price,
                    'currency' => $product->currency,
                    'region' => $product->parentRegion->name ?? $product->region->name ?? null,
                    'images' => $product->images->map(function ($image) {
                        return ['image_url' => $image->image_url];
                    })->toArray(),
                ];
            })->values();
    
            Log::info("Mapped products: " . json_encode($productsWithParentRegion));
    
            // Manually construct the response to avoid default serialization
            return response()->json([
                'success' => true,
                'products' => $productsWithParentRegion,
                'pagination' => [
                    'current_page' => $paginatedProducts->currentPage(),
                    'last_page' => $paginatedProducts->lastPage(),
                    'per_page' => $paginatedProducts->perPage(),
                    'total' => $paginatedProducts->total(),
                ]
            ], 200);
    
        } catch (\Exception $e) {
            Log::error("Search error: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'An error occurred while searching products: ' . $e->getMessage(),
            ], 500);
        }
    
        /**
         * Calculate trigram similarity (helper function for sorting)
         */
    }

    private function similarity($string1, $string2)
        {
            $string1 = strtolower(trim($string1));
            $string2 = strtolower(trim($string2));
            if (empty($string1) || empty($string2)) {
                return 0;
            }
            return DB::selectOne("SELECT similarity(?, ?) as sim", [$string1, $string2])->sim ?? 0;
        }

        public function getCategory($id)
        {
            try {
                
                $subcategory = Subcategory::with('category')->find($id);
        
                
                if (!$subcategory) {
                    return response()->json([
                        'error' => 'Subcategory not found',
                    ], 404);
                }
        
              
                if (!$subcategory->category) {
                    return response()->json([
                        'error' => 'Parent category not found for this subcategory',
                    ], 404);
                }
        
           
                return response()->json([
                    'category' => [
                        'id' => $subcategory->id,
                        'name' => $subcategory->name,
                        'category_id' => $subcategory->category->id,
                        'category_name' => $subcategory->category->name,
                    ],
                ], 200);
        
            } catch (\Exception $e) {
                return response()->json([
                    'error' => 'An error occurred while fetching the category: ' . $e->getMessage(),
                ], 500);
            }
        }
}