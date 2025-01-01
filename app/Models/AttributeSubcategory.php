<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttributeSubcategory extends Model
{
    public function subcategoryId()
    {
        return $this->belongsTo(Subcategory::class, 'subcategory_id');
    }

    public function attributeId()
    {
        return $this->belongsTo(Attribute::class, 'attribute_id');
    }
}
