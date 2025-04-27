<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;
use Laravel\Scout\Attributes\SearchUsingFullText;

class Product extends Model
{
    use Searchable;

    protected $table = 'products';

    protected $fillable = [
        'subcategory_id', 'name', 'slug', 'description', 'price', 'currency', 'type', 'region_id', 'user_id', 'latitude', 'longitude'
    ];

    public function subcategory() {
        return $this->belongsTo(Subcategory::class);
    }

    public function attributeValues() {
        return $this->belongsToMany(
            AttributeValue::class,
            'product_attribute_values',
            'product_id', 
            'attribute_value_id' 
        );
    }

    public function images() {
        return $this->hasMany(ProductImage::class, 'product_id', 'id');
    }

    public function attributes()
    {
        return $this->belongsToMany(Attribute::class, 'product_attribute_values', 'product_id', 'attribute_value_id');
    }

    public function region() {
        return $this->belongsTo(Region::class, 'region_id');
    }

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function favoritedBy() {
        return $this->belongsToMany(User::class, 'favorites')->withTimestamps();
    }

    #[SearchUsingFullText(['name', 'description', 'slug'])]
    public function toSearchableArray(): array
    {
        return [
            'name' => $this->name,
            'description' => $this->description,
            'slug' => $this->slug,
        ];
    }
}