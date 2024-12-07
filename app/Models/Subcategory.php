<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subcategory extends Model
{
    protected $table = 'subcategories';

    protected $fillable = [
        'name', 'category_id'
    ];

    public function category() {
        return $this->belongsTo(Category::class);
    }

    public function products() {
        return $this->hasMany(Product::class);
    }

    public function attributes()
    {
        return $this->belongsToMany(
            Attribute::class,
            'attribute_subcategories',
                'subcategory_id',
            'attribute_id'
        );
    }
}
