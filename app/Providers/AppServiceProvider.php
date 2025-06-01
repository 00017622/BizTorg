<?php

namespace App\Providers;

use App\Models\ShopRating;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;
use SocialiteProviders\Manager\SocialiteWasCalled;
use SocialiteProviders\Telegram\Provider as TelegramProvider;
use App\Models\ShopSubscription;
use App\Observers\ShopRatingObserver;
use App\Observers\ShopSubscriptionObserver;

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

        ShopSubscription::observe(ShopSubscriptionObserver::class);
        ShopRating::observe(ShopRatingObserver::class);
    }
}
