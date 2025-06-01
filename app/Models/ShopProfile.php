<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Log;

class ShopProfile extends Model
{
    protected $fillable = [
        'user_id',
        'shop_name',
        'description',
        'tax_id_number',
        'contact_name',
        'address',
        'phone',
        'banner_url',
        'profile_url',
        'is_online', //skipping this
        'facebook_link',
        'telegram_link',
        'instagram_link',
        'website',
        'verified',
        'rating',
        'subscribers',
        'total_reviews',
        'views',
        'latitude',
        'longitude',
        'rating_sum',
        'rating_count'
    ];

    protected $casts = [
        'is_online' => 'boolean',
        'rating' => 'float',
        'rating_sum' => 'integer',
        'rating_count' => 'integer',
        'verified' => 'boolean',
    ];

   

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function subscriptions() {
        return $this->hasMany(ShopSubscription::class, 'shop_id');
    }

    public function subscribers() {
        return $this->belongsToMany(User::class, 'shop_subscriptions', 'shop_id', 'user_id');
    }

    public function ratings() {
        return $this->hasMany(ShopRating::class, 'shop_profile_id');
    }

    public function raters() {
        return $this->belongsToMany(User::class, 'shop_ratings', 'shop_profile_id', 'user_id');
    }

    public function addRating($userId, $rating)
    {
        try {
            Log::info("Adding rating: user_id=$userId, rating=$rating, initial rating_sum={$this->rating_sum}, rating_count={$this->rating_count}");
            $this->refresh();
    
            Log::info("Creating new rating for shop_profile_id={$this->id}");
            $shopRating = ShopRating::withoutEvents(function () use ($userId, $rating) {
                return ShopRating::create([
                    'user_id' => $userId,
                    'shop_profile_id' => $this->id,
                    'rating' => $rating,
                ]);
            });
    
            $this->rating_count = $this->rating_count + 1;
            $this->rating_sum = $this->rating_sum + $rating;
            $this->save(); // Explicitly save rating_count and rating_sum
            Log::info("After creation: rating_sum={$this->rating_sum}, rating_count={$this->rating_count}, shopRating ID={$shopRating->id}");
    
            $this->updateAverageRating();
        } catch (\Exception $e) {
            Log::error("Failed to add rating: " . $e->getMessage());
            throw $e; // Let the controller handle the error
        }
    }
    
    public function updateAverageRating()
    {
        Log::info("Updating average rating: rating_sum={$this->rating_sum}, rating_count={$this->rating_count}");
        if ($this->rating_count > 0) {
            $this->rating = round($this->rating_sum / $this->rating_count, 2);
        } else {
            $this->rating = 0.0;
        }
        $this->save();
        Log::info("Updated average rating: rating={$this->rating}");
    }
}
