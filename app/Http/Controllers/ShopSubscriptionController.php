<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ShopProfile;
use Illuminate\Support\Facades\Log;

class ShopSubscriptionController extends Controller
{
    public function subscribe(Request $request, $shopId)
    {
        try {
            // Log the incoming request
            Log::info('Subscribe request received', [
                'shop_id' => $shopId,
                'user_id' => $request->user()->id
            ]);

            $user = $request->user();
            $shop = ShopProfile::findOrFail($shopId);

            // Check if already subscribed
            $isSubscribed = $user->subscribedShops()->where('shop_id', $shop->id)->exists();
            Log::debug('Subscription check', [
                'user_id' => $user->id,
                'shop_id' => $shop->id,
                'is_subscribed' => $isSubscribed
            ]);

            if ($isSubscribed) {
                Log::warning('User already subscribed to shop', [
                    'user_id' => $user->id,
                    'shop_id' => $shop->id
                ]);
                return response()->json(['message' => 'Already subscribed'], 400);
            }

            // Attach the shop to the user's subscriptions
            $user->subscribedShops()->attach($shop->id);
            Log::info('Subscription attached', [
                'user_id' => $user->id,
                'shop_id' => $shop->id
            ]);

            // Increment subscribers count
            $shop->increment('subscribers');
            Log::info('Subscribers count incremented', [
                'shop_id' => $shop->id,
                'new_subscribers_count' => $shop->subscribers
            ]);

            return response()->json([
                'message' => 'Subscribed successfully',
                'subscribers' => $shop->subscribers
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error in subscribe method', [
                'shop_id' => $shopId,
                'user_id' => $request->user()->id,
                'error' => $e->getMessage()
            ]);
            return response()->json(['message' => 'Failed to subscribe'], 500);
        }
    }

    public function unsubscribe(Request $request, $shopId)
    {
        try {
            // Log the incoming request
            Log::info('Unsubscribe request received', [
                'shop_id' => $shopId,
                'user_id' => $request->user()->id
            ]);

            $user = $request->user();
            $shop = ShopProfile::findOrFail($shopId);

            // Check if subscribed
            $isSubscribed = $user->subscribedShops()->where('shop_id', $shop->id)->exists();
            Log::debug('Subscription check', [
                'user_id' => $user->id,
                'shop_id' => $shop->id,
                'is_subscribed' => $isSubscribed
            ]);

            if (!$isSubscribed) {
                Log::warning('User not subscribed to shop', [
                    'user_id' => $user->id,
                    'shop_id' => $shop->id
                ]);
                return response()->json(['message' => 'Not subscribed'], 400);
            }

            // Detach the shop from the user's subscriptions
            $user->subscribedShops()->detach($shop->id);
            Log::info('Subscription detached', [
                'user_id' => $user->id,
                'shop_id' => $shop->id
            ]);

            // Decrement subscribers count
            $shop->decrement('subscribers');
            Log::info('Subscribers count decremented', [
                'shop_id' => $shop->id,
                'new_subscribers_count' => $shop->subscribers
            ]);

            return response()->json([
                'message' => 'Unsubscribed successfully',
                'subscribers' => $shop->subscribers
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error in unsubscribe method', [
                'shop_id' => $shopId,
                'user_id' => $request->user()->id,
                'error' => $e->getMessage()
            ]);
            return response()->json(['message' => 'Failed to unsubscribe'], 500);
        }
    }
}