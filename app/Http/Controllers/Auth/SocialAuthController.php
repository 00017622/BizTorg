<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthController extends Controller
{
    public function redirectToGoogle() {
        return Socialite::driver('google')->redirect();
    }

    public function handleGoogleCallback() {
        try {
            $googleUser = Socialite::driver('google')->user();

            $user = User::where('email', $googleUser->getEmail())->first();

            if ($user) {
                Auth::login($user);
            } else {
                $user = User::create([
                    'name' => $googleUser->getName(),
                    'email' => $googleUser->getEmail(),
                    'google_id' => $googleUser->getEmail(),
                    'password' => bcrypt(Str::random(16)),
                    'avatar' => $googleUser->getAvatar(),
                ]);

                Auth::login($user);
            }

            return redirect()->route('index.show');

        } catch(\Exception $e) {
            return redirect()->route('login')->withErrors(['msg' => 'Failed to login with Google.']);
        }
    }

    public function redirectToFacebook() {
        return Socialite::driver('facebook')->redirect();
    }

    public function handleFacebookCallback()
{
    try {
        $facebookUser = Socialite::driver('facebook')->user();

        // Initialize variables
        $facebookId = $facebookUser->getId();
        $email = $facebookUser->getEmail();

        // Check if a user exists by Facebook ID or email (skip email if null)
        $userQuery = User::where('facebook_id', $facebookId);
        if ($email !== null) {
            $userQuery->orWhere('email', $email);
        }
        $user = $userQuery->first();

        if ($user) {
            // If user exists, update Facebook ID if missing
            if (!$user->facebook_id) {
                $user->update(['facebook_id' => $facebookId]);
            }

            // Log in the user
            Auth::login($user);
        } else {
            // Create a new user (email can be null)
            $user = User::create([
                'name' => $facebookUser->getName(),
                'email' => $email, // Can be null
                'facebook_id' => $facebookId,
                'password' => bcrypt(Str::random(16)),
                'role_id' => 0,
                'avatar' => $facebookUser->getAvatar(),
            ]);

            Auth::login($user);
        }

        return redirect()->route('index.show');
    } catch (\Exception $e) {
        // Log the error and redirect back to the login page
        Log::error('Facebook login error: ' . $e->getMessage());
        return redirect()->route('login')->withErrors(['msg' => 'Failed to login with Facebook.']);
    }
}


    public function redirectToTelegram() {
        return Socialite::provider('telegram')->redirect();
    }

    public function handleTelegramCallback() {
        try  {
            $telegramUser = Socialite::driver('telegram')->user();
            
           $findUser = User::where('telegram_id', $telegramUser->getId())->first();

           $findUserEmail = User::where('email', $telegramUser->getEmail())->first();

           if ($findUser) {
            Auth::login($findUser);

           } elseif ($findUserEmail) {
                Auth::login($findUserEmail);
           } else {
            $user = User::create([
                'name' => $telegramUser->getName(),
                'telegram_id' => $telegramUser->getId(),
                'password' => bcrypt(Str::random(16)),
                'role_id' => 0,
                'avatar' => $telegramUser->getAvatar(),
            ]);

            Auth::login($user);
           }

           return redirect()->route('index.show');
            
        }
        catch(\Exception $e) {
            Log::error('Telegram login error: ' . $e->getMessage());
            return redirect()->route('login')->withErrors(['msg' => 'Failed to login with telegram']);
        }
    }
}
