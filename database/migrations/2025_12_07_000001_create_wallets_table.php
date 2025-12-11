<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Bảng ví của user
        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->onDelete('cascade');
            $table->decimal('balance', 20, 2)->default(0); // Số dư hiện tại
            $table->decimal('frozen_balance', 20, 2)->default(0); // Số dư bị đóng băng (đang đấu giá)
            $table->string('currency', 10)->default('VND');
            $table->enum('status', ['active', 'frozen', 'suspended'])->default('active');
            $table->timestamps();
            
            $table->index('user_id');
            $table->index('status');
        });

        // Bảng lịch sử giao dịch ví
        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('transaction_code', 50)->unique(); // Mã giao dịch
            $table->enum('type', [
                'deposit',      // Nạp tiền
                'withdraw',     // Rút tiền
                'payment',      // Thanh toán
                'receive',      // Nhận tiền
                'refund',       // Hoàn tiền
                'freeze',       // Đóng băng (đặt cọc đấu giá)
                'unfreeze',     // Giải phóng đóng băng
                'auction_win',  // Thanh toán đấu giá thắng
                'auction_receive', // Nhận tiền từ đấu giá
            ]);
            $table->decimal('amount', 20, 2); // Số tiền
            $table->decimal('balance_before', 20, 2); // Số dư trước giao dịch
            $table->decimal('balance_after', 20, 2); // Số dư sau giao dịch
            $table->string('description')->nullable();
            $table->string('reference_type')->nullable(); // auction, order, deposit, etc.
            $table->unsignedBigInteger('reference_id')->nullable(); // ID của auction, order, etc.
            $table->enum('status', ['pending', 'completed', 'failed', 'cancelled'])->default('completed');
            $table->json('metadata')->nullable(); // Thông tin bổ sung
            $table->timestamps();
            
            $table->index(['wallet_id', 'type']);
            $table->index(['user_id', 'created_at']);
            $table->index('transaction_code');
            $table->index(['reference_type', 'reference_id']);
        });

        // Bảng yêu cầu nạp tiền
        Schema::create('deposit_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('wallet_id')->constrained()->onDelete('cascade');
            $table->string('request_code', 50)->unique();
            $table->decimal('amount', 20, 2);
            $table->enum('payment_method', ['bank_transfer', 'momo', 'vnpay', 'zalopay']);
            $table->enum('status', ['pending', 'processing', 'completed', 'failed', 'cancelled'])->default('pending');
            $table->string('bank_name')->nullable();
            $table->string('bank_account')->nullable();
            $table->string('transaction_ref')->nullable(); // Mã giao dịch ngân hàng
            $table->text('note')->nullable();
            $table->text('admin_note')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->foreignId('confirmed_by')->nullable()->constrained('users');
            $table->timestamps();
            
            $table->index(['user_id', 'status']);
            $table->index('request_code');
        });

        // Bảng yêu cầu rút tiền
        Schema::create('withdraw_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('wallet_id')->constrained()->onDelete('cascade');
            $table->string('request_code', 50)->unique();
            $table->decimal('amount', 20, 2);
            $table->decimal('fee', 20, 2)->default(0); // Phí rút tiền
            $table->decimal('actual_amount', 20, 2); // Số tiền thực nhận
            $table->string('bank_name');
            $table->string('bank_account');
            $table->string('account_holder'); // Tên chủ tài khoản
            $table->enum('status', ['pending', 'processing', 'completed', 'rejected', 'cancelled'])->default('pending');
            $table->text('note')->nullable();
            $table->text('admin_note')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->foreignId('processed_by')->nullable()->constrained('users');
            $table->timestamps();
            
            $table->index(['user_id', 'status']);
            $table->index('request_code');
        });

        // Bảng thanh toán đấu giá
        Schema::create('auction_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('auction_id')->constrained()->onDelete('cascade');
            $table->foreignId('winner_id')->constrained('users')->onDelete('cascade'); // Người thắng
            $table->foreignId('seller_id')->constrained('users')->onDelete('cascade'); // Người bán
            $table->string('payment_code', 50)->unique();
            $table->decimal('amount', 20, 2); // Số tiền phải thanh toán
            $table->decimal('platform_fee', 20, 2)->default(0); // Phí sàn
            $table->decimal('seller_receive', 20, 2); // Số tiền seller nhận
            $table->enum('status', [
                'pending',      // Chờ thanh toán
                'paid',         // Đã thanh toán
                'transferred',  // Đã chuyển cho seller
                'cancelled',    // Đã hủy
                'refunded',     // Đã hoàn tiền
                'expired',      // Hết hạn thanh toán
            ])->default('pending');
            $table->timestamp('payment_deadline'); // Hạn thanh toán
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('transferred_at')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
            
            $table->index(['auction_id', 'status']);
            $table->index(['winner_id', 'status']);
            $table->index(['seller_id', 'status']);
            $table->index('payment_deadline');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auction_payments');
        Schema::dropIfExists('withdraw_requests');
        Schema::dropIfExists('deposit_requests');
        Schema::dropIfExists('wallet_transactions');
        Schema::dropIfExists('wallets');
    }
};
