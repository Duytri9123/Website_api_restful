<?php

namespace App\Http\Requests;

use App\Enums\ProductStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreProductRequest extends FormRequest
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
        return [
            'name'          => 'required|string|max:255',
            'description'   => 'nullable|string',
            'short_description' => 'nullable|string|max:400',
            'brand_id'      => 'required|exists:brands,id',
            'category_id'   => 'required|exists:categories,id',
            'status'        => ['required', new Enum(ProductStatus::class)],

            'variants'                       => 'required|array|min:1',
            // Dấu * có nghĩa là áp dụng rule cho mọi phần tử trong mảng 'variants'
            'variants.*.sku'                 => 'nullable|string|max:255|unique:product_variants,sku',
            'variants.*.selling_price'       => 'required|numeric|min:0',
            'variants.*.original_price'      => 'nullable|numeric|min:0|gte:variants.*.selling_price',
            'variants.*.quantity'            => 'required|integer|min:0',
            'variants.*.is_default'          => 'required|boolean',
            // Validate mảng các giá trị thuộc tính để định nghĩa biến thể
            'variants.*.attribute_value_ids' => 'required|array|min:1',
            'variants.*.attribute_value_ids.*' => 'required|exists:attribute_values,id',

            'images' => [
                'nullable',
                'array',
                function ($attribute, $value, $fail) {
                    $videoCount = 0;
                    foreach ($value as $file) {
                        if (str_starts_with($file->getMimeType(), 'video')) {
                            $videoCount++;
                        }
                    }
                    if ($videoCount > 1) { // Giới hạn chỉ 1 video
                        $fail('Chỉ được phép upload tối đa 1 video cho mỗi sản phẩm.');
                    }
                }
            ],
            'images.*' => ['required', 'file', 'mimetypes:image/jpeg,image/png,video/mp4,video/webm', 'max:20480'],
            'variants.*.image_indexes'   => 'nullable|array',
            'variants.*.image_indexes.*' => 'integer',
            'option_ids'   => 'nullable|array',
            'option_ids.*' => 'required|exists:attribute_values,id',
        ];
    }
    public function messages(): array
    {
        return [
            'name.required' => 'Tên sản phẩm là bắt buộc.',
            'brand_id.required' => 'Vui lòng chọn thương hiệu.',
            'category_id.required' => 'Vui lòng chọn danh mục.',

            'variants.required' => 'Sản phẩm phải có ít nhất một attribute.',
            'variants.*.sku.required' => 'SKU của attribute là bắt buộc.',
            'variants.*.sku.unique' => 'SKU ":input" đã tồn tại.',
            'variants.*.selling_price.required' => 'Giá bán của attribute là bắt buộc.',
            'variants.*.quantity.required' => 'Số lượng của attribute là bắt buộc.',
            'variants.*.attribute_value_ids.required' => 'Mỗi attribute phải được định nghĩa bởi ít nhất một thuộc tính.',
        ];
    }
}
