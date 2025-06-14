<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BrandsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Brand::all();
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
    public function store(Request $request)
    {
        $data = $request->validate(
            [
                'name' => 'required|string|max:255',
                'slug' => 'string|max:255',

            ]
        );

        if ($request->hasFile('img_url')) {
            $path = $request->file('img_url')->store('brand_image', 'public');
            $data['img_url'] = Storage::url($path);
        }

        $brand = Brand::create($data);

        return response()->json(
            [
                'success' => true,
                'message' => 'Tạo Brand Thành Công',
                'data' => $brand
            ],
            201
        );
    }

    /**
     * Display the specified resource.
     */
    public function show(Brand $brand)
    {
        return $brand;
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
    public function update(Request $request, string $id)
    {
        $brand = Brand::find($id);
        if (!$brand) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy brand',
            ], 404);
        }
        $data = $request->validate(
            [
                'name' => 'required|string|max:255',
                'slug' => 'string|max:255',
            ]
        );
        if ($request->hasFile('img_url')) {
            //xóa ảnh
            if ($brand->img_url) {
                $oldImage = str_replace('/storage/', '', $brand->img_url);
                Storage::disk('public')->delete($oldImage);
            }
            //cập nhật ảnh
            $imgPath = $request->file('img_url')->store('brand_image', 'public');
            $data['img_url'] = Storage::url($imgPath);
        }

        $brand->update($data);
        return response()->json(
            [
                'success' => true,
                'message' => 'Cập nhật danh mục thành công',
                'data' => $brand
            ]
        );
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {

            $brand = Brand::findOrFail($id);
            if (!$brand) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy thương hiệu'
                ], 404);
            }
            if ($brand->products()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không thể xóa thương hiệu đã có sản phẩm'
                ], 400);
            }


            // Xóa logo
            if ($brand->img_url) {
                $relativePath = str_replace('/storage/', '', $brand->img_url);
                Storage::disk('public')->delete($relativePath);
            }
            $brand->delete();

            return response()->json(
                [

                    'success' => true,
                    'message' => 'Xóa Brands thành công',
                ]
            );
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi xóa thương hiệu',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
