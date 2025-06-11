<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Region extends Model
{
    protected $fillable = ['name', 'parent_id'];

    public function parentId() {
        return $this->belongsTo(Region::class, 'parent_id');
    }

    public function children() {
        return $this->hasMany(Region::class, 'parent_id');
    }

    public function products() {
        return $this->hasMany(Product::class, 'region_id');
    }
}
