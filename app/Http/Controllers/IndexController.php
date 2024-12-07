<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;

class IndexController extends Controller
{
    public function index() {
        $slugs = ['transport', 'nedvizhimost', 'elektronika', 'biznes-i-uslugi', 'dom-i-sad'];

        $categories = Category::with('subcategories')->get();
        
        $displayedCategories = Category::whereIn('slug', $slugs)->with(['subcategories' => function($query) {
            $query->take(5)->with(['products' => function ($query) {
                $query->with('images')->limit(1);
            }]);
        }])->get();

        return view('welcome', compact('categories', 'displayedCategories'));
    }
}
