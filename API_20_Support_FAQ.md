# API Support & FAQ - Hỗ trợ khách hàng

**Base URL:** `{{base_url}}` = `http://localhost:8000/api`

---

## FAQs (Public)

## 1. Danh sách câu hỏi thường gặp – GET /faqs

**Mục đích:** Xem danh sách FAQ (public, không cần auth).

### Request

**Method:** `GET`  
**URL:** `{{base_url}}/faqs`

**Headers:**
```
Accept: application/json
```

**Query Parameters (optional):**
```
?category=payment
&search=thanh+toan
&page=1
&per_page=20
```

**Body:** Không cần

### Response mẫu

**Success (200):**
```json
{
  "data": [
    {
      "id": 1,
      "category": "payment",
      "question": "Làm thế nào để thanh toán?",
      "answer": "Bạn có thể thanh toán qua VNPay, Momo, ZaloPay hoặc COD (thanh toán khi nhận hàng).",
      "order": 1,
      "views_count": 1250,
      "is_helpful_count": 45,
      "created_at": "2025-11-01T10:00:00.000000Z"
    },
    {
      "id": 2,
      "category": "shipping",
      "question": "Thời gian giao hàng là bao lâu?",
      "answer": "Thời gian giao hàng từ 2-5 ngày tùy theo khu vực. Nội thành HCM và HN: 1-2 ngày.",
      "order": 2,
      "views_count": 980,
      "is_helpful_count": 38,
      "created_at": "2025-11-01T10:00:00.000000Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 20,
    "total": 50
  },
  "categories": [
    {"value": "general", "label": "Chung"},
    {"value": "account", "label": "Tài khoản"},
    {"value": "payment", "label": "Thanh toán"},
    {"value": "shipping", "label": "Vận chuyển"},
    {"value": "return", "label": "Đổi trả"},
    {"value": "seller", "label": "Người bán"}
  ]
}
```

---

## SUPPORT TICKETS (Require Auth)

## 2. Danh sách tickets – GET /support/tickets

**Mục đích:** User xem danh sách tickets hỗ trợ của mình.

### Request

**Method:** `GET`  
**URL:** `{{base_url}}/support/tickets`

**Headers:**
```
Accept: application/json
Authorization: Bearer {{token}}
```

**Query Parameters (optional):**
```
?page=1
&per_page=20
&status=open
&category=payment
```

**Body:** Không cần

### Response mẫu

**Success (200):**
```json
{
  "data": [
    {
      "id": 1,
      "ticket_number": "TKT-20251201-0001",
      "user_id": 5,
      "category": "payment",
      "priority": "high",
      "subject": "Không thể thanh toán qua VNPay",
      "status": "open",
      "last_message": {
        "content": "Tôi đã thử nhiều lần nhưng vẫn bị lỗi",
        "created_at": "2025-12-01T14:30:00.000000Z"
      },
      "messages_count": 3,
      "assigned_to": {
        "id": 1,
        "name": "Support Agent"
      },
      "created_at": "2025-12-01T10:00:00.000000Z",
      "updated_at": "2025-12-01T14:30:00.000000Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 20,
    "total": 5
  }
}
```

**Ticket Status:**
- `open` - Mới tạo
- `in_progress` - Đang xử lý
- `waiting_customer` - Chờ khách hàng
- `resolved` - Đã giải quyết
- `closed` - Đã đóng

**Priority:**
- `low` - Thấp
- `normal` - Bình thường
- `high` - Cao
- `urgent` - Khẩn cấp

---

## 3. Tạo ticket mới – POST /support/tickets

**Mục đích:** User tạo ticket hỗ trợ mới.

### Request

**Method:** `POST`  
**URL:** `{{base_url}}/support/tickets`

**Headers:**
```
Content-Type: application/json
Accept: application/json
Authorization: Bearer {{token}}
```

**Body → raw → JSON:**
```json
{
  "category": "payment",
  "priority": "high",
  "subject": "Không thể thanh toán qua VNPay",
  "description": "Tôi đã thử thanh toán đơn hàng #ORD-20251201-0001 qua VNPay nhưng bị lỗi 'Transaction failed'. Vui lòng hỗ trợ.",
  "attachments": [
    "https://example.com/uploads/screenshot.jpg"
  ],
  "order_id": 1
}
```

**Required fields:**
- `category` - Danh mục: `general`, `account`, `payment`, `shipping`, `return`, `technical`, `other`
- `subject` - Tiêu đề
- `description` - Mô tả chi tiết

**Optional fields:**
- `priority` - Độ ưu tiên (default: normal)
- `attachments` - File đính kèm (array URLs)
- `order_id` - ID đơn hàng liên quan

### Response mẫu

**Success (201):**
```json
{
  "message": "Support ticket created successfully. Our team will respond soon.",
  "data": {
    "id": 1,
    "ticket_number": "TKT-20251201-0001",
    "user_id": 5,
    "category": "payment",
    "priority": "high",
    "subject": "Không thể thanh toán qua VNPay",
    "status": "open",
    "created_at": "2025-12-01T10:00:00.000000Z"
  }
}
```

---

## 4. Chi tiết ticket – GET /support/tickets/{ticket}

**Mục đích:** Xem chi tiết ticket và tất cả tin nhắn.

### Request

