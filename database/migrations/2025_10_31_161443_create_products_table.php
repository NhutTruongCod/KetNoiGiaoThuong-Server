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
        Schema::create('products', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('shop_id')->index('idx_products_shop');
            $table->unsignedBigInteger('category_id')->nullable()->index('idx_products_category');
            $table->string('title', 191);
            $table->text('description')->nullable();
            $table->decimal('price', 12)->default(0);
            $table->unsignedInteger('stock_qty')->default(0);
            $table->enum('status', ['draft', 'active', 'inactive'])->default('active');
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('products');
    }
};
