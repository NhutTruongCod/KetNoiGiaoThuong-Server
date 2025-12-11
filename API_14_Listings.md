# API Listings - Quản lý tin đăng

**Base URL:** `{{base_url}}` = `http://localhost:8000/api`

---

## 1. Danh sách tin đăng – GET /listings

**Mục đích:** Lấy danh sách tất cả tin đăng (public, có filter).

### Request

**Method:** `GET`  
**URL:** `{{base_url}}/listings`

**Headers:**
```
Accept: application/json
```

**Query Parameters (optional):**
```
?page=1
&limit=20
&search=iphone
&category=Điện tử
&shop_id=1
&type=sell
&status=published
```

**Body:** Không cần

### Response mẫu

**Success (200):**
```json
{
  "status": "success",
  "data": [
    {
      "id": 123,
      "user_id": 2,
      "shop_id": 1,
      "shop": {
        "id": 1,
        "name": "Tech Store",
        "logo": "https://example.com/logo.jpg",
        "rating": 4.5
      },
      "title": "iPhone 15 Pro Max 256GB",
      "slug": "iphone-15-pro-max-256gb-1234567890",
      "description": "Hàng chính hãng, mới 100%, bảo hành 12 tháng",
      "category": "Điện tử",
      "type": "sell",
      "price_cents": 3490000000,
      "currency": "VND",
      "stock_qty": 50,
      "total_reviews": 12,
      "rating": 4.50,
      "images": [
        "https://example.com/image1.jpg",
        "https://example.com/image2.jpg"
      ],
      "location_text": "Hà Nội",
      "latitude": 21.028511,
      "longitude": 105.804817,
      "status": "published",
      "is_active": true,
      "is_public": true,
      "meta": {
        "brand": "Apple",
        "color": "Titan Tự Nhiên",
        "storage": "256GB",
        "warranty": "12 tháng"
      },
      "created_at": "2025-11-01T10:00:00.000000Z",
      "updated_at": "2025-12-01T10:00:00.000000Z"
    }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 20,
    "total": 150,
    "last_page": 8
  }
}
```

**Listing Types:**
- `sell` - Bán
- `buy` - Mua
- `service` - Dịch vụ

**Listing Status:**
- `draft` - Nháp
- `published` - Đã xuất bản
- `archived` - Đã lưu trữ

---

## 2. Chi tiết tin đăng – GET /listings/{listing}

**Mục đích:** Xem chi tiết một tin đăng.

### Request

**Method:** `GET`  
**URL:** `{{base_url}}/listings/123`

**Headers:**
```
Accept: application/json
```

**Body:** Không cần

### Response mẫu

**Success (200):**
```json
{
  "id": 123,
  "shop_id": 1,
  "shop": {
    "id": 1,
    "name": "Tech Store",
    "slug": "tech-store",
    "logo": "https://example.com/logo.jpg",
    "banner": "https://example.com/banner.jpg",
    "address": "123 Nguyễn Huệ, Q1, TP.HCM",
    "phone": "0901234567",
    "rating": 4.5,
    "total_products": 150,
    "total_orders": 350
  },
  "category_id": 1,
  "category": {
    "id": 1,
    "name": "Điện tử",
    "slug": "dien-tu"
  },
  "title": "iPhone 15 Pro Max 256GB",
  "slug": "iphone-15-pro-max-256gb",
  "description": "Hàng chính hãng VN/A, mới 100%, nguyên seal. Bảo hành 12 tháng tại Apple Store. Giao hàng toàn quốc.",
  "type": "product",
  "price": 29990000,
  "original_price": 34990000,
  "discount_percent": 14,
  "currency": "VND",
  "stock_qty": 50,
  "min_order_qty": 1,
  "max_order_qty": 5,
  "images": [
    "https://example.com/image1.jpg",
    "https://example.com/image2.jpg",
    "https://example.com/image3.jpg"
  ],
  "video_url": "https://youtube.com/watch?v=xxx",
  "specifications": {
    "brand": "Apple",
    "model": "iPhone 15 Pro Max",
    "color": "Titan Tự Nhiên",
    "storage": "256GB",
    "ram": "8GB",
    "screen": "6.7 inch",
    "warranty": "12 tháng"
  },
  "location": {
    "city": "Ho Chi Minh",
    "district": "District 1",
    "address": "123 Nguyễn Huệ"
  },
  "shipping": {
    "free_shipping": false,
    "shipping_fee": 30000,
    "estimated_days": "2-3"
  },
  "views_count": 1250,
  "likes_count": 45,
  "comments_count": 12,
  "orders_count": 25,
  "status": "active",
  "is_featured": false,
  "featured_until": null,
  "tags": ["iphone", "apple", "smartphone"],
  "seo": {
    "meta_title": "iPhone 15 Pro Max 256GB - Giá tốt",
    "meta_description": "Mua iPhone 15 Pro Max 256GB chính hãng..."
  },
  "created_at": "2025-11-01T10:00:00.000000Z",
  "updated_at": "2025-12-01T10:00:00.000000Z"
}
```

**Lưu ý:** Mỗi lần GET sẽ tăng views_count lên 1.

---

## 3. Tạo tin đăng mới – POST /listings

**Mục đích:** Seller tạo tin đăng mới.

### Request

**Method:** `POST`  
**URL:** `{{base_url}}/listings`

**Headers:**
```
Content-Type: application/json
Accept: application/json
Authorization: Bearer {{seller_token}}
```

**Body → raw → JSON:**
```json
{
  "title": "iPhone 15 Pro Max 256GB",
  "description": "Hàng chính hãng VN/A, mới 100%, nguyên seal. Bảo hành 12 tháng tại Apple Store.",
  "price_cents": 3490000000,
  "category": "Điện tử",
  "type": "sell",
  "stock_qty": 50,
  "shop_id": 1,
  "images": [
    "https://example.com/uploads/image1.jpg",
    "https://example.com/uploads/image2.jpg"
  ],
  "location_text": "Hà Nội",
  "latitude": 21.028511,
  "longitude": 105.804817,
  "meta": {
    "brand": "Apple",
    "color": "Titan Tự Nhiên",
    "storage": "256GB",
    "warranty": "12 tháng"
  },
  "status": "draft"
}
```

**Required fields:**
- `title` - Tiêu đề (max 255 ký tự)
- `price_cents` - Giá (tính bằng cents/đồng, integer)

**Optional fields:**
- `slug` - Slug (auto-generate nếu không có)
- `description` - Mô tả (max 5000 ký tự)
- `category` - Danh mục (string, max 100 ký tự)
- `type` - Loại: sell, buy, service (default: sell)
- `currency` - Tiền tệ (default: VND)
- `stock_qty` - Số lượng tồn kho (integer)
- `shop_id` - ID gian hàng
- `images` - Ảnh (array URLs, max 512 ký tự mỗi URL)
- `location_text` - Địa chỉ text
- `latitude` - Vĩ độ (-90 đến 90)
- `longitude` - Kinh độ (-180 đến 180)
- `meta` - Metadata (JSON object)
- `status` - draft, published, archived (default: draft)
- `is_active` - Kích hoạt (boolean, default: true)
- `is_public` - Công khai (boolean, default: true)

### Response mẫu

**Success (201):**
```json
{
  "status": "success",
  "message": "Bài đăng đã được tạo thành công",
  "data": {
    "id": 123,
    "user_id": 2,
    "shop_id": 1,
    "shop": {
      "id": 1,
      "name": "Tech Store"
    },
    "user": {
      "id": 2,
      "name": "Nguyễn Văn A",
      "email": "seller@example.com"
    },
    "title": "iPhone 15 Pro Max 256GB",
    "slug": "iphone-15-pro-max-256gb-1733308800",
    "price_cents": 3490000000,
    "currency": "VND",
    "status": "draft",
    "is_active": true,
    "is_public": true,
    "created_at": "2025-12-01T10:00:00.000000Z",
    "updated_at": "2025-12-01T10:00:00.000000Z"
  }
}
```

**Error - Không phải Seller (403):**
```json
{
  "status": "error",
  "message": "Chỉ người bán (seller) mới có quyền đăng tin. Vui lòng nâng cấp tài khoản."
}
```

---

## 4. Cập nhật tin đăng – PUT /listings/{listing}

**Mục đích:** Seller cập nhật tin đăng của mình.

### Request

**Method:** `PUT`  
**URL:** `  `

