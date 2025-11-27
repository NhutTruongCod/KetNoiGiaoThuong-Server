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
        Schema::table('orders', function (Blueprint $table) {
            $table->foreign(['buyer_id'], 'fk_orders_buyer')->references(['id'])->on('users')->onUpdate('NO ACTION');
            $table->foreign(['shop_id'], 'fk_orders_shop')->references(['id'])->on('shops')->onUpdate('NO ACTION');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign('fk_orders_buyer');
            $table->dropForeign('fk_orders_shop');
        });
    }
};
