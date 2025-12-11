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
        // Check and add columns if they don't exist
        $columns = Schema::getColumnListing('auctions');
        
        Schema::table('auctions', function (Blueprint $table) use ($columns) {
            if (!in_array('shop_id', $columns)) {
                $table->unsignedBigInteger('shop_id')->nullable()->after('listing_id');
            }
            if (!in_array('reserve_price_cents', $columns)) {
                $table->unsignedBigInteger('reserve_price_cents')->nullable()->after('starting_price_cents');
            }
            if (!in_array('bid_increment_cents', $columns)) {
                $table->unsignedBigInteger('bid_increment_cents')->default(100000)->after('reserve_price_cents'); // Default 1000 VND
            }
            if (!in_array('total_bids', $columns)) {
                $table->integer('total_bids')->default(0)->after('current_price_cents');
            }
            if (!in_array('winner_id', $columns)) {
                $table->unsignedBigInteger('winner_id')->nullable()->after('total_bids');
            }
            if (!in_array('auto_extend', $columns)) {
                $table->boolean('auto_extend')->default(true)->after('ends_at');
            }
            if (!in_array('extend_minutes', $columns)) {
                $table->integer('extend_minutes')->default(5)->after('auto_extend');
            }
            if (!in_array('max_bids_per_user', $columns)) {
                $table->integer('max_bids_per_user')->default(0)->after('extend_minutes'); // 0 = unlimited
            }
        });
        
        // Update status enum to include more states
        DB::statement("ALTER TABLE auctions MODIFY COLUMN status ENUM('upcoming', 'active', 'paused', 'ended', 'cancelled') DEFAULT 'upcoming'");
        
        // Add indexes and foreign keys
        try {
            Schema::table('auctions', function (Blueprint $table) {
                $table->index('shop_id', 'idx_auction_shop');
            });
        } catch (\Exception $e) {}
        
        try {
            Schema::table('auctions', function (Blueprint $table) {
                $table->index('winner_id', 'idx_auction_winner');
            });
        } catch (\Exception $e) {}
        
        try {
            Schema::table('auctions', function (Blueprint $table) {
                $table->index('status', 'idx_auction_status');
            });
        } catch (\Exception $e) {}
        
        try {
            Schema::table('auctions', function (Blueprint $table) {
                $table->index('ends_at', 'idx_auction_ends_at');
            });
        } catch (\Exception $e) {}
        
        try {
            Schema::table('auctions', function (Blueprint $table) {
                $table->foreign('shop_id', 'fk_auctions_shop')->references('id')->on('shops')->onDelete('CASCADE');
            });
        } catch (\Exception $e) {}
        
        try {
            Schema::table('auctions', function (Blueprint $table) {
                $table->foreign('winner_id', 'fk_auctions_winner')->references('id')->on('users')->onDelete('SET NULL');
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
        Schema::table('auctions', function (Blueprint $table) {
            try { $table->dropForeign('fk_auctions_shop'); } catch (\Exception $e) {}
            try { $table->dropForeign('fk_auctions_winner'); } catch (\Exception $e) {}
            try { $table->dropIndex('idx_auction_shop'); } catch (\Exception $e) {}
            try { $table->dropIndex('idx_auction_winner'); } catch (\Exception $e) {}
            try { $table->dropIndex('idx_auction_status'); } catch (\Exception $e) {}
            try { $table->dropIndex('idx_auction_ends_at'); } catch (\Exception $e) {}
            
            $table->dropColumn([
                'shop_id',
                'reserve_price_cents',
                'bid_increment_cents',
                'total_bids',
                'winner_id',
                'auto_extend',
                'extend_minutes',
                'max_bids_per_user',
            ]);
        });
        
        // Revert status enum
        DB::statement("ALTER TABLE auctions MODIFY COLUMN status ENUM('active', 'paused', 'closed') DEFAULT 'active'");
    }
};