**Headers:**
```
Content-Type: application/json
Accept: application/json
Authorization: Bearer {{seller_token}}
```

**Body → raw → JSON:**
```json
{
  "title": "iPhone 15 Pro Max 256GB - Giá tốt nhất",
  "description": "Cập nhật: Giảm giá sốc!",
  "price_cents": 3290000000,
  "stock_qty": 45,
  "status": "published"
}
```

### Response mẫu

**Success (200):**
```json
{
  "status": "success",
  "message": "Bài đăng đã được cập nhật thành công",
  "data": {
    "id": 123,
    "user_id": 2,
    "shop_id": 1,
    "title": "iPhone 15 Pro Max 256GB - Giá tốt nhất",
    "slug": "iphone-15-pro-max-256gb-gia-tot-nhat-1733308900",
    "price_cents": 3290000000,
    "stock_qty": 45,
    "status": "published",
    "updated_at": "2025-12-01T11:00:00.000000Z"
  }
}
```

**Error - Không có quyền (403):**
```json
{
  "status": "error",
  "message": "Bạn không có quyền cập nhật bài đăng này"
}
```

---

## 5. Xóa tin đăng – DELETE /listings/{listing}

**Mục đích:** Seller xóa tin đăng của mình.

### Request

**Method:** `DELETE`  
**URL:** `{{base_url}}/listings/123`

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
  "status": "success",
  "message": "Bài đăng đã được xóa thành công"
}
```

**Error - Không có quyền (403):**
```json
{
  "status": "error",
  "message": "Bạn không có quyền xóa bài đăng này"
}
```

**Error - Đang có quảng cáo (409):**
```json
{
  "status": "error",
  "message": "Không thể xóa vì bài đăng đang trong chiến dịch quảng cáo"
}
```

---

## 6. Admin duyệt tin đăng – PUT /admin/listings/{listing}/approve

**Mục đích:** Admin duyệt tin đăng.

### Request

**Method:** `PUT`  
**URL:** `{{base_url}}/admin/listings/123/approve`

**Headers:**
```
Content-Type: application/json
Accept: application/json
Authorization: Bearer {{admin_token}}
```

**Body → raw → JSON:**
```json
{
  "status": "published",
  "reason": "Tin đăng hợp lệ"
}
```

**Hoặc từ chối:**
```json
{
  "status": "archived",
  "reason": "Ảnh không rõ ràng, vui lòng cập nhật lại"
}
```

### Response mẫu

**Success (200):**
```json
{
  "status": "success",
  "message": "Bài đăng đã được duyệt",
  "data": {
    "id": 123,
    "user_id": 2,
    "shop_id": 1,
    "title": "iPhone 15 Pro Max 256GB",
    "status": "published",
    "updated_at": "2025-12-01T15:00:00.000000Z"
  }
}
```

**Error - Không phải Admin (403):**
```json
{
  "status": "error",
  "message": "Chỉ admin mới có quyền duyệt bài đăng"
}
```

---

## Query Parameters Chi Tiết

| Parameter | Type | Description | Example |
|-----------|------|-------------|---------|
| `search` | string | Tìm theo title, description | `?search=iphone` |
| `category` | string | Lọc theo danh mục | `?category=Điện tử` |
| `shop_id` | integer | Lọc theo shop | `?shop_id=1` |
| `type` | string | sell, buy, service | `?type=sell` |
| `status` | string | draft, published, archived | `?status=published` |
| `page` | integer | Số trang | `?page=1` |
| `limit` | integer | Số item/trang | `?limit=20` |

---

## Postman Tests Script

```javascript
// Cho POST /listings
if (pm.response.code === 201) {
    var jsonData = pm.response.json();
    if (jsonData.data && jsonData.data.id) {
        pm.environment.set("listing_id", jsonData.data.id);
        console.log("Listing ID saved:", jsonData.data.id);
    }
}
```

---

## Test Flow trong Postman

1. **GET /listings** → Xem danh sách (public)
2. **GET /listings?category_id=1&min_price=10000000** → Lọc
3. **GET /listings/123** → Xem chi tiết
4. **Login as Seller** → Lưu seller_token
5. **POST /listings** → Tạo tin đăng mới
6. **PUT /listings/123** → Cập nhật tin đăng
7. **DELETE /listings/123** → Xóa tin đăng
8. **Login as Admin** → Duyệt tin

---

## Lưu ý

- Buyer không thể tạo listings
- Seller chỉ sửa/xóa listings của mình
- Admin có thể duyệt/từ chối listings
- Mỗi plan có giới hạn số lượng listings
- Images nên upload trước, sau đó gửi URLs
- Slug tự động tạo từ title
- Views count tăng mỗi lần xem chi tiết
- Featured listings hiển thị ưu tiên


---

## 7. Upload ảnh sản phẩm – POST /listings/{listing}/images

**Mục đích:** Upload file ảnh trực tiếp cho sản phẩm (thay vì nhập URL).

### Request

**Method:** `POST`  
**URL:** `{{base_url}}/listings/123/images`

**Headers:**
```
Accept: application/json
Authorization: Bearer {{seller_token}}
Content-Type: multipart/form-data
```

**Body → form-data:**
```
images[]: (file) image1.jpg
images[]: (file) image2.png
images[]: (file) image3.webp
```

**Giới hạn:**
- Tối đa 10 ảnh mỗi lần upload
- Mỗi ảnh tối đa 5MB
- Định dạng: jpeg, png, jpg, gif, webp

### Response mẫu

**Success (200):**
```json
{
  "status": "success",
  "message": "Upload 3 ảnh thành công",
  "data": {
    "uploaded": [
      {
        "id": 1,
        "listing_id": 123,
        "url": "http://localhost:8000/storage/listings/123/listing_123_1701849600_0.jpg",
        "sort_order": 1
      },
      {
        "id": 2,
        "listing_id": 123,
        "url": "http://localhost:8000/storage/listings/123/listing_123_1701849600_1.png",
        "sort_order": 2
      }
    ],
    "all_images": [
      "http://localhost:8000/storage/listings/123/listing_123_1701849600_0.jpg",
      "http://localhost:8000/storage/listings/123/listing_123_1701849600_1.png"
    ]
  }
}
```

**Error - Validation (422):**
```json
{
  "status": "error",
  "message": "Validation failed",
  "errors": {
    "images.0": ["Mỗi ảnh tối đa 5MB"],
    "images.1": ["Chỉ chấp nhận định dạng: jpeg, png, jpg, gif, webp"]
  }
}
```

---

## 8. Xóa ảnh sản phẩm – DELETE /listings/{listing}/images/{imageId}

**Mục đích:** Xóa một ảnh của sản phẩm.

### Request

**Method:** `DELETE`  
**URL:** `{{base_url}}/listings/123/images/5`

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
  "status": "success",
  "message": "Đã xóa ảnh",
  "data": {
    "remaining_images": [
      "http://localhost:8000/storage/listings/123/listing_123_1701849600_1.png"
    ]
  }
}
```

