<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attribute;
use App\Models\Product;
use App\Models\Region;
use App\Models\Subcategory;
use Cache;
use Exception;
use Illuminate\Support\Facades\Log;

class RegionsController extends Controller {
    public function fetchRegions() {
        $cacheKey = 'parent_regions';
        $cacheDuration = 60 * 180;
        $parentRegions = Cache::remember($cacheKey, $cacheDuration, function () {
            return Region::where('parent_id', null)->get();
        });
        return response()->json([
            'parent_regions' => $parentRegions,
        ]);
    }

    public function fetchChildRegions($parentRegionId) {
        $cacheKey = 'child_regions' . $parentRegionId;
        $cacheDuration = 60 * 180;
        $childRegions = Cache::remember($cacheKey, $cacheDuration, function () use($parentRegionId){
            return Region::where('parent_id', $parentRegionId)->get();
        }); 
        return response()->json([
            'child_regions' => $childRegions,
        ], 200);
    }
}