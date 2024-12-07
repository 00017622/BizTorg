<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;
use SocialiteProviders\Manager\SocialiteWasCalled;
use SocialiteProviders\Telegram\Provider as TelegramProvider;


class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(function (\SocialiteProviders\Manager\SocialiteWasCalled $event) {
            $event->extendSocialite('telegram', \SocialiteProviders\Telegram\Provider::class);
        });
    }
}
