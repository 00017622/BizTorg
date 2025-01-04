<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Category;
use Carbon\Carbon;

class SitemapController extends Controller
{
    public function generateSitemap()
    {
        $sitemap = '<?xml version="1.0" encoding="UTF-8"?>';
        $sitemap .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        $sitemap .= '
        <url>
            <loc>https://biztorg.uz/</loc>
            <lastmod>' . Carbon::now()->toDateString() . '</lastmod>
            <priority>1.0</priority>
        </url>';
        $categories = Category::all();
        foreach ($categories as $category) {
            $sitemap .= '
            <url>
                <loc>' . htmlspecialchars('https://biztorg.uz/category/' . $category->slug, ENT_QUOTES, 'UTF-8') . '</loc>
                <lastmod>' . $category->updated_at->toDateString() . '</lastmod>
                <priority>0.9</priority>
            </url>';
        }
        $products = Product::all();
        foreach ($products as $product) {
            $sitemap .= '
            <url>
                <loc>' . htmlspecialchars('https://biztorg.uz/obyavlenie/' . $product->slug, ENT_QUOTES, 'UTF-8') . '</loc>
                <lastmod>' . $product->updated_at->toDateString() . '</lastmod>
                <priority>0.9</priority>
            </url>';
        }
        $sitemap .= '</urlset>';
        return response(trim($sitemap), 200)
            ->header('Content-Type', 'application/xml');
    }
}
