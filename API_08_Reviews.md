# API Reviews - Đánh giá sản phẩm

**Base URL:** `{{base_url}}` = `http://localhost:8000/api`

---

## 1. Danh sách đánh giá – GET /reviews

**Mục đích:** Lấy danh sách đánh giá (public, không cần auth).

**Logic hiển thị:**
- **Public:** Mọi người đều xem được danh sách đánh giá
- **User đã đăng nhập:** Xem được và biết mình đã đánh dấu "hữu ích" chưa
- **Seller:** Xem được đánh giá về shop mình và có thể phản hồi

### Request

**Method:** `GET`  
**URL:** `{{base_url}}/reviews`

**Headers:**
```
Accept: application/json
```

**Query Parameters (optional):**
```
?page=1
&per_page=20
&listing_id=123
&shop_id=1
&user_id=5
&rating=5
&verified=true
&with_reply=true
&with_images=true
&sort_by=helpful_count
&sort_order=desc
```

**Body:** Không cần

### Response mẫu

**Success (200):**
```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "order_id": 10,
        "listing_id": 123,
        "listing": {
          "id": 123,
          "title": "iPhone 15 Pro Max 256GB",
          "images": ["https://example.com/image1.jpg"]
        },
        "shop_id": 1,
        "shop": {
          "id": 1,
          "name": "Tech Store",
          "phone": "0901234567"
        },
        "user_id": 5,
        "user": {
          "id": 5,
          "name": "Nguyễn Văn B",
          "avatar": "https://example.com/avatar.jpg"
        },
        "rating": 5,
        "comment": "Sản phẩm tuyệt vời! Giao hàng nhanh, đóng gói cẩn thận. Rất hài lòng!",
        "images": [
          "https://example.com/review1.jpg",
          "https://example.com/review2.jpg"
        ],
        "helpful_count": 15,
        "is_verified_purchase": true,
        "seller_reply": {
          "content": "Cảm ơn bạn đã tin tưởng shop!",
          "user_id": 3,
          "created_at": "2025-12-03T11:00:00.000000Z"
        },
        "seller_reply_at": "2025-12-03T11:00:00.000000Z",
        "is_helpful": true,
        "created_at": "2025-12-01T10:00:00.000000Z",
        "updated_at": "2025-12-01T10:00:00.000000Z"
      }
    ],
    "total": 50,
    "per_page": 20,
    "last_page": 3
  }
}
```

**Rating Values:**
- `1` - Rất không hài lòng
- `2` - Không hài lòng
- `3` - Bình thường
- `4` - Hài lòng
- `5` - Rất hài lòng

---

## 2. Chi tiết đánh giá – GET /reviews/{id}

**Mục đích:** Xem chi tiết một đánh giá.

### Request

**Method:** `GET`  
**URL:** `{{base_url}}/reviews/1`

**Headers:**
```
Accept: application/json
```

**Body:** Không cần

### Response mẫu

**Success (200):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "order_id": 10,
    "order": {
      "id": 10,
      "order_number": "ORD-20251201-0001",
      "status": "completed"
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
      ]
    },
    "shop_id": 1,
    "shop": {
      "id": 1,
      "name": "Tech Store",
      "address": "123 Nguyễn Huệ, Q1, TP.HCM",
      "phone": "0901234567",
      "rating": 4.5
    },
    "user_id": 5,
    "user": {
      "id": 5,
      "name": "Nguyễn Văn B",
      "email": "buyer1@tradehub.com",
      "avatar": "https://example.com/avatar.jpg"
    },
    "rating": 5,
    "comment": "Sản phẩm tuyệt vời! Giao hàng nhanh, đóng gói cẩn thận. Rất hài lòng! Sẽ ủng hộ shop lâu dài.",
    "images": [
      "https://example.com/review1.jpg",
      "https://example.com/review2.jpg",
      "https://example.com/review3.jpg"
    ],
    "helpful_count": 15,
    "is_verified_purchase": true,
    "seller_reply": {
      "content": "Cảm ơn bạn đã tin tưởng shop! Chúng tôi rất vui khi bạn hài lòng.",
      "user_id": 3,
      "created_at": "2025-12-03T11:00:00.000000Z"
    },
    "seller_reply_at": "2025-12-03T11:00:00.000000Z",
    "is_helpful": false,
    "created_at": "2025-12-01T10:00:00.000000Z",
    "updated_at": "2025-12-01T10:00:00.000000Z"
  }
}
```

**Error - Không tìm thấy (404):**
```json
{
  "success": false,
  "message": "Đánh giá không tồn tại"
}
```

---

## 3. Tạo đánh giá mới – POST /reviews

**Mục đích:** Buyer tạo đánh giá sau khi nhận hàng (đơn hàng đã completed).

**Lưu ý:** 
- Chỉ buyer của đơn hàng mới được đánh giá
- Đơn hàng phải ở trạng thái `completed`
- Mỗi đơn hàng chỉ được đánh giá 1 lần
- Tự động set `is_verified_purchase = true`

### Request

**Method:** `POST`  
**URL:** `{{base_url}}/reviews`

**Headers:**
```
Content-Type: application/json
Accept: application/json
Authorization: Bearer {{token}}
```

**Body → raw → JSON:**
```json
{
  "order_id": 10,
  "listing_id": 123,
  "rating": 5,
  "comment": "Sản phẩm tuyệt vời! Giao hàng nhanh, đóng gói cẩn thận. Rất hài lòng!",
  "images": [
    "https://example.com/uploaded/image1.jpg",
    "https://example.com/uploaded/image2.jpg"
  ]
}
```

**Required fields:**
- `order_id` - ID đơn hàng (phải đã completed)
- `rating` - Điểm đánh giá (1-5)
- `comment` - Nội dung đánh giá (min: 10 ký tự, max: 1000 ký tự)

**Optional fields:**
- `listing_id` - ID tin đăng (tự động lấy từ order nếu không có)
- `images` - Array URLs ảnh đã upload (max 5 ảnh)

**Lưu ý về ảnh:**
- Nếu muốn upload ảnh, cần upload trước qua endpoint riêng (hoặc dùng base64)
- Hoặc dùng form-data (xem phần dưới)

### Response mẫu

**Success (201):**
```json
{
  "success": true,
  "message": "Đánh giá thành công",
  "data": {
    "id": 1,
    "order_id": 10,
    "listing_id": 123,
    "shop_id": 1,
    "user_id": 5,
    "rating": 5,
    "comment": "Sản phẩm tuyệt vời! Giao hàng nhanh, đóng gói cẩn thận. Rất hài lòng!",
    "images": [
      "/storage/reviews/abc123.jpg",
      "/storage/reviews/def456.jpg"
    ],
    "is_verified_purchase": true,
    "helpful_count": 0,
    "created_at": "2025-12-01T10:00:00.000000Z"
  }
}
```

**Error - Đã đánh giá rồi (400):**
```json
{
  "success": false,
  "message": "Đơn hàng này đã được đánh giá"
}
```

**Error - Đơn hàng chưa hoàn thành (400):**
```json
{
  "success": false,
  "message": "Chỉ có thể đánh giá đơn hàng đã hoàn thành"
}
```

**Error - Không phải đơn hàng của bạn (403):**
```json
{
  "success": false,
  "message": "Bạn chỉ có thể đánh giá đơn hàng của chính mình"
}
```

### Cách 2: Upload ảnh cùng lúc (form-data)

**Headers:**
```
Content-Type: multipart/form-data
Accept: application/json
Authorization: Bearer {{token}}
```

**Body → form-data:**
```
order_id: 10
listing_id: 123
rating: 5
comment: Sản phẩm tuyệt vời! Giao hàng nhanh, đóng gói cẩn thận. Rất hài lòng!
images[]: [file1.jpg]
images[]: [file2.jpg]
```

**Lưu ý:**
- Dùng form-data khi muốn upload file trực tiếp
- Mỗi ảnh max 2MB, định dạng: jpg, jpeg, png
- Max 5 ảnh

---

## 4. Cập nhật đánh giá – PUT /reviews/{id}

**Mục đích:** User cập nhật đánh giá của mình.

### Request

**Method:** `PUT`  
**URL:** `{{base_url}}/reviews/1`

**Headers:**
```
Content-Type: application/json
Accept: application/json
Authorization: Bearer {{token}}
```

**Body → raw → JSON:**
```json
{
  "rating": 4,
  "comment": "Sản phẩm tốt nhưng giao hơi chậm. Cập nhật: Đã liên hệ với shop và được giải quyết tốt."
}
```

**Hoặc cập nhật với ảnh mới (form-data):**
```
rating: 4
comment: Sản phẩm tốt...
images[]: [new_file1.jpg]
images[]: [new_file2.jpg]
```

### Response mẫu

**Success (200):**
```json
{
  "success": true,
  "message": "Cập nhật đánh giá thành công",
  "data": {
    "id": 1,
    "rating": 4,
    "comment": "Sản phẩm tốt nhưng giao hơi chậm. Cập nhật: Đã liên hệ với shop và được giải quyết tốt.",
    "images": [
      "/storage/reviews/new1.jpg",
      "/storage/reviews/new2.jpg"
    ],
    "updated_at": "2025-12-01T15:00:00.000000Z"
  }
}
```

**Error - Không có quyền (403):**
```json
{
  "success": false,
  "message": "Bạn không có quyền cập nhật đánh giá này"
}
```

---

## 5. Xóa đánh giá – DELETE /reviews/{id}

**Mục đích:** User xóa đánh giá của mình (hoặc Admin xóa đánh giá vi phạm).

### Request

**Method:** `DELETE`  
**URL:** `{{base_url}}/reviews/1`

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
  "success": true,
  "message": "Xóa đánh giá thành công"
}
```

**Error - Không có quyền (403):**
```json
{
  "success": false,
  "message": "Bạn không có quyền xóa đánh giá này"
}
```

**Lưu ý:** 
- User chỉ có thể xóa đánh giá của mình
- Admin có thể xóa bất kỳ đánh giá nào
- Khi xóa, ảnh đánh giá cũng bị xóa
- Rating của listing và shop sẽ được cập nhật lại

---

## 6. Thống kê đánh giá – GET /reviews/summary

**Mục đích:** Lấy thống kê rating và phân bố điểm của listing hoặc shop.

### Request

**Method:** `GET`  
**URL:** `{{base_url}}/reviews/summary`

**Headers:**
```
Accept: application/json
```

**Query Parameters (required một trong hai):**
```
?listing_id=123
hoặc
?shop_id=1
```

**Body:** Không cần

### Response mẫu

**Success (200):**
```json
{
  "success": true,
  "data": {
    "total_reviews": 50,
    "average_rating": 4.5,
    "rating_distribution": {
      "5": 30,
      "4": 15,
      "3": 3,
      "2": 1,
      "1": 1
    }
  }
}
```

**Error - Thiếu tham số (400):**
```json
{
  "success": false,
  "message": "Cần cung cấp listing_id hoặc shop_id"
}
```

---

## 7. Đánh dấu hữu ích – POST /reviews/{id}/helpful

**Mục đích:** User đánh dấu đánh giá là hữu ích.

### Request

**Method:** `POST`  
**URL:** `{{base_url}}/reviews/1/helpful`

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
  "success": true,
  "message": "Đã đánh dấu đánh giá là hữu ích",
  "data": {
    "helpful_count": 16
  }
}
```

**Error - Đã đánh dấu rồi (400):**
```json
{
  "success": false,
  "message": "Bạn đã đánh dấu đánh giá này là hữu ích rồi"
}
```

---

## 8. Bỏ đánh dấu hữu ích – DELETE /reviews/{id}/helpful

**Mục đích:** User bỏ đánh dấu hữu ích.

### Request

**Method:** `DELETE`  
**URL:** `{{base_url}}/reviews/1/helpful`

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
  "success": true,
  "message": "Đã bỏ đánh dấu hữu ích",
  "data": {
    "helpful_count": 15
  }
}
```

---

## 9. Seller phản hồi – POST /reviews/{id}/reply

**Mục đích:** Seller phản hồi đánh giá về shop mình.

### Request

**Method:** `POST`  
**URL:** `{{base_url}}/reviews/1/reply`

**Headers:**
```
Content-Type: application/json
Accept: application/json
Authorization: Bearer {{seller_token}}
```

**Body → raw → JSON:**
```json
{
  "reply": "Cảm ơn bạn đã tin tưởng shop! Chúng tôi rất vui khi bạn hài lòng."
}
```

**Required fields:**
- `reply` - Nội dung phản hồi (min: 10 ký tự, max: 500 ký tự)

### Response mẫu

**Success (200):**
```json
{
  "success": true,
  "message": "Đã phản hồi đánh giá",
  "data": {
    "id": 1,
    "seller_reply": {
      "content": "Cảm ơn bạn đã tin tưởng shop! Chúng tôi rất vui khi bạn hài lòng.",
      "user_id": 3,
      "created_at": "2025-12-03T11:00:00.000000Z"
    },
    "seller_reply_at": "2025-12-03T11:00:00.000000Z"
  }
}
```

