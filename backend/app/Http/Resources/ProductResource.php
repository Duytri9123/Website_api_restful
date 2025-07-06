<?php

namespace App\Http\Resources;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $thumbnailUrl = null;
        if ($this->whenLoaded('thumbnailImage') && $this->thumbnailImage) {
            $thumbnailUrl = asset('storage/' . $this->thumbnailImage->url);
        }

        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'short_description' => $this->short_description,

            // Lấy giá trị chuỗi của Enum để trả về qua API
            'status' => $this->status->value,
            'views' => $this->views,
            'img_url' => $thumbnailUrl,

            'brand' => new BrandResource($this->whenLoaded('brand')),
            'category' => new CategoryResource($this->whenLoaded('category')),
            // Mối quan hệ danh sách (hasMany, belongsToMany)
            'images' => ProductImageResource::collection($this->whenLoaded('images')),
            'variants' => ProductVariantResource::collection($this->whenLoaded('variants')),

        ];
    }
}
