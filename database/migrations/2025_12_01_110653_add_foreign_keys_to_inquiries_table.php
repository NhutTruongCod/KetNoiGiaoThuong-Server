<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inquiries', function (Blueprint $table) {
            $table->foreign('listing_id', 'inq_listing_fk')
                  ->references('id')->on('listings')
                  ->onDelete('cascade'); // xóa listing thì xóa luôn inquiry
        });
    }

    public function down(): void
    {
        Schema::table('inquiries', function (Blueprint $table) {
            $table->dropForeign('inq_listing_fk');
        });
    }
};
