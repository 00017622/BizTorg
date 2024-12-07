<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductImage extends Model
{
    protected $fillable = ['product_id', 'image_url'];

    public function productId() {
        return $this->belongsTo(Product::class);
    }
}
