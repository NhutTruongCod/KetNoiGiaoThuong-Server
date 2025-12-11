<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $columns = Schema::getColumnListing('auction_bids');
        
        Schema::table('auction_bids', function (Blueprint $table) use ($columns) {
            if (!in_array('is_winning', $columns)) {
                $table->boolean('is_winning')->default(false)->after('amount_cents');
            }
            if (!in_array('is_auto_bid', $columns)) {
                $table->boolean('is_auto_bid')->default(false)->after('is_winning');
            }
        });
        
        // Add index for is_winning
        try {
            Schema::table('auction_bids', function (Blueprint $table) {
                $table->index(['auction_id', 'is_winning'], 'idx_auction_winning_bid');
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
        Schema::table('auction_bids', function (Blueprint $table) {
            try { $table->dropIndex('idx_auction_winning_bid'); } catch (\Exception $e) {}
            
            $table->dropColumn([
                'is_winning',
                'is_auto_bid',
            ]);
        });
    }
};
