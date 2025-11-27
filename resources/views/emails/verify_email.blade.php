@component('mail::message')
# Xin chào {{ $fullName }},

Cảm ơn bạn đã đăng ký tài khoản tại **Kết Nối Doanh Nghiệp** 🎉
Để hoàn tất quá trình đăng ký, vui lòng nhập mã OTP bên dưới để xác minh email của bạn:

@component('mail::panel')
## 🔐 Mã OTP của bạn là:
# {{ $otp }}
@endcomponent

> ⚠️ Lưu ý: Mã OTP có hiệu lực trong **10 phút**.
Nếu bạn không thực hiện yêu cầu này, hãy bỏ qua email này.

Trân trọng,<br>
**Đội ngũ Kết Nối Doanh Nghiệp**
@endcomponent