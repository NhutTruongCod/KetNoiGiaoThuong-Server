# API Notifications - Thông báo

**Base URL:** `{{base_url}}` = `http://localhost:8000/api`

---

## 1. Lấy danh sách thông báo của người dùng – GET /notifications

**Mục đích:** User xem danh sách thông báo của mình.

### Request

**Method:** `GET`  
**URL:** `{{base_url}}/notifications`

**Headers:**
```
Accept: application/json
Authorization: Bearer {{token}}
```

**Query Parameters (optional):**
```
?page=1
&per_page=20
&unread_only=true
&type=order
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
      "type": "order",
      "title": "Đơn hàng mới",
      "message": "Bạn có đơn hàng mới #ORD-20251201-0001",
      "data": {
        "order_id": 1,
        "order_number": "ORD-20251201-0001",
        "amount": 29990000
      },
      "action_url": "/orders/1",
      "icon": "shopping-cart",
      "is_read": false,
      "read_at": null,
      "created_at": "2025-12-01T10:00:00.000000Z"
    },
    {
      "id": 2,
      "type": "payment",
      "title": "Thanh toán thành công",
      "message": "Thanh toán đơn hàng #ORD-20251201-0001 đã thành công",
      "data": {
        "payment_id": 1,
        "order_id": 1,
        "amount": 29990000
      },
      "action_url": "/payments/1",
      "icon": "credit-card",
      "is_read": true,
      "read_at": "2025-12-01T11:00:00.000000Z",
      "created_at": "2025-12-01T10:30:00.000000Z"
    },
    {
      "id": 3,
      "type": "review",
      "title": "Đánh giá mới",
      "message": "Sản phẩm của bạn nhận được đánh giá 5 sao",
      "data": {
        "review_id": 1,
        "listing_id": 123,
        "rating": 5
      },
      "action_url": "/reviews/1",
      "icon": "star",
      "is_read": false,
      "read_at": null,
      "created_at": "2025-12-01T09:00:00.000000Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 20,
    "total": 50,
    "last_page": 3
  },
  "summary": {
    "total_notifications": 50,
    "unread_count": 15
  }
}
```

**Notification Types:**
- `order` - Đơn hàng
- `payment` - Thanh toán
- `review` - Đánh giá
- `message` - Tin nhắn
- `listing` - Tin đăng
- `shop` - Gian hàng
- `system` - Hệ thống
- `promotion` - Quảng cáo
- `verification` - Xác minh

---

## 2. Xem chi tiết một thông báo – GET /notifications/{id}

**Mục đích:** Xem chi tiết một thông báo.

### Request

**Method:** `GET`  
**URL:** `{{base_url}}/notifications/1`

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
  "user_id": 2,
  "type": "order",
  "title": "Đơn hàng mới",
  "message": "Bạn có đơn hàng mới #ORD-20251201-0001 từ khách hàng Nguyễn Văn B",
  "data": {
    "order_id": 1,
    "order_number": "ORD-20251201-0001",
    "buyer_name": "Nguyễn Văn B",
    "amount": 29990000,
    "items": [
      {
        "listing_id": 123,
        "title": "iPhone 15 Pro Max",
        "quantity": 1
      }
    ]
  },
  "action_url": "/orders/1",
  "action_text": "Xem đơn hàng",
  "icon": "shopping-cart",
  "priority": "high",
  "is_read": false,
  "read_at": null,
  "created_at": "2025-12-01T10:00:00.000000Z",
  "updated_at": "2025-12-01T10:00:00.000000Z"
}
```

**Error - Không có quyền (403):**
```json
{
  "message": "You can only view your own notifications"
}
```

---

## 3. Đánh dấu một thông báo đã đọc – PUT /notifications/{id}/read

**Mục đích:** Đánh dấu một thông báo là đã đọc.

### Request

**Method:** `PUT`  
**URL:** `{{base_url}}/notifications/1/read`

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
  "message": "Notification marked as read",
  "data": {
    "id": 1,
    "is_read": true,
    "read_at": "2025-12-01T11:00:00.000000Z"
  }
}
```

---

## 4. Đánh dấu tất cả thông báo là đã đọc – PUT /notifications/read-all

**Mục đích:** Đánh dấu tất cả thông báo chưa đọc là đã đọc.

### Request

**Method:** `PUT`  
**URL:** `{{base_url}}/notifications/read-all`

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
  "message": "All notifications marked as read",
  "data": {
    "marked_count": 15,
    "read_at": "2025-12-01T11:00:00.000000Z"
  }
}
```

---

## 5. Xóa một thông báo – DELETE /notifications/{id}

**Mục đích:** Xóa một thông báo.

### Request

**Method:** `DELETE`  
**URL:** `{{base_url}}/notifications/1`

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
  "message": "Notification deleted successfully"
}
```

**Error - Không có quyền (403):**
```json
{
  "message": "You can only delete your own notifications"
}
```

---

## 6. Xóa toàn bộ thông báo của người dùng – DELETE /notifications/delete-all

**Mục đích:** Xóa tất cả thông báo của user.

### Request

**Method:** `DELETE`  
**URL:** `{{base_url}}/notifications/delete-all`

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
  "message": "All notifications deleted successfully",
  "data": {
    "deleted_count": 50
  }
}
```

---

## Query Parameters Chi Tiết

| Parameter | Type | Description | Example |
|-----------|------|-------------|---------|
| `unread_only` | boolean | Chỉ lấy thông báo chưa đọc | `?unread_only=true` |
| `type` | string | Lọc theo loại | `?type=order` |
| `priority` | string | low, normal, high | `?priority=high` |
| `date_from` | date | Từ ngày | `?date_from=2025-01-01` |
| `date_to` | date | Đến ngày | `?date_to=2025-12-31` |
| `page` | integer | Số trang | `?page=1` |
| `per_page` | integer | Số item/trang | `?per_page=20` |

---

## Notification Types Chi Tiết

### Order Notifications
```json
{
  "type": "order",
  "title": "Đơn hàng mới",
  "message": "Bạn có đơn hàng mới #ORD-20251201-0001",
  "data": {
    "order_id": 1,
    "order_number": "ORD-20251201-0001",
    "status": "pending"
  }
}
```

### Payment Notifications
```json
{
  "type": "payment",
  "title": "Thanh toán thành công",
  "message": "Thanh toán đơn hàng #ORD-20251201-0001 đã thành công",
  "data": {
    "payment_id": 1,
    "order_id": 1,
    "amount": 29990000
  }
}
```

### Review Notifications
```json
{
  "type": "review",
  "title": "Đánh giá mới",
  "message": "Sản phẩm của bạn nhận được đánh giá 5 sao",
  "data": {
    "review_id": 1,
    "listing_id": 123,
    "rating": 5
  }
}
```

### Message Notifications
```json
{
  "type": "message",
  "title": "Tin nhắn mới",
  "message": "Bạn có tin nhắn mới từ Nguyễn Văn B",
  "data": {
    "message_id": 1,
    "sender_id": 5,
    "sender_name": "Nguyễn Văn B"
  }
}
```

### System Notifications
```json
{
  "type": "system",
  "title": "Cập nhật hệ thống",
  "message": "Hệ thống sẽ bảo trì vào 2h sáng ngày 05/12/2025",
  "data": {
    "maintenance_date": "2025-12-05T02:00:00.000000Z"
  }
}
```

---

## Priority Levels

- `low` - Thấp (thông báo thông thường)
- `normal` - Bình thường (mặc định)
- `high` - Cao (cần chú ý ngay)

---

## Postman Tests Script

### Kiểm tra unread count

```javascript
// Cho GET /notifications
if (pm.response.code === 200) {
    var jsonData = pm.response.json();
    pm.environment.set("unread_count", jsonData.summary.unread_count);
    console.log("Unread notifications:", jsonData.summary.unread_count);
}
```

### Lưu notification ID

```javascript
// Cho GET /notifications
if (pm.response.code === 200) {
    var jsonData = pm.response.json();
    if (jsonData.data && jsonData.data.length > 0) {
        pm.environment.set("notification_id", jsonData.data[0].id);
        console.log("First notification ID:", jsonData.data[0].id);
    }
}
```

---

## Test Flow trong Postman

1. **Login** → Lưu token
2. **GET /notifications** → Xem danh sách thông báo
3. **GET /notifications?unread_only=true** → Chỉ xem chưa đọc
4. **GET /notifications/1** → Xem chi tiết thông báo
5. **PUT /notifications/1/read** → Đánh dấu đã đọc
6. **PUT /notifications/read-all** → Đánh dấu tất cả đã đọc
7. **DELETE /notifications/1** → Xóa một thông báo
8. **DELETE /notifications/delete-all** → Xóa tất cả

---

## Real-time Notifications

Để nhận thông báo real-time, có thể sử dụng:

### WebSocket (Laravel Echo)
```javascript
// Frontend
Echo.private(`user.${userId}`)
    .notification((notification) => {
        console.log('New notification:', notification);
        // Update UI
    });
```

### Polling
```javascript
// Poll every 30 seconds
setInterval(() => {
    fetch('/api/notifications?unread_only=true')
        .then(response => response.json())
        .then(data => {
            updateNotificationBadge(data.summary.unread_count);
        });
}, 30000);
```

---

## Lưu ý

- Notifications tự động tạo khi có sự kiện
- User chỉ xem được thông báo của mình
- Unread count hiển thị trên badge/icon
- Priority cao nên hiển thị nổi bật
- Action URL để redirect user đến trang liên quan
- Nên có pagination vì số lượng thông báo có thể nhiều
- Delete all nên có confirm trước khi xóa
- Real-time notifications cải thiện UX
