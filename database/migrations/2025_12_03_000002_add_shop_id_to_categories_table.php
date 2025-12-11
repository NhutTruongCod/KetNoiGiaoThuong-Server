<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Bước 1: Xóa tất cả categories cũ (nếu có)
        DB::table('categories')->truncate();
        
        Schema::table('categories', function (Blueprint $table) {
            // Bước 2: Thêm shop_id - Categories thuộc shop cụ thể
            $table->unsignedBigInteger('shop_id')->after('id');
            $table->foreign('shop_id')->references('id')->on('shops')->onDelete('cascade');
            
            // Bước 3: Drop unique constraint cũ trên slug
            $table->dropUnique(['slug']);
            
            // Bước 4: Thêm unique constraint mới: slug unique trong cùng shop
            $table->unique(['shop_id', 'slug'], 'unique_category_per_shop');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('categories', function (Blueprint $table) {
            // Drop unique constraint
            $table->dropUnique('unique_category_per_shop');
            
            // Drop foreign key và column
            $table->dropForeign(['shop_id']);
            $table->dropColumn('shop_id');
            
            // Restore unique constraint cũ
            $table->unique('slug');
        });
    }
};
