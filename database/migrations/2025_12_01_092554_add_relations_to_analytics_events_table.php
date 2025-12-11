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
        Schema::table('analytics_events', function (Blueprint $table) {
            // Gắn công ty & listing
            $table->unsignedBigInteger('shop_id')->nullable()->after('user_id');
            $table->unsignedBigInteger('listing_id')->nullable()->after('shop_id');

            $table->index('shop_id', 'ae_shop_idx');
            $table->index('listing_id', 'ae_listing_idx');

            // Gắn chặt thêm FK cho cột đã có
            $table->foreign('user_id')
                ->references('id')->on('users')
                ->onDelete('set null');

            $table->foreign('order_id')
                ->references('id')->on('orders')
                ->onDelete('set null');

            // ad_campaign_id chính là promotion/campaign
            $table->foreign('ad_campaign_id')
                ->references('id')->on('promotions')
                ->onDelete('set null');

            $table->foreign('shop_id')
                ->references('id')->on('shops')
                ->onDelete('set null');

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
        Schema::table('analytics_events', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['order_id']);
            $table->dropForeign(['ad_campaign_id']);
            $table->dropForeign(['shop_id']);
            $table->dropForeign(['listing_id']);

            $table->dropColumn(['shop_id', 'listing_id']);
        });
    }
};
