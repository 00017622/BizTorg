<?php

namespace App\Http\Controllers;

use App\Models\ShopProfile;
use Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ShopRatingController extends Controller
{
    public function rateShop(Request $request)
    {
        try {

            $shopId = $request->input('shop_id');

            $shopProfile = ShopProfile::find($shopId);

            $userId = Auth::user()->id;

            $rating = $request->input('rating');

            if (!$shopProfile) {
                return response()->json(['error' => 'ShopProfile not found'], 404);
            }

            $shopProfile->addRating($userId, $rating);

            $shopProfile->refresh();

            return response()->json([
                'message' => 'Rating added successfully',
                'shop_profile' => $shopProfile,
                'rating_sum' => $shopProfile->rating_sum,
                'rating' => $shopProfile->rating,
                'rating_count' => $shopProfile->rating_count,
            ], 200);
        } catch (\Exception $e) {
            Log::error("Failed to add test rating: " . $e->getMessage());
            return response()->json(['error' => 'Failed to add rating: ' . $e->getMessage()], 500);
        }
    }
}