<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductRequest;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Helpers\ProductHelper;
use App\Models\ProductImage;
use GuzzleHttp\Handler\Proxy;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ProductsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $products = Product::with(['category', 'brand', 'product_images'])->get();

        return response()->json(
            $products
        );
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ProductRequest $request)
    {
        DB::beginTransaction();
        try {
            $data = $request->validated();
            if (!isset($data['sku'])) {
                $data['sku'] = ProductHelper::generateSKU($data['name']);
            }
            $product = Product::create($data);
//thêm ảnh mới
            if ($request->hasFile('product_images')) {
                $productImages = $request->file('product_images');

                ProductHelper::handleProductImages($product, $productImages);
            }

            $product->load(['category', 'brand', 'product_images']);
            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Tạo sản phẩm thành công',
                'data' => $product
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi tạo sản phẩm',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    /**
     * Display the specified resource.
     */
    public function show(Product $product)
    {
        $product->load(['category', 'brand', 'product_images']);

        return response()->json([
            'success' => true,
            'data' => $product
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ProductRequest $request, string $id)
    {
        DB::beginTransaction();
        try {
            $product = Product::findOrFail($id);
            $data = $request->validated();

            $product->update($data);
            //nếu có ảnh mới,thêm ảnh mới vào
            if ($request->hasFile('product_images')) {
                $productImages = $request->file('product_images');
                ProductHelper::handleProductImages($product, $productImages);
            }
            $product->load(['category', 'brand', 'product_images']);
            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Cập nhật sản phẩm thành công',
                'data' => $product
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(
                [
                    'success' => false,
                    'message' => 'có lỗi không cập nhật được sản phẩm',
                    'error' => $e->getMessage(),
                ],
                500
            );
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        DB::beginTransaction();
        try {
            $product = Product::with('product_images')->findOrFail($id);
            ProductHelper::deleteProductImages($product);
            $product->delete();
            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Xóa sản phẩm thành công'
            ]);
        } catch (\Exception $e) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Có lỗi xảy ra khi xóa sản phẩm',
                    'error' => $e->getMessage()
                ],
                500
            );
        }
    }
}
