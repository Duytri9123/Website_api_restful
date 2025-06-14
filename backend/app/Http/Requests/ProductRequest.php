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
            // Bắt buộc
            'name' => 'required|string|max:255',
            'quantity' => 'required|integer|min:0',
            'original_price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',

            // Tùy chọn - sẽ có giá trị mặc định nếu không nhập
            'slug' => 'nullable|string|max:255|unique:products,slug,' . $productId,
            'description' => 'nullable|string',
            'short_description' => 'nullable|string|max:1000',
            'sku' => 'nullable|string|max:100|unique:products,sku,' . $productId,
            'weight' => 'nullable|numeric|min:0',
            'dimensions' => 'nullable|string|max:255',
            'status' => 'nullable|in:active,inactive,out_of_stock,discontinued',
            'brand_id' => 'nullable|exists:brands,id',
            'category_id' => 'nullable|exists:categories,id',

            // File upload
            'product_images' => 'nullable|array',
            'product_images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'deleted_images' => 'array',
            'deleted_images.*' => 'exists:product_images,id',

        ];
    }
    public function messages(): array
    {
        return [
            'name.required' => 'Tên sản phẩm là bắt buộc',
            'name.max' => 'Tên sản phẩm không được vượt quá 255 ký tự',
            'quantity.required' => 'Số lượng tồn kho là bắt buộc',
            'quantity.integer' => 'Số lượng phải là số nguyên',
            'quantity.min' => 'Số lượng phải lớn hơn hoặc bằng 0',
            'original_price.required' => 'Giá gốc là bắt buộc',
            'original_price.numeric' => 'Giá gốc phải là số',
            'original_price.min' => 'Giá gốc phải lớn hơn hoặc bằng 0',
            'selling_price.required' => 'Giá bán là bắt buộc',
            'selling_price.numeric' => 'Giá bán phải là số',
            'selling_price.min' => 'Giá bán phải lớn hơn hoặc bằng 0',
            'sku.unique' => 'Mã SKU đã tồn tại',
            'slug.unique' => 'Slug đã tồn tại',
            'weight.numeric' => 'Cân nặng phải là số',
            'weight.min' => 'Cân nặng phải lớn hơn hoặc bằng 0',
            'status.in' => 'Trạng thái không hợp lệ',
            'brand_id.exists' => 'Thương hiệu không tồn tại',
            'category_id.exists' => 'Danh mục không tồn tại',
            'short_description.max' => 'Mô tả ngắn không được vượt quá 1000 ký tự',
            'product_images.array' => 'Hình ảnh phải là một mảng',
            'product_images.*.image' => 'File phải là hình ảnh',
            'product_images.*.mimes' => 'Hình ảnh phải có định dạng: jpeg, png, jpg, gif',
            'product_images.*.max' => 'Kích thước hình ảnh không được vượt quá 2MB',
        ];
    }

    protected function prepareForValidation()
    {
        // Loại bỏ validation cho SKU nếu đang tạo mới và không có SKU
        if ($this->isMethod('post') && !$this->has('sku')) {
            // SKU sẽ được tự động tạo trong controller
        }
    }
}
