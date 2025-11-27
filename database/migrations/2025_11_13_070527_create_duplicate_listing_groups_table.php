<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('duplicate_listing_groups', function (Blueprint $table) {
            $table->id();
            $table->string('detected_by'); // AI, manual, system
            $table->text('note')->nullable();
            $table->enum('status', ['pending', 'resolved', 'ignored'])->default('pending');
            $table->integer('confidence_score')->nullable(); // Độ tin cậy phát hiện (0-100)
            $table->json('duplicate_items'); // Lưu danh sách listing_ids
            $table->timestamps();
            
            // Indexes for performance
            $table->index('status');
            $table->index('detected_by');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('duplicate_listing_groups');
    }
};