<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('user_subscriptions', function (Blueprint $table) {
            $table->integer('duration_months')->default(1)->after('plan_id');
            $table->decimal('price', 10, 2)->default(0)->after('duration_months');
            $table->decimal('discount_amount', 10, 2)->default(0)->after('price');
            $table->decimal('final_amount', 10, 2)->default(0)->after('discount_amount');
            $table->string('payment_method', 50)->nullable()->after('final_amount');
            $table->string('coupon_code', 50)->nullable()->after('payment_method');
            $table->enum('status', ['pending', 'active', 'expired', 'cancelled'])->default('pending')->after('coupon_code');
            $table->date('start_date')->nullable()->after('status');
            $table->date('end_date')->nullable()->after('start_date');
            $table->boolean('auto_renew')->default(false)->after('is_active');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::table('user_subscriptions', function (Blueprint $table) {
            $table->dropColumn([
                'duration_months',
                'price',
                'discount_amount',
                'final_amount',
                'payment_method',
                'coupon_code',
                'status',
                'start_date',
                'end_date',
                'auto_renew',
                'created_at',
                'updated_at'
            ]);
        });
    }
};
