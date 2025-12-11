<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\AuctionPayment;
use App\Models\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CheckExpiredAuctionPayments extends Command
{
    protected $signature = 'auctions:check-expired-payments';
    protected $description = 'Kiểm tra và xử lý các thanh toán đấu giá đã hết hạn';

    public function handle()
    {
        $this->info('Đang kiểm tra các thanh toán đấu giá hết hạn...');

        // Tìm các payment đã hết hạn
        $expiredPayments = AuctionPayment::where('status', 'pending')
            ->where('payment_deadline', '<', now())
            ->with(['auction.listing', 'winner', 'seller'])
            ->get();

        $processed = 0;

        foreach ($expiredPayments as $payment) {
            try {
                DB::beginTransaction();

                // Đánh dấu hết hạn
                $payment->update([
                    'status' => 'expired',
                    'note' => 'Hết hạn thanh toán tự động',
                ]);

                $listingTitle = $payment->auction->listing->title ?? 'Sản phẩm đấu giá';

                // Thông báo cho người thắng
                Notification::create([
                    'user_id' => $payment->winner_id,
                    'title' => '⚠️ Thanh toán đấu giá đã hết hạn',
                    'message' => "Bạn đã không thanh toán đúng hạn cho phiên đấu giá \"{$listingTitle}\". " .
                                "Giao dịch đã bị hủy.",
                    'type' => 'auction',
                ]);

                // Thông báo cho seller
                Notification::create([
                    'user_id' => $payment->seller_id,
                    'title' => 'Thanh toán đấu giá hết hạn',
                    'message' => "Người thắng đấu giá \"{$listingTitle}\" đã không thanh toán đúng hạn. " .
                                "Bạn có thể tạo phiên đấu giá mới.",
                    'type' => 'auction',
                ]);

                DB::commit();
                $processed++;

                $this->info("Đã xử lý payment #{$payment->id} - Auction #{$payment->auction_id}");

            } catch (\Exception $e) {
                DB::rollBack();
                Log::error("Lỗi xử lý expired payment #{$payment->id}: " . $e->getMessage());
                $this->error("Lỗi payment #{$payment->id}: " . $e->getMessage());
            }
        }

        $this->info("Hoàn tất! Đã xử lý {$processed} thanh toán hết hạn.");

        return Command::SUCCESS;
    }
}
