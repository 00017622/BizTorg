<?php

namespace App\Observers;

use App\Models\ShopRating;
use Log;

class ShopRatingObserver
{
    /**
     * Handle the ShopRating "created" event.
     */
    public function created(ShopRating $shopRating): void
    {
        Log::info("Observer triggered for shopRating ID: {$shopRating->id}, shop_profile_id: {$shopRating->shop_profile_id}");
        $shopProfile = $shopRating->shopProfile;

        if ($shopProfile) {
            Log::info("ShopProfile found: ID {$shopProfile->id}");
            $shopProfile->addRating($shopRating->user_id, $shopRating->rating);
        } else {
            Log::warning("ShopProfile not found for shop_profile_id: {$shopRating->shop_profile_id}");
        }
    }

    /**
     * Handle the ShopRating "updated" event.
     */
    public function updated(ShopRating $shopRating): void
    {
        $shopProfile = $shopRating->shopProfile;
        if ($shopProfile) {

            Log::info("Observer triggered for shopRating ID: {$shopRating->id}, shop_profile_id: {$shopRating->shop_profile_id}");
            
            $oldRating = $shopRating->getOriginal('rating');
            if ($oldRating !== $shopRating->rating) {
                $shopProfile->rating_sum = $shopProfile->rating_sum - $oldRating + $shopRating->rating;
                $shopProfile->updateAverageRating();
            }
        }
    }

    /**
     * Handle the ShopRating "deleted" event.
     */
    public function deleted(ShopRating $shopRating)
    {
        $shopProfile = $shopRating->shopProfile;
        if ($shopProfile) {
            $shopProfile->rating_sum = $shopProfile->rating_sum - $shopRating->rating;
            $shopProfile->rating_count = $shopProfile->rating_count - 1;
            $shopProfile->updateAverageRating();
        }
    }

    /**
     * Handle the ShopRating "restored" event.
     */
    public function restored(ShopRating $shopRating): void
    {
        //
    }

    /**
     * Handle the ShopRating "force deleted" event.
     */
    public function forceDeleted(ShopRating $shopRating): void
    {
        //
    }
}
