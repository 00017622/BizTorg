<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttributeSubcategory extends Model
{
    /**
     * Relationship to Subcategory.
     */
    public function subcategoryId()
    {
        return $this->belongsTo(Subcategory::class, 'subcategory_id');
    }

    /**
     * Relationship to Attribute.
     */
    public function attributeId()
    {
        return $this->belongsTo(Attribute::class, 'attribute_id');
    }
}
