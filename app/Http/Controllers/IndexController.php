<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;

class IndexController extends Controller
{
    public function index()
{
    $slugs = ['transport', 'nedvizhimost', 'elektronika', 'biznes-i-uslugi', 'dom-i-sad'];

    $categories = Category::with('subcategories')->get();
    
    $displayedCategories = Category::whereIn('slug', $slugs)->with('subcategories')->get();

    // Fetch all products with their images

    $usdRate = 12750;

    $products = Product::with(['images', 'region', 'user'])->orderBy('created_at', 'desc')->paginate(24);

    return view('welcome', compact('categories', 'displayedCategories', 'products', 'usdRate'));
}

    public function getPaginatedProducts(Request $request)
    {
        $page = $request->input('page', 1);
        $perPage = $request->input('per_page', 24);

        $products = Product::with(['images', 'region', 'user'])
            ->orderBy('created_at', 'desc')->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'products' => $products->items(),
            'current_page' => $products->currentPage(),
            'last_page' => $products->lastPage(),
            'total' => $products->total(),
        ]);
    }
}