**Error - Không phải chủ shop (403):**
```json
{
  "success": false,
  "message": "Chỉ chủ shop mới có thể phản hồi đánh giá"
}
```

---

## 10. Đánh giá của tôi – GET /reviews/my-reviews

**Mục đích:** Xem tất cả đánh giá mình đã viết.

### Request

**Method:** `GET`  
**URL:** `{{base_url}}/reviews/my-reviews`

**Headers:**
```
Accept: application/json
Authorization: Bearer {{token}}
```

**Query Parameters (optional):**
```
?page=1
&per_page=20
&sort_by=created_at
&sort_order=desc
```

**Body:** Không cần

### Response mẫu

**Success (200):**
```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "rating": 5,
        "comment": "Sản phẩm tốt...",
        "listing": {
          "id": 123,
          "title": "iPhone 15 Pro Max"
        },
        "shop": {
          "id": 1,
          "name": "Tech Store"
        },
        "order": {
          "id": 10,
          "order_number": "ORD-20251201-0001"
        },
        "created_at": "2025-12-01T10:00:00.000000Z"
      }
    ],
    "total": 5,
    "per_page": 20,
    "last_page": 1
  }
}
```

---

## Query Parameters Chi Tiết

| Parameter | Type | Description | Example |
|-----------|------|-------------|---------|
| `listing_id` | integer | Lọc theo tin đăng | `?listing_id=123` |
| `shop_id` | integer | Lọc theo shop | `?shop_id=1` |
| `user_id` | integer | Lọc theo user | `?user_id=5` |
| `rating` | integer | Lọc theo rating (1-5) | `?rating=5` |
| `verified` | boolean | Chỉ xem đánh giá đã mua | `?verified=true` |
| `with_reply` | boolean | Có seller reply | `?with_reply=true` |
| `with_images` | boolean | Có ảnh | `?with_images=true` |
| `page` | integer | Số trang | `?page=1` |
| `per_page` | integer | Số item/trang | `?per_page=20` |
| `sort_by` | string | helpful_count, rating, created_at | `?sort_by=helpful_count` |
| `sort_order` | string | asc, desc | `?sort_order=desc` |

---

## Postman Tests Script

### Lưu review_id sau khi tạo

```javascript
// Cho POST /reviews
if (pm.response.code === 201) {
    var jsonData = pm.response.json();
    if (jsonData.data && jsonData.data.id) {
        pm.environment.set("review_id", jsonData.data.id);
        console.log("Review ID saved:", jsonData.data.id);
    }
}
```

---

## Test Flow trong Postman

### Buyer Flow:
1. **Login as Buyer** → Lưu token
2. **Tạo và hoàn thành order** (xem API_07_Orders.md)
3. **POST /reviews** → Tạo đánh giá cho order
4. **GET /reviews?listing_id=123** → Xem đánh giá của listing
5. **PUT /reviews/{id}** → Cập nhật đánh giá (nếu cần)
6. **GET /reviews/my-reviews** → Xem đánh giá của mình

### Public Flow:
1. **GET /reviews?listing_id=123** → Xem đánh giá (không cần auth)
2. **GET /reviews/1** → Xem chi tiết đánh giá
3. **GET /reviews/summary?listing_id=123** → Xem thống kê

### User Flow (đánh dấu hữu ích):
1. **Login as User** → Lưu token
2. **POST /reviews/1/helpful** → Đánh dấu hữu ích
3. **DELETE /reviews/1/helpful** → Bỏ đánh dấu

### Seller Flow:
1. **Login as Seller** → Lưu token
2. **GET /reviews?shop_id=1** → Xem đánh giá về shop
3. **POST /reviews/1/reply** → Phản hồi đánh giá

---

## Lưu ý

- Chỉ có thể đánh giá đơn hàng đã completed
- Mỗi order chỉ được đánh giá 1 lần
- User chỉ có thể sửa/xóa đánh giá của mình
- Seller có thể reply đánh giá về shop mình
- Ảnh review giúp tăng độ tin cậy
- `is_verified_purchase = true` nghĩa là đã mua hàng thật
- Rating từ 1-5 sao
- Helpful count: số người thấy review hữu ích
- Xem reviews không cần đăng nhập
- Tạo/sửa/xóa/helpful/reply cần đăng nhập
- Rating của listing và shop tự động cập nhật khi có review mới/sửa/xóa
