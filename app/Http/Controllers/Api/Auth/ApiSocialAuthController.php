<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;

class ApiSocialAuthController extends Controller {
    public function googleSignIn(Request $request) {

        try {
            $access_token = $request->input('access_token');

            if (!$access_token) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Access token is required.',
                ], 400);
            }
    
            $googleUser = Socialite::driver('google')->userFromToken($access_token);

            if (!$googleUser) {
                Log::error("Failed to retrieve Google user details.");
                return response()->json([
                    'status' => 'error',
                    'message' => 'Google user not found.',
                ], 400);
            }

            $user = User::where('email', $googleUser->getEmail())->first();

            if ($user) {
                $user->update([
                    'avatar' => $googleUser->getAvatar(),
                    'google_id' => $googleUser->getId(),
                ]);
            } else {
                $user = User::create([
                    'name' => $googleUser->getName(),
                    'email' => $googleUser->getEmail(),
                    'google_id' => $googleUser->getId(),
                    'password' => bcrypt(Str::random(16)),
                    'avatar' => $googleUser->getAvatar(),
                    'role_id' => 0,
                ]);
            }

            $token = $user->createToken('Auth-Api')->plainTextToken;

            return response()->json([
                'status' => 'success',
                'message' => 'Google login successful.',
                'token' => $token,
                'uuid' => $user->id,
            ], 200); 
          
        } catch (Exception $e) {
            Log::error('Google login error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Error while signing in.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function facebookSignIn(Request $request) {
        $request->validate([
            'access_token' => 'required|string',
        ]);
        try {
            $access_token = $request->input('access_token');

            if (!$access_token) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Access token is required.',
                ], 400);
            }

            $facebookUser = Socialite::driver('facebook')->userFromToken($access_token);

            if (!$facebookUser) {
                Log::error("Failed to retrieve Facebook user details.");
                return response()->json([
                    'status' => 'error',
                    'message' => 'Facebook user not found.',
                ], 400);
            }

            $user = User::where('email', $facebookUser->getEmail())->first();

            if ($user) {
                $user->update([
                    'avatar' => $facebookUser->getAvatar(),
                    'facebook_id' => $facebookUser->getId(),
                ]);
            } else {
                $user = User::create([
                    'name' => $facebookUser->getName(),
                    'email' => $facebookUser->getEmail(),
                    'facebook_id' => $facebookUser->getId(),
                    'password' => bcrypt(Str::random(16)),
                    'avatar' => $facebookUser->getAvatar(),
                    'role_id' => 0,
                ]);
            }

            $token = $user->createToken('Auth-Api')->plainTextToken;

            return response()->json([
                'status' => 'success',
                'message' => 'Facebook login successful.',
                'token' => $token,
                'uuid' => $user->id,
            ], 200, ['Content-Type' => 'application/json; charset=UTF-8'], JSON_UNESCAPED_UNICODE); 

        } catch(Exception $e) {
            Log::error('Facebook login error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Error while signing in.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
