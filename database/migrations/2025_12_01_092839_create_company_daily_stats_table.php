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
        Schema::create('company_daily_stats', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->unsignedBigInteger('shop_id');
            $t->date('stat_date');

            // Lượt truy cập / hành vi
            $t->unsignedBigInteger('page_views')->default(0);
            $t->unsignedBigInteger('unique_visitors')->default(0);
            $t->unsignedBigInteger('listing_views')->default(0);

            // Đăng tin
            $t->unsignedBigInteger('new_listings')->default(0);

            // Giao dịch
            $t->unsignedBigInteger('orders_count')->default(0);
            $t->decimal('orders_revenue', 14, 2)->default(0);

            // Quảng cáo
            $t->unsignedBigInteger('ad_impressions')->default(0);
            $t->unsignedBigInteger('ad_clicks')->default(0);
            $t->unsignedBigInteger('ad_conversions')->default(0);
            $t->decimal('ad_spent', 14, 2)->default(0);

            // Gói thành viên
            $t->unsignedBigInteger('new_subscriptions')->default(0);
            $t->decimal('subscription_revenue', 14, 2)->default(0);

            $t->timestamps();

            $t->unique(['shop_id', 'stat_date'], 'uniq_shop_date');
            $t->index(['stat_date'], 'idx_stat_date');
        });

        Schema::table('company_daily_stats', function (Blueprint $t) {
            $t->foreign('shop_id')
              ->references('id')->on('shops')
              ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('company_daily_stats');
    }
};
