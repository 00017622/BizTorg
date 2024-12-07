<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductAttributeValue extends Model
{
    public function productId() {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function attributeValueId() {
        return $this->belongsTo(AttributeValue::class, 'id');
    }
}
