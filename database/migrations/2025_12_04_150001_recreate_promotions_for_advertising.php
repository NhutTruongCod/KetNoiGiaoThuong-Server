<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Disable foreign key checks
        Schema::disableForeignKeyConstraints();
        
        // Drop old promotions table
        Schema::dropIfExists('promotions');
        
        // Create new promotions table for advertising
        Schema::create('promotions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->nullable()->constrained('shops')->onDelete('cascade');
            $table->foreignId('listing_id')->constrained('listings')->onDelete('cascade');
            $table->enum('type', ['featured', 'top_search', 'homepage_banner', 'category_banner']);
            $table->integer('duration_days');
            $table->decimal('budget', 12, 2);
            $table->decimal('spent', 12, 2)->default(0);
            $table->decimal('daily_budget', 12, 2)->nullable();
            
            // Performance metrics
            $table->bigInteger('impressions')->default(0);
            $table->integer('clicks')->default(0);
            $table->decimal('ctr', 5, 2)->default(0); // Click-through rate
            $table->integer('conversions')->default(0);
            $table->decimal('conversion_rate', 5, 2)->default(0);
            $table->decimal('cost_per_click', 12, 2)->default(0);
            $table->decimal('cost_per_conversion', 12, 2)->default(0);
            
            // Target audience
            $table->json('target_audience')->nullable();
            
            // Status and dates
            $table->enum('status', ['pending', 'active', 'paused', 'completed', 'cancelled'])->default('pending');
            $table->date('start_date');
            $table->date('end_date');
            
            // Featured settings
            $table->boolean('is_featured')->default(false);
            $table->integer('featured_position')->nullable();
            
            // Payment & refund
            $table->string('payment_url')->nullable();
            $table->decimal('refund_amount', 12, 2)->nullable();
            $table->text('refund_note')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index('shop_id');
            $table->index('listing_id');
            $table->index('type');
            $table->index('status');
            $table->index(['status', 'type']);
            $table->index('is_featured');
            $table->index('featured_position');
            $table->index(['start_date', 'end_date']);
        });
        
        // Re-enable foreign key checks
        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        Schema::dropIfExists('promotions');
        
        // Recreate old promotions table structure
        Schema::create('promotions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->nullable()->constrained('shops')->onDelete('cascade');
            $table->foreignId('listing_id')->nullable()->constrained('listings')->onDelete('set null');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('image_url');
            $table->enum('status', ['active', 'inactive', 'expired', 'upcoming'])->default('upcoming');
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('discount_percentage', 5, 2)->nullable();
            $table->decimal('min_order_amount', 10, 2)->nullable();
            $table->integer('max_usage')->nullable();
            $table->string('promo_code')->unique()->nullable();
            $table->boolean('is_featured')->default(false);
            $table->timestamps();
            
            $table->index('status');
            $table->index('start_date');
            $table->index('end_date');
            $table->index('promo_code');
            $table->index('is_featured');
            $table->index(['status', 'start_date', 'end_date']);
        });
    }
};
