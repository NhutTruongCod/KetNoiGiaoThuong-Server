# API Bookmarks - Yêu thích

**Base URL:** `{{base_url}}` = `http://localhost:8000/api`

---

## 1. Danh sách yêu thích – GET /bookmarks

**Mục đích:** Lấy danh sách tin đăng đã lưu yêu thích.

### Request

**Method:** `GET`  
**URL:** `{{base_url}}/bookmarks`

**Headers:**
```
Accept: application/json
Authorization: Bearer {{token}}
```

**Query Parameters (optional):**
```
?page=1
&per_page=20
&category_id=1
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
      "user_id": 5,
      "listing_id": 123,
      "listing": {
        "id": 123,
        "title": "iPhone 15 Pro Max 256GB",
        "slug": "iphone-15-pro-max-256gb",
        "price": 29990000,
        "images": [
          "https://example.com/image1.jpg"
        ],
        "shop": {
          "id": 1,
          "name": "Tech Store"
        },
        "status": "active",
        "is_available": true
      },
      "created_at": "2025-12-01T10:00:00.000000Z"
    },
    {
      "id": 2,
      "user_id": 5,
      "listing_id": 124,
      "listing": {
        "id": 124,
        "title": "Samsung Galaxy S24 Ultra",
        "slug": "samsung-galaxy-s24-ultra",
        "price": 27990000,
        "images": [
          "https://example.com/image2.jpg"
        ],
        "shop": {
          "id": 2,
          "name": "Mobile Store"
        },
        "status": "active",
        "is_available": true
      },
      "created_at": "2025-11-30T15:00:00.000000Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 20,
    "total": 15,
    "last_page": 1
  }
}
```

---

## 2. Thêm vào yêu thích – POST /bookmarks

**Mục đích:** Lưu tin đăng vào danh sách yêu thích.

### Request

**Method:** `POST`  
**URL:** `{{base_url}}/bookmarks`

**Headers:**
```
Content-Type: application/json
Accept: application/json
Authorization: Bearer {{token}}
```

**Body → raw → JSON:**
```json
{
  "listing_id": 123
}
```

**Required fields:**
- `listing_id` - ID tin đăng

### Response mẫu

**Success (201):**
```json
{
  "message": "Listing added to bookmarks successfully",
  "data": {
    "id": 1,
    "user_id": 5,
    "listing_id": 123,
    "created_at": "2025-12-01T10:00:00.000000Z"
  }
}
```

**Error - Đã lưu rồi (400):**
```json
{
  "message": "Listing is already in your bookmarks"
}
```

**Error - Listing không tồn tại (404):**
```json
{
  "message": "Listing not found"
}
```

---

## 3. Xóa khỏi yêu thích – DELETE /bookmarks/{listing_id}

**Mục đích:** Xóa tin đăng khỏi danh sách yêu thích.

### Request

**Method:** `DELETE`  
**URL:** `{{base_url}}/bookmarks/123`

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
  "message": "Listing removed from bookmarks successfully"
}
```

**Error - Không có trong yêu thích (404):**
```json
{
  "message": "Listing not found in your bookmarks"
}
```

---

## Query Parameters Chi Tiết

| Parameter | Type | Description | Example |
|-----------|------|-------------|---------|
| `category_id` | integer | Lọc theo danh mục | `?category_id=1` |
| `min_price` | integer | Giá tối thiểu | `?min_price=10000000` |
| `max_price` | integer | Giá tối đa | `?max_price=30000000` |
| `sort` | string | newest, oldest, price_asc, price_desc | `?sort=newest` |
| `page` | integer | Số trang | `?page=1` |
| `per_page` | integer | Số item/trang | `?per_page=20` |

---

## Postman Tests Script

```javascript
// Cho POST /bookmarks
if (pm.response.code === 201) {
    var jsonData = pm.response.json();
    console.log("Bookmark added:", jsonData.data.listing_id);
}

// Cho GET /bookmarks
if (pm.response.code === 200) {
    var jsonData = pm.response.json();
    console.log("Total bookmarks:", jsonData.meta.total);
}
```

---

## Test Flow trong Postman

1. **Login** → Lưu token
2. **GET /listings** → Tìm listing muốn lưu
3. **POST /bookmarks** → Thêm vào yêu thích
4. **GET /bookmarks** → Xem danh sách yêu thích
5. **DELETE /bookmarks/123** → Xóa khỏi yêu thích

---

## Use Cases

### Buyer Use Cases
- Lưu sản phẩm để mua sau
- So sánh giá các sản phẩm đã lưu
- Theo dõi sản phẩm yêu thích
- Nhận thông báo khi giá giảm

### UI Suggestions
- Icon trái tim để bookmark
- Badge hiển thị số lượng bookmarks
- Filter bookmarks theo category
- Sort theo giá, thời gian
- Notification khi sản phẩm giảm giá

---

## Lưu ý

- Mỗi user có thể bookmark nhiều listings
- Không thể bookmark cùng listing 2 lần
- Bookmark tự động xóa khi listing bị xóa
- Có thể bookmark listing của bất kỳ shop nào
- Seller cũng có thể bookmark listings
- Không giới hạn số lượng bookmarks
