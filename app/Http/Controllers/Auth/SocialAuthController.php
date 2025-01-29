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
                $user->update([
                    'google_id' => $googleUser->getId(),
                    'avatar' => $googleUser->getAvatar(),
                ]);
                Auth::login($user);
            } else {
                $user = User::create([
                    'name' => $googleUser->getName(),
                    'email' => $googleUser->getEmail(),
                    'google_id' => $googleUser->getId(),
                    'password' => bcrypt(Str::random(16)),
                    'avatar' => $googleUser->getAvatar(),
                    'role_id' => 0,
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

        $facebookId = $facebookUser->getId();
        $email = $facebookUser->getEmail();

        $userQuery = User::where('facebook_id', $facebookId);
        if ($email !== null) {
            $userQuery->orWhere('email', $email);
        }
        $user = $userQuery->first();

        if ($user) {
            if (!$user->facebook_id) {
                $user->update(['facebook_id' => $facebookId]);
            }
            Auth::login($user);
        } else {
            $user = User::create([
                'name' => $facebookUser->getName(),
                'email' => $email,
                'facebook_id' => $facebookId,
                'password' => bcrypt(Str::random(16)),
                'role_id' => 0,
                'avatar' => $facebookUser->getAvatar(),
            ]);

            Auth::login($user);
        }

        return redirect()->route('index.show');
    } catch (\Exception $e) {
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
