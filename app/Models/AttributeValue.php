<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttributeValue extends Model
{

    protected $fillable = ['value', 'slug'];

    public function attributes() {
        return $this->belongsToMany(Attribute::class, 'attribute_attribute_values');
    }

    public function products() {
        return $this->belongsToMany(
            Product::class,
            'product_attribute_values',
            'attribute_value_id', 
            'product_id'
        );
    }
}
