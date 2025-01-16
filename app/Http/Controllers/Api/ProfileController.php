<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProfileUpdateRequest;
use App\Models\Profile;
use App\Models\Region;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function updateProfile(ProfileUpdateRequest $request)
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

        $request->user()->update([
            'name' => $validatedData['name'],
            'email' => $validatedData['email'],
        ]);

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $avatarPath = $request->user()->profile->avatar ?? null;
        if ($request->hasFile('avatar')) {
            if ($avatarPath && Storage::exists('public/' . $avatarPath)) {
                Storage::delete('public/' . $avatarPath);
            }

            $avatarPath = $request->file('avatar')->store('avatars', 'public');
        }

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

        return response()->json(['message' => 'Profile updated']);
    }

    public function deleteProfile($id)
    {
        $user = User::findOrFail($id);
        $user->delete();

        return response()->json(['message' => 'Лошара успешно удален']);
    }

    public function getUserData(Request $request)
    {
        $user = $request->user();
        $section = '';
        return view('profile.userprofile', compact('user', 'section'));
    }

    public function getUserDataJson($id)
    {
        $user = User::findOrFail($id);
        return response()->json($user, 201);
    }


    public function storeProfile(Request $request)
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

        if ($request->hasFile('avatar')) {
            $validated['avatar'] = $request->file('avatar')->store('avatars', 'public');
        } else {
            $validated['avatar'] = null;
        }

        $profile = Profile::create($validated);

        return response()->json(['message' => 'Profile created!', $profile]);
    }
}
