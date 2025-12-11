# API Chat & Messages - Tin nhắn

**Base URL:** `{{base_url}}` = `http://localhost:8000/api`

---

## 1. Danh sách cuộc trò chuyện – GET /chat/conversations

**Mục đích:** Lấy danh sách tất cả cuộc trò chuyện của user.

### Request

**Method:** `GET`  
**URL:** `{{base_url}}/chat/conversations`

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
```

**Body:** Không cần

### Response mẫu

**Success (200):**
```json
{
  "data": [
    {
      "user_id": 5,
      "user": {
        "id": 5,
        "name": "Nguyễn Văn B",
        "avatar": "https://example.com/avatar.jpg",
        "is_online": true,
        "last_seen": "2025-12-01T15:00:00.000000Z"
      },
      "last_message": {
        "id": 100,
        "message": "Sản phẩm còn hàng không ạ?",
        "sender_id": 5,
        "is_read": false,
        "created_at": "2025-12-01T14:30:00.000000Z"
      },
      "unread_count": 3,
      "listing": {
        "id": 123,
        "title": "iPhone 15 Pro Max",
        "image": "https://example.com/image.jpg"
      }
    },
    {
      "user_id": 6,
      "user": {
        "id": 6,
        "name": "Trần Thị C",
        "avatar": "https://example.com/avatar2.jpg",
        "is_online": false,
        "last_seen": "2025-12-01T10:00:00.000000Z"
      },
      "last_message": {
        "id": 95,
        "message": "Cảm ơn bạn!",
        "sender_id": 2,
        "is_read": true,
        "created_at": "2025-12-01T12:00:00.000000Z"
      },
      "unread_count": 0,
      "listing": null
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 20,
    "total": 15,
    "total_unread": 5
  }
}
```

---

## 2. Tin nhắn với một user – GET /chat/messages/{user_id}

**Mục đích:** Lấy tất cả tin nhắn với một user cụ thể.

### Request

**Method:** `GET`  
**URL:** `{{base_url}}/chat/messages/5`

**Headers:**
```
Accept: application/json
Authorization: Bearer {{token}}
```

**Query Parameters (optional):**
```
?page=1
&per_page=50
&listing_id=123
```

**Body:** Không cần

### Response mẫu

**Success (200):**
```json
{
  "data": [
    {
      "id": 100,
      "sender_id": 5,
      "sender": {
        "id": 5,
        "name": "Nguyễn Văn B",
        "avatar": "https://example.com/avatar.jpg"
      },
      "receiver_id": 2,
      "message": "Sản phẩm còn hàng không ạ?",
      "listing_id": 123,
      "listing": {
        "id": 123,
        "title": "iPhone 15 Pro Max",
        "image": "https://example.com/image.jpg",
        "price": 29990000
      },
      "attachments": [],
      "is_read": false,
      "read_at": null,
      "created_at": "2025-12-01T14:30:00.000000Z"
    },
    {
      "id": 99,
      "sender_id": 2,
      "sender": {
        "id": 2,
        "name": "Tech Store",
        "avatar": "https://example.com/logo.jpg"
      },
      "receiver_id": 5,
      "message": "Dạ còn hàng ạ. Bạn cần bao nhiêu?",
      "listing_id": 123,
      "attachments": [],
      "is_read": true,
      "read_at": "2025-12-01T14:25:00.000000Z",
      "created_at": "2025-12-01T14:20:00.000000Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 50,
    "total": 10
  },
  "user": {
    "id": 5,
    "name": "Nguyễn Văn B",
    "avatar": "https://example.com/avatar.jpg",
    "is_online": true
  }
}
```

---

## 3. Gửi tin nhắn – POST /chat/messages

**Mục đích:** Gửi tin nhắn cho user khác.

### Request

**Method:** `POST`  
**URL:** `{{base_url}}/chat/messages`

**Headers:**
```
Content-Type: application/json
Accept: application/json
Authorization: Bearer {{token}}
```

**Body → raw → JSON:**
```json
{
  "to_user_id": 5,
  "body": "Xin chào, sản phẩm của bạn còn hàng không?",
  "listing_id": 123
}
```

### ⚠️ QUAN TRỌNG - Tên field chính xác:

| Field | Tên đúng | Tên SAI (gây lỗi 422) |
|-------|----------|----------------------|
| ID người nhận | `to_user_id` | ~~receiver_id~~ |
| Nội dung tin nhắn | `body` | ~~message~~ |
| ID sản phẩm | `listing_id` | ✅ |

**Required fields:**
- `to_user_id` - ID người nhận (integer, phải tồn tại trong bảng users)
- `body` - Nội dung tin nhắn (string, max 2000 ký tự)

**Optional fields:**
- `listing_id` - ID tin đăng nếu chat về sản phẩm (integer)

### Response mẫu

**Success (201):**
```json
{
  "data": {
    "id": 101,
    "from_user_id": 2,
    "to_user_id": 5,
    "body": "Xin chào, sản phẩm của bạn còn hàng không?",
    "listing_id": 123,
    "is_read": false,
    "created_at": "2025-12-01T15:00:00.000000Z",
    "from_user": {
      "id": 2,
      "full_name": "Nguyễn Văn A"
    },
    "to_user": {
      "id": 5,
      "full_name": "Shop ABC"
    }
  }
}
```

**Error - Validation Failed (422):**
```json
{
  "message": "The to user id field is required.",
  "errors": {
    "to_user_id": ["The to user id field is required."],
    "body": ["The body field is required."]
  }
}
```

**Lỗi thường gặp:**
- Dùng `receiver_id` thay vì `to_user_id` → Lỗi 422
- Dùng `message` thay vì `body` → Lỗi 422

---

## 4. Đánh dấu tin nhắn đã đọc – PUT /chat/messages/{user_id}/read

**Mục đích:** Đánh dấu tất cả tin nhắn với user này là đã đọc.

### Request

**Method:** `PUT`  
**URL:** `{{base_url}}/chat/messages/5/read`

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
  "message": "Messages marked as read",
  "data": {
    "marked_count": 3,
    "read_at": "2025-12-01T15:30:00.000000Z"
  }
}
```

---

## Query Parameters Chi Tiết

| Parameter | Type | Description | Example |
|-----------|------|-------------|---------|
| `unread_only` | boolean | Chỉ lấy cuộc trò chuyện có tin chưa đọc | `?unread_only=true` |
| `listing_id` | integer | Lọc theo tin đăng | `?listing_id=123` |
| `page` | integer | Số trang | `?page=1` |
| `per_page` | integer | Số item/trang | `?per_page=50` |

---

## Real-time Chat

Để chat real-time, sử dụng WebSocket (Laravel Echo):

```javascript
// Frontend
Echo.private(`chat.${userId}`)
    .listen('MessageSent', (e) => {
        console.log('New message:', e.message);
        // Update UI
    });
```

---

## Postman Tests Script

```javascript
// Cho POST /chat/messages
if (pm.response.code === 201) {
    var jsonData = pm.response.json();
    pm.environment.set("message_id", jsonData.data.id);
    console.log("Message sent:", jsonData.data.message);
}
```

---

## Test Flow trong Postman

1. **Login as Buyer** → Lưu token
2. **GET /chat/conversations** → Xem danh sách cuộc trò chuyện
3. **GET /chat/messages/5** → Xem tin nhắn với seller
4. **POST /chat/messages** → Gửi tin nhắn
5. **PUT /chat/messages/5/read** → Đánh dấu đã đọc

---

## Lưu ý

- Tin nhắn được sắp xếp theo thời gian
- Unread count hiển thị số tin chưa đọc
- Attachments: Ảnh, file đính kèm
- Real-time chat cần WebSocket
- Listing context: Chat về sản phẩm cụ thể
- Online status: Hiển thị user online/offline
- Last seen: Lần cuối online
