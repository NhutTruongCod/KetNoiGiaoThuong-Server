<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $columns = Schema::getColumnListing('reviews');
        
        Schema::table('reviews', function (Blueprint $table) use ($columns) {
            // Rename columns
            if (in_array('reviewer_id', $columns) && !in_array('user_id', $columns)) {
                $table->renameColumn('reviewer_id', 'user_id');
            }
            
            if (in_array('content', $columns) && !in_array('comment', $columns)) {
                $table->renameColumn('content', 'comment');
            }
        });
        
        // Refresh column list after rename
        $columns = Schema::getColumnListing('reviews');
        
        Schema::table('reviews', function (Blueprint $table) use ($columns) {
            // Add new columns
            if (!in_array('listing_id', $columns)) {
                $table->unsignedBigInteger('listing_id')->nullable()->after('order_id');
            }
            
            if (!in_array('shop_id', $columns)) {
                $table->unsignedBigInteger('shop_id')->nullable()->after('listing_id');
            }
            
            if (!in_array('images', $columns)) {
                $table->json('images')->nullable()->after('comment');
            }
            
            if (!in_array('helpful_count', $columns)) {
                $table->integer('helpful_count')->default(0)->after('images');
            }
            
            if (!in_array('is_verified_purchase', $columns)) {
                $table->boolean('is_verified_purchase')->default(false)->after('helpful_count');
            }
            
            if (!in_array('seller_reply', $columns)) {
                $table->json('seller_reply')->nullable()->after('is_verified_purchase');
            }
            
            if (!in_array('seller_reply_at', $columns)) {
                $table->timestamp('seller_reply_at')->nullable()->after('seller_reply');
            }
            
            if (!in_array('updated_at', $columns)) {
                $table->timestamp('updated_at')->nullable()->after('created_at');
            }
        });
        
        // Add indexes
        try {
            Schema::table('reviews', function (Blueprint $table) {
                $table->index('listing_id', 'idx_reviews_listing');
            });
        } catch (\Exception $e) {}
        
        try {
            Schema::table('reviews', function (Blueprint $table) {
                $table->index('shop_id', 'idx_reviews_shop');
            });
        } catch (\Exception $e) {}
        
        try {
            Schema::table('reviews', function (Blueprint $table) {
                $table->index('rating', 'idx_reviews_rating');
            });
        } catch (\Exception $e) {}
        
        try {
            Schema::table('reviews', function (Blueprint $table) {
                $table->index('is_verified_purchase', 'idx_reviews_verified');
            });
        } catch (\Exception $e) {}
        
        // Add foreign keys
        try {
            Schema::table('reviews', function (Blueprint $table) {
                $table->foreign('listing_id', 'fk_reviews_listing')->references('id')->on('listings')->onDelete('CASCADE');
            });
        } catch (\Exception $e) {}
        
        try {
            Schema::table('reviews', function (Blueprint $table) {
                $table->foreign('shop_id', 'fk_reviews_shop')->references('id')->on('shops')->onDelete('CASCADE');
            });
        } catch (\Exception $e) {}
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('reviews', function (Blueprint $table) {
            try { $table->dropForeign('fk_reviews_listing'); } catch (\Exception $e) {}
            try { $table->dropForeign('fk_reviews_shop'); } catch (\Exception $e) {}
            
            try { $table->dropIndex('idx_reviews_listing'); } catch (\Exception $e) {}
            try { $table->dropIndex('idx_reviews_shop'); } catch (\Exception $e) {}
            try { $table->dropIndex('idx_reviews_rating'); } catch (\Exception $e) {}
            try { $table->dropIndex('idx_reviews_verified'); } catch (\Exception $e) {}
            
            $table->dropColumn([
                'listing_id',
                'shop_id',
                'images',
                'helpful_count',
                'is_verified_purchase',
                'seller_reply',
                'seller_reply_at',
                'updated_at',
            ]);
            
            // Rename back
            $table->renameColumn('user_id', 'reviewer_id');
            $table->renameColumn('comment', 'content');
        });
    }
};
