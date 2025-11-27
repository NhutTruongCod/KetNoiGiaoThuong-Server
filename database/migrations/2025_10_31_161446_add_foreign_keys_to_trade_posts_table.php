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
        Schema::table('trade_posts', function (Blueprint $table) {
            $table->foreign(['author_id'], 'fk_trade_posts_author')->references(['id'])->on('users')->onUpdate('NO ACTION')->onDelete('CASCADE');
            $table->foreign(['category_id'], 'fk_trade_posts_category')->references(['id'])->on('categories')->onUpdate('NO ACTION')->onDelete('SET NULL');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('trade_posts', function (Blueprint $table) {
            $table->dropForeign('fk_trade_posts_author');
            $table->dropForeign('fk_trade_posts_category');
        });
    }
};
