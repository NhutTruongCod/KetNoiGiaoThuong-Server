<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('page_views', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('company_id')->index();
            $t->unsignedBigInteger('user_id')->nullable()->index();
            $t->string('session_id', 64)->nullable()->index();   // sid từ FE (localStorage)
            $t->string('path', 255);
            $t->string('referrer', 255)->nullable();
            $t->string('user_agent', 255)->nullable();
            $t->string('request_id', 64)->nullable();
            $t->string('correlation_id', 64)->nullable();
            $t->unsignedInteger('duration_ms')->nullable();      // thời gian trên trang
            $t->timestamps();

            // ✅ Index gộp cho lọc theo công ty + khoảng thời gian
            $t->index(['company_id', 'created_at'], 'pv_company_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_views');
    }
};
