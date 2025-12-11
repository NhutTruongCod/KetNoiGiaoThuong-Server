# API Shops - Quản lý gian hàng

**Base URL:** `{{base_url}}` = `http://localhost:8000/api`

---

## 1. Danh sách gian hàng – GET /shops

**Mục đích:** Lấy danh sách tất cả gian hàng (public, không cần auth).

### Request

**Method:** `GET`  
**URL:** `{{base_url}}/shops`

**Headers:**
```
Accept: application/json
```

**Query Parameters (optional):**
```
?page=1
&per_page=20
&search=tech
&status=active
&user_id=1
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
      "user_id": 2,
      "name": "Tech Store",
      "slug": "tech-store",
      "description": "Chuyên cung cấp thiết bị điện tử",
      "logo": "https://example.com/logos/tech-store.jpg",
      "banner": "https://example.com/banners/tech-store.jpg",
      "address": "123 Nguyễn Huệ, Q1, TP.HCM",
      "phone": "0901234567",
      "email": "tech@example.com",
      "is_active": true,
      "rating": 4.5,
      "total_products": 150,
      "total_orders": 350,
      "created_at": "2025-11-01T10:00:00.000000Z",
      "owner": {
        "id": 2,
        "name": "Công ty ABC",
        "email": "seller1@tradehub.com"
      }
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

---

## 2. Chi tiết gian hàng – GET /shops/{shop}

**Mục đích:** Xem thông tin chi tiết một gian hàng.

### Request

**Method:** `GET`  
**URL:** `{{base_url}}/shops/1`

**Headers:**
```
Accept: application/json
```

**Body:** Không cần

### Response mẫu

**Success (200):**
```json
{
  "id": 1,
  "user_id": 2,
  "name": "Tech Store",
  "slug": "tech-store",
  "description": "Chuyên cung cấp thiết bị điện tử chính hãng",
  "logo": "https://example.com/logos/tech-store.jpg",
  "banner": "https://example.com/banners/tech-store.jpg",
  "address": "123 Nguyễn Huệ, Quận 1, TP.HCM",
  "phone": "0901234567",
  "email": "tech@example.com",
  "website": "https://techstore.com",
  "is_active": true,
  "rating": 4.5,
  "total_reviews": 120,
  "total_products": 150,
  "total_orders": 350,
  "total_followers": 500,
  "created_at": "2025-11-01T10:00:00.000000Z",
  "updated_at": "2025-12-01T10:00:00.000000Z",
  "owner": {
    "id": 2,
    "name": "Công ty ABC",
    "email": "seller1@tradehub.com",
    "phone": "0901234567"
  },
  "social_links": {
    "facebook": "https://facebook.com/techstore",
    "instagram": "https://instagram.com/techstore"
  }
}
```

**Error - Không tìm thấy (404):**
```json
{
  "message": "Shop not found"
}
```

---

## 3. Tạo gian hàng mới – POST /shops

**Mục đích:** Seller tạo gian hàng của mình.

### Request

**Method:** `POST`  
**URL:** `{{base_url}}/shops`

**Headers:**
```
Content-Type: application/json
Accept: application/json
Authorization: Bearer {{seller_token}}
```

**Body → raw → JSON:**
```json
{
  "name": "Tech Store Pro",
  "description": "Chuyên cung cấp thiết bị điện tử chính hãng, giá tốt nhất thị trường",
  "address": "123 Nguyễn Huệ, Quận 1, TP.HCM",
  "phone": "0901234567",
  "email": "techpro@example.com",
  "website": "https://techstorepro.com",
  "logo": "https://example.com/logo.jpg",
  "banner": "https://example.com/banner.jpg",
  "social_links": {
    "facebook": "https://facebook.com/techstorepro",
    "instagram": "https://instagram.com/techstorepro",
    "zalo": "0901234567"
  }
}
```

**Required fields:**
- `name` - Tên gian hàng (unique)
- `description` - Mô tả
- `address` - Địa chỉ
- `phone` - Số điện thoại

**Optional fields:**
- `email` - Email liên hệ
- `website` - Website
- `logo` - URL logo
- `banner` - URL banner
- `social_links` - Links mạng xã hội

### Response mẫu

**Success (201):**
```json
{
  "message": "Shop created successfully",
  "data": {
    "id": 5,
    "user_id": 2,
    "name": "Tech Store Pro",
    "slug": "tech-store-pro",
    "description": "Chuyên cung cấp thiết bị điện tử chính hãng, giá tốt nhất thị trường",
    "address": "123 Nguyễn Huệ, Quận 1, TP.HCM",
    "phone": "0901234567",
    "email": "techpro@example.com",
    "is_active": true,
    "created_at": "2025-12-01T10:00:00.000000Z"
  }
}
```

**Error - Validation (422):**
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "name": ["The name has already been taken."],
    "phone": ["The phone field is required."]
  }
}
```

**Error - Không phải Seller (403):**
```json
{
  "message": "Only sellers can create shops"
}
```

---

## 4. Cập nhật gian hàng – PUT /shops/{shop}

**Mục đích:** Seller cập nhật thông tin gian hàng của mình.

### Request

**Method:** `PUT`  
**URL:** `{{base_url}}/shops/1`

**Headers:**
```
Content-Type: application/json
Accept: application/json
Authorization: Bearer {{seller_token}}
```

**Body → raw → JSON:**
```json
{
  "name": "Tech Store Pro - Updated",
  "description": "Mô tả mới",
  "address": "456 Lê Lợi, Quận 1, TP.HCM",
  "phone": "0901234568",
  "email": "newemail@example.com",
  "website": "https://newwebsite.com",
  "is_active": true,
  "logo": "https://example.com/new-logo.jpg",
  "banner": "https://example.com/new-banner.jpg"
}
```

### Response mẫu

**Success (200):**
```json
{
  "message": "Shop updated successfully",
  "data": {
    "id": 1,
    "name": "Tech Store Pro - Updated",
    "description": "Mô tả mới",
    "address": "456 Lê Lợi, Quận 1, TP.HCM",
    "phone": "0901234568",
    "updated_at": "2025-12-01T11:00:00.000000Z"
  }
}
```

**Error - Không có quyền (403):**
```json
{
  "message": "You can only update your own shop"
}
```

---

## 5. Xóa gian hàng – DELETE /shops/{shop}

**Mục đích:** Seller xóa gian hàng của mình.

### Request

**Method:** `DELETE`  
**URL:** `{{base_url}}/shops/1`

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
  "message": "Shop deleted successfully"
}
```

**Error - Không có quyền (403):**
```json
{
  "message": "You can only delete your own shop"
}
```

**Error - Shop có listings (400):**
```json
{
  "message": "Cannot delete shop with active listings. Please delete all listings first."
}
```

---

## Query Parameters Chi Tiết

| Parameter | Type | Description | Example |
|-----------|------|-------------|---------|
| `search` | string | Tìm theo tên, mô tả | `?search=tech` |
| `status` | string | active, inactive | `?status=active` |
| `user_id` | integer | Lọc theo chủ shop | `?user_id=2` |
| `page` | integer | Số trang | `?page=1` |
| `per_page` | integer | Số item/trang | `?per_page=20` |
| `sort` | string | Sắp xếp: created_at, name, rating | `?sort=rating` |
| `order` | string | asc, desc | `?order=desc` |

---

## Postman Tests Script

### Lưu shop_id sau khi tạo

```javascript
// Cho POST /shops
if (pm.response.code === 201) {
    var jsonData = pm.response.json();
    if (jsonData.data && jsonData.data.id) {
        pm.environment.set("shop_id", jsonData.data.id);
        console.log("Shop ID saved:", jsonData.data.id);
    }
}
```

---

## Test Flow trong Postman

1. **Login as Seller** → Lưu seller_token
2. **POST /shops** → Tạo shop mới, lưu shop_id
3. **GET /shops** → Xem danh sách shops
4. **GET /shops/{shop_id}** → Xem chi tiết shop vừa tạo
5. **PUT /shops/{shop_id}** → Cập nhật thông tin shop
6. **DELETE /shops/{shop_id}** → Xóa shop (nếu cần)

---

## Lưu ý

- Chỉ Seller mới có thể tạo shop
- Buyer không thể tạo shop (sẽ nhận lỗi 403)
- Một Seller có thể có nhiều shops
- Không thể xóa shop nếu còn listings đang active
- Slug tự động tạo từ name
