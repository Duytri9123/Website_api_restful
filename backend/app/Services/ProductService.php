<?php

namespace App\Services;

use App\Models\Product;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProductService
{
    public function __construct(
        protected VariantService $variantService,
        protected ImageService $imageService
    ) {}


    public function list(Request $request)
    {
        $query = Product::query()->with([
            'brand',
            'category',
            'thumbnailImage',
            'defaultVariant' => function ($q) {
                $q->with('attributeValues.productAttribute'); // Tải thuộc tính cho biến thể mặc định
            },
            'attributeValues.productAttribute' // Nếu có thuộc tính cấp sản phẩm
        ]);

        // Lọc theo category_id
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }
        // Lọc theo từ khóa tìm kiếm
        if ($request->filled('search')) {
            $searchTerm = '%' . $request->input('search') . '%';
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', $searchTerm)
                    ->orWhere('description', 'like', $searchTerm); // Có thể tìm kiếm cả trong mô tả
            });
        }
        // Thêm các bộ lọc khác nếu cần (ví dụ: theo brand_id, status, giá...)
        if ($request->filled('brand_id')) {
            $query->where('brand_id', $request->input('brand_id'));
        }
        if ($request->filled('status')) {
            // Đảm bảo $request->input('status') là một giá trị hợp lệ của ProductStatus Enum
            $query->where('status', $request->input('status'));
        }

        // Sắp xếp
        if ($request->filled('sort_by') && $request->filled('sort_order')) {
            $sortBy = $request->input('sort_by');
            $sortOrder = $request->input('sort_order');
            // Chỉ cho phép các cột sắp xếp hợp lệ để tránh SQL Injection
            $allowedSorts = ['name', 'created_at', 'price']; // price cần join với defaultVariant
            if (in_array($sortBy, $allowedSorts)) {
                $query->orderBy($sortBy, $sortOrder);
            }
        } else {
            $query->latest(); // Mặc định sắp xếp theo mới nhất
        }


        return $query->paginate(12)->withQueryString();
    }

    public function create(array $validatedData, Request $request): Product
    {
        try {
            return DB::transaction(function () use ($validatedData, $request) {

                $product = Product::create(Arr::except($validatedData, ['variants', 'images', 'option_ids']));

                if (isset($validatedData['option_ids'])) {
                    $product->attributeValues()->sync($validatedData['option_ids']);
                }

                if (!empty($validatedData['variants'])) {
                    $this->variantService->sync($product, $validatedData['variants']);
                }

                $product->load('variants.attributeValues');

                if ($request->hasFile('images')) {
                    $this->assignImages($product, $request->file('images'), $request->input('variants', []));
                }

                return $product;
            });
        } catch (Exception $e) {
            Log::error('Lỗi khi tạo sản phẩm', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function update(Product $product, array $validatedData, Request $request): Product
    {
        try {
            return DB::transaction(function () use ($product, $validatedData, $request) {


                $product->update(Arr::except($validatedData, ['variants', 'new_images', 'deleted_images', 'option_ids']));


                if ($request->has('option_ids')) {
                    $product->attributeValues()->sync($validatedData['option_ids'] ?? []);
                }
                if ($request->filled('deleted_images')) {
                    $this->imageService->deleteManyByIds($request->input('deleted_images'));
                }

                if ($request->has('variants')) {
                    $this->variantService->sync($product, $validatedData['variants']);
                }

                if ($request->hasFile('new_images')) {

                    $product->load('variants.attributeValues');

                    $this->assignImages($product, $request->file('new_images'), $request->input('variants', []));
                }

                return $product;
            });
        } catch (Exception $e) {
            Log::error('Lỗi khi cập nhật sản phẩm', ['product_id' => $product->id, 'error' => $e->getMessage()]);
            throw $e;
        }
    }


    public function delete(Product $product): void
    {
        try {
            DB::transaction(function () use ($product) {

                $this->imageService->deleteAllForProduct($product);

                $product->delete();
            });
        } catch (Exception $e) {
            Log::error('Lỗi khi xóa sản phẩm', ['product_id' => $product->id, 'error' => $e->getMessage()]);
            throw $e;
        }
    }
    private function assignImages(Product $product, array $uploadedFiles, array $variantsData): void
    {
        $assignedImageIndexes = [];

        $attributeMap = $product->variants
            ->mapWithKeys(function ($variant) {
                $key = implode('-', collect($variant->attributeValues->pluck('id'))->sort()->values()->all());
                return [$key => $variant];
            });

        foreach ($variantsData as $variantData) {
            if (!empty($variantData['image_indexes'])) {
                $attrIds = collect($variantData['attribute_value_ids'] ?? [])->sort()->values()->all();
                $key = implode('-', $attrIds);

                $variantModel = $attributeMap[$key] ?? null;

                if ($variantModel) {
                    foreach ($variantData['image_indexes'] as $imageIndex) {
                        if (isset($uploadedFiles[$imageIndex])) {
                            $this->imageService->store($uploadedFiles[$imageIndex], $product, $variantModel);
                            $assignedImageIndexes[] = $imageIndex;
                        }
                    }
                }
            }
        }

        foreach ($uploadedFiles as $index => $file) {
            if (!in_array($index, $assignedImageIndexes)) {
                $this->imageService->store($file, $product);
            }
        }
    }
}
