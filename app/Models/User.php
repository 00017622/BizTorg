<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends \TCG\Voyager\Models\User
{
    use HasFactory, HasApiTokens, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id',
        'facebook_id',
        'telegram_id',
        'google_id',
        'avatar',
        'fcm_token',
        'isShop',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'isShop' => 'boolean',
        ];
    }

    // Explicitly define the id accessor to avoid conflicts
    public function getIdAttribute()
    {
        return $this->attributes['id'];
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function profile()
    {
        return $this->hasOne(Profile::class, 'user_id', 'id');
    }

    public function favoriteProducts()
    {
        return $this->belongsToMany(Product::class, 'favorites')->withTimestamps();
    }

    public function isShop() {
        return $this->isShop;
    }

    public function shopProfile() {
        return $this->hasOne(ShopProfile::class, 'user_id', 'id');
    }

    public function subscribedShops() {
        return $this->belongsToMany(ShopProfile::class, 'shop_subscriptions', 'user_id', 'shop_id');
    }

    public function subscriptions() {
        return $this->hasMany(ShopSubscription::class, 'user_id');
    }
}
