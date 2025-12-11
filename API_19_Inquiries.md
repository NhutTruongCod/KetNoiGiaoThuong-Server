# API Inquiries - Yêu cầu tư vấn

**Base URL:** `{{base_url}}` = `http://localhost:8000/api`

---

## 1. Gửi yêu cầu tư vấn – POST /inquiries

**Mục đích:** User gửi yêu cầu tư vấn về sản phẩm (không cần đăng nhập).

### Request

**Method:** `POST`  
**URL:** `{{base_url}}/inquiries`

**Headers:**
```
Content-Type: application/json
Accept: application/json
```

**Body → raw → JSON:**
```json
{
  "listing_id": 123,
  "name": "Nguyễn Văn B",
  "email": "nguyenvanb@example.com",
  "phone": "0987654321",
  "message": "Tôi muốn đặt hàng số lượng lớn, có giảm giá không?",
  "preferred_contact": "phone"
}
```

**Required fields:**
- `listing_id` - ID tin đăng
- `name` - Tên người gửi
- `email` - Email
- `phone` - Số điện thoại
- `message` - Nội dung yêu cầu

**Optional fields:**
- `preferred_contact` - Cách liên hệ ưu tiên: `email`, `phone`, `both`
- `company_name` - Tên công ty (nếu có)
- `quantity` - Số lượng muốn mua

### Response mẫu

**Success (201):**
```json
{
  "message": "Inquiry submitted successfully. The seller will contact you soon.",
  "data": {
    "id": 1,
    "listing_id": 123,
    "listing": {
      "id": 123,
      "title": "iPhone 15 Pro Max 256GB",
      "shop": {
        "id": 1,
        "name": "Tech Store"
      }
    },
    "name": "Nguyễn Văn B",
    "email": "nguyenvanb@example.com",
    "phone": "0987654321",
    "message": "Tôi muốn đặt hàng số lượng lớn, có giảm giá không?",
    "status": "pending",
    "created_at": "2025-12-01T10:00:00.000000Z"
  }
}
```

