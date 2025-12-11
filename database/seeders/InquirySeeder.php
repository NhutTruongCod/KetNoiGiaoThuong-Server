<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Inquiry;
use App\Models\Listing;

class InquirySeeder extends Seeder
{
    public function run(): void
    {
        $listings = Listing::take(8)->get();

        if ($listings->count() === 0) {
            $this->command->warn('⚠️  No listings found. Skipping inquiry seeder.');
            return;
        }

        $inquiries = [
            [
                'name' => 'Nguyễn Văn A',
                'email' => 'nguyenvana@example.com',
                'phone' => '0901234567',
                'message' => 'Xin chào, tôi muốn biết thêm thông tin về sản phẩm này. Sản phẩm có còn hàng không?',
            ],
            [
                'name' => 'Trần Thị B',
                'email' => 'tranthib@example.com',
                'phone' => '0912345678',
                'message' => 'Cho tôi hỏi sản phẩm này có bảo hành bao lâu? Và có giao hàng tận nơi không?',
            ],
            [
                'name' => 'Lê Văn C',
                'email' => null,
                'phone' => '0923456789',
                'message' => 'Tôi quan tâm đến sản phẩm này. Vui lòng liên hệ lại cho tôi qua số điện thoại.',
            ],
            [
                'name' => 'Phạm Thị D',
                'email' => 'phamthid@example.com',
                'phone' => null,
                'message' => 'Sản phẩm này có màu khác không? Tôi muốn xem thêm ảnh thực tế.',
            ],
            [
                'name' => 'Hoàng Văn E',
                'email' => 'hoangvane@example.com',
                'phone' => '0945678901',
                'message' => 'Giá này đã bao gồm VAT chưa? Có giảm giá nếu mua số lượng lớn không?',
            ],
            [
                'name' => 'Võ Thị F',
                'email' => 'vothif@example.com',
                'phone' => '0956789012',
                'message' => 'Tôi muốn đặt hàng ngay. Khi nào có thể giao hàng?',
            ],
            [
                'name' => 'Đặng Văn G',
                'email' => null,
                'phone' => '0967890123',
                'message' => 'Sản phẩm có thể đổi trả trong bao lâu? Và điều kiện đổi trả như thế nào?',
            ],
            [
                'name' => 'Bùi Thị H',
                'email' => 'buithih@example.com',
                'phone' => '0978901234',
                'message' => 'Tôi ở Hà Nội, ship về có mất bao lâu? Phí ship là bao nhiêu?',
            ],
        ];

        $count = 0;
        foreach ($listings as $index => $listing) {
            if ($index >= count($inquiries)) break;

            $inquiryData = $inquiries[$index];
            
            Inquiry::create([
                'listing_id' => $listing->id,
                'name' => $inquiryData['name'],
                'email' => $inquiryData['email'],
                'phone' => $inquiryData['phone'],
                'message' => $inquiryData['message'],
                'source_ip' => '127.0.0.' . rand(1, 255),
                'created_at' => now()->subDays(rand(1, 15)),
            ]);
            $count++;
        }

        $this->command->info('✅ Created ' . $count . ' inquiries');
    }
}
