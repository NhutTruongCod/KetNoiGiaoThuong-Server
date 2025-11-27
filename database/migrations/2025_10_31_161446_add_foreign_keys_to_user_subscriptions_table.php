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
        Schema::table('user_subscriptions', function (Blueprint $table) {
            $table->foreign(['plan_id'], 'fk_user_sub_plan')->references(['id'])->on('subscription_plans')->onUpdate('NO ACTION')->onDelete('CASCADE');
            $table->foreign(['user_id'], 'fk_user_sub_user')->references(['id'])->on('users')->onUpdate('NO ACTION')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user_subscriptions', function (Blueprint $table) {
            $table->dropForeign('fk_user_sub_plan');
            $table->dropForeign('fk_user_sub_user');
        });
    }
};
