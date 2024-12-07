<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttributeAttributeValue extends Model
{
    protected $fillable = ['attribute_id', 'attribute_value_id'];

    public function attributeId() {
        return $this->belongsToMany(Attribute::class, 'attribute_id');
    }

    public function attributeValueId()
    {
        return $this->belongsTo(AttributeValue::class, 'attribute_value_id');
    }
}
