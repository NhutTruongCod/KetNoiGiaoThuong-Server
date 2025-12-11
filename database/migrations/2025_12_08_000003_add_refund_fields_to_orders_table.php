<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $columns = Schema::getColumnListing('orders');
        
        Schema::table('orders', function (Blueprint $table) use ($columns) {
            // Refund fields
            if (!in_array('refund_requested_at', $columns)) {
                $table->timestamp('refund_requested_at')->nullable();
            }
            if (!in_array('refund_reason', $columns)) {
                $table->text('refund_reason')->nullable();
            }
            if (!in_array('refund_evidence', $columns)) {
                $table->json('refund_evidence')->nullable();
            }
            if (!in_array('refund_processed_at', $columns)) {
                $table->timestamp('refund_processed_at')->nullable();
            }
            if (!in_array('refund_processed_by', $columns)) {
                $table->unsignedBigInteger('refund_processed_by')->nullable();
            }
            if (!in_array('refund_admin_note', $columns)) {
                $table->text('refund_admin_note')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'refund_requested_at',
                'refund_reason',
                'refund_evidence',
                'refund_processed_at',
                'refund_processed_by',
                'refund_admin_note',
            ]);
        });
    }
};
