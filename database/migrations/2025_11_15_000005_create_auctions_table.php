<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auctions', function (Blueprint $table) {
            $table->id();

            // liên kết tới listings
            $table->foreignId('listing_id')
                ->constrained()
                ->cascadeOnDelete();

            // cho phép null, tránh lỗi default timestamp
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();

            $table->unsignedBigInteger('starting_price_cents');
            $table->unsignedBigInteger('current_price_cents')->default(0);

            // active | paused | closed
            $table->string('status', 20)->default('active');

            // người tạo auction
            $table->foreignId('created_by')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auctions');
    }
};