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
        Schema::table('moderation_reports', function (Blueprint $table) {
            $table->foreign(['reporter_id'], 'fk_mod_reporter')->references(['id'])->on('users')->onUpdate('NO ACTION')->onDelete('CASCADE');
            $table->foreign(['target_post_id'], 'fk_mod_target_post')->references(['id'])->on('trade_posts')->onUpdate('NO ACTION')->onDelete('SET NULL');
            $table->foreign(['target_user_id'], 'fk_mod_target_user')->references(['id'])->on('users')->onUpdate('NO ACTION')->onDelete('SET NULL');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('moderation_reports', function (Blueprint $table) {
            $table->dropForeign('fk_mod_reporter');
            $table->dropForeign('fk_mod_target_post');
            $table->dropForeign('fk_mod_target_user');
        });
    }
};
