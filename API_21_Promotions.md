# API Promotions - Quảng cáo

**Base URL:** `{{base_url}}` = `http://localhost:8000/api`

---

## 1. Danh sách quảng cáo – GET /promotion

**Mục đích:** Seller xem danh sách quảng cáo của mình.

### Request

**Method:** `GET`  
**URL:** `{{base_url}}/promotion`

**Headers:**
```
Accept: application/json
Authorization: Bearer {{seller_token}}
```

**Query Parameters (optional):**
```
?page=1
&per_page=20
&status=active
&type=featured
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
      "type": "featured",
      "duration_days": 7,
      "budget": 500000,
      "spent": 350000,
      "impressions": 15000,
      "clicks": 450,
      "ctr": 3.0,
      "start_date": "2025-12-01",
      "end_date": "2025-12-08",
      "status": "active",
      "created_at": "2025-12-01T10:00:00.000000Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 20,
    "total": 10
  },
  "summary": {
    "total_budget": 5000000,
    "total_spent": 3500000,
    "total_impressions": 150000,
    "total_clicks": 4500,
    "avg_ctr": 3.0
  }
}
```

**Promotion Types:**
- `featured` - Tin nổi bật
- `top_search` - Top tìm kiếm
- `homepage_banner` - Banner trang chủ
- `category_banner` - Banner danh mục

**Promotion Status:**
- `pending` - Chờ duyệt
- `active` - Đang chạy
- `paused` - Tạm dừng
- `completed` - Hoàn thành
- `cancelled` - Đã hủy

---

## 2. Quảng cáo đang chạy – GET /promotion/active

**Mục đích:** Xem các quảng cáo đang active.

### Request

**Method:** `GET`  
**URL:** `{{base_url}}/promotion/active`

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
  "data": [
    {
      "id": 1,
      "listing_id": 123,
      "listing": {
        "id": 123,
        "title": "iPhone 15 Pro Max 256GB"
      },
      "type": "featured",
      "budget": 500000,
      "spent": 350000,
      "remaining_budget": 150000,
      "impressions": 15000,
      "clicks": 450,
      "days_remaining": 3,
      "end_date": "2025-12-08",
      "status": "active"
    }
  ]
}
```

---

## 3. Chi tiết quảng cáo – GET /promotion/{id}

**Mục đích:** Xem chi tiết một quảng cáo.

### Request

**Method:** `GET`  
**URL:** `{{base_url}}/promotion/1`

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
    "price": 29990000,
    "images": ["https://example.com/image.jpg"]
  },
  "type": "featured",
  "duration_days": 7,
  "budget": 500000,
  "spent": 350000,
  "remaining_budget": 150000,
  "daily_budget": 71428,
  "target_audience": {
    "locations": ["Ho Chi Minh", "Ha Noi"],
    "age_range": [25, 45],
    "interests": ["electronics", "technology"]
  },
  "performance": {
    "impressions": 15000,
    "clicks": 450,
    "ctr": 3.0,
    "conversions": 25,
    "conversion_rate": 5.56,
    "cost_per_click": 777.78,
    "cost_per_conversion": 14000
  },
  "start_date": "2025-12-01",
  "end_date": "2025-12-08",
  "status": "active",
  "is_featured": true,
  "featured_position": 1,
  "created_at": "2025-12-01T10:00:00.000000Z",
  "updated_at": "2025-12-01T10:00:00.000000Z"
}
```

---

## 4. Tạo quảng cáo mới – POST /promotion

**Mục đích:** Seller tạo quảng cáo cho listing.

### Request

**Method:** `POST`  
**URL:** `{{base_url}}/promotion`

**Headers:**
```
Content-Type: application/json
Accept: application/json
Authorization: Bearer {{seller_token}}
```

**Body → raw → JSON:**
```json
{
  "listing_id": 123,
  "type": "featured",
  "duration_days": 7,
  "budget": 500000,
  "target_audience": {
    "locations": ["Ho Chi Minh", "Ha Noi"],
    "age_range": [25, 45],
    "interests": ["electronics", "technology"]
  },
  "start_date": "2025-12-01"
}
```

**Required fields:**
- `listing_id` - ID tin đăng
- `type` - Loại quảng cáo
- `duration_days` - Số ngày chạy
- `budget` - Ngân sách

**Optional fields:**
- `target_audience` - Đối tượng mục tiêu
- `start_date` - Ngày bắt đầu (default: hôm nay)

