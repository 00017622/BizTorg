<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Observers\ShopSubscriptionObserver;

class ShopSubscription extends Model
{
    protected $fillable = [
        'user_id',
        'shop_id',
    ];

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function shop() {
        return $this->belongsTo(ShopProfile::class, 'shop_id');
    }

}
