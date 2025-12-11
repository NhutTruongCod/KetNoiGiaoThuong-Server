# API Login History - Lịch sử đăng nhập

**Base URL:** `{{base_url}}` = `http://localhost:8000/api`

---

## 1. Xem lịch sử đăng nhập của chính mình – GET /login-history

**Mục đích:** User xem lịch sử đăng nhập của tài khoản mình.

### Request

**Method:** `GET`  
**URL:** `{{base_url}}/login-history`

**Headers:**
```
Accept: application/json
Authorization: Bearer {{token}}
```

**Query Parameters (optional):**
```
?page=1
&per_page=20
&date_from=2025-01-01
&date_to=2025-12-31
```

**Body:** Không cần

### Response mẫu

**Success (200):**
```json
{
  "data": [
    {
      "id": 1,
      "user_id": 2,
      "ip_address": "192.168.1.100",
      "user_agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36",
      "device_type": "desktop",
      "browser": "Chrome",
      "os": "Windows 10",
      "location": {
        "country": "Vietnam",
        "city": "Ho Chi Minh",
        "timezone": "Asia/Ho_Chi_Minh"
      },
      "login_at": "2025-12-01T10:00:00.000000Z",
      "logout_at": null,
      "is_successful": true,
      "created_at": "2025-12-01T10:00:00.000000Z"
    },
    {
      "id": 2,
      "user_id": 2,
      "ip_address": "192.168.1.101",
      "user_agent": "Mozilla/5.0 (iPhone; CPU iPhone OS 14_0 like Mac OS X)",
      "device_type": "mobile",
      "browser": "Safari",
      "os": "iOS 14",
      "location": {
        "country": "Vietnam",
        "city": "Ha Noi"
      },
      "login_at": "2025-11-30T15:00:00.000000Z",
      "logout_at": "2025-11-30T18:00:00.000000Z",
      "is_successful": true,
      "created_at": "2025-11-30T15:00:00.000000Z"
    },
    {
      "id": 3,
      "user_id": 2,
      "ip_address": "192.168.1.102",
      "user_agent": "Unknown",
      "device_type": "unknown",
      "login_at": "2025-11-29T10:00:00.000000Z",
      "is_successful": false,
      "failure_reason": "Invalid password",
      "created_at": "2025-11-29T10:00:00.000000Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 20,
    "total": 50,
    "last_page": 3
  },
  "summary": {
    "total_logins": 50,
    "successful_logins": 48,
    "failed_logins": 2,
    "unique_devices": 3,
    "last_login": "2025-12-01T10:00:00.000000Z"
  }
}
```

---

## ADMIN APIs

## 2. Admin xem lịch sử đăng nhập của tất cả users – GET /admin/login-history

**Mục đích:** Admin xem tất cả lịch sử đăng nhập trong hệ thống.

### Request

**Method:** `GET`  
**URL:** `{{base_url}}/admin/login-history`

**Headers:**
```
Accept: application/json
Authorization: Bearer {{admin_token}}
```

**Query Parameters (optional):**
```
?page=1
&per_page=20
&user_id=2
&is_successful=true
&ip_address=192.168.1.100
&date_from=2025-01-01
&date_to=2025-12-31
&device_type=mobile
&sort=login_at
&order=desc
```

**Body:** Không cần

### Response mẫu

**Success (200):**
```json
{
  "data": [
    {
      "id": 1,
      "user_id": 2,
      "user": {
        "id": 2,
        "name": "Công ty ABC",
        "email": "seller1@tradehub.com",
        "role": "seller"
      },
      "ip_address": "192.168.1.100",
      "user_agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36",
      "device_type": "desktop",
      "browser": "Chrome 120",
      "os": "Windows 10",
      "location": {
        "country": "Vietnam",
        "city": "Ho Chi Minh",
        "region": "Ho Chi Minh City",
        "timezone": "Asia/Ho_Chi_Minh",
        "latitude": 10.8231,
        "longitude": 106.6297
      },
      "login_at": "2025-12-01T10:00:00.000000Z",
      "logout_at": null,
      "session_duration": null,
      "is_successful": true,
      "failure_reason": null,
      "created_at": "2025-12-01T10:00:00.000000Z"
    },
    {
      "id": 2,
      "user_id": 5,
      "user": {
        "id": 5,
        "name": "Nguyễn Văn B",
        "email": "buyer1@tradehub.com",
        "role": "buyer"
      },
      "ip_address": "192.168.1.101",
      "user_agent": "Mozilla/5.0 (iPhone; CPU iPhone OS 14_0 like Mac OS X)",
      "device_type": "mobile",
      "browser": "Safari",
      "os": "iOS 14",
      "location": {
        "country": "Vietnam",
        "city": "Ha Noi"
      },
      "login_at": "2025-12-01T09:30:00.000000Z",
      "logout_at": "2025-12-01T12:00:00.000000Z",
      "session_duration": 9000,
      "is_successful": true,
      "created_at": "2025-12-01T09:30:00.000000Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 20,
    "total": 500,
    "last_page": 25
  },
  "summary": {
    "total_logins_today": 150,
    "successful_logins_today": 145,
    "failed_logins_today": 5,
    "unique_users_today": 80,
    "device_breakdown": {
      "desktop": 90,
      "mobile": 55,
      "tablet": 5
    }
  }
}
```

**Error - Không phải Admin (403):**
```json
{
  "message": "This action is unauthorized.",
  "error": "Admin access required"
}
```

---

## 3. Admin xem lịch sử đăng nhập của một user cụ thể – GET /admin/users/{userId}/login-history

