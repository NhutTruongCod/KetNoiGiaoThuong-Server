@component('mail::message')
{{-- Header với logo công ty --}}
<div style="text-align: center; margin-bottom: 30px;">
    <h1 style="color: #2563eb; font-size: 28px; margin: 0;">
        🏢 KẾT NỐI GIAO THƯƠNG
    </h1>
    <p style="color: #64748b; font-size: 14px; margin-top: 5px;">
        Nền tảng kết nối doanh nghiệp hàng đầu Việt Nam
    </p>
</div>

{{-- Greeting --}}
<div style="margin-bottom: 25px;">
    <h2 style="color: #1e293b; font-size: 20px;">
        Xin chào <strong style="color: #2563eb;">{{ $fullName }}</strong>,
    </h2>
    <p style="color: #475569; font-size: 16px; line-height: 1.6;">
        Chúng tôi nhận được yêu cầu đặt lại mật khẩu cho tài khoản của bạn.
    </p>
    <p style="color: #475569; font-size: 16px; line-height: 1.6;">
        Để tiếp tục, vui lòng sử dụng mã OTP bên dưới để xác thực và đặt lại mật khẩu mới:
    </p>
</div>

{{-- OTP Panel --}}
@component('mail::panel')
<div style="text-align: center; padding: 20px;">
    <p style="color: #64748b; font-size: 14px; margin: 0 0 10px 0; text-transform: uppercase; letter-spacing: 1px;">
        🔐 Mã xác thực đặt lại mật khẩu
    </p>
    <div style="background: linear-gradient(135deg, #f59e0b 0%, #ef4444 100%); padding: 20px; border-radius: 12px; margin: 15px 0;">
        <h1 style="color: #ffffff; font-size: 42px; font-weight: bold; letter-spacing: 8px; margin: 0; font-family: 'Courier New', monospace;">
            {{ $otp }}
        </h1>
    </div>
    <p style="color: #64748b; font-size: 13px; margin: 10px 0 0 0;">
        Nhập mã này vào trang đặt lại mật khẩu
    </p>
</div>
@endcomponent

{{-- Warning Box --}}
<div style="background-color: #fee2e2; border-left: 4px solid #ef4444; padding: 15px; margin: 25px 0; border-radius: 6px;">
    <p style="color: #991b1b; font-size: 14px; margin: 0; line-height: 1.6;">
        <strong>⚠️ Lưu ý bảo mật:</strong><br>
        • Mã OTP có hiệu lực trong <strong>10 phút</strong><br>
        • Không chia sẻ mã này với bất kỳ ai<br>
        • Nếu bạn KHÔNG yêu cầu đặt lại mật khẩu, vui lòng <strong>BỎ QUA</strong> email này<br>
        • Nếu có dấu hiệu bất thường, hãy liên hệ với chúng tôi ngay
    </p>
</div>

{{-- Info Box --}}
<div style="background-color: #dbeafe; border-left: 4px solid #3b82f6; padding: 15px; margin: 25px 0; border-radius: 6px;">
    <p style="color: #1e40af; font-size: 14px; margin: 0; line-height: 1.6;">
        <strong>💡 Mẹo bảo mật:</strong><br>
        • Sử dụng mật khẩu mạnh (ít nhất 8 ký tự)<br>
        • Kết hợp chữ hoa, chữ thường, số và ký tự đặc biệt<br>
        • Không sử dụng mật khẩu giống với các tài khoản khác
    </p>
</div>

{{-- Support Section --}}
<div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #e2e8f0;">
    <p style="color: #64748b; font-size: 14px; line-height: 1.6;">
        Bạn cần hỗ trợ? Liên hệ với chúng tôi:<br>
        📧 Email: <a href="mailto:support@ketnoigiaothuong.vn" style="color: #2563eb;">support@ketnoigiaothuong.vn</a><br>
        📞 Hotline: <strong>1900-xxxx</strong><br>
        🌐 Website: <a href="http://127.0.0.1:8000" style="color: #2563eb;">ketnoigiaothuong.vn</a>
    </p>
</div>

{{-- Footer --}}
<div style="margin-top: 30px; text-align: center;">
    <p style="color: #475569; font-size: 15px; margin-bottom: 5px;">
        Trân trọng,
    </p>
    <p style="color: #2563eb; font-size: 16px; font-weight: bold; margin: 0;">
        Đội ngũ Kết Nối Giao Thương
    </p>
    <p style="color: #94a3b8; font-size: 12px; margin-top: 20px;">
        © {{ date('Y') }} Kết Nối Giao Thương. All rights reserved.
    </p>
</div>
@endcomponent