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
        Schema::create('payments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('order_id')->index('idx_payments_order');
            $table->enum('method', ['cod', 'bank_transfer', 'momo', 'vnpay'])->default('cod');
            $table->enum('status', ['unpaid', 'paid', 'refunded'])->default('unpaid')->index('idx_payments_status');
            $table->decimal('amount', 12);
            $table->dateTime('paid_at')->nullable();
            $table->dateTime('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('payments');
    }
};
