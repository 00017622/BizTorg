<?php 

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attribute;
use App\Models\Product;
use App\Models\Region;
use App\Models\Subcategory;
use Exception;
use Illuminate\Support\Facades\Log;

class ProductController extends Controller {
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
            $categoryName = $selectedSubCategory->category()->first();

            if (!$selectedSubCategory) {
                return response()->json([
                    'error' => 'No such subcategory found',
                ], 404);
            }

            $attributes = $selectedSubCategory->attributes()->with('attributeValues')->get();
            return response()->json([
                'attributes' => $attributes,
                'categoryName' => $categoryName->name,
            ], 200);

        } catch(Exception $e) {
            return response()->json([
                'error' => 'Error occured' . $e,
            ], 500);
        }
    }
}