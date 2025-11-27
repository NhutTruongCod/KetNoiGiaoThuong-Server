<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $t) {
            $t->id();
            $t->string('order_id', 64)->unique();
            $t->unsignedBigInteger('company_id')->index();   // công ty bán
            $t->unsignedBigInteger('user_id')->nullable()->index();
            $t->integer('amount_cents')->default(0);
            $t->enum('status', ['created','paid','shipped','completed'])
              ->default('created')->index();
            $t->string('request_id', 64)->nullable();
            $t->string('correlation_id', 64)->nullable();
            $t->timestamps();

            // ✅ Index gộp tối ưu lọc theo công ty + trạng thái + thời gian
            $t->index(['company_id', 'status', 'created_at'], 'tx_company_status_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
