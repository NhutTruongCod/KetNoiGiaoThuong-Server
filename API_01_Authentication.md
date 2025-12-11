# API Authentication - Xác thực người dùng

**Base URL:** `{{base_url}}` = `http://localhost:8000/api`

---

## 1. Đăng ký tài khoản – POST /auth/register

**Mục đích:** Tạo user mới, gửi OTP xác thực email.

### Request

**Method:** `POST`  
**URL:** `{{base_url}}/auth/register`

**Headers:**
```
Content-Type: application/json
Accept: application/json
```

**Body → raw → JSON:**
```json
{
  "name": "Công ty ABC",
  "email": "abc@example.com",
  "phone": "0989123456",
  "password": "Password123!",
  "password_confirmation": "Password123!",
  "role": "seller"
}
```

**Roles có thể dùng:**
- `buyer` - Người mua
- `seller` - Người bán
- `admin` - Quản trị viên (chỉ tạo qua seeder)

### Response mẫu

**Success (200):**
```json
{
  "status": "success",
  "message": "Registered successfully. Please check your email for OTP verification.",
  "data": {
    "user": {
      "id": 1,
      "name": "Công ty ABC",
      "email": "abc@example.com",
      "phone": "0989123456",
      "role": "seller",
      "is_verified": false,
      "created_at": "2025-12-01T10:00:00.000000Z"
    }
  }
}
```

**Error - Validation (422):**
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "email": ["The email has already been taken."],
    "password": ["The password must be at least 8 characters."]
  }
}
```

**Lưu ý:** Sau bước này, hệ thống sẽ gửi OTP tới email. Kiểm tra log hoặc email để lấy OTP.

---

## 2. Xác thực email bằng OTP – POST /auth/verify-email

**Mục đích:** Dùng email + OTP để xác thực user.

### Request

**Method:** `POST`  
**URL:** `{{base_url}}/auth/verify-email`

**Headers:**
```
Content-Type: application/json
Accept: application/json
```

**Body → raw → JSON:**
```json
{
  "email": "abc@example.com",
  "otp": "123456"
}
```

### Response mẫu

**Success (200):**
```json
{
  "status": "success",
  "message": "Email verified successfully."
}
```

**Error - OTP sai/hết hạn (422):**
```json
{
  "status": "error",
  "message": "Invalid or expired OTP code."
}
```

---

## 3. Gửi lại OTP xác thực email – POST /auth/resend-verification-otp

**Mục đích:** Gửi lại OTP nếu user chưa nhận được hoặc OTP đã hết hạn.

### Request

**Method:** `POST`  
**URL:** `{{base_url}}/auth/resend-verification-otp`

**Headers:**
```
Content-Type: application/json
Accept: application/json
```

**Body → raw → JSON:**
```json
{
  "email": "abc@example.com"
}
```

### Response mẫu

**Success (200):**
```json
{
  "status": "success",
  "message": "OTP has been resent to your email."
}
```

**Error - Email đã xác thực (400):**
```json
{
  "status": "error",
  "message": "Email is already verified."
}
```

---

## 4. Đăng nhập – POST /auth/login

**Mục đích:** Đăng nhập và nhận JWT token.

### Request

**Method:** `POST`  
**URL:** `{{base_url}}/auth/login`

**Headers:**
```
Content-Type: application/json
Accept: application/json
```

**Body → raw → JSON:**
```json
{
  "email": "abc@example.com",
  "password": "Password123!"
}
```

### Response mẫu

**Success (200):**
```json
{
  "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
  "token_type": "bearer",
  "expires_in": 3600,
  "user": {
    "id": 1,
    "name": "Công ty ABC",
    "email": "abc@example.com",
    "role": "seller",
    "is_verified": true
  }
}
```

**Error - Sai thông tin (401):**
```json
{
  "error": "Unauthorized",
  "message": "Invalid credentials"
}
```

**Error - Email chưa xác thực (403):**
```json
{
  "error": "Email not verified",
  "message": "Please verify your email before logging in."
}
```

**Lưu ý:** Lưu `access_token` vào Postman Environment variable `{{token}}` để dùng cho các request khác.

**Postman Tests Script (tự động lưu token):**
```javascript
if (pm.response.code === 200) {
    var jsonData = pm.response.json();
    if (jsonData.access_token) {
        pm.environment.set("token", jsonData.access_token);
        console.log("Token saved:", jsonData.access_token);
    }
}
```

---

## 5. Đăng xuất – POST /auth/logout

**Mục đích:** Đăng xuất và vô hiệu hóa token hiện tại.

### Request

**Method:** `POST`  
**URL:** `{{base_url}}/auth/logout`

**Headers:**
```
Content-Type: application/json
Accept: application/json
Authorization: Bearer {{token}}
```

**Body:** Không cần

### Response mẫu

**Success (200):**
```json
{
  "message": "Successfully logged out"
}
```

---

## 6. Refresh Token – POST /auth/refresh

**Mục đích:** Làm mới token khi sắp hết hạn.

### Request

**Method:** `POST`  
**URL:** `{{base_url}}/auth/refresh`

**Headers:**
```
Content-Type: application/json
Accept: application/json
Authorization: Bearer {{token}}
```

**Body:** Không cần

### Response mẫu

**Success (200):**
```json
{
  "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
  "token_type": "bearer",
  "expires_in": 3600
}
```

**Lưu ý:** Cập nhật token mới vào environment variable.

---

## 7. Quên mật khẩu – POST /auth/forgot-password

**Mục đích:** Gửi link/token reset mật khẩu qua email.

### Request

**Method:** `POST`  
**URL:** `{{base_url}}/auth/forgot-password`

**Headers:**
```
Content-Type: application/json
Accept: application/json
```

**Body → raw → JSON:**
```json
{
  "email": "abc@example.com"
}
```

### Response mẫu

**Success (200):**
```json
{
  "message": "Password reset link has been sent to your email."
}
```

**Error - Email không tồn tại (404):**
```json
{
  "message": "We can't find a user with that email address."
}
```

---

## 8. Reset mật khẩu – POST /auth/reset-password

**Mục đích:** Đặt lại mật khẩu mới bằng token từ email.

### Request

**Method:** `POST`  
**URL:** `{{base_url}}/auth/reset-password`

**Headers:**
```
Content-Type: application/json
Accept: application/json
```

**Body → raw → JSON:**
```json
  {
    "email": "abc@example.com",
    "token": "reset_token_from_email",
    "password": "NewPassword123!",
    "password_confirmation": "NewPassword123!"
  }
```

### Response mẫu

**Success (200):**
```json
{
  "message": "Password has been reset successfully."
}
```

**Error - Token không hợp lệ (400):**
```json
{
  "message": "Invalid or expired reset token."
}
```

---

## Postman Environment Variables

Tạo Environment trong Postman với các biến sau:

```json
{
  "base_url": "http://localhost:8000/api",
  "token": "",
  "admin_token": "",
  "seller_token": "",
  "buyer_token": ""
}
```

## Test Flow trong Postman

1. **Register** → Lấy OTP từ log/email
2. **Verify Email** → Xác thực OTP
3. **Login** → Lưu token tự động
4. **Sử dụng token** cho các API khác
5. **Logout** khi cần

---

**Rate Limiting:** 5 requests/minute cho tất cả auth endpoints