### Response mẫu

**Success (201):**
```json
{
  "message": "Promotion created successfully",
  "data": {
    "id": 1,
    "listing_id": 123,
    "type": "featured",
    "duration_days": 7,
    "budget": 500000,
    "start_date": "2025-12-01",
    "end_date": "2025-12-08",
    "status": "pending",
    "payment_url": "https://vnpay.vn/payment?token=xxx",
    "created_at": "2025-12-01T10:00:00.000000Z"
  }
}
```

**Lưu ý:** Cần thanh toán qua `payment_url` để kích hoạt.

---

## 5. Cập nhật quảng cáo – PUT /promotion/{id}

**Mục đích:** Seller cập nhật quảng cáo.

### Request

**Method:** `PUT`  
**URL:** `{{base_url}}/promotion/1`

**Headers:**
```
Content-Type: application/json
Accept: application/json
Authorization: Bearer {{seller_token}}
```

**Body → raw → JSON:**
```json
{
  "budget": 700000,
  "duration_days": 10,
  "target_audience": {
    "locations": ["Ho Chi Minh", "Ha Noi", "Da Nang"]
  }
}
```

### Response mẫu

**Success (200):**
```json
{
  "message": "Promotion updated successfully",
  "data": {
    "id": 1,
    "budget": 700000,
    "duration_days": 10,
    "end_date": "2025-12-11",
    "updated_at": "2025-12-01T11:00:00.000000Z"
  }
}
```

---

## 6. Đặt tin nổi bật – PATCH /promotion/{id}/featured

**Mục đích:** Đặt/bỏ tin nổi bật và vị trí.

### Request

**Method:** `PATCH`  
**URL:** `{{base_url}}/promotion/1/featured`

**Headers:**
```
Content-Type: application/json
Accept: application/json
Authorization: Bearer {{seller_token}}
```

**Body → raw → JSON:**
```json
{
  "is_featured": true,
  "featured_position": 1
}
```

### Response mẫu

**Success (200):**
```json
{
  "message": "Featured status updated successfully",
  "data": {
    "id": 1,
    "is_featured": true,
    "featured_position": 1,
    "updated_at": "2025-12-01T11:00:00.000000Z"
  }
}
```

---

## 7. Xóa quảng cáo – DELETE /promotion/{id}

**Mục đích:** Seller xóa/hủy quảng cáo.

### Request

**Method:** `DELETE`  
**URL:** `{{base_url}}/promotion/1`

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
  "message": "Promotion cancelled successfully",
  "data": {
    "id": 1,
    "status": "cancelled",
    "refund_amount": 150000,
    "refund_note": "Hoàn lại số tiền chưa sử dụng"
  }
}
```

---

## Pricing

| Type | Price/Day | Description |
|------|-----------|-------------|
| Featured | 50,000 VND | Tin nổi bật trên trang chủ |
| Top Search | 70,000 VND | Top kết quả tìm kiếm |
| Homepage Banner | 100,000 VND | Banner trang chủ |
| Category Banner | 80,000 VND | Banner danh mục |

**Discounts:**
- 7 days: 10% off
- 14 days: 15% off
- 30 days: 20% off

---

## Postman Tests Script

```javascript
// Cho POST /promotion
if (pm.response.code === 201) {
    var jsonData = pm.response.json();
    pm.environment.set("promotion_id", jsonData.data.id);
    pm.environment.set("payment_url", jsonData.data.payment_url);
    console.log("Promotion ID:", jsonData.data.id);
}
```

---

## Test Flow trong Postman

1. **Login as Seller** → Lưu seller_token
2. **POST /promotion** → Tạo quảng cáo
3. **Thanh toán** qua payment_url
4. **GET /promotion** → Xem danh sách
5. **GET /promotion/active** → Xem đang chạy
6. **GET /promotion/1** → Xem chi tiết & performance
7. **PUT /promotion/1** → Cập nhật budget
8. **PATCH /promotion/1/featured** → Đặt nổi bật
9. **DELETE /promotion/1** → Hủy quảng cáo

---

## Lưu ý

- Cần thanh toán trước khi kích hoạt
- Budget tự động trừ dần theo clicks/impressions
- CTR = (Clicks / Impressions) × 100
- Conversion tracking từ clicks → orders
- Target audience giúp tối ưu hiệu quả
- Featured position: 1-10 (1 = cao nhất)
- Refund nếu hủy sớm
- Performance metrics real-time
