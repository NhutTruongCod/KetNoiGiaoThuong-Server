<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name', 191);
            $table->string('slug', 191)->unique();
            
            // Giữ lại cái này từ file cũ để làm danh mục cha/con
            $table->unsignedBigInteger('parent_id')->nullable()->index('fk_categories_parent');
            
            // Thêm mấy cái hay ho từ file mới
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);

            // Dùng timestamps() để có cả created_at và updated_at
            $table->timestamps(); 
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('categories');
    }
};
