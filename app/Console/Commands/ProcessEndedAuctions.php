<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Auction;
use App\Models\AuctionPayment;
use App\Models\Notification;
use App\Models\Wallet;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessEndedAuctions extends Command
{
    protected $signature = 'auctions:process-ended';
    protected $description = 'Xử lý các phiên đấu giá đã kết thúc - tạo yêu cầu thanh toán cho người thắng';

    // Thời hạn thanh toán (giờ)
    const PAYMENT_DEADLINE_HOURS = 24;
    
    // Phí sàn mặc định (%) - cho user free
    const DEFAULT_COMMISSION_RATE = 10;

    public function handle()
    {
        $this->info('Đang xử lý các phiên đấu giá đã kết thúc...');

        // Tìm các auction đã hết thời gian nhưng chưa được xử lý
        $endedAuctions = Auction::where('ends_at', '<=', now())
            ->where('status', 'active')
            ->whereDoesntHave('auctionPayment') // Chưa có payment record
            ->with(['listing', 'bids' => function($q) {
                $q->orderBy('amount_cents', 'desc')->limit(1);
            }])
            ->get();

        $processed = 0;
        $noWinner = 0;

        foreach ($endedAuctions as $auction) {
            try {
                DB::beginTransaction();

                // Lấy bid cao nhất
                $highestBid = $auction->bids->first();

                if (!$highestBid) {
                    // Không có ai đặt giá
                    $auction->update(['status' => 'ended']);
                    $noWinner++;
                    
                    // Thông báo cho seller
                    Notification::create([
                        'user_id' => $auction->created_by,
                        'title' => 'Đấu giá kết thúc - Không có người thắng',
                        'message' => "Phiên đấu giá \"{$auction->listing->title}\" đã kết thúc nhưng không có ai tham gia.",
                        'type' => 'auction',
                    ]);

                    DB::commit();
                    continue;
                }

                // Kiểm tra reserve price
                if ($auction->reserve_price_cents && $auction->current_price_cents < $auction->reserve_price_cents) {
                    $auction->update(['status' => 'ended']);
                    $noWinner++;

                    Notification::create([
                        'user_id' => $auction->created_by,
                        'title' => 'Đấu giá kết thúc - Chưa đạt giá dự trữ',
                        'message' => "Phiên đấu giá \"{$auction->listing->title}\" đã kết thúc nhưng chưa đạt giá dự trữ.",
                        'type' => 'auction',
                    ]);

                    DB::commit();
                    continue;
                }

                // Có người thắng - tạo payment record
                $winnerId = $highestBid->user_id;
                $amount = $auction->current_price_cents / 100; // Convert cents to VND
                
                // Get seller's commission rate based on subscription plan
                $seller = User::find($auction->created_by);
                $commissionRate = $seller ? $seller->getCommissionRate() : self::DEFAULT_COMMISSION_RATE;
                
                $platformFee = $amount * ($commissionRate / 100);
                $sellerReceive = $amount - $platformFee;

                $auctionPayment = AuctionPayment::create([
                    'auction_id' => $auction->id,
                    'winner_id' => $winnerId,
                    'seller_id' => $auction->created_by,
                    'payment_code' => 'AUC' . time() . rand(1000, 9999),
                    'amount' => $amount,
                    'platform_fee' => $platformFee,
                    'seller_receive' => $sellerReceive,
                    'status' => 'pending',
                    'payment_deadline' => now()->addHours(self::PAYMENT_DEADLINE_HOURS),
                ]);

                // Cập nhật auction
                $auction->update([
                    'status' => 'ended',
                    'winner_id' => $winnerId,
                ]);

                // Thông báo cho người thắng
                Notification::create([
                    'user_id' => $winnerId,
                    'title' => '🎉 Chúc mừng! Bạn đã thắng đấu giá',
                    'message' => "Bạn đã thắng phiên đấu giá \"{$auction->listing->title}\" với giá " . 
                                number_format($amount, 0, ',', '.') . " VND. " .
                                "Vui lòng thanh toán trong vòng " . self::PAYMENT_DEADLINE_HOURS . " giờ.",
                    'type' => 'auction',
                ]);

                // Thông báo cho seller
                Notification::create([
                    'user_id' => $auction->created_by,
                    'title' => 'Đấu giá kết thúc - Có người thắng',
                    'message' => "Phiên đấu giá \"{$auction->listing->title}\" đã kết thúc với giá " .
                                number_format($amount, 0, ',', '.') . " VND. " .
                                "Đang chờ người thắng thanh toán.",
                    'type' => 'auction',
                ]);

                DB::commit();
                $processed++;

                $this->info("Đã xử lý auction #{$auction->id} - Winner: {$winnerId}");

            } catch (\Exception $e) {
                DB::rollBack();
                Log::error("Lỗi xử lý auction #{$auction->id}: " . $e->getMessage());
                $this->error("Lỗi auction #{$auction->id}: " . $e->getMessage());
            }
        }

        $this->info("Hoàn tất! Đã xử lý {$processed} phiên đấu giá, {$noWinner} phiên không có người thắng.");

        return Command::SUCCESS;
    }
}
