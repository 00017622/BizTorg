<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attribute extends Model
{
    protected $fillable = ['name'];
    
    public function subcategories()
{
    return $this->belongsToMany(
        Subcategory::class,
        'attribute_subcategories',
        'attribute_id',
        'subcategory_id'
    );
}

public function attributeValues() {
    return $this->belongsToMany(AttributeValue::class, 'attribute_attribute_values', 'attribute_id', 'attribute_value_id');
}

}
