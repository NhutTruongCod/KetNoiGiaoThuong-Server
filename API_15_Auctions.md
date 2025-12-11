# API Auctions - Đấu giá

**Base URL:** `{{base_url}}` = `http://localhost:8000/api`

---

## 1. Danh sách đấu giá – GET /auctions

**Mục đích:** Lấy danh sách tất cả phiên đấu giá.

### Request

**Method:** `GET`  
**URL:** `{{base_url}}/auctions`

**Headers:**
```
Accept: application/json
```

**Query Parameters (optional):**
```
?page=1
&per_page=20
&status=active
&category_id=1
&min_price=10000000
&max_price=50000000
&sort=ending_soon
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
        "images": ["https://example.com/image1.jpg"]
      },
      "shop_id": 1,
      "shop": {
        "id": 1,
        "name": "Tech Store"
      },
      "starting_price": 10000000,
      "current_price": 15000000,
      "reserve_price": 20000000,
      "bid_increment": 500000,
      "total_bids": 15,
      "start_time": "2025-12-05T10:00:00.000000Z",
      "end_time": "2025-12-10T18:00:00.000000Z",
      "status": "active",
      "time_remaining": "4 days 5 hours",
      "highest_bidder": {
        "id": 10,
        "name": "Nguyễn Văn C"
      },
      "created_at": "2025-12-01T10:00:00.000000Z"
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

**Auction Status:**
- `upcoming` - Sắp diễn ra
- `active` - Đang diễn ra
- `ended` - Đã kết thúc
- `cancelled` - Đã hủy

---

## 2. Chi tiết đấu giá – GET /auctions/{auction}

**Mục đích:** Xem chi tiết một phiên đấu giá.

### Request

**Method:** `GET`  
**URL:** `{{base_url}}/auctions/1`

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
  "listing_id": 123,
  "listing": {
    "id": 123,
    "title": "iPhone 15 Pro Max 256GB",
    "description": "Hàng chính hãng...",
    "images": [
      "https://example.com/image1.jpg",
      "https://example.com/image2.jpg"
    ],
    "specifications": {
      "brand": "Apple",
      "storage": "256GB"
    }
  },
  "shop_id": 1,
  "shop": {
    "id": 1,
    "name": "Tech Store",
    "rating": 4.5
  },
  "starting_price": 10000000,
  "current_price": 15000000,
  "reserve_price": 20000000,
  "bid_increment": 500000,
  "total_bids": 15,
  "start_time": "2025-12-05T10:00:00.000000Z",
  "end_time": "2025-12-10T18:00:00.000000Z",
  "status": "active",
  "time_remaining": "4 days 5 hours",
  "time_remaining_seconds": 363000,
  "highest_bidder": {
    "id": 10,
    "name": "Nguyễn Văn C",
    "bid_amount": 15000000,
    "bid_time": "2025-12-09T14:00:00.000000Z"
  },
  "bid_history": [
    {
      "bidder_name": "Nguyễn Văn C",
      "amount": 15000000,
      "time": "2025-12-09T14:00:00.000000Z"
    },
    {
      "bidder_name": "Trần Thị D",
      "amount": 14500000,
      "time": "2025-12-09T13:30:00.000000Z"
    }
  ],
  "rules": {
    "auto_extend": true,
    "extend_minutes": 5,
    "max_bids_per_user": 10
  },
  "created_at": "2025-12-01T10:00:00.000000Z"
}
```

---

## 3. Tạo đấu giá mới – POST /auctions

**Mục đích:** Seller tạo phiên đấu giá cho listing.

### Request

**Method:** `POST`  
**URL:** `{{base_url}}/auctions`

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
  "starting_price": 10000000,
  "reserve_price": 20000000,
  "bid_increment": 500000,
  "start_time": "2025-12-05T10:00:00",
  "end_time": "2025-12-10T18:00:00",
  "auto_extend": true,
  "extend_minutes": 5
}
```

**Required fields:**
- `listing_id` - ID tin đăng
- `starting_price` - Giá khởi điểm
- `bid_increment` - Bước giá
- `start_time` - Thời gian bắt đầu
- `end_time` - Thời gian kết thúc

**Optional fields:**
- `reserve_price` - Giá dự trữ (giá tối thiểu để bán)
- `auto_extend` - Tự động gia hạn khi có bid cuối phút
- `extend_minutes` - Số phút gia hạn

### Response mẫu

**Success (201):**
```json
{
  "message": "Auction created successfully",
  "data": {
    "id": 1,
    "listing_id": 123,
    "starting_price": 10000000,
    "reserve_price": 20000000,
    "bid_increment": 500000,
    "start_time": "2025-12-05T10:00:00.000000Z",
    "end_time": "2025-12-10T18:00:00.000000Z",
    "status": "upcoming",
    "created_at": "2025-12-01T10:00:00.000000Z"
  }
}
```

---

## 4. Cập nhật đấu giá – PUT /auctions/{auction}

**Mục đích:** Seller cập nhật phiên đấu giá (chỉ khi chưa có bid).

### Request

**Method:** `PUT`  
**URL:** `{{base_url}}/auctions/1`

**Headers:**
```
Content-Type: application/json
Accept: application/json
Authorization: Bearer {{seller_token}}
```

**Body → raw → JSON:**
```json
{
  "reserve_price": 18000000,
  "end_time": "2025-12-11T18:00:00"
}
```

### Response mẫu

**Success (200):**
```json
{
  "message": "Auction updated successfully",
  "data": {
    "id": 1,
    "reserve_price": 18000000,
    "end_time": "2025-12-11T18:00:00.000000Z",
    "updated_at": "2025-12-01T11:00:00.000000Z"
  }
}
```

**Error - Đã có bid (400):**
```json
{
  "message": "Cannot update auction with existing bids"
}
```

---

## 5. Xóa đấu giá – DELETE /auctions/{auction}

**Mục đích:** Seller xóa phiên đấu giá (chỉ khi chưa có bid).

### Request

**Method:** `DELETE`  
**URL:** `{{base_url}}/auctions/1`

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
  "message": "Auction deleted successfully"
}
```

---

## 6. Đặt giá – POST /auctions/{auction}/bids

**Mục đích:** User đặt giá trong phiên đấu giá.

### Request

**Method:** `POST`  
**URL:** `{{base_url}}/auctions/1/bids`

**Headers:**
```
Content-Type: application/json
Accept: application/json
Authorization: Bearer {{token}}
```

**Body → raw → JSON:**
```json
{
  "amount": 100000
}
```

### ⚠️ QUAN TRỌNG - Format số tiền:
- `amount` là số tiền **VND** (KHÔNG phải cents)
- Ví dụ: `100000` = 100,000 VND (một trăm nghìn)
- **KHÔNG** nhân 100 khi gửi
- Backend lưu trữ dạng cents nhưng FE gửi VND

### Cách tính giá tối thiểu:
```
minimum_bid = (current_price_cents + bid_increment_cents) / 100

Ví dụ:
- current_price_cents = 9590234 → 95,902.34 VND
- bid_increment_cents = 360393 → 3,603.93 VND  
- minimum_bid = ceil(95902.34 + 3603.93) = 99,507 VND
```

**Required fields:**
- `amount` - Số tiền VND (phải >= minimum_bid)

### Response mẫu

**Success (201):**
```json
{
  "message": "Bid placed successfully",
  "data": {
    "id": 1,
    "auction_id": 1,
    "user_id": 5,
    "amount": 15500000,
    "is_winning": true,
    "created_at": "2025-12-09T15:00:00.000000Z"
  },
  "auction": {
    "current_price": 15500000,
    "total_bids": 16,
    "time_remaining": "1 day 3 hours"
  }
}
```

**Error - Giá thấp hơn (400):**
```json
{
  "message": "Bid amount must be at least 16000000 VND",
  "current_price": 15500000,
  "bid_increment": 500000,
  "minimum_bid": 16000000
}
```

**Error - Đấu giá đã kết thúc (400):**
```json
{
  "message": "Auction has ended"
}
```

---

## 7. Danh sách giá đã đặt – GET /auctions/{auction}/bids

**Mục đích:** Xem danh sách các lần đặt giá.

### Request

**Method:** `GET`  
**URL:** `{{base_url}}/auctions/1/bids`

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
  "data": [
    {
      "id": 1,
      "user_id": 5,
      "user": {
        "id": 5,
        "name": "Nguyễn Văn C"
      },
      "amount": 15500000,
      "is_winning": true,
      "created_at": "2025-12-09T15:00:00.000000Z"
    },
    {
      "id": 2,
      "user_id": 6,
      "user": {
        "id": 6,
        "name": "Trần Thị D"
      },
      "amount": 15000000,
      "is_winning": false,
      "created_at": "2025-12-09T14:30:00.000000Z"
    }
  ],
  "meta": {
    "total_bids": 16,
    "highest_bid": 15500000,
    "lowest_bid": 10500000
  }
}
```

---

## 8. Giá của tôi – GET /auctions/my-bids

**Mục đích:** Xem tất cả các phiên đấu giá mình đã tham gia.

**Lưu ý:** Route này nằm ngoài `{auction}` parameter, gọi trực tiếp `/auctions/my-bids`

### Request

**Method:** `GET`  
**URL:** `{{base_url}}/auctions/my-bids`

**Headers:**
```
Accept: application/json
Authorization: Bearer {{token}}
```

**Query Parameters (optional):**
```
?page=1
&per_page=20
&status=active
&is_winning=true
```

**Body:** Không cần

### Response mẫu

**Success (200):**
```json
{
  "data": [
    {
      "auction_id": 1,
      "auction": {
        "id": 1,
        "listing": {
          "title": "iPhone 15 Pro Max",
          "images": ["url"]
        },
        "current_price": 15500000,
        "end_time": "2025-12-10T18:00:00.000000Z",
        "status": "active"
      },
      "my_highest_bid": 15500000,
      "is_winning": true,
      "total_my_bids": 3,
      "last_bid_at": "2025-12-09T15:00:00.000000Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 20,
    "total": 5
  }
}
```

---

## Postman Tests Script

```javascript
// Cho POST /auctions
if (pm.response.code === 201) {
    var jsonData = pm.response.json();
    pm.environment.set("auction_id", jsonData.data.id);
    console.log("Auction ID:", jsonData.data.id);
}

// Cho POST /auctions/{auction}/bids
if (pm.response.code === 201) {
    var jsonData = pm.response.json();
    console.log("Bid placed:", jsonData.data.amount);
    console.log("Is winning:", jsonData.data.is_winning);
}
```

---

## Test Flow trong Postman

1. **GET /auctions** → Xem danh sách đấu giá
2. **GET /auctions/1** → Xem chi tiết
3. **Login as Seller** → Tạo auction
4. **POST /auctions** → Tạo phiên đấu giá
5. **Login as Buyer** → Đặt giá
6. **POST /auctions/1/bids** → Đặt giá
7. **GET /auctions/1/bids** → Xem lịch sử giá
8. **GET /auctions/my-bids** → Xem giá của tôi

---

## Lưu ý

- Chỉ Seller mới tạo được auction
- Không thể đặt giá cho auction của mình
- Giá phải >= current_price + bid_increment
- Auto-extend: Tự động gia hạn 5 phút nếu có bid cuối
- Reserve price: Giá tối thiểu để bán
- Khi kết thúc, người giá cao nhất thắng
- Seller liên hệ người thắng để giao dịch
