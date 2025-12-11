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
        Schema::table('page_views', function (Blueprint $t) {
            // company_id -> shops.id
            $t->foreign('company_id')
              ->references('id')->on('shops')
              ->onDelete('cascade');

            // tuỳ chọn: gắn thêm context
            $t->unsignedBigInteger('listing_id')->nullable()->after('company_id');
            $t->unsignedBigInteger('promotion_id')->nullable()->after('listing_id');

            $t->index('listing_id', 'pv_listing_idx');
            $t->index('promotion_id', 'pv_promotion_idx');

            $t->foreign('listing_id')
              ->references('id')->on('listings')
              ->onDelete('set null');

            $t->foreign('promotion_id')
              ->references('id')->on('promotions')
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
        Schema::table('page_views', function (Blueprint $t) {
            $t->dropForeign(['company_id']);
            $t->dropForeign(['listing_id']);
            $t->dropForeign(['promotion_id']);
            $t->dropColumn(['listing_id', 'promotion_id']);
        });
    }
};
