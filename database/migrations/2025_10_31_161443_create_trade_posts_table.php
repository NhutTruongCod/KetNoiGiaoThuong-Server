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
        Schema::create('trade_posts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('author_id')->index('idx_trade_posts_author');
            $table->unsignedBigInteger('category_id')->nullable()->index('fk_trade_posts_category');
            $table->string('title', 191);
            $table->text('body')->nullable();
            $table->enum('type', ['sell', 'buy', 'service'])->default('sell');
            $table->decimal('price', 12)->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected', 'archived'])->default('pending')->index('idx_trade_posts_status');
            $table->dateTime('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('trade_posts');
    }
};
