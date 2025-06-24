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
        $query = Product::query()->with(['brand:id,name,slug', 'category:id,name,slug', 'thumbnailImage', 'defaultVariant']);

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->input('search') . '%');
        }

        return $query->latest()->paginate(12)->withQueryString();
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

        foreach ($variantsData as $i => $variantData) {

            if (!empty($variantData['image_indexes'])) {

                $variantModel = $product->variants->firstWhere('id', $variantData['id'] ?? null);

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
            if ($file && !in_array($index, $assignedImageIndexes)) {
                $this->imageService->store($file, $product);
            }
        }
    }
}
