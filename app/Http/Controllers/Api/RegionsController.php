<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attribute;
use App\Models\Product;
use App\Models\Region;
use App\Models\Subcategory;
use Exception;
use Illuminate\Support\Facades\Log;

class RegionsController extends Controller {
    public function fetchRegions() {
        $parentRegions = Region::where('parent_id', null)->get();
        return response()->json([
            'parent_regions' => $parentRegions,
        ]);
    }

    public function fetchChildRegions($parentRegionId) {
        $childRegions = Region::where('parent_id', $parentRegionId)->get();
        return response()->json([
            'child_regions' => $childRegions,
        ], 200);
    }
}