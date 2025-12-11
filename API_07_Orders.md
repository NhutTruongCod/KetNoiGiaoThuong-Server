# API Orders - Quản lý đơn hàng

**Base URL:** `{{base_url}}` = `http://localhost:8000/api`

---

## 1. Danh sách đơn hàng – GET /orders

**Mục đích:** Lấy danh sách đơn hàng của user.

**Logic hiển thị:**
- **Admin:** Xem tất cả đơn hàng trong hệ thống
- **User thường:** Xem đơn mình mua (buyer_id) + đơn mình bán (seller_id)
- **Seller:** Có thể vừa mua vừa bán, nên xem cả đơn mua và đơn bán của mình

### Request

**Method:** `GET`  
**URL:** `{{base_url}}/orders`

**Headers:**
```
Accept: application/json
Authorization: Bearer {{token}}
```

**Query Parameters (optional):**
```
?page=1
&per_page=20
&status=pending
&date_from=2025-01-01
&date_to=2025-12-31
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
      "order_number": "ORD-20251201-0001",
      "buyer_id": 5,
      "buyer": {
        "id": 5,
        "name": "Nguyễn Văn B",
        "email": "buyer1@tradehub.com",
        "phone": "0987654321"
      },
      "listing_id": 123,
      "listing": {
        "id": 123,
        "title": "iPhone 15 Pro Max 256GB",
        "price": 29990000,
        "images": ["https://example.com/image1.jpg"]
      },
      "shop_id": 1,
      "shop": {
        "id": 1,
        "name": "Tech Store",
        "phone": "0901234567"
      },
      "quantity": 1,
      "unit_price": 29990000,
      "total_amount": 29990000,
      "shipping_fee": 0,
      "discount_amount": 0,
      "final_amount": 29990000,
      "status": "pending",
      "payment_method": "cod",
      "payment_status": "unpaid",
      "shipping_address": {
        "name": "Nguyễn Văn B",
        "phone": "0987654321",
        "address": "456 Lê Lợi",
        "ward": "Phường 1",
        "district": "Quận 1",
        "city": "TP.HCM"
      },
      "note": "Giao giờ hành chính",
      "created_at": "2025-12-01T10:00:00.000000Z",
      "updated_at": "2025-12-01T10:00:00.000000Z"
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

**Order Status:**
- `pending` - Chờ xác nhận
- `confirmed` - Đã xác nhận
- `processing` - Đang xử lý
- `shipping` - Đang giao hàng
- `delivered` - Đã giao hàng
- `completed` - Hoàn thành
- `cancelled` - Đã hủy
- `refunded` - Đã hoàn tiền

**Payment Status:**
- `unpaid` - Chưa thanh toán
- `paid` - Đã thanh toán
- `refunded` - Đã hoàn tiền

---

## 2. Chi tiết đơn hàng – GET /orders/{id}

**Mục đích:** Xem chi tiết một đơn hàng.

### Request

**Method:** `GET`  
**URL:** `{{base_url}}/orders/1`

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
  "order_number": "ORD-20251201-0001",
  "buyer_id": 5,
  "buyer": {
    "id": 5,
    "name": "Nguyễn Văn B",
    "email": "buyer1@tradehub.com",
    "phone": "0987654321"
  },
  "listing_id": 123,
  "listing": {
    "id": 123,
    "title": "iPhone 15 Pro Max 256GB",
    "description": "Hàng chính hãng, mới 100%",
    "price": 29990000,
    "images": [
      "https://example.com/image1.jpg",
      "https://example.com/image2.jpg"
    ],
    "shop_id": 1
  },
  "shop_id": 1,
  "shop": {
    "id": 1,
    "name": "Tech Store",
    "address": "123 Nguyễn Huệ, Q1, TP.HCM",
    "phone": "0901234567",
    "email": "tech@example.com"
  },
  "quantity": 1,
  "unit_price": 29990000,
  "total_amount": 29990000,
  "shipping_fee": 0,
  "discount_amount": 0,
  "tax_amount": 0,
  "final_amount": 29990000,
  "status": "pending",
  "payment_method": "cod",
  "payment_status": "unpaid",
  "shipping_address": {
    "name": "Nguyễn Văn B",
    "phone": "0987654321",
    "address": "456 Lê Lợi",
    "ward": "Phường 1",
    "district": "Quận 1",
    "city": "TP.HCM",
    "postal_code": "700000"
  },
  "note": "Giao giờ hành chính",
  "tracking_number": null,
  "shipped_at": null,
  "delivered_at": null,
  "cancelled_at": null,
  "cancel_reason": null,
  "created_at": "2025-12-01T10:00:00.000000Z",
  "updated_at": "2025-12-01T10:00:00.000000Z",
  "status_history": [
    {
      "status": "pending",
      "note": "Đơn hàng đã được tạo",
      "created_at": "2025-12-01T10:00:00.000000Z"
    }
  ]
}
```

**Error - Không có quyền (403):**
```json
{
  "message": "You can only view your own orders"
}
```

---

## 3. Tạo đơn hàng mới – POST /orders

**Mục đích:** Tạo đơn hàng mới (mọi user đã đăng nhập đều có thể mua hàng).

**Lưu ý:** 
- Buyer có thể mua hàng
- Seller cũng có thể mua hàng từ shop khác
- Admin cũng có thể mua hàng
- Không giới hạn role, chỉ cần đăng nhập

### Request

**Method:** `POST`  
**URL:** `{{base_url}}/orders`

**Headers:**
```
Content-Type: application/json
Accept: application/json
Authorization: Bearer {{token}}
```

**Body → raw → JSON:**
```json
{
  "listing_id": 123,
  "quantity": 1,
  "shipping_address": {
    "name": "Nguyễn Văn B",
    "phone": "0987654321",
    "address": "456 Lê Lợi",
    "ward": "Phường 1",
    "district": "Quận 1",
    "city": "TP.HCM",
    "postal_code": "700000"
  },
  "payment_method": "cod",
  "note": "Giao giờ hành chính, gọi trước 30 phút",
  "coupon_code": "DISCOUNT10"
}
```

**Required fields:**
- `listing_id` - ID tin đăng
- `quantity` - Số lượng
- `shipping_address` - Địa chỉ giao hàng
  - `name` - Tên người nhận
  - `phone` - SĐT người nhận
  - `address` - Địa chỉ
  - `district` - Quận/Huyện
  - `city` - Tỉnh/Thành phố
- `payment_method` - Phương thức: `cod`, `vnpay`, `momo`, `bank_transfer`

**Optional fields:**
- `note` - Ghi chú cho seller
- `coupon_code` - Mã giảm giá
- `ward` - Phường/Xã
- `postal_code` - Mã bưu điện

### Response mẫu

**Success (201):**
```json
{
  "message": "Order created successfully",
  "data": {
    "id": 1,
    "order_number": "ORD-20251201-0001",
    "listing_id": 123,
    "quantity": 1,
    "total_amount": 29990000,
    "shipping_fee": 0,
    "discount_amount": 0,
    "final_amount": 29990000,
    "status": "pending",
    "payment_method": "cod",
    "payment_status": "unpaid",
    "created_at": "2025-12-01T10:00:00.000000Z"
  }
}
```

**Error - Hết hàng (400):**
```json
{
  "message": "Product is out of stock",
  "available_quantity": 0
}
```

**Error - Số lượng không đủ (400):**
```json
{
  "message": "Not enough stock",
  "requested": 10,
  "available": 5
}
```

---

## 4. Cập nhật đơn hàng – PUT /orders/{id}

**Mục đích:** Cập nhật trạng thái đơn hàng (seller xác nhận, cập nhật tracking, etc).

### Request

**Method:** `PUT`  
**URL:** `{{base_url}}/orders/1`

**Headers:**
```
Content-Type: application/json
Accept: application/json
Authorization: Bearer {{token}}
```

**Body → raw → JSON:**
```json
{
  "status": "confirmed",
  "note": "Đơn hàng đã được xác nhận, sẽ giao trong 2-3 ngày"
}
```

**Hoặc cập nhật tracking:**
```json
{
  "status": "shipping",
  "tracking_number": "VN123456789",
  "note": "Đơn hàng đang được giao bởi Giao Hàng Nhanh"
}
```

**Hoặc xác nhận đã giao:**
```json
{
  "status": "delivered",
  "note": "Đã giao hàng thành công"
}
```

### Response mẫu

**Success (200):**
```json
{
  "message": "Order updated successfully",
  "data": {
    "id": 1,
    "order_number": "ORD-20251201-0001",
    "status": "confirmed",
    "tracking_number": null,
    "updated_at": "2025-12-01T11:00:00.000000Z"
  }
}
```

**Error - Không có quyền (403):**
```json
{
  "message": "Only shop owner can update order status"
}
```

**Error - Trạng thái không hợp lệ (400):**
```json
{
  "message": "Cannot change status from completed to pending"
}
```

---

## 5. Hủy đơn hàng – DELETE /orders/{id}

**Mục đích:** Buyer hoặc Seller hủy đơn hàng.

### Request

**Method:** `DELETE`  
**URL:** `{{base_url}}/orders/1`

**Headers:**
```
Content-Type: application/json
Accept: application/json
Authorization: Bearer {{token}}
```

**Body → raw → JSON (optional):**
```json
{
  "cancel_reason": "Khách hàng đổi ý không mua nữa"
}
```

### Response mẫu

**Success (200):**
```json
{
  "message": "Order cancelled successfully",
  "data": {
    "id": 1,
    "order_number": "ORD-20251201-0001",
    "status": "cancelled",
    "cancel_reason": "Khách hàng đổi ý không mua nữa",
    "cancelled_at": "2025-12-01T12:00:00.000000Z"
  }
}
```

**Error - Không thể hủy (400):**
```json
{
  "message": "Cannot cancel order that is already shipped or delivered"
}
```

---

## Query Parameters Chi Tiết

| Parameter | Type | Description | Example |
|-----------|------|-------------|---------|
| `status` | string | Lọc theo trạng thái | `?status=pending` |
| `payment_status` | string | unpaid, paid, refunded | `?payment_status=unpaid` |
| `date_from` | date | Từ ngày | `?date_from=2025-01-01` |
| `date_to` | date | Đến ngày | `?date_to=2025-12-31` |
| `page` | integer | Số trang | `?page=1` |
| `per_page` | integer | Số item/trang | `?per_page=20` |
| `sort` | string | created_at, total_amount | `?sort=created_at` |
| `order` | string | asc, desc | `?order=desc` |

---

## Order Status Flow

```
pending → confirmed → processing → shipping → delivered → completed
   ↓
cancelled
```

**Buyer có thể hủy:** pending, confirmed  
**Seller có thể hủy:** pending, confirmed, processing

---

## Postman Tests Script

### Lưu order_id sau khi tạo

```javascript
// Cho POST /orders
if (pm.response.code === 201) {
    var jsonData = pm.response.json();
    if (jsonData.data && jsonData.data.id) {
        pm.environment.set("order_id", jsonData.data.id);
        pm.environment.set("order_number", jsonData.data.order_number);
        console.log("Order ID saved:", jsonData.data.id);
    }
}
```

---

## Test Flow trong Postman

### Buyer Flow:
1. **Login as Buyer** → Lưu token
2. **POST /orders** → Tạo đơn hàng mới
3. **GET /orders** → Xem danh sách đơn hàng
4. **GET /orders/{id}** → Xem chi tiết đơn hàng
5. **DELETE /orders/{id}** → Hủy đơn (nếu cần)

### Seller Flow:
1. **Login as Seller** → Lưu token
2. **GET /orders** → Xem đơn hàng bán
3. **PUT /orders/{id}** → Xác nhận đơn (status: confirmed)
4. **PUT /orders/{id}** → Cập nhật đang giao (status: shipping)
5. **PUT /orders/{id}** → Xác nhận đã giao (status: delivered)

---

## Lưu ý

- Buyer chỉ xem được đơn hàng của mình
- Seller chỉ xem được đơn hàng của shop mình
- Không thể hủy đơn đã giao hoặc hoàn thành
- Payment method COD không cần thanh toán trước
- VNPay/Momo cần redirect đến payment gateway
- Tracking number giúp buyer theo dõi đơn hàng
