<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\Subcategory;
use Cache;
use DB;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

class CategoryController extends Controller {
    public function allCategories() {
        $cacheKey = 'home_page_response';
        $cacheDuration = 60 * 10;

        return Cache::remember($cacheKey, $cacheDuration, function () {
            try {
                $categories = Cache::rememberForever('all_categories_forever', function() {
                    return Category::all();
                });
                $slugs = ['transport', 'nedvizhimost', 'elektronika', 'detskij-mir', 'rabota', 'moda-i-stil', 'dom-i-sad', 'biznes-i-uslugi', 'dom-i-sad'];
                $displayedCategories = Category::whereIn('slug', $slugs)
        ->with(['subcategories.products.images', 'subcategories.products.region'])
        ->get()
        ->map(function ($category) {
            $products = $category->subcategories->flatMap(function ($subcategory) {
                return $subcategory->products;
            })->take(30);
    
            $category->setRelation('products', $products);
    
            $category->unsetRelation('subcategories');
    
            return $category;
        });
    
    
                return response()->json([
                    'categories' => $categories,
                    'displayedCategories' => $displayedCategories,
                ]);
    
            } catch(Exception $e) {
                return response()->json([
                    'error' => "Error fetching categories:" . $e
                ], 500);
            }
        });
        
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
                ->with(['images', 'region']);
    
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
                ->with(['images', 'region'])
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
    
            Log::info("Combined search results: " . $combinedProducts->toJson());
    
            // Log the SQL query
            Log::info("SQL Query: " . json_encode(DB::getQueryLog()));
    
            return response()->json([
                'success' => true,
                'products' => $paginatedProducts->items(),
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