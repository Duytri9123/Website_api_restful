<?php

namespace App\Helpers;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductHelper
{
    public static function generateSKU($name)
    {
        $prefix = strtoupper(substr(Str::slug($name, ''), 0, 3));
        $timestamp = substr(time(), -6);
        do {
            $random = strtoupper(Str::random(3));
            $sku = $prefix . $timestamp . $random;
        } while (Product::where('sku', $sku)->exists());
        return $sku;
    }
    public static function handleProductImages($product, $productImages)
    {
        if (!is_array($productImages) && !is_iterable($productImages)) {
            return;
        }
        $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $maxFileSize = 5 * 1024 * 1024;

        foreach ($productImages as $image) {
            if (!$image instanceof UploadedFile || !$image->isValid()) {
                continue;
            }
            $extension = strtolower($image->getClientOriginalExtension());
            if (!in_array($extension, $allowedTypes)) {
                continue;
            }
            if ($image->getSize() > $maxFileSize) {
                continue;
            }
            $uniqueName = microtime(true). '-' . Str::random(10) . '.' . $image->getClientOriginalExtension();
            $path = $image->storeAs('product_images', $uniqueName, 'public');
            if ($path) {
                ProductImage::create([
                    'product_id' => $product->id,
                    'image' => $path,
                ]);
            }
        }
    }
    public static function removeProductImages($id)
    {
        // Validate input
        if (empty($id)) {
            return;
        }

        if (!is_array($id)) {
            $id = [$id];
        }

        $images = ProductImage::whereIn('id', $id)->get();

        foreach ($images as $image) {
            $imagePath = $image->image;
            if (Storage::disk('public')->exists($imagePath)) {
                Storage::disk('public')->delete($imagePath);
            }
            $image->delete();
        }
    }
    public static function deleteProductImages($product)
    {
        $images = $product->product_images;
        foreach ($images as $image) {
            $imagePath = $image->image;
            if (Storage::disk('public')->exists($imagePath)) {
                Storage::disk('public')->delete($imagePath);
            }
        }
        $product->product_images()->delete();
    }
}
