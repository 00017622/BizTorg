<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProfileUpdateRequest;
use App\Models\Profile;
use App\Models\Region;
use App\Models\ShopProfile;
use App\Models\User;
use Cache;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function updateProfile(Request $request)
{
    $id = $request->input('uuid');

    if (!is_numeric($id)) {
        return response()->json(['error' => 'Invalid ID'], 400);
    }

    $validatedData = $request->validate([
        'name' => 'required|string|max:255',
        'email' => "required|email|max:255|unique:users,email,{$id}",
        'phone' => 'required|string|max:20',
      
        'region_id' => 'nullable|exists:regions,id',
    ]);

    $user = User::findOrFail($id); 

    $user->update([
        'name' => $validatedData['name'],
        'email' => $validatedData['email'],
    ]);

    if ($user->wasChanged('email')) {
        $user->email_verified_at = null;
        $user->save();
    }

    $user->profile()->updateOrCreate(
        ['user_id' => $user->id],
        [
            'phone' => $validatedData['phone'],
        
            'region_id' => $validatedData['region_id'],
        ]
    );

    Cache::forget("user_data_{$id}");

    return response()->json(['message' => 'Profile updated'], 200);
}


    public function getUserDataJson(Request $request, $id)
{

    $currentUserId = $request->query('current_user_id');


    $cacheKey = "user_data_{$id}_viewer_{$currentUserId}";
    $cacheDuration = 1; 

    

    $userData = Cache::remember($cacheKey, $cacheDuration, function () use ($id, $currentUserId) {
        $user = User::findOrFail($id);

        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }    
        
        $userProfile = Profile::where('user_id', $id)->first();
        $region = $userProfile ? Region::where('id', $userProfile->region_id)->first() : null;

        // $shopProfileData = [];

        // $myProfile = false;

        $isAlreadySubscriber = false;

        $hasAlreadyRated = false;

        $shopProfile = null; // Default null


      if ($user->isShop) {
    $shopProfile = $user->shopProfile;

    if ($currentUserId) {
        $isAlreadySubscriber = $shopProfile->subscribers()->where('user_id', $currentUserId)->exists();
        $hasAlreadyRated = $shopProfile->raters()->where('user_id', $currentUserId)->exists();
    }
}

        return [
            'user' => $user,
            'user_profile' => $userProfile,
            'region' => $region,
            'isShop' => $user->isShop,
            'shop_profile' => $shopProfile,
            'isAlreadySubscriber' => $isAlreadySubscriber,
            'hasAlreadyRated' => $hasAlreadyRated,
        ];
    });

    return response()->json($userData);
}

}
