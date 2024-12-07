<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Profile extends Model
{
    protected $fillable = [
        'phone',
        'user_id',
        'region_id',
        'address',
        'avatar',
        'latitude',
        'longitude'
    ];

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function region() {
        return $this->belongsTo(Region::class);
    }

    
}
