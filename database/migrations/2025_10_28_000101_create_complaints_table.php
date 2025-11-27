<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('complaints', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('complainant_user_id');
            $table->string('target_type', 32); // user, trade_post, product, order, review, shop
            $table->unsignedBigInteger('target_id');
            $table->string('type', 32)->default('other'); // fraud, spam, abuse, ip, other
            $table->string('status', 32)->default('open'); // open, under_review, resolved, rejected
            $table->text('reason');
            $table->text('resolution')->nullable();
            $table->unsignedBigInteger('assigned_admin_id')->nullable();
            $table->unsignedBigInteger('resolved_by_admin_id')->nullable();
            $table->timestamps();

            $table->index(['target_type', 'target_id']);
            $table->index(['status', 'created_at']);
            $table->index(['complainant_user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('complaints');
    }
};

