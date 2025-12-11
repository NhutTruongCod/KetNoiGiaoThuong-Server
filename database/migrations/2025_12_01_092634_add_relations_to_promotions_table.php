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
        Schema::table('promotions', function (Blueprint $table) {
            $table->unsignedBigInteger('shop_id')->nullable()->after('id');
            $table->unsignedBigInteger('listing_id')->nullable()->after('shop_id');

            $table->index('shop_id', 'promo_shop_idx');
            $table->index('listing_id', 'promo_listing_idx');

            $table->foreign('shop_id')
                ->references('id')->on('shops')
                ->onDelete('cascade');

            $table->foreign('listing_id')
                ->references('id')->on('listings')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('promotions', function (Blueprint $table) {
            $table->dropForeign(['shop_id']);
            $table->dropForeign(['listing_id']);
            $table->dropColumn(['shop_id', 'listing_id']);
        });
    }
};
