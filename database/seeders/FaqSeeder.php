<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Faq;

class FaqSeeder extends Seeder
{
    public function run(): void
    {
        $faqs = [
            [
                'question' => 'Làm thế nào để đăng ký tài khoản?',
                'answer' => 'Bạn có thể đăng ký tài khoản bằng cách nhấn vào nút "Đăng ký" trên trang chủ, điền thông tin email, số điện thoại và mật khẩu. Sau đó xác thực qua mã OTP được gửi về email hoặc SMS.',
                'category' => 'account',
                'is_public' => true,
                'sort_order' => 1,
            ],
            [
                'question' => 'Tôi có thể đăng tin mua bán không?',
                'answer' => 'Chỉ tài khoản Seller (người bán) mới có thể đăng tin. Nếu bạn là Buyer, vui lòng nâng cấp tài khoản lên Seller trong phần cài đặt.',
                'category' => 'listing',
                'is_public' => true,
                'sort_order' => 2,
            ],
            [
                'question' => 'Làm thế nào để nâng cấp tài khoản?',
                'answer' => 'Vào phần "Cài đặt tài khoản" > "Nâng cấp lên Seller", điền thông tin doanh nghiệp và xác thực danh tính. Admin sẽ duyệt trong vòng 24-48 giờ.',
                'category' => 'account',
                'is_public' => true,
                'sort_order' => 3,
            ],
            [
                'question' => 'Phí đăng tin là bao nhiêu?',
                'answer' => 'Đăng tin cơ bản hoàn toàn miễn phí. Bạn có thể nâng cấp lên gói VIP để tin đăng được ưu tiên hiển thị và có thêm nhiều tính năng.',
                'category' => 'pricing',
                'is_public' => true,
                'sort_order' => 4,
            ],
            [
                'question' => 'Làm thế nào để tham gia đấu giá?',
                'answer' => 'Tìm sản phẩm đấu giá trong mục "Đấu giá", xem chi tiết và đặt giá thầu. Hệ thống sẽ tự động cập nhật giá cao nhất và thông báo khi bạn thắng đấu giá.',
                'category' => 'auction',
                'is_public' => true,
                'sort_order' => 5,
            ],
            [
                'question' => 'Tôi có thể thanh toán bằng cách nào?',
                'answer' => 'Chúng tôi hỗ trợ nhiều phương thức thanh toán: chuyển khoản ngân hàng, thẻ tín dụng, ví điện tử (Momo, ZaloPay, VNPay).',
                'category' => 'payment',
                'is_public' => true,
                'sort_order' => 6,
            ],
            [
                'question' => 'Làm thế nào để liên hệ với người bán?',
                'answer' => 'Bạn có thể nhắn tin trực tiếp qua hệ thống chat, gọi điện thoại hoặc gửi form liên hệ trên trang chi tiết sản phẩm.',
                'category' => 'contact',
                'is_public' => true,
                'sort_order' => 7,
            ],
            [
                'question' => 'Tôi có thể đánh giá người bán không?',
                'answer' => 'Có, sau khi giao dịch hoàn tất, bạn có thể đánh giá người bán về chất lượng sản phẩm, thái độ phục vụ và thời gian giao hàng.',
                'category' => 'review',
                'is_public' => true,
                'sort_order' => 8,
            ],
        ];

        foreach ($faqs as $faq) {
            Faq::create($faq);
        }

        $this->command->info(' Created ' . count($faqs) . ' FAQs');
    }
}
