<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            $columns = Schema::getColumnListing('listings');
            
            if (!in_array('total_reviews', $columns)) {
                $table->integer('total_reviews')->default(0)->after('stock_qty');
            }
            
            if (!in_array('rating', $columns)) {
                $table->decimal('rating', 3, 2)->default(0)->after('total_reviews'); // 0.00 - 5.00
            }
        });
        
        // Add indexes
        try {
            Schema::table('listings', function (Blueprint $table) {
                $table->index('rating', 'idx_listings_rating');
            });
        } catch (\Exception $e) {}
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            try { $table->dropIndex('idx_listings_rating'); } catch (\Exception $e) {}
            
            $columns = Schema::getColumnListing('listings');
            
            $dropColumns = [];
            if (in_array('total_reviews', $columns)) {
                $dropColumns[] = 'total_reviews';
            }
            if (in_array('rating', $columns)) {
                $dropColumns[] = 'rating';
            }
            
            if (!empty($dropColumns)) {
                $table->dropColumn($dropColumns);
            }
        });
    }
};
