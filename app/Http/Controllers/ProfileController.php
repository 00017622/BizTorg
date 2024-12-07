<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\Profile;
use App\Models\Region;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        $profile = $request->user()->profile;
        $regions = Region::where('parent_id', null)->get();

        return view('profile.edit', [
            'user' => $request->user(),
            'profile' => $profile,
            'regions' => $regions,
            'section' => 'edit',
        ]);
    }

    
    public function update(ProfileUpdateRequest $request): RedirectResponse
{
    $validatedData = $request->validate([
        'name' => 'required|string|max:255',
        'email' => 'required|email|max:255|unique:users,email,' . $request->user()->id,
        'phone' => 'required|string|max:20',
        'address' => 'required|string|max:255',
        'region_id' => 'nullable|exists:regions,id',
        'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        'latitude' => 'required|numeric|between:-90,90',
        'longitude' => 'required|numeric|between:-180,180',
    ]);

    // Update the user's basic info
    $request->user()->update([
        'name' => $validatedData['name'],
        'email' => $validatedData['email'],
    ]);

    // Reset email verification if email is updated
    if ($request->user()->isDirty('email')) {
        $request->user()->email_verified_at = null;
    }

    // Handle avatar upload
    $avatarPath = $request->user()->profile->avatar ?? null; // Keep the current avatar if no new file is uploaded
    if ($request->hasFile('avatar')) {
        // Delete old avatar if exists
        if ($avatarPath && Storage::exists('public/' . $avatarPath)) {
            Storage::delete('public/' . $avatarPath);
        }

        // Store new avatar
        $avatarPath = $request->file('avatar')->store('avatars', 'public');
    }

    // Use updateOrCreate for the profile
    $request->user()->profile()->updateOrCreate(
        ['user_id' => $request->user()->id],
        [
            'avatar' => $avatarPath,
            'phone' => $validatedData['phone'],
            'address' => $validatedData['address'],
            'region_id' => $validatedData['region_id'],
            'latitude' => $validatedData['latitude'],
            'longitude' => $validatedData['longitude'],
        ]
    );

    return Redirect::route('profile.view')->with('status', 'profile-updated');
}



    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }

    public function getUserData(Request $request) {
        $user = $request->user();
        $section = '';
        return view('profile.userprofile', compact('user', 'section'));
    }

    public function getUserProducts(Request $request) {
        $user = $request->user();
        $products = $user->products()->get();
        $section = 'products';
        return view('profile.userproducts', compact('user', 'products', 'section'));
    }

    public function getUserFavorites(Request $request) {
        $user = $request->user();
        $favorites = $user->favoriteProducts()->get();
        $section = 'favorites';

        return view('profile.userfavorites', compact('favorites', 'user', 'section'));
    }

    public function toggleFavorites(Request $request)
    {
        $user = $request->user();
        $productId = $request->input('product_id');
    
        $user->favoriteProducts()->toggle($productId);
    
        $isFavorited = $user->favoriteProducts()->where('product_id', $productId)->exists();
    
        return response()->json([
            'status' => 'success',
            'isFavorited' => $isFavorited,
        ]);
    }
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users',
            'phone' => 'nullable|string|max:15',
            'address' => 'nullable|string|max:255',
            'region_id' => 'required|exists:regions,id',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'avatar' => 'nullable|image|max:2048',
        ]);
    
        // Handle avatar upload if present
        if ($request->hasFile('avatar')) {
            $validated['avatar'] = $request->file('avatar')->store('avatars', 'public');
        } else {
            $validated['avatar'] = null; // Default to null if no file is uploaded
        }
    
        // Create the profile
        Profile::create($validated);
    
        // Redirect with a success message
        return redirect()->route('profile.view')->with('status', 'profile-created');
    }
    


    public function create(Request $request) {
        $regions = Region::where('parent_id', null)->get();
        $user = $request->user();
        $section = '';
        return view('profile.create_profile', compact('regions', 'user', 'section'));
    }
    
}
