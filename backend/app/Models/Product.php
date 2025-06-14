<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Product extends Model
{
    use HasSlug;
    protected $fillable = [
        'name',
        'slug',
        'description',
        'short_description',
        'sku',
        'original_price',
        'selling_price',
        'view_count',
        'quantity',
        'status',
        'weight',
        'dimensions',
        'brand_id',
        'category_id',
        'created_by',
        'updated_by',
        'deleted_by',
    ];
    protected $casts = [
        'original_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'weight' => 'decimal:2',
        'view_count' => 'integer',
        'quantity' => 'integer',
    ];

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('name')
            ->saveSlugsTo('slug');
    }
    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function product_images()
    {
        return $this->hasMany(ProductImage::class);
    }
}