**Mục đích:** Admin xem chi tiết lịch sử đăng nhập của một user.

### Request

**Method:** `GET`  
**URL:** `{{base_url}}/admin/users/2/login-history`

**Headers:**
```
Accept: application/json
Authorization: Bearer {{admin_token}}
```

**Query Parameters (optional):**
```
?page=1
&per_page=20
&is_successful=true
&date_from=2025-01-01
&date_to=2025-12-31
```

**Body:** Không cần

### Response mẫu

**Success (200):**
```json
{
  "user": {
    "id": 2,
    "name": "Công ty ABC",
    "email": "seller1@tradehub.com",
    "role": "seller",
    "created_at": "2025-11-01T10:00:00.000000Z"
  },
  "data": [
    {
      "id": 1,
      "ip_address": "192.168.1.100",
      "user_agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36",
      "device_type": "desktop",
      "browser": "Chrome 120",
      "os": "Windows 10",
      "location": {
        "country": "Vietnam",
        "city": "Ho Chi Minh",
        "isp": "VNPT"
      },
      "login_at": "2025-12-01T10:00:00.000000Z",
      "logout_at": null,
      "is_successful": true,
      "created_at": "2025-12-01T10:00:00.000000Z"
    },
    {
      "id": 2,
      "ip_address": "192.168.1.100",
      "user_agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36",
      "device_type": "desktop",
      "browser": "Chrome 120",
      "os": "Windows 10",
      "location": {
        "country": "Vietnam",
        "city": "Ho Chi Minh"
      },
      "login_at": "2025-11-30T15:00:00.000000Z",
      "logout_at": "2025-11-30T18:00:00.000000Z",
      "session_duration": 10800,
      "is_successful": true,
      "created_at": "2025-11-30T15:00:00.000000Z"
    },
    {
      "id": 3,
      "ip_address": "103.1.2.3",
      "user_agent": "Unknown",
      "device_type": "unknown",
      "login_at": "2025-11-29T10:00:00.000000Z",
      "is_successful": false,
      "failure_reason": "Invalid password",
      "created_at": "2025-11-29T10:00:00.000000Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 20,
    "total": 50,
    "last_page": 3
  },
  "summary": {
    "total_logins": 50,
    "successful_logins": 48,
    "failed_logins": 2,
    "unique_ips": 5,
    "unique_devices": 3,
    "first_login": "2025-11-01T10:00:00.000000Z",
    "last_login": "2025-12-01T10:00:00.000000Z",
    "most_used_device": "desktop",
    "most_used_browser": "Chrome"
  }
}
```

**Error - User không tồn tại (404):**
```json
{
  "message": "User not found"
}
```

---

## Query Parameters Chi Tiết

| Parameter | Type | Description | Example |
|-----------|------|-------------|---------|
| `user_id` | integer | Lọc theo user (Admin only) | `?user_id=2` |
| `is_successful` | boolean | Lọc theo trạng thái | `?is_successful=true` |
| `ip_address` | string | Lọc theo IP | `?ip_address=192.168.1.100` |
| `device_type` | string | desktop, mobile, tablet | `?device_type=mobile` |
| `date_from` | date | Từ ngày | `?date_from=2025-01-01` |
| `date_to` | date | Đến ngày | `?date_to=2025-12-31` |
| `page` | integer | Số trang | `?page=1` |
| `per_page` | integer | Số item/trang | `?per_page=20` |
| `sort` | string | login_at, created_at | `?sort=login_at` |
| `order` | string | asc, desc | `?order=desc` |

---

## Device Types

- `desktop` - Máy tính để bàn
- `mobile` - Điện thoại
- `tablet` - Máy tính bảng
- `unknown` - Không xác định

---

## Failure Reasons

- `Invalid password` - Sai mật khẩu
- `Invalid email` - Email không tồn tại
- `Account locked` - Tài khoản bị khóa
- `Too many attempts` - Quá nhiều lần thử
- `Email not verified` - Email chưa xác thực

---

## Postman Tests Script

### Kiểm tra login history

```javascript
// Cho GET /login-history
if (pm.response.code === 200) {
    var jsonData = pm.response.json();
    console.log("Total logins:", jsonData.summary.total_logins);
    console.log("Last login:", jsonData.summary.last_login);
}
```

---

## Test Flow trong Postman

### User Flow:
1. **Login** → Tạo login history record
2. **GET /login-history** → Xem lịch sử đăng nhập của mình

### Admin Flow:
1. **Login as Admin** → Lưu admin_token
2. **GET /admin/login-history** → Xem tất cả lịch sử
3. **GET /admin/login-history?is_successful=false** → Xem các lần đăng nhập thất bại
4. **GET /admin/users/2/login-history** → Xem lịch sử của user cụ thể

---

## Use Cases

### Security Monitoring
- Phát hiện đăng nhập bất thường (IP lạ, thiết bị lạ)
- Theo dõi các lần đăng nhập thất bại
- Cảnh báo khi có nhiều lần thử đăng nhập sai

### User Analytics
- Thống kê thiết bị người dùng sử dụng
- Phân tích thời gian sử dụng
- Theo dõi vị trí địa lý

### Compliance
- Lưu trữ lịch sử truy cập
- Audit trail cho security
- Báo cáo cho quản lý

---

## Lưu ý

- Login history tự động tạo khi user đăng nhập
- Lưu trữ IP, device, location để security
- User chỉ xem được lịch sử của mình
- Admin xem được tất cả lịch sử
- Session duration tính bằng giây
- Failed logins giúp phát hiện tấn công brute force
- Nên giới hạn số lần đăng nhập sai (rate limiting)