---

## 9. Sắp xếp lại thứ tự ảnh – PUT /listings/{listing}/images/reorder

**Mục đích:** Thay đổi thứ tự hiển thị ảnh (ảnh đầu tiên là ảnh chính).

### Request

**Method:** `PUT`  
**URL:** `{{base_url}}/listings/123/images/reorder`

**Headers:**
```
Content-Type: application/json
Accept: application/json
Authorization: Bearer {{seller_token}}
```

**Body → raw → JSON:**
```json
{
  "image_ids": [3, 1, 2]
}
```

### Response mẫu

**Success (200):**
```json
{
  "status": "success",
  "message": "Đã sắp xếp lại ảnh",
  "data": {
    "images": [
      "http://localhost:8000/storage/listings/123/listing_123_1701849600_2.jpg",
      "http://localhost:8000/storage/listings/123/listing_123_1701849600_0.jpg",
      "http://localhost:8000/storage/listings/123/listing_123_1701849600_1.png"
    ]
  }
}
```

---

## Test Upload Ảnh với cURL

```bash
# Upload nhiều ảnh
curl -X POST "http://localhost:8000/api/listings/123/images" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -F "images[]=@/path/to/image1.jpg" \
  -F "images[]=@/path/to/image2.png"

# Xóa ảnh
curl -X DELETE "http://localhost:8000/api/listings/123/images/5" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN"

# Sắp xếp lại
curl -X PUT "http://localhost:8000/api/listings/123/images/reorder" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{"image_ids": [3, 1, 2]}'
```
