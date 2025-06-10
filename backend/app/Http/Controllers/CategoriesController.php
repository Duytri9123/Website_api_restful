<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Helpers\CategoryHelper;

class CategoriesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Category::all();
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
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:categories,id',
        ]);
        //upload ảnh nếu có
        if ($request->hasFile('img_url')) {
            $path = $request->file('img_url')->store('category_image', 'public');
            $data['img_url'] = Storage::url($path);
        }
        //kiểm tra có parent không
        if (isset($data['parent_id'])) {
            $parentCategory = Category::find($data['parent_id']);
            if (!$parentCategory) {
                return response()->json(
                    [
                        'success' => false,
                        'message' => 'danh mục parent không tồn tại'
                    ],
                    400
                );
            }
            $depth = CategoryHelper::getCategoryDepth($parentCategory);
            if ($depth >= 2) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không thể tạo danh mục con quá 3 cấp'
                ], 400);
            }
        }
        $category = Category::create($data);
        $category->load('parent:id,name');
        return response()->json([
            'success' => true,
            'message' => 'Tạo danh mục thành công',
            'data' => $category
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Category $category)
    {
        return $category;
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

        $category = Category::find($id);
        if (!$category) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'không tìm thấy danh mục',
                ],
                404
            );
        }
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:categories,id',

        ]);

        if ($request->hasFile('img_url')) {
            //xóa image cũ
            if ($category->img_url) {
                $oldImagePath = str_replace('/storage/', '', $category->img_url);
                Storage::disk('public')->delete($oldImagePath);
            }
            $imgPath = $request->file('img_url')->store('category_image', 'public');
            $data['img_url'] = Storage::url($imgPath);
        }

        // kiểm tra parent nếu có thay đổi
        if (isset($data['parent_id']) && $data['parent_id'] != $category->parent_id) {
            // Không thể set parent là chính nó hoặc con cháu của nó

            if ($data['parent_id'] == $id) {
                return response()->json(
                    [
                        'success' => false,
                        'message' => 'không thể set danh mục làm cha chính nó'
                    ],
                    400
                );
            }
            //kiểm tra xem parent có phải là con cháu của category hiện tại không
            if (CategoryHelper::isDescendant($id, $data['parent_id'])) {
                return response()->json(
                    [
                        'success' => false,
                        'message' => 'không thể set danh mục con làm danh mục cha'
                    ],
                    400

                );
            }
            //kiểm tra danh mục tạo quá cấp 3 không
            if ($data['parent_id']) {
                $parentCategory = Category::find($data['parent_id']);
                if ($parentCategory) {
                    $depth = CategoryHelper::getCategoryDepth($parentCategory);
                    if ($depth > 2) {
                        return response()->json(
                            [
                                'success' => false,
                                'message' => 'không thể tạo danh mục con quá cấp 3'
                            ],
                            400
                        );
                    }
                }
            }
        }
        $category->update($data);
        $category->load('parent:id,name');

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật danh mục thành công',
            'data' => $category
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $category = Category::find($id);
        if (!$category) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'không tồn tại danh mục',
                ],
                404
            );
        }
        // kiểm tra có danh mục con hay không
        if ($category->children()->exists()) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Không thể xóa danh mục có danh mục con'
                ],
                400
            );
        }
        //xóa ảnh khỏi thư mục
        if ($category->img_url) {
            $oldImage = str_replace('/storage/', '', $category->img_url);
            Storage::disk('public')->delete($oldImage);
        }
        //xóa category
        $category->delete();
        return response()->json([
            'success' => true,
            'message' => 'Xóa danh mục thành công'
        ]);
    }
}
