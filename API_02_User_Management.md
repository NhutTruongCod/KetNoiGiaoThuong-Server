# API User Management - Quản lý người dùng

**Base URL:** `{{base_url}}` = `http://localhost:8000/api`

---

## 1. Lấy thông tin user hiện tại – GET /user

**Mục đích:** Lấy thông tin chi tiết của user đang đăng nhập.

### Request

**Method:** `GET`  
**URL:** `{{base_url}}/user`

**Headers:**
```
Accept: application/json
Authorization: Bearer {{token}}
```

**Body:** Không cần

### Response mẫu

**Success (200):**
```json
{
  "id": 1,
  "name": "Công ty ABC",
  "email": "abc@example.com",
  "phone": "0989123456",
  "role": "seller",
  "email_verified_at": "2025-12-01T10:00:00.000000Z",
  "is_verified": true,
  "created_at": "2025-12-01T09:00:00.000000Z",
  "updated_at": "2025-12-01T10:00:00.000000Z"
}
```

**Error - Chưa đăng nhập (401):**
```json
{
  "message": "Unauthenticated."
}
```

---

## 2. Danh sách tất cả users (Admin) – GET /admin/users

**Mục đích:** Lấy danh sách tất cả users trong hệ thống (chỉ Admin).

### Request

**Method:** `GET`  
**URL:** `{{base_url}}/admin/users`

**Headers:**
```
Accept: application/json
Authorization: Bearer {{admin_token}}
```

**Query Parameters (optional):**
```
?page=1
&per_page=20
&search=abc
&role=seller
&is_verified=true
&sort=created_at
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
      "name": "Công ty ABC",
      "email": "abc@example.com",
      "phone": "0989123456",
      "role": "seller",
      "is_verified": true,
      "email_verified_at": "2025-12-01T10:00:00.000000Z",
      "created_at": "2025-12-01T09:00:00.000000Z",
      "last_login_at": "2025-12-01T11:00:00.000000Z"
    },
    {
      "id": 2,
      "name": "Nguyễn Văn A",
      "email": "buyer@example.com",
      "phone": "0987654321",
      "role": "buyer",
      "is_verified": true,
      "email_verified_at": "2025-12-01T10:30:00.000000Z",
      "created_at": "2025-12-01T09:30:00.000000Z",
      "last_login_at": "2025-12-01T12:00:00.000000Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 20,
    "total": 150,
    "last_page": 8,
    "from": 1,
    "to": 20
  },
  "links": {
    "first": "http://localhost:8000/api/admin/users?page=1",
    "last": "http://localhost:8000/api/admin/users?page=8",
    "prev": null,
    "next": "http://localhost:8000/api/admin/users?page=2"
  }
}
```

**Error - Không có quyền (403):**
```json
{
  "message": "This action is unauthorized.",
  "error": "Admin access required"
}
```

**Error - Chưa đăng nhập (401):**
```json
{
  "message": "Unauthenticated."
}
```

---

## Query Parameters Chi Tiết

### Tìm kiếm và lọc

| Parameter | Type | Description | Example |
|-----------|------|-------------|---------|
| `search` | string | Tìm theo tên, email, phone | `?search=abc` |
| `role` | string | Lọc theo role: buyer, seller, admin | `?role=seller` |
| `is_verified` | boolean | Lọc theo trạng thái xác thực | `?is_verified=true` |
| `page` | integer | Số trang | `?page=1` |
| `per_page` | integer | Số item/trang (max 100) | `?per_page=20` |
| `sort` | string | Sắp xếp theo field | `?sort=created_at` |
| `order` | string | Thứ tự: asc, desc | `?order=desc` |

### Ví dụ kết hợp

```
GET {{base_url}}/admin/users?search=abc&role=seller&is_verified=true&page=1&per_page=20&sort=created_at&order=desc
```

---

## User Roles

| Role | Value | Description |
|------|-------|-------------|
| Buyer | `buyer` | Người mua - Chỉ được mua hàng, không đăng tin |
| Seller | `seller` | Người bán - Có thể đăng tin, quản lý shop |
| Admin | `admin` | Quản trị viên - Toàn quyền hệ thống |

---

## Postman Tests Script

### Lưu user info vào environment

```javascript
// Cho GET /user
if (pm.response.code === 200) {
    var jsonData = pm.response.json();
    pm.environment.set("user_id", jsonData.id);
    pm.environment.set("user_role", jsonData.role);
    pm.environment.set("user_email", jsonData.email);
    console.log("User info saved:", jsonData);
}
```

---

## Test Flow trong Postman

1. **Login** → Lưu token
2. **GET /user** → Kiểm tra thông tin user hiện tại
3. **Login as Admin** → Lưu admin_token
4. **GET /admin/users** → Xem danh sách tất cả users

---

## Lưu ý

- API `/user` yêu cầu token hợp lệ
- API `/admin/users` chỉ dành cho Admin
- Buyer cố gắng truy cập `/admin/users` sẽ nhận lỗi 403
- Token hết hạn sẽ nhận lỗi 401, cần refresh hoặc login lại