**Method:** `GET`  
**URL:** `{{base_url}}/support/tickets/1`

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
  "ticket_number": "TKT-20251201-0001",
  "user_id": 5,
  "user": {
    "id": 5,
    "name": "Nguyễn Văn B",
    "email": "buyer1@tradehub.com"
  },
  "category": "payment",
  "priority": "high",
  "subject": "Không thể thanh toán qua VNPay",
  "description": "Tôi đã thử thanh toán đơn hàng #ORD-20251201-0001 qua VNPay nhưng bị lỗi 'Transaction failed'. Vui lòng hỗ trợ.",
  "status": "in_progress",
  "assigned_to": {
    "id": 1,
    "name": "Support Agent",
    "avatar": "https://example.com/agent.jpg"
  },
  "order_id": 1,
  "order": {
    "id": 1,
    "order_number": "ORD-20251201-0001",
    "total_amount": 29990000
  },
  "messages": [
    {
      "id": 1,
      "user_id": 5,
      "user": {
        "id": 5,
        "name": "Nguyễn Văn B",
        "role": "customer"
      },
      "message": "Tôi đã thử thanh toán đơn hàng #ORD-20251201-0001 qua VNPay nhưng bị lỗi 'Transaction failed'. Vui lòng hỗ trợ.",
      "attachments": [
        "https://example.com/uploads/screenshot.jpg"
      ],
      "is_staff": false,
      "created_at": "2025-12-01T10:00:00.000000Z"
    },
    {
      "id": 2,
      "user_id": 1,
      "user": {
        "id": 1,
        "name": "Support Agent",
        "role": "staff"
      },
      "message": "Xin chào, chúng tôi đã nhận được yêu cầu của bạn. Bạn có thể thử lại với phương thức thanh toán khác không?",
      "attachments": [],
      "is_staff": true,
      "created_at": "2025-12-01T11:00:00.000000Z"
    },
    {
      "id": 3,
      "user_id": 5,
      "user": {
        "id": 5,
        "name": "Nguyễn Văn B"
      },
      "message": "Tôi đã thử lại nhưng vẫn bị lỗi",
      "attachments": [],
      "is_staff": false,
      "created_at": "2025-12-01T14:30:00.000000Z"
    }
  ],
  "created_at": "2025-12-01T10:00:00.000000Z",
  "updated_at": "2025-12-01T14:30:00.000000Z"
}
```

---

## 5. Trả lời ticket – POST /support/tickets/{ticket}/messages

**Mục đích:** User hoặc staff trả lời ticket.

### Request

**Method:** `POST`  
**URL:** `{{base_url}}/support/tickets/1/messages`

**Headers:**
```
Content-Type: application/json
Accept: application/json
Authorization: Bearer {{token}}
```

**Body → raw → JSON:**
```json
{
  "message": "Tôi đã thử lại với Momo và đã thanh toán thành công. Cảm ơn!",
  "attachments": []
}
```

### Response mẫu

**Success (201):**
```json
{
  "message": "Message sent successfully",
  "data": {
    "id": 4,
    "ticket_id": 1,
    "user_id": 5,
    "message": "Tôi đã thử lại với Momo và đã thanh toán thành công. Cảm ơn!",
    "is_staff": false,
    "created_at": "2025-12-01T15:00:00.000000Z"
  }
}
```

---

## 6. Đóng ticket – PUT /support/tickets/{ticket}/close

**Mục đích:** User hoặc staff đóng ticket đã giải quyết.

### Request

**Method:** `PUT`  
**URL:** `{{base_url}}/support/tickets/1/close`

**Headers:**
```
Content-Type: application/json
Accept: application/json
Authorization: Bearer {{token}}
```

**Body → raw → JSON (optional):**
```json
{
  "resolution": "Đã giải quyết. Khách hàng thanh toán thành công qua Momo.",
  "satisfaction_rating": 5
}
```

### Response mẫu

**Success (200):**
```json
{
  "message": "Ticket closed successfully",
  "data": {
    "id": 1,
    "status": "closed",
    "resolution": "Đã giải quyết. Khách hàng thanh toán thành công qua Momo.",
    "closed_at": "2025-12-01T16:00:00.000000Z"
  }
}
```

---

## Postman Tests Script

```javascript
// Cho POST /support/tickets
if (pm.response.code === 201) {
    var jsonData = pm.response.json();
    pm.environment.set("ticket_id", jsonData.data.id);
    pm.environment.set("ticket_number", jsonData.data.ticket_number);
    console.log("Ticket created:", jsonData.data.ticket_number);
}
```

---

## Test Flow trong Postman

1. **GET /faqs** → Xem FAQ (không cần auth)
2. **Login** → Lưu token
3. **POST /support/tickets** → Tạo ticket
4. **GET /support/tickets** → Xem danh sách tickets
5. **GET /support/tickets/1** → Xem chi tiết ticket
6. **POST /support/tickets/1/messages** → Trả lời ticket
7. **PUT /support/tickets/1/close** → Đóng ticket

---

## Lưu ý

- FAQ public, không cần auth
- Support tickets cần auth
- User chỉ xem tickets của mình
- Staff/Admin xem tất cả tickets
- Email notification khi có reply
- Priority cao được xử lý trước
- Attachments: Screenshots, files
- Satisfaction rating sau khi đóng
- SLA tracking cho response time
