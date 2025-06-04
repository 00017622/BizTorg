<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Profile;
use App\Models\ShopProfile;
use App\Models\User;
use Auth;
use Illuminate\Http\Request;
use Log;

class ShopProfileController extends Controller
{
    public function store(Request $request) {

        try {
            $validatedData = $request->validate([
                'shop_name' => 'required|max:255',
                'description' => 'required|string',
                'tax_id_number' => 'nullable|string|max:25',
                'contact_name' => 'string|max:255',
                'address' => 'string|max:255',
                'phone' => 'required|string|max:20',
                // 'banner_url' => 'nullable|image|mimes:jpeg,png,jpg,svg|max:2048',
                // 'profile_url' => 'nullable|image|mimes:jpeg,png,jpg,svg|max:2048',
                'facebook_link' => 'nullable|string|max:300',
                'telegram_link' => 'nullable|string|max:300',
                'instagram_link' => 'nullable|string|max:300',
                'website' => 'nullable|string',
                'latitude' => 'nullable|sometimes|numeric|between:-90,90',
                'longitude' => 'nullable|sometimes|numeric|between:-180,180',
            ]);

            Log::info('Shop Profile creation attempt', [
                'user_id' => Auth::id(),
                'validated_data' => $validatedData,
            ]);
    
            $user = Auth::user();
    
            if ($user->shopProfile) {
                return response()->json([
                    'message' => 'У вас уже есть созданный магазин',
                ], 400);
            }
    
            $shopProfile = ShopProfile::create([
                'user_id' => $user->id,
                'shop_name' => $validatedData['shop_name'],
                'description' => $validatedData['description'],
                'tax_id_number' => $validatedData['tax_id_number'],
                'contact_name' => $validatedData['contact_name'],
                'address' => $validatedData['address'],
                'phone' => $validatedData['phone'],
                // 'banner_url' => $validatedData['banner_url'],
                // 'profile_url' => $validatedData['profile_url'],
                'facebook_link' => $validatedData['facebook_link'],
                'telegram_link' => $validatedData['telegram_link'],
                'instagram_link' => $validatedData['instagram_link'],
                'website' => $validatedData['website'],
                'verified' => false,
                'rating' => 0.0,
                'subscribers' => 0,
                'total_reviews' => 0,
                'views' => 0,
                'latitude' => $validatedData['latitude'] ?? null,
                'longitude' => $validatedData['longitude'] ?? null,
            ]);
    
            $user->update(['isShop' => true]);

            Log::info('ShopProfile created successfully', [
                'shop_id' => $shopProfile->id,
                'user_id' => $user->id,
            ]);
    
            return response()->json([
                'message' => 'Shop profile created successfully',
                'data' => $shopProfile,
            ], 201);
        } catch(\Exception $e) {
            return response()->json([
                'error' => 'Error happened' . ' ' . $e->getMessage(),
            ], 500);

            Log::error('Failed to create ShopProfile', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'stack_trace' => $e->getTraceAsString(),
            ]);
        }
    }

    public function updateShopData(Request $request) {
        try {

            $validatedData = $request->validate([
                'shop_name' => 'required|max:255',
                'description' => 'required|string',
                'tax_id_number' => 'nullable|string|max:25',
                'contact_name' => 'string|max:255',
                'address' => 'string|max:255',
                'phone' => 'required|string|max:20',
                'facebook_link' => 'nullable|string|max:300',
                'telegram_link' => 'nullable|string|max:300',
                'instagram_link' => 'nullable|string|max:300',
                'website' => 'nullable|string',
                'latitude' => 'nullable|sometimes|numeric|between:-90,90',
                'longitude' => 'nullable|sometimes|numeric|between:-180,180',
            ]);

            Log::info('Shop Profile update attempt', [
                'user_id' => Auth::id(),
                'validated_data' => $validatedData,
            ]);

            $user = Auth::user();

            $shopProfile = ShopProfile::updateOrCreate(
                ['user_id' => $user->id],
                [
                'shop_name' => $validatedData['shop_name'],
                'description' => $validatedData['description'],
                'tax_id_number' => $validatedData['tax_id_number'],
                'contact_name' => $validatedData['contact_name'],
                'address' => $validatedData['address'],
                'phone' => $validatedData['phone'],
                'facebook_link' => $validatedData['facebook_link'],
                'telegram_link' => $validatedData['telegram_link'],
                'instagram_link' => $validatedData['instagram_link'],
                'website' => $validatedData['website'],
                'latitude' => $validatedData['latitude'] ?? null,
                'longitude' => $validatedData['longitude'] ?? null,
            ]);

            Log::info('ShopProfile created successfully', [
                'shop_id' => $shopProfile->id,
                'user_id' => $user->id,
            ]);

       
    
            return response()->json([
                'message' => 'Shop profile updated successfully',
                'data' => $shopProfile,
              
            ], 200);

        } catch(\Exception $e) {
            Log::error('Error updating ShopProfile: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'error' => $e->getTraceAsString(),
            ]);
    
            return response()->json([
                'message' => 'Failed to update shop profile',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    public function updateImages(Request $request, $shopId) {
        try {

            $shopProfile = ShopProfile::findOrFail($shopId);

            if ($shopProfile->user_id !== Auth::id()) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $validatedData = $request->validate([
                'banner_url' => 'nullable|image|mimes:jpeg,png,jpg,svg|max:2048',
                'profile_url' => 'nullable|image|mimes:jpeg,png,jpg,svg|max:2048',
            ]);

            if($request->hasFile('banner_url')) {
                $path = $request->file('banner_url')->store('banners', 'public');
                Log::info("Banner Image uploaded successfully to: $path");
                $shopProfile->banner_url = $path;
            }

            if ($request->hasFile('profile_url')) {
                $path = $request->file('profile_url')->store('avatars', 'public');
                Log::info("Profile Image uploaded successfully to: $path");
                $shopProfile->profile_url = $path;
            }

            $shopProfile->save();

            Log::info('ShopProfile images updated', [
                'shop_id' => $shopProfile->id,
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'message' => 'Изображение успешно обновлено',
                'data' => $shopProfile,
            ]);

        } catch(\Exception $e) {
            Log::error('Error occured' . '-' . $e->getMessage());
            return response()->json([
                'message' => 'error occured',
            ], 500);
        }
    }

    public function getShopProfile(Request $request, $shopId) {

        try {
            $user = Auth::user();

            if(!$user) {
                return response()->json([
                    'message' => 'Unauthorized access. Please log in.',
                ], 401);
            }

            $shopProfile = ShopProfile::findOrFail($shopId);

            if ($user->id != $shopProfile->user_id) {
                return response()->json([
                    'message' => 'You have no permission to access this shop info'
                ], 403);
            }

            $isAlreadySubscriber = false;
            $hasAlreadyRated = false;

            if($user->isShop) {
                $isAlreadySubscriber = $user->shopProfile->subscribers()->where('user_id', $user->id)->exists();
                $hasAlreadyRated = $user->shopProfile->raters()->where('user_id', $user->id)->exists();
            }
    
            return response()->json([
                'shop_profile' => $shopProfile,
                'message' => 'success',
                'isAlreadySubscriber' => $isAlreadySubscriber,
                'hasAlreadyRated' => $hasAlreadyRated,
            ], 200);
        } catch(\Exception $e)  {
            Log::error('The error ocurred' . ' ' . $e->getMessage());
            return response()->json([
                'message' => 'error',
                'error' => $e->getMessage(),
            ]);
        }
    }


    public function getUserProducts(Request $request, $userId)
{
    try {
        // Directly query the Product model, filtering by user_id and eager loading relationships
        $userProducts = Product::with('region')
            ->where('user_id', $userId)
            ->get()->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'price' => $product->price,
                                'currency' => $product->currency,
                                'created_at' => $product->created_at,
                                'updated_at' => $product->updated_at,
                                'region' => $product->parentRegion->name ?? $product->region->name ?? null,
                                'images' => $product->images->map(function ($image) {
                                    return ['image_url' => $image->image_url];
                                })->toArray(),
                ];
            });

        $userProductsCount = $userProducts->count();

        return response()->json([
            'products' => $userProducts,
            'products_count' => $userProductsCount,
            'message' => 'success',
        ], 200);
    } catch (\Exception $e) {
        Log::error('Error occurred fetching userProducts: ' . $e->getMessage());
        return response()->json(['message' => 'Failed to fetch user products'], 500);
    }
}
}
