<?php

namespace App\Helpers;

use App\Models\Category;

class CategoryHelper

{
    public static function getCategoryDepth($category): int
    {
        if (!$category) {
            return 0;
        }

        $depth = 0;
        $current = $category;

        while ($current && $current->parent_id) {
            $depth++;
            $current = Category::find($current->parent_id);

            // Tránh infinite loop
            if ($depth > 10) {
                break;
            }
        }

        return $depth;
    }
    //Kiểm tra xem categoryId có phải là descendant của parentId không
    public static function isDescendant($categoryId, $parentId): bool
    {
        if (!$categoryId || !$parentId || $categoryId == $parentId) {
            return false;
        }
        $parent = Category::find($parentId);
        $depth = 0;
        while ($parent && $parent->parent_id) {
            if ($parent->parent_id == $categoryId) {
                return true;
            }
            $parent = Category::find($parent->parent_id);

            // Tránh infinite loop
            if (++$depth > 10) {
                break;
            }
        }

        return false;
    }
}
