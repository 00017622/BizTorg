<?php

namespace App\Observers;

use App\Models\ShopSubscription;
use App\Models\ShopProfile;

class ShopSubscriptionObserver
{
    /**
     * Handle the ShopSubscription "created" event.
     */
    public function created(ShopSubscription $shopSubscription): void
    {
        $shopSubscription->shop->increment('subscribers');
    }

    public function deleted(ShopSubscription $shopSubscription): void
    {
        $shopSubscription->shop->decrement('subscribers');
    }

    /**
     * Handle the ShopSubscription "updated" event.
     */
    public function updated(ShopSubscription $shopSubscription): void
    {
        //
    }

   
    public function restored(ShopSubscription $shopSubscription): void
    {
        //
    }

    /**
     * Handle the ShopSubscription "force deleted" event.
     */
    public function forceDeleted(ShopSubscription $shopSubscription): void
    {
        //
    }
}
