<?php

namespace App\Http\Requests;

use App\Enums\ProductStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class UpdateProductRequest extends FormRequest
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

        $productId = $this->route('product')->id;

        $rules = [

            'name'          => ['sometimes', 'required', 'string', 'max:255'],
            'description'   => 'nullable|string',
            'short_description' => 'nullable|string|max:400',
            'brand_id'      => 'sometimes|required|exists:brands,id',
            'category_id'   => 'sometimes|required|exists:categories,id',
            'status'        => ['sometimes', 'required', new Enum(ProductStatus::class)],


            'variants'      => 'sometimes|array',

            'new_images'       => 'nullable|array',
            'new_images.*'     => ['required', 'file', 'mimetypes:image/jpeg,image/png,video/mp4,video/quicktime', 'max:20480'],
            'deleted_images'   => 'nullable|array',
            'deleted_images.*' => 'integer|exists:product_images,id,product_id,' . $productId,
            'option_ids'   => 'sometimes|array',
            'option_ids.*' => 'required|exists:attribute_values,id',
        ];

        foreach ($this->input('variants', []) as $index => $variantData) {
            $variantId = $variantData['id'] ?? null;

            $rules["variants.{$index}.id"]               = 'nullable|exists:product_variants,id';
            $rules["variants.{$index}.sku"]              = ['nullable', 'string', 'max:255', Rule::unique('product_variants', 'sku')->ignore($variantId)];
            $rules["variants.{$index}.selling_price"]    = 'required|numeric|min:0';
            $rules["variants.{$index}.original_price"]   = 'nullable|numeric|min:0|gte:variants.' . $index . '.selling_price';
            $rules["variants.{$index}.quantity"]         = 'required|integer|min:0';
            $rules["variants.{$index}.is_default"]       = 'required|boolean';
            $rules["variants.{$index}.attribute_value_ids"] = 'required|array|min:1';
            $rules["variants.{$index}.attribute_value_ids.*"] = 'required|exists:attribute_values,id';
            $rules["variants.{$index}.image_indexes"]    = 'nullable|array';
            $rules["variants.{$index}.image_indexes.*"]  = 'integer';
        }

        return $rules;
    }
}
