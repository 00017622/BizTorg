<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShopRating extends Model
{
    protected $table = 'shop_ratings';

    protected $fillable = [
        'user_id',
        'shop_profile_id',
        'rating',
    ];

    public function user() {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function shopProfile() {
        return $this->belongsTo(ShopProfile::class, 'shop_profile_id', 'id');
    }
}
