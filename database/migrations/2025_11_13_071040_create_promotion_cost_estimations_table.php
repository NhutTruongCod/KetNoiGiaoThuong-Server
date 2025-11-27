<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promotion_cost_estimations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->onDelete('cascade');
            $table->foreignId('listing_id')->nullable()->constrained()->onDelete('cascade');
            $table->enum('promotion_type', ['banner', 'video_ads', 'social_media', 'search_ads', 'email_marketing', 'in_app_ads']);
            $table->integer('duration_days');
            $table->decimal('budget', 12, 2); // Ngân sách dự kiến
            $table->decimal('estimated_cost', 12, 2); // Chi phí ước tính thực tế
            $table->string('currency', 3)->default('VND');
            $table->enum('status', ['pending', 'approved', 'rejected', 'completed', 'cancelled'])->default('pending');
            $table->string('calculation_method')->default('AI-based model');
            $table->text('calculation_details')->nullable(); // Chi tiết tính toán
            $table->decimal('estimated_impressions', 12, 0)->nullable(); // Lượt hiển thị ước tính
            $table->decimal('estimated_clicks', 12, 0)->nullable(); // Lượt click ước tính
            $table->decimal('estimated_conversions', 12, 0)->nullable(); // Chuyển đổi ước tính
            $table->text('notes')->nullable();
            $table->timestamps();
            
            // Indexes for performance
            $table->index('store_id');
            $table->index('listing_id');
            $table->index('promotion_type');
            $table->index('status');
            $table->index('created_at');
            $table->index(['store_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promotion_cost_estimations');
    }
};