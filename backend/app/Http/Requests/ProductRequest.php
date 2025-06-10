<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
         $productId = $this->route('product');
        return [
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'detailed_description' => 'nullable|string',
            'short_description' => 'nullable|string',
            'sku' => 'required|string|max:100|unique:products,sku,' . $this->product,
            'original_price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'view_count' => 'nullable|integer|min:0',
            'stock_quantity' => 'nullable|integer|min:0',
            'status' => 'required|in:active,inactive,out_of_stock,discontinued',
            'brand_id' => 'nullable|exists:brands,id',
            'category_id' => 'nullable|exists:categories,id',
        ];
    }
}
