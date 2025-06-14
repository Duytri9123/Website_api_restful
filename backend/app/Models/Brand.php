<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Brand extends Model
{
    use HasSlug;
    protected $fillable = ['name','slug','img_url'];

    public function getSlugOptions():SlugOptions
    {
        return SlugOptions::create()
        ->generateSlugsFrom('name')
        ->saveSlugsTo('slug');
    }
    public function products()
    {
        return $this ->hasMany(Product::class);
    }
}
