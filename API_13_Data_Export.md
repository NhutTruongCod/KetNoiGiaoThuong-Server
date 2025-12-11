# API Data Export - Xuất dữ liệu cá nhân

**Base URL:** `{{base_url}}` = `http://localhost:8000/api`

---

## 1. Gửi yêu cầu xuất dữ liệu cá nhân – POST /data/export/request

**Mục đích:** User yêu cầu xuất dữ liệu cá nhân (GDPR compliance).

### Request

**Method:** `POST`  
**URL:** `{{base_url}}/data/export/request`

**Headers:**
```
Content-Type: application/json
Accept: application/json
Authorization: Bearer {{token}}
```

**Body → raw → JSON:**
```json
{
  "data_types": [
    "profile",
    "listings",
    "orders",
    "reviews",
    "messages",
    "payments"
  ],
  "format": "csv",
  "date_from": "2025-01-01",
  "date_to": "2025-12-31",
  "include_deleted": false
}
```

**Required fields:**
- `data_types` - Loại dữ liệu cần xuất (array)
- `format` - Định dạng: `csv`, `json`, `xlsx`

**Optional fields:**
- `date_from` - Từ ngày
- `date_to` - Đến ngày
- `include_deleted` - Bao gồm dữ liệu đã xóa (default: false)

**Data Types có thể xuất:**
- `profile` - Thông tin cá nhân
- `listings` - Tin đăng
- `orders` - Đơn hàng
- `reviews` - Đánh giá
- `messages` - Tin nhắn
- `payments` - Thanh toán
- `notifications` - Thông báo
- `login_history` - Lịch sử đăng nhập
- `all` - Tất cả dữ liệu

### Response mẫu

**Success (201):**
```json
{
  "message": "Data export request created successfully. We will process your request and notify you when it's ready.",
  "data": {
    "id": 1,
    "user_id": 2,
    "data_types": [
      "profile",
      "listings",
      "orders",
      "reviews"
    ],
    "format": "csv",
    "status": "pending",
    "date_from": "2025-01-01",
    "date_to": "2025-12-31",
    "estimated_completion": "2025-12-01T11:00:00.000000Z",
    "created_at": "2025-12-01T10:00:00.000000Z"
  }
}
```

**Error - Đã có yêu cầu pending (400):**
```json
{
  "message": "You already have a pending export request. Please wait for it to complete."
}
```

---

## 2. Kiểm tra trạng thái yêu cầu xuất dữ liệu – GET /data/export/status/{id}

**Mục đích:** Kiểm tra tiến trình xử lý yêu cầu xuất dữ liệu.

### Request

**Method:** `GET`  
**URL:** `{{base_url}}/data/export/status/1`

**Headers:**
```
Accept: application/json
Authorization: Bearer {{token}}
```

**Body:** Không cần

### Response mẫu

**Success - Processing (200):**
```json
{
  "id": 1,
  "user_id": 2,
  "data_types": [
    "profile",
    "listings",
    "orders",
    "reviews"
  ],
  "format": "csv",
  "status": "processing",
  "progress": 45,
  "current_step": "Exporting orders data...",
  "estimated_completion": "2025-12-01T11:00:00.000000Z",
  "created_at": "2025-12-01T10:00:00.000000Z",
  "updated_at": "2025-12-01T10:30:00.000000Z"
}
```

**Success - Completed (200):**
```json
{
  "id": 1,
  "user_id": 2,
  "data_types": [
    "profile",
    "listings",
    "orders",
    "reviews"
  ],
  "format": "csv",
  "status": "completed",
  "progress": 100,
  "file_size": 2048576,
  "file_size_human": "2 MB",
  "download_url": "https://example.com/exports/user-2-export-20251201.zip",
  "expires_at": "2025-12-08T10:00:00.000000Z",
  "completed_at": "2025-12-01T10:45:00.000000Z",
  "created_at": "2025-12-01T10:00:00.000000Z"
}
```

**Export Status:**
- `pending` - Chờ xử lý
- `processing` - Đang xử lý
- `completed` - Hoàn thành
- `failed` - Thất bại
- `expired` - Đã hết hạn

---

## 3. Lấy thông tin tải file dữ liệu đã xuất – GET /data/export/download/{id}

**Mục đích:** Download file dữ liệu đã xuất.

### Request

**Method:** `GET`  
**URL:** `{{base_url}}/data/export/download/1`

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
  "download_url": "https://example.com/exports/user-2-export-20251201.zip",
  "file_name": "user-2-export-20251201.zip",
  "file_size": 2048576,
  "file_size_human": "2 MB",
  "format": "csv",
  "expires_at": "2025-12-08T10:00:00.000000Z",
  "downloads_count": 0,
  "max_downloads": 5
}
```

**Lưu ý:** 
- File có thời hạn 7 ngày
- Tối đa download 5 lần
- Sau đó file sẽ bị xóa vĩnh viễn

**Error - File đã hết hạn (410):**
```json
{
  "message": "Export file has expired. Please create a new export request."
}
```

**Error - Vượt quá số lần download (429):**
```json
{
  "message": "Maximum download limit reached"
}
```

---

## 4. Hủy một yêu cầu xuất dữ liệu đang xử lý – DELETE /data/export/cancel/{id}

**Mục đích:** Hủy yêu cầu xuất dữ liệu đang pending hoặc processing.

### Request

**Method:** `DELETE`  
**URL:** `{{base_url}}/data/export/cancel/1`

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
  "message": "Export request cancelled successfully",
  "data": {
    "id": 1,
    "status": "cancelled",
    "cancelled_at": "2025-12-01T10:30:00.000000Z"
  }
}
```

**Error - Không thể hủy (400):**
```json
{
  "message": "Cannot cancel completed or expired export request"
}
```

---

## 5. Xem lịch sử yêu cầu xuất dữ liệu – GET /data/export/history

**Mục đích:** Xem tất cả các yêu cầu xuất dữ liệu đã tạo.

### Request

**Method:** `GET`  
**URL:** `{{base_url}}/data/export/history`

**Headers:**
```
Accept: application/json
Authorization: Bearer {{token}}
```

**Query Parameters (optional):**
```
?page=1
&per_page=20
&status=completed
```

**Body:** Không cần

### Response mẫu

**Success (200):**
```json
{
  "data": [
    {
      "id": 1,
      "data_types": [
        "profile",
        "listings",
        "orders"
      ],
      "format": "csv",
      "status": "completed",
      "file_size": 2048576,
      "file_size_human": "2 MB",
      "download_url": "https://example.com/exports/user-2-export-20251201.zip",
      "expires_at": "2025-12-08T10:00:00.000000Z",
      "downloads_count": 2,
      "completed_at": "2025-12-01T10:45:00.000000Z",
      "created_at": "2025-12-01T10:00:00.000000Z"
    },
    {
      "id": 2,
      "data_types": [
        "all"
      ],
      "format": "json",
      "status": "processing",
      "progress": 60,
      "current_step": "Exporting messages...",
      "created_at": "2025-11-30T10:00:00.000000Z"
    },
    {
      "id": 3,
      "data_types": [
        "orders",
        "payments"
      ],
      "format": "xlsx",
      "status": "expired",
      "completed_at": "2025-11-20T10:00:00.000000Z",
      "expired_at": "2025-11-27T10:00:00.000000Z",
      "created_at": "2025-11-20T09:00:00.000000Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 20,
    "total": 10,
    "last_page": 1
  }
}
```

---

## Export File Structure

### CSV Format
```
user-2-export-20251201.zip
├── profile.csv
├── listings.csv
├── orders.csv
├── reviews.csv
├── messages.csv
└── README.txt
```

### JSON Format
```
user-2-export-20251201.zip
├── profile.json
├── listings.json
├── orders.json
├── reviews.json
├── messages.json
└── README.txt
```

### XLSX Format
```
user-2-export-20251201.zip
└── user-data-export.xlsx
    ├── Profile (sheet)
    ├── Listings (sheet)
    ├── Orders (sheet)
    ├── Reviews (sheet)
    └── Messages (sheet)
```

---

## Query Parameters Chi Tiết

| Parameter | Type | Description | Example |
|-----------|------|-------------|---------|
| `status` | string | pending, processing, completed, failed, expired | `?status=completed` |
| `page` | integer | Số trang | `?page=1` |
| `per_page` | integer | Số item/trang | `?per_page=20` |

---

## Postman Tests Script

### Lưu export request ID

```javascript
// Cho POST /data/export/request
if (pm.response.code === 201) {
    var jsonData = pm.response.json();
    if (jsonData.data && jsonData.data.id) {
        pm.environment.set("export_request_id", jsonData.data.id);
        console.log("Export request ID:", jsonData.data.id);
    }
}
```

### Kiểm tra status và lưu download URL

```javascript
// Cho GET /data/export/status/{id}
if (pm.response.code === 200) {
    var jsonData = pm.response.json();
    console.log("Status:", jsonData.status);
    console.log("Progress:", jsonData.progress + "%");
    
    if (jsonData.status === "completed" && jsonData.download_url) {
        pm.environment.set("export_download_url", jsonData.download_url);
        console.log("Download URL:", jsonData.download_url);
    }
}
```

---

## Test Flow trong Postman

1. **Login** → Lưu token
2. **POST /data/export/request** → Tạo yêu cầu xuất dữ liệu
3. **GET /data/export/status/{id}** → Kiểm tra trạng thái (polling)
4. **GET /data/export/download/{id}** → Download file khi completed
5. **GET /data/export/history** → Xem lịch sử
6. **DELETE /data/export/cancel/{id}** → Hủy yêu cầu (nếu cần)

---

## Processing Time

Thời gian xử lý phụ thuộc vào:
- Số lượng dữ liệu
- Loại dữ liệu
- Định dạng file

**Ước tính:**
- Profile only: ~1 phút
- Listings + Orders: ~5 phút
- All data: ~10-15 phút

---

## GDPR Compliance

API này tuân thủ GDPR (General Data Protection Regulation):
- User có quyền xuất dữ liệu cá nhân
- Dữ liệu được mã hóa khi lưu trữ
- File tự động xóa sau 7 ngày
- Giới hạn số lần download
- Log tất cả các lần truy cập

---

## Lưu ý

- Chỉ có thể có 1 yêu cầu pending/processing tại một thời điểm
- File có thời hạn 7 ngày
- Tối đa download 5 lần
- Xử lý bất đồng bộ (background job)
- Nhận notification khi hoàn thành
- Format CSV dễ mở bằng Excel
- Format JSON dễ xử lý bằng code
- Format XLSX có nhiều sheets
- File được nén thành ZIP
- Bao gồm README.txt hướng dẫn
