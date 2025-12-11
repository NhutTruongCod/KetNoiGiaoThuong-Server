# API Social Features - Like & Comment

**Base URL:** `{{base_url}}` = `http://localhost:8000/api`

---

## 1. Like tin đăng – POST /listings/{listing}/like

**Mục đích:** User like một tin đăng.

### Request

**Method:** `POST`  
**URL:** `{{base_url}}/listings/123/like`

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
  "message": "Listing liked successfully",
  "data": {
    "listing_id": 123,
    "user_id": 5,
    "likes_count": 46,
    "is_liked": true,
    "created_at": "2025-12-01T10:00:00.000000Z"
  }
}
```

**Error - Đã like rồi (400):**
```json
{
  "message": "You have already liked this listing"
}
```

---

## 2. Unlike tin đăng – DELETE /listings/{listing}/like

**Mục đích:** User bỏ like tin đăng.

### Request

**Method:** `DELETE`  
**URL:** `{{base_url}}/listings/123/like`

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
  "message": "Listing unliked successfully",
  "data": {
    "listing_id": 123,
    "likes_count": 45,
    "is_liked": false
  }
}
```

---

## 3. Bình luận tin đăng – POST /listings/{listing}/comments

**Mục đích:** User bình luận về tin đăng.

### Request

**Method:** `POST`  
**URL:** `{{base_url}}/listings/123/comments`

**Headers:**
```
Content-Type: application/json
Accept: application/json
Authorization: Bearer {{token}}
```

**Body → raw → JSON:**
```json
{
  "content": "Sản phẩm có bảo hành không ạ?",
  "parent_id": null
}
```

**Hoặc reply comment:**
```json
{
  "content": "Có bảo hành 12 tháng nhé bạn",
  "parent_id": 1
}
```

**Required fields:**
- `content` - Nội dung bình luận

**Optional fields:**
- `parent_id` - ID comment cha (để reply)

### Response mẫu

**Success (201):**
```json
{
  "message": "Comment posted successfully",
  "data": {
    "id": 1,
    "listing_id": 123,
    "user_id": 5,
    "user": {
      "id": 5,
      "name": "Nguyễn Văn B",
      "avatar": "https://example.com/avatar.jpg"
    },
    "content": "Sản phẩm có bảo hành không ạ?",
    "parent_id": null,
    "replies_count": 0,
    "likes_count": 0,
    "created_at": "2025-12-01T10:00:00.000000Z"
  }
}
```

---

## 4. Xem bình luận – GET /listings/{listing}/comments

**Mục đích:** Xem tất cả bình luận của tin đăng.

### Request

**Method:** `GET`  
**URL:** `{{base_url}}/listings/123/comments`

**Headers:**
```
Accept: application/json
Authorization: Bearer {{token}}
```

**Query Parameters (optional):**
```
?page=1
&per_page=20
&sort=newest
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
      "user_id": 5,
      "user": {
        "id": 5,
        "name": "Nguyễn Văn B",
        "avatar": "https://example.com/avatar.jpg",
        "role": "buyer"
      },
      "content": "Sản phẩm có bảo hành không ạ?",
      "parent_id": null,
      "replies_count": 1,
      "likes_count": 2,
      "is_liked": false,
      "created_at": "2025-12-01T10:00:00.000000Z",
      "replies": [
        {
          "id": 2,
          "user_id": 2,
          "user": {
            "id": 2,
            "name": "Tech Store",
            "avatar": "https://example.com/logo.jpg",
            "role": "seller"
          },
          "content": "Có bảo hành 12 tháng nhé bạn",
          "parent_id": 1,
          "likes_count": 5,
          "is_liked": true,
          "created_at": "2025-12-01T10:30:00.000000Z"
        }
      ]
    },
    {
      "id": 3,
      "listing_id": 123,
      "user_id": 6,
      "user": {
        "id": 6,
        "name": "Trần Thị C",
        "avatar": "https://example.com/avatar2.jpg"
      },
      "content": "Sản phẩm chất lượng tốt!",
      "parent_id": null,
      "replies_count": 0,
      "likes_count": 10,
      "is_liked": false,
      "created_at": "2025-12-01T09:00:00.000000Z",
      "replies": []
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 20,
    "total": 12,
    "last_page": 1
  }
}
```

---

## 5. Like comment – POST /comments/{comment}/like

**Mục đích:** User like một comment.

### Request

**Method:** `POST`  
**URL:** `{{base_url}}/comments/1/like`

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
  "message": "Comment liked successfully",
  "data": {
    "comment_id": 1,
    "likes_count": 3,
    "is_liked": true
  }
}
```

---

## 6. Unlike comment – DELETE /comments/{comment}/like

**Mục đích:** User bỏ like comment.

### Request

**Method:** `DELETE`  
**URL:** `{{base_url}}/comments/1/like`

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
  "message": "Comment unliked successfully",
  "data": {
    "comment_id": 1,
    "likes_count": 2,
    "is_liked": false
  }
}
```

---

## 7. Xóa comment – DELETE /comments/{comment}

**Mục đích:** User xóa comment của mình.

### Request

**Method:** `DELETE`  
**URL:** `{{base_url}}/comments/1`

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
  "message": "Comment deleted successfully"
}
```

**Error - Không có quyền (403):**
```json
{
  "message": "You can only delete your own comments"
}
```

---

## 8. Sửa comment – PUT /comments/{comment}

**Mục đích:** User sửa comment của mình.

### Request

**Method:** `PUT`  
**URL:** `{{base_url}}/comments/1`

**Headers:**
```
Content-Type: application/json
Accept: application/json
Authorization: Bearer {{token}}
```

**Body → raw → JSON:**
```json
{
  "content": "Sản phẩm có bảo hành bao lâu ạ? (đã sửa)"
}
```

### Response mẫu

**Success (200):**
```json
{
  "message": "Comment updated successfully",
  "data": {
    "id": 1,
    "content": "Sản phẩm có bảo hành bao lâu ạ? (đã sửa)",
    "is_edited": true,
    "updated_at": "2025-12-01T11:00:00.000000Z"
  }
}
```

---

## Query Parameters Chi Tiết

| Parameter | Type | Description | Example |
|-----------|------|-------------|---------|
| `sort` | string | newest, oldest, most_liked | `?sort=newest` |
| `page` | integer | Số trang | `?page=1` |
| `per_page` | integer | Số item/trang | `?per_page=20` |

---

## Postman Tests Script

```javascript
// Cho POST /listings/{listing}/like
if (pm.response.code === 200) {
    var jsonData = pm.response.json();
    console.log("Likes count:", jsonData.data.likes_count);
    console.log("Is liked:", jsonData.data.is_liked);
}

// Cho POST /listings/{listing}/comments
if (pm.response.code === 201) {
    var jsonData = pm.response.json();
    pm.environment.set("comment_id", jsonData.data.id);
    console.log("Comment ID:", jsonData.data.id);
}
```

---

## Test Flow trong Postman

1. **Login** → Lưu token
2. **GET /listings/123** → Xem tin đăng
3. **POST /listings/123/like** → Like tin đăng
4. **POST /listings/123/comments** → Bình luận
5. **GET /listings/123/comments** → Xem bình luận
6. **POST /comments/1/like** → Like comment
7. **PUT /comments/1** → Sửa comment
8. **DELETE /comments/1** → Xóa comment
9. **DELETE /listings/123/like** → Unlike tin đăng

---

## Comment Structure

```
Comment 1 (parent)
├── Reply 1 (child)
├── Reply 2 (child)
└── Reply 3 (child)

Comment 2 (parent)
└── Reply 1 (child)
    └── Reply 2 (nested reply)
```

---

## Lưu ý

- Like/Unlike toggle: Click lần 2 để unlike
- Comment có thể reply (nested comments)
- User chỉ sửa/xóa comment của mình
- Admin có thể xóa bất kỳ comment nào
- Seller có thể reply comment về sản phẩm
- Likes count hiển thị số người like
- Comments count hiển thị tổng số comment
- Real-time updates với WebSocket
- Notification khi có reply