**Error - Validation (422):**
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "email": ["The email field is required."],
    "phone": ["The phone format is invalid."]
  }
}
```

---

## 2. Danh sách yêu cầu tư vấn (Seller) – GET /inquiries

**Mục đích:** Seller xem danh sách yêu cầu tư vấn về sản phẩm của mình.

### Request

**Method:** `GET`  
**URL:** `{{base_url}}/inquiries`

**Headers:**
```
Accept: application/json
Authorization: Bearer {{seller_token}}
```

**Query Parameters (optional):**
```
?page=1
&per_page=20
&status=pending
&listing_id=123
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
      "listing_id": 123,
      "listing": {
        "id": 123,
        "title": "iPhone 15 Pro Max 256GB",
        "price": 29990000,
        "image": "https://example.com/image.jpg"
      },
      "name": "Nguyễn Văn B",
      "email": "nguyenvanb@example.com",
      "phone": "0987654321",
      "company_name": "Công ty ABC",
      "message": "Tôi muốn đặt hàng số lượng lớn, có giảm giá không?",
      "quantity": 100,
      "preferred_contact": "phone",
      "status": "pending",
      "notes": null,
      "contacted_at": null,
      "created_at": "2025-12-01T10:00:00.000000Z"
    },
    {
      "id": 2,
      "listing_id": 124,
      "listing": {
        "id": 124,
        "title": "Samsung Galaxy S24 Ultra",
        "price": 27990000,
        "image": "https://example.com/image2.jpg"
      },
      "name": "Trần Thị C",
      "email": "tranthic@example.com",
      "phone": "0912345678",
      "message": "Sản phẩm có màu xanh không?",
      "status": "contacted",
      "notes": "Đã gọi điện, khách hàng sẽ mua vào tuần sau",
      "contacted_at": "2025-12-01T14:00:00.000000Z",
      "created_at": "2025-11-30T15:00:00.000000Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 20,
    "total": 25,
    "last_page": 2
  },
  "summary": {
    "total_inquiries": 25,
    "pending": 10,
    "contacted": 12,
    "completed": 3
  }
}
```

**Inquiry Status:**
- `pending` - Chờ xử lý
- `contacted` - Đã liên hệ
- `completed` - Hoàn thành
- `cancelled` - Đã hủy

---

## 3. Chi tiết yêu cầu tư vấn (Seller) – GET /inquiries/{id}

**Mục đích:** Seller xem chi tiết một yêu cầu tư vấn.

### Request

**Method:** `GET`  
**URL:** `{{base_url}}/inquiries/1`

**Headers:**
```
Accept: application/json
Authorization: Bearer {{seller_token}}
```

**Body:** Không cần

### Response mẫu

**Success (200):**
```json
{
  "id": 1,
  "listing_id": 123,
  "listing": {
    "id": 123,
    "title": "iPhone 15 Pro Max 256GB",
    "slug": "iphone-15-pro-max-256gb",
    "price": 29990000,
    "stock_qty": 50,
    "images": ["https://example.com/image.jpg"],
    "shop_id": 1
  },
  "name": "Nguyễn Văn B",
  "email": "nguyenvanb@example.com",
  "phone": "0987654321",
  "company_name": "Công ty ABC",
  "message": "Tôi muốn đặt hàng số lượng lớn (100 chiếc), có giảm giá không? Và thời gian giao hàng là bao lâu?",
  "quantity": 100,
  "preferred_contact": "phone",
  "status": "pending",
  "notes": null,
  "contacted_at": null,
  "ip_address": "192.168.1.100",
  "user_agent": "Mozilla/5.0...",
  "created_at": "2025-12-01T10:00:00.000000Z",
  "updated_at": "2025-12-01T10:00:00.000000Z"
}
```

---

## 4. Cập nhật trạng thái yêu cầu (Seller) – PUT /inquiries/{id}

**Mục đích:** Seller cập nhật trạng thái và ghi chú.

### Request

**Method:** `PUT`  
**URL:** `{{base_url}}/inquiries/1`

**Headers:**
```
Content-Type: application/json
Accept: application/json
Authorization: Bearer {{seller_token}}
```

**Body → raw → JSON:**
```json
{
  "status": "contacted",
  "notes": "Đã gọi điện tư vấn. Khách hàng đồng ý mua 100 chiếc với giá 28.5 triệu/chiếc. Sẽ giao hàng trong 5 ngày."
}
```

### Response mẫu

**Success (200):**
```json
{
  "message": "Inquiry updated successfully",
  "data": {
    "id": 1,
    "status": "contacted",
    "notes": "Đã gọi điện tư vấn. Khách hàng đồng ý mua 100 chiếc với giá 28.5 triệu/chiếc. Sẽ giao hàng trong 5 ngày.",
    "contacted_at": "2025-12-01T14:00:00.000000Z",
    "updated_at": "2025-12-01T14:00:00.000000Z"
  }
}
```

---

## 5. Xóa yêu cầu tư vấn (Seller) – DELETE /inquiries/{id}

**Mục đích:** Seller xóa yêu cầu tư vấn spam.

### Request

**Method:** `DELETE`  
**URL:** `{{base_url}}/inquiries/1`

**Headers:**
```
Accept: application/json
Authorization: Bearer {{seller_token}}
```

**Body:** Không cần

### Response mẫu

**Success (200):**
```json
{
  "message": "Inquiry deleted successfully"
}
```

---

## Query Parameters Chi Tiết

| Parameter | Type | Description | Example |
|-----------|------|-------------|---------|
| `status` | string | pending, contacted, completed, cancelled | `?status=pending` |
| `listing_id` | integer | Lọc theo tin đăng | `?listing_id=123` |
| `date_from` | date | Từ ngày | `?date_from=2025-01-01` |
| `date_to` | date | Đến ngày | `?date_to=2025-12-31` |
| `page` | integer | Số trang | `?page=1` |
| `per_page` | integer | Số item/trang | `?per_page=20` |

---

## Postman Tests Script

```javascript
// Cho POST /inquiries
if (pm.response.code === 201) {
    var jsonData = pm.response.json();
    pm.environment.set("inquiry_id", jsonData.data.id);
    console.log("Inquiry ID:", jsonData.data.id);
}
```

---

## Test Flow trong Postman

### Public Flow (không cần auth):
1. **POST /inquiries** → Gửi yêu cầu tư vấn

### Seller Flow:
1. **Login as Seller** → Lưu seller_token
2. **GET /inquiries** → Xem danh sách yêu cầu
3. **GET /inquiries?status=pending** → Lọc yêu cầu chờ xử lý
4. **GET /inquiries/1** → Xem chi tiết
5. **PUT /inquiries/1** → Cập nhật trạng thái
6. **DELETE /inquiries/1** → Xóa (nếu spam)

---

## Email Notifications

Khi có inquiry mới, seller nhận email:

```
Subject: Yêu cầu tư vấn mới về sản phẩm "iPhone 15 Pro Max"

Khách hàng: Nguyễn Văn B
Email: nguyenvanb@example.com
Phone: 0987654321
Công ty: Công ty ABC

Nội dung:
Tôi muốn đặt hàng số lượng lớn (100 chiếc), có giảm giá không?

Số lượng: 100
Liên hệ ưu tiên: Điện thoại

[Xem chi tiết] [Liên hệ ngay]
```

---

## Lưu ý

- Không cần đăng nhập để gửi inquiry
- Seller nhận email notification ngay lập tức
- Seller chỉ xem inquiry về sản phẩm của mình
- Admin có thể xem tất cả inquiries
- IP address và user agent được lưu để chống spam
- Preferred contact giúp seller biết cách liên hệ tốt nhất
- Notes giúp seller ghi nhớ cuộc trao đổi
- Status tracking giúp quản lý leads
