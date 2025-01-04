<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\Subcategory;
use Exception;
use Illuminate\Support\Facades\Log;

class CategoryController extends Controller {
    public function allCategories() {
        try {
            $categories = Category::all();
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
        
    }

    public function fetchSubcategories($categoryId) {
        try {
            $subcategories = Subcategory::where('category_id', $categoryId)->get();
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