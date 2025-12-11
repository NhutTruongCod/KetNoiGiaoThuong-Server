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
        Cảm ơn bạn đã đăng ký tài khoản tại <strong>Kết Nối Giao Thương</strong>! 🎉
    </p>
    <p style="color: #475569; font-size: 16px; line-height: 1.6;">
        Để hoàn tất quá trình đăng ký và bảo mật tài khoản của bạn, vui lòng xác thực email bằng mã OTP bên dưới:
    </p>
</div>

{{-- OTP Panel --}}
@component('mail::panel')
<div style="text-align: center; padding: 20px;">
    <p style="color: #64748b; font-size: 14px; margin: 0 0 10px 0; text-transform: uppercase; letter-spacing: 1px;">
        🔐 Mã xác thực OTP
    </p>
    <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 20px; border-radius: 12px; margin: 15px 0;">
        <h1 style="color: #ffffff; font-size: 42px; font-weight: bold; letter-spacing: 8px; margin: 0; font-family: 'Courier New', monospace;">
            {{ $otp }}
        </h1>
    </div>
    <p style="color: #64748b; font-size: 13px; margin: 10px 0 0 0;">
        Vui lòng nhập mã này vào trang đăng ký để xác thực email
    </p>
</div>
@endcomponent

{{-- Warning Box --}}
<div style="background-color: #fef3c7; border-left: 4px solid #f59e0b; padding: 15px; margin: 25px 0; border-radius: 6px;">
    <p style="color: #92400e; font-size: 14px; margin: 0; line-height: 1.6;">
        <strong>⚠️ Lưu ý quan trọng:</strong><br>
        • Mã OTP có hiệu lực trong <strong>10 phút</strong><br>
        • Không chia sẻ mã này với bất kỳ ai<br>
        • Nếu bạn không thực hiện yêu cầu này, vui lòng bỏ qua email
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