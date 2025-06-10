<?php

use App\Models\Brand;
use App\Models\Category;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->nullable();

            $table->longText('detailed_description')->nullable();
            $table->text('short_description')->nullable();

            $table->string('sku')->unique();//định danh sản phẩm

            $table->decimal('original_price', 10, 2);//giá gốc
            $table->decimal('selling_price', 10, 2);//giá khuyến mãi

            $table->integer('view_count')->default(0); //số lượng xem sản phẩm
            $table->integer('stock_quantity')->default(0);//hàng tồn kho

            $table->enum('status', ['active', 'inactive', 'out_of_stock', 'discontinued'])->default('active');//trang thai cua san pham

            $table->foreignIdFor(Brand::class,'brand_id')->nullable();
            $table->foreignIdFor(Category::class,'category_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
