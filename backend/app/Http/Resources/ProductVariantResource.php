<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductVariantResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sku' => $this->sku,
            'selling_price' => $this->selling_price,
            'original_price' => $this->original_price,
            'quantity' => $this->quantity,
            'is_default' => $this->is_default,

            // Hiển thị danh sách các thuộc tính đã định nghĩa nên biến thể này
            'attributes' => AttributeValueResource::collection($this->whenLoaded('attributeValues')),
            'images' => ProductImageResource::collection($this->whenLoaded('images')),
        ];
    }
}
