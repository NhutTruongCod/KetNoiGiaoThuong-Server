<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Thêm cột quyền lợi vào subscription_plans
        Schema::table('subscription_plans', function (Blueprint $table) {
            if (!Schema::hasColumn('subscription_plans', 'commission_rate')) {
                $table->decimal('commission_rate', 5, 2)->default(10.00)->after('features'); // Chiết khấu %
            }
            if (!Schema::hasColumn('subscription_plans', 'search_boost')) {
                $table->integer('search_boost')->default(0)->after('commission_rate'); // Ưu tiên tìm kiếm (0-100)
            }
            if (!Schema::hasColumn('subscription_plans', 'free_promotions')) {
                $table->integer('free_promotions')->default(0)->after('search_boost'); // Số lượt quảng cáo miễn phí
            }
            if (!Schema::hasColumn('subscription_plans', 'badge')) {
                $table->string('badge', 50)->nullable()->after('free_promotions'); // Badge hiển thị (pro, vip, etc)
            }
        });

        // Thêm cột vào user_subscriptions để track thanh toán
        Schema::table('user_subscriptions', function (Blueprint $table) {
            if (!Schema::hasColumn('user_subscriptions', 'payment_code')) {
                $table->string('payment_code', 50)->nullable()->after('payment_method');
            }
            if (!Schema::hasColumn('user_subscriptions', 'payment_proof')) {
                $table->string('payment_proof', 500)->nullable()->after('payment_code'); // URL ảnh chứng từ
            }
            if (!Schema::hasColumn('user_subscriptions', 'admin_note')) {
                $table->text('admin_note')->nullable()->after('payment_proof');
            }
            if (!Schema::hasColumn('user_subscriptions', 'approved_by')) {
                $table->foreignId('approved_by')->nullable()->after('admin_note')->constrained('users');
            }
            if (!Schema::hasColumn('user_subscriptions', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('approved_by');
            }
        });

        // Thêm cột subscription vào users để track gói hiện tại
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'subscription_plan_id')) {
                $table->foreignId('subscription_plan_id')->nullable()->after('status')->constrained('subscription_plans');
            }
            if (!Schema::hasColumn('users', 'subscription_expires_at')) {
                $table->timestamp('subscription_expires_at')->nullable()->after('subscription_plan_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['subscription_plan_id']);
            $table->dropColumn(['subscription_plan_id', 'subscription_expires_at']);
        });

        Schema::table('user_subscriptions', function (Blueprint $table) {
            $table->dropForeign(['approved_by']);
            $table->dropColumn(['payment_code', 'payment_proof', 'admin_note', 'approved_by', 'approved_at']);
        });

        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->dropColumn(['commission_rate', 'search_boost', 'free_promotions', 'badge']);
        });
    }
};
