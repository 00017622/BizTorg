<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\Subcategory;
use Cache;
use Exception;
use Illuminate\Support\Facades\Log;

class CategoryController extends Controller {
    public function allCategories() {
        $cacheKey = 'home_page_response';
        $cacheDuration = 60 * 10;

        return Cache::remember($cacheKey, $cacheDuration, function () {
            try {
                $categories = Cache::rememberForever('all_categories_forever', function() {
                    return Category::all();
                });
                $slugs = ['transport', 'nedvizhimost', 'elektronika', 'biznes-i-uslugi', 'dom-i-sad'];
                $displayedCategories = Category::whereIn('slug', $slugs)
        ->with(['subcategories.products.images', 'subcategories.products.region'])
        ->get()
        ->map(function ($category) {
            $products = $category->subcategories->flatMap(function ($subcategory) {
                return $subcategory->products;
            })->take(12);
    
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
}