# API Identity Verification - Xác minh danh tính

**Base URL:** `{{base_url}}` = `http://localhost:8000/api`

---

## 1. Lấy thông tin hồ sơ người dùng – GET /identity/profile

**Mục đích:** Xem thông tin hồ sơ xác thực của **chính mình** (user đang đăng nhập).

**⚠️ Lưu ý:** API này lấy profile của user từ token, không cần truyền user ID.

### Request

**Method:** `GET`  
**URL:** `{{base_url}}/identity/profile`

**URL thực tế:** `http://localhost:8000/api/identity/profile`

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
  "status": "success",
  "data": {
    "id": 1,
    "user_id": 2,
    "identity_type": "personal",
    "full_name": "Nguyễn Văn A",
    "date_of_birth": "1990-01-01",
    "business_name": null,
    "business_license": null,
    "address": "123 Nguyễn Huệ, Q1, TP.HCM",
    "phone": "0901234567",
    "identity_status": "pending",
    "verified_at": null,
    "created_at": "2025-12-01T10:00:00.000000Z",
    "updated_at": "2025-12-01T10:00:00.000000Z"
  }
}
```

**Error - Chưa có profile (404):**
```json
{
  "status": "error",
  "message": "Identity profile not found"
}
```

**Identity Status:**
- `pending` - Đang chờ duyệt
- `verified` - Đã xác minh
- `rejected` - Bị từ chối
- `null` - Chưa gửi yêu cầu

---

## 2. Cập nhật thông tin hồ sơ – PUT /identity/profile

**Mục đích:** User cập nhật thông tin hồ sơ **của chính mình**.

**⚠️ Lưu ý:** API này cập nhật profile của user từ token, không cần truyền user ID.

### Request

**Method:** `PUT`  
**URL:** `{{base_url}}/identity/profile`

**URL thực tế:** `http://localhost:8000/api/identity/profile`

**Headers:**
```
Content-Type: application/json
Accept: application/json
Authorization: Bearer {{token}}
```

**Body → raw → JSON:**
```json
{
  "identity_type": "personal",
  "full_name": "Nguyễn Văn A",
  "date_of_birth": "1990-01-01",
  "phone": "0901234567",
  "address": "123 Nguyễn Huệ, Quận 1, TP.HCM"
}
```

**Hoặc cho doanh nghiệp:**
```json
{
  "identity_type": "business",
  "full_name": "Nguyễn Văn A",
  "business_name": "Công ty TNHH ABC",
  "business_license": "0123456789",
  "phone": "0901234567",
  "address": "123 Nguyễn Huệ, Quận 1, TP.HCM"
}
```

**Optional fields:**
- `identity_type` - personal hoặc business
- `full_name` - Họ tên đầy đủ
- `date_of_birth` - Ngày sinh (cho personal)
- `business_name` - Tên doanh nghiệp (cho business)
- `business_license` - Mã số doanh nghiệp (cho business)
- `address` - Địa chỉ
- `phone` - Số điện thoại

### Response mẫu

**Success (200):**
```json
{
  "status": "success",
  "message": "Identity profile updated successfully",
  "data": {
    "id": 1,
    "user_id": 2,
    "identity_type": "personal",
    "full_name": "Nguyễn Văn A",
    "date_of_birth": "1990-01-01",
    "phone": "0901234567",
    "address": "123 Nguyễn Huệ, Quận 1, TP.HCM",
    "created_at": "2025-12-01T10:00:00.000000Z",
    "updated_at": "2025-12-01T11:00:00.000000Z"
  }
}
```

**Error - Validation (400):**
```json
{
  "status": "error",
  "message": "Invalid input data",
  "errors": {
    "identity_type": ["The identity type must be personal or business."],
    "phone": ["The phone field must not be greater than 32 characters."]
  }
}
```

---

## 3. Gửi yêu cầu xác minh danh tính – POST /identity/verify-request

**Mục đích:** User gửi yêu cầu xác minh danh tính với tài liệu (CCCD/CMND/Giấy phép kinh doanh).

**⚠️ Lưu ý:** API này gửi yêu cầu xác minh cho chính user đang đăng nhập.

### Request

**Method:** `POST`  
**URL:** `{{base_url}}/identity/verify-request`

**URL thực tế:** `http://localhost:8000/api/identity/verify-request`

**Headers:**
```
Content-Type: application/json
Accept: application/json
Authorization: Bearer {{token}}
```

**Body → raw → JSON:**
```json
{
  "document_type": "id_card",
  "document_url": "https://example.com/uploads/cccd-front.jpg"
}
```

**Required fields:**
- `document_type` - Loại tài liệu: `id_card`, `business_license`, `tax_code`
- `document_url` - URL ảnh tài liệu đã upload

### Response mẫu

**Success (201):**
```json
{
  "status": "success",
  "message": "Verification request submitted successfully",
  "data": {
    "id": 1,
    "user_id": 2,
    "document_type": "id_card",
    "document_url": "https://example.com/uploads/cccd-front.jpg",
    "status": "pending",
    "created_at": "2025-12-01T10:00:00.000000Z",
    "updated_at": "2025-12-01T10:00:00.000000Z"
  }
}
```

**Error - Đã có yêu cầu pending (409):**
```json
{
  "status": "error",
  "message": "A verification request is already pending approval"
}
```

**Error - Đã được xác minh (409):**
```json
{
  "status": "error",
  "message": "Business already verified"
}
```

**Error - Validation (400):**
```json
{
  "status": "error",
  "message": "Missing required information",
  "errors": {
    "document_type": ["The document type field is required."],
    "document_url": ["The document url field is required."]
  }
}
```

---

## 4. Xem lịch sử xác minh danh tính – GET /identity/verify-history

**Mục đích:** Xem tất cả các lần gửi yêu cầu xác minh **của chính mình**.

**⚠️ Lưu ý:** API này lấy lịch sử của user từ token, không cần truyền user ID.

### Request

**Method:** `GET`  
**URL:** `{{base_url}}/identity/verify-history`

**URL thực tế:** `http://localhost:8000/api/identity/verify-history`

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
  "status": "success",
  "data": [
    {
      "id": 1,
      "user_id": 2,
      "document_type": "id_card",
      "document_url": "https://example.com/uploads/cccd-front.jpg",
      "status": "approved",
      "admin_note": "Hồ sơ hợp lệ",
      "approved_by": 1,
      "created_at": "2025-12-01T10:00:00.000000Z",
      "updated_at": "2025-12-01T15:00:00.000000Z"
    },
    {
      "id": 2,
      "user_id": 2,
      "document_type": "business_license",
      "document_url": "https://example.com/uploads/gpkd.jpg",
      "status": "rejected",
      "admin_note": "Ảnh không rõ, vui lòng gửi lại",
      "approved_by": 1,
      "created_at": "2025-11-25T10:00:00.000000Z",
      "updated_at": "2025-11-25T15:00:00.000000Z"
    }
  ],
  "total": 2
}
```

---

## ADMIN APIs

## 5. Admin xem danh sách tất cả yêu cầu xác minh – GET /identity/verify-requests

**Mục đích:** Admin xem tất cả yêu cầu xác minh danh tính.

### Request

**Method:** `GET`  
**URL:** `{{base_url}}/identity/verify-requests`

**Headers:**
```
Accept: application/json
Authorization: Bearer {{admin_token}}
```

**Query Parameters (optional):**
```
?page=1
&per_page=20
&status=pending
&search=nguyen
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
      "user": {
        "id": 2,
        "name": "Công ty ABC",
        "email": "seller1@tradehub.com"
      },
      "full_name": "Nguyễn Văn A",
      "id_type": "cccd",
      "id_number": "001234567890",
      "id_front_image": "https://example.com/id-front.jpg",
      "id_back_image": "https://example.com/id-back.jpg",
      "selfie_image": "https://example.com/selfie.jpg",
      "verification_status": "pending",
      "created_at": "2025-12-01T10:00:00.000000Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 20,
    "total": 50,
    "last_page": 3
  }
}
```

---

## 6. Admin xem chi tiết một yêu cầu xác minh – GET /identity/verify-requests/{id}

**Mục đích:** Admin xem chi tiết yêu cầu để duyệt.

### Request

**Method:** `GET`  
**URL:** `{{base_url}}/identity/verify-requests/1`

**Headers:**
```
Accept: application/json
Authorization: Bearer {{admin_token}}
```

**Body:** Không cần

### Response mẫu

**Success (200):**
```json
{
  "id": 1,
  "user_id": 2,
  "user": {
    "id": 2,
    "name": "Công ty ABC",
    "email": "seller1@tradehub.com",
    "phone": "0901234567",
    "role": "seller",
    "created_at": "2025-11-01T10:00:00.000000Z"
  },
  "full_name": "Nguyễn Văn A",
  "id_type": "cccd",
  "id_number": "001234567890",
  "id_front_image": "https://example.com/id-front.jpg",
  "id_back_image": "https://example.com/id-back.jpg",
  "selfie_image": "https://example.com/selfie.jpg",
  "address": "123 Nguyễn Huệ, Q1, TP.HCM",
  "date_of_birth": "1990-01-01",
  "verification_status": "pending",
  "admin_notes": null,
  "verified_at": null,
  "created_at": "2025-12-01T10:00:00.000000Z",
  "updated_at": "2025-12-01T10:00:00.000000Z"
}
```

---

## 7. Duyệt yêu cầu xác minh (Admin) – PUT /identity/verify-request/:id/approve

**Mục đích:** Admin duyệt yêu cầu xác minh danh tính.

### Request

**Method:** `PUT`  
**URL:** `{{base_url}}/identity/verify-request/:id/approve`

**Path Variables:**
- `id` = `1` (ID của verification request)

**URL thực tế:** `http://localhost:8000/api/identity/verify-request/1/approve`

**Headers:**
```
Content-Type: application/json
Accept: application/json
Authorization: Bearer {{admin_token}}
```

**Body → raw → JSON:**
```json
{
  "admin_notes": "Hồ sơ hợp lệ, đã xác minh thành công"
}
```

### Response mẫu

**Success (200):**
```json
{
  "status": "success",
  "message": "Verification request approved successfully",
  "data": {
    "id": 1,
    "user_id": 2,
    "document_type": "id_card",
    "document_url": "https://example.com/uploads/cccd-front.jpg",
    "status": "approved",
    "admin_note": "Hồ sơ hợp lệ, đã xác minh thành công",
    "approved_by": 1,
    "created_at": "2025-12-01T10:00:00.000000Z",
    "updated_at": "2025-12-01T15:00:00.000000Z"
  }
}
```

**Error - Not found (404):**
```json
{
  "status": "error",
  "message": "Verification request not found"
}
```

---

## 8. Từ chối yêu cầu xác minh (Admin) – PUT /identity/verify-request/:id/reject

**Mục đích:** Admin từ chối yêu cầu xác minh với lý do.

### Request

**Method:** `PUT`  
**URL:** `{{base_url}}/identity/verify-request/:id/reject`

**Path Variables:**
- `id` = `1` (ID của verification request)

**URL thực tế:** `http://localhost:8000/api/identity/verify-request/1/reject`

**Headers:**
```
Content-Type: application/json
Accept: application/json
Authorization: Bearer {{admin_token}}
```

**Body → raw → JSON:**
```json
{
  "admin_notes": "Ảnh CCCD không rõ ràng, vui lòng chụp lại và gửi lại yêu cầu"
}
```

**Required fields:**
- `admin_notes` - Lý do từ chối (bắt buộc)

### Response mẫu

**Success (200):**
```json
{
  "status": "success",
  "message": "Verification request rejected",
  "data": {
    "id": 1,
    "user_id": 2,
    "document_type": "id_card",
    "document_url": "https://example.com/uploads/cccd-front.jpg",
    "status": "rejected",
    "admin_note": "Ảnh CCCD không rõ ràng, vui lòng chụp lại và gửi lại yêu cầu",
    "approved_by": 1,
    "created_at": "2025-12-01T10:00:00.000000Z",
    "updated_at": "2025-12-01T15:00:00.000000Z"
  }
}
```

**Error - Missing admin_note (400):**
```json
{
  "status": "error",
  "message": "Admin note is required when rejecting",
  "errors": {
    "admin_note": ["The admin note field is required."]
  }
}
```

**Error - Not found (404):**
```json
{
  "status": "error",
  "message": "Verification request not found"
}
```

---

## Query Parameters Chi Tiết (Admin)

| Parameter | Type | Description | Example |
|-----------|------|-------------|---------|
| `status` | string | pending, approved, rejected | `?status=pending` |
| `search` | string | Tìm theo tên, email, số CCCD | `?search=nguyen` |
| `date_from` | date | Từ ngày | `?date_from=2025-01-01` |
| `date_to` | date | Đến ngày | `?date_to=2025-12-31` |
| `page` | integer | Số trang | `?page=1` |
| `per_page` | integer | Số item/trang | `?per_page=20` |

---

## Postman Tests Script

### Lưu verification request ID

```javascript
// Cho POST /identity/verify-request
if (pm.response.code === 201) {
    var jsonData = pm.response.json();
    if (jsonData.data && jsonData.data.id) {
        pm.environment.set("verify_request_id", jsonData.data.id);
        console.log("Verification request ID saved:", jsonData.data.id);
    }
}
```

---

## Test Flow trong Postman

### User Flow:
1. **Login as Seller** → Lưu token
2. **GET /identity/profile** → Xem hồ sơ hiện tại
3. **PUT /identity/profile** → Cập nhật thông tin cơ bản
4. **POST /identity/verify-request** → Gửi yêu cầu xác minh
5. **GET /identity/verify-history** → Xem lịch sử

### Admin Flow:
1. **Login as Admin** → Lưu admin_token
2. **GET /identity/verify-requests?status=pending** → Xem yêu cầu chờ duyệt
3. **GET /identity/verify-requests/1** → Xem chi tiết yêu cầu
4. **PUT /identity/verify-request/1/approve** → Duyệt yêu cầu
   HOẶC
   **PUT /identity/verify-request/1/reject** → Từ chối yêu cầu

---

## Lưu ý

- User chỉ có thể có 1 yêu cầu pending tại một thời điểm
- Nếu bị reject, user có thể gửi lại yêu cầu mới
- Ảnh nên upload lên server trước, sau đó gửi URL
- Admin notes bắt buộc khi reject
- Sau khi approved, user không thể gửi yêu cầu mới
