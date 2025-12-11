# API Subscription Plans & Subscriptions - Gói thành viên

**Base URL:** `{{base_url}}` = `http://localhost:8000/api`

---

## PLANS (Public)

## 1. Lấy danh sách gói thành viên – GET /plans

**Mục đích:** Xem tất cả các gói thành viên có sẵn (public, không cần auth).

### Request

**Method:** `GET`  
**URL:** `{{base_url}}/plans`

**Headers:**
```
Accept: application/json
```

**Body:** Không cần

### Response mẫu

**Success (200):**
```json
{
  "data": [
    {
      "id": 1,
      "name": "Free",
      "slug": "free",
      "description": "Gói miễn phí cho người mới bắt đầu",
      "price": 0,
      "currency": "VND",
      "duration_days": 30,
      "features": {
        "max_listings": 10,
        "max_images_per_listing": 5,
        "featured_listings": 0,
        "priority_support": false,
        "analytics": false,
        "custom_domain": false
      },
      "is_active": true,
      "is_popular": false,
      "sort_order": 1,
      "created_at": "2025-11-01T10:00:00.000000Z"
    },
    {
      "id": 2,
      "name": "Basic",
      "slug": "basic",
      "description": "Gói cơ bản cho seller nhỏ",
      "price": 500000,
      "currency": "VND",
      "duration_days": 30,
      "features": {
        "max_listings": 50,
        "max_images_per_listing": 10,
        "featured_listings": 5,
        "priority_support": false,
        "analytics": true,
        "custom_domain": false
      },
      "is_active": true,
      "is_popular": false,
      "sort_order": 2,
      "created_at": "2025-11-01T10:00:00.000000Z"
    },
    {
      "id": 3,
      "name": "Pro",
      "slug": "pro",
      "description": "Gói chuyên nghiệp cho seller lớn",
      "price": 1000000,
      "currency": "VND",
      "duration_days": 30,
      "features": {
        "max_listings": 200,
        "max_images_per_listing": 20,
        "featured_listings": 20,
        "priority_support": true,
        "analytics": true,
        "custom_domain": false
      },
      "is_active": true,
      "is_popular": true,
      "sort_order": 3,
      "created_at": "2025-11-01T10:00:00.000000Z"
    },
    {
      "id": 4,
      "name": "Enterprise",
      "slug": "enterprise",
      "description": "Gói doanh nghiệp không giới hạn",
      "price": 2000000,
      "currency": "VND",
      "duration_days": 30,
      "features": {
        "max_listings": -1,
        "max_images_per_listing": 50,
        "featured_listings": -1,
        "priority_support": true,
        "analytics": true,
        "custom_domain": true,
        "api_access": true,
        "dedicated_account_manager": true
      },
      "is_active": true,
      "is_popular": false,
      "sort_order": 4,
      "created_at": "2025-11-01T10:00:00.000000Z"
    }
  ]
}
```

**Lưu ý:** `max_listings: -1` nghĩa là không giới hạn

---

## 2. Chi tiết gói thành viên – GET /plans/{id}

**Mục đích:** Xem chi tiết một gói thành viên.

### Request

**Method:** `GET`  
**URL:** `{{base_url}}/plans/3`

**Headers:**
```
Accept: application/json
```

**Body:** Không cần

### Response mẫu

**Success (200):**
```json
{
  "id": 3,
  "name": "Pro",
  "slug": "pro",
  "description": "Gói chuyên nghiệp cho seller lớn. Bao gồm tất cả tính năng cần thiết để phát triển kinh doanh.",
  "price": 1000000,
  "currency": "VND",
  "duration_days": 30,
  "features": {
    "max_listings": 200,
    "max_images_per_listing": 20,
    "featured_listings": 20,
    "priority_support": true,
    "analytics": true,
    "custom_domain": false,
    "promotion_discount": 10
  },
  "benefits": [
    "Đăng tối đa 200 tin",
    "20 tin nổi bật miễn phí",
    "Hỗ trợ ưu tiên 24/7",
    "Thống kê chi tiết",
    "Giảm 10% chi phí quảng cáo"
  ],
  "is_active": true,
  "is_popular": true,
  "sort_order": 3,
  "total_subscribers": 150,
  "created_at": "2025-11-01T10:00:00.000000Z",
  "updated_at": "2025-12-01T10:00:00.000000Z"
}
```

---

## SUBSCRIPTIONS (Require Auth)

## 3. Đăng ký gói thành viên mới – POST /subscriptions

**Mục đích:** User đăng ký một gói thành viên.

### Request

**Method:** `POST`  
**URL:** `{{base_url}}/subscriptions`

**Headers:**
```
Content-Type: application/json
Accept: application/json
Authorization: Bearer {{token}}
```

**Body → raw → JSON:**
```json
{
  "plan_id": 3,
  "payment_method": "vnpay",
  "duration_months": 1,
  "coupon_code": "PROMO10"
}
```

**Required fields:**
- `plan_id` - ID gói thành viên
- `payment_method` - Phương thức: `vnpay`, `momo`, `bank_transfer`

**Optional fields:**
- `duration_months` - Số tháng (1, 3, 6, 12) - default: 1
- `coupon_code` - Mã giảm giá

### Response mẫu

**Success (201):**
```json
{
  "message": "Subscription created successfully",
  "data": {
    "id": 1,
    "user_id": 2,
    "plan_id": 3,
    "plan": {
      "id": 3,
      "name": "Pro",
      "price": 1000000
    },
    "duration_months": 1,
    "price": 1000000,
    "discount_amount": 100000,
    "final_amount": 900000,
    "status": "pending",
    "start_date": "2025-12-01",
    "end_date": "2025-12-31",
    "payment_method": "vnpay",
    "payment_url": "https://sandbox.vnpayment.vn/paymentv2/vpcpay.html?...",
    "created_at": "2025-12-01T10:00:00.000000Z"
  }
}
```

**Lưu ý:** Cần redirect user đến `payment_url` để thanh toán.

**Error - Đã có gói active (400):**
```json
{
  "message": "You already have an active subscription. Please cancel it first or wait until it expires."
}
```

---

## 4. Gia hạn gói hiện tại – PUT /subscriptions/{id}/renew

**Mục đích:** Gia hạn gói thành viên đang sử dụng.

### Request

**Method:** `PUT`  
**URL:** `{{base_url}}/subscriptions/1/renew`

**Headers:**
```
Content-Type: application/json
Accept: application/json
Authorization: Bearer {{token}}
```

**Body → raw → JSON:**
```json
{
  "duration_months": 3,
  "payment_method": "vnpay"
}
```

### Response mẫu

**Success (200):**
```json
{
  "message": "Subscription renewed successfully",
  "data": {
    "id": 1,
    "plan_id": 3,
    "duration_months": 3,
    "price": 3000000,
    "discount_amount": 300000,
    "final_amount": 2700000,
    "old_end_date": "2025-12-31",
    "new_end_date": "2026-03-31",
    "payment_url": "https://sandbox.vnpayment.vn/paymentv2/vpcpay.html?...",
    "created_at": "2025-12-01T10:00:00.000000Z"
  }
}
```

**Lưu ý:** Gia hạn nhiều tháng thường có giảm giá:
- 3 tháng: giảm 10%
- 6 tháng: giảm 15%
- 12 tháng: giảm 20%

---

## 5. Xem gói hiện tại của người dùng – GET /subscriptions/current

**Mục đích:** Xem gói thành viên đang sử dụng.

### Request

**Method:** `GET`  
**URL:** `{{base_url}}/subscriptions/current`

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
  "plan_id": 3,
  "plan": {
    "id": 3,
    "name": "Pro",
    "slug": "pro",
    "price": 1000000,
    "features": {
      "max_listings": 200,
      "max_images_per_listing": 20,
      "featured_listings": 20,
      "priority_support": true,
      "analytics": true
    }
  },
  "status": "active",
  "start_date": "2025-12-01",
  "end_date": "2025-12-31",
  "days_remaining": 30,
  "usage": {
    "listings_used": 45,
    "listings_remaining": 155,
    "featured_listings_used": 5,
    "featured_listings_remaining": 15
  },
  "auto_renew": false,
  "created_at": "2025-12-01T10:00:00.000000Z",
  "updated_at": "2025-12-01T10:00:00.000000Z"
}
```

**Error - Không có gói active (404):**
```json
{
  "message": "No active subscription found"
}
```

---

## 6. Lịch sử các gói đã đăng ký – GET /subscriptions/history

**Mục đích:** Xem lịch sử tất cả các gói đã đăng ký.

### Request

**Method:** `GET`  
**URL:** `{{base_url}}/subscriptions/history`

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
```

**Body:** Không cần

### Response mẫu

**Success (200):**
```json
{
  "data": [
    {
      "id": 1,
      "plan_id": 3,
      "plan": {
        "id": 3,
        "name": "Pro"
      },
      "status": "active",
      "start_date": "2025-12-01",
      "end_date": "2025-12-31",
      "price": 1000000,
      "created_at": "2025-12-01T10:00:00.000000Z"
    },
    {
      "id": 2,
      "plan_id": 2,
      "plan": {
        "id": 2,
        "name": "Basic"
      },
      "status": "expired",
      "start_date": "2025-11-01",
      "end_date": "2025-11-30",
      "price": 500000,
      "created_at": "2025-11-01T10:00:00.000000Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 20,
    "total": 5,
    "last_page": 1
  }
}
```

---

## 7. Hủy gói VIP trước thời hạn – DELETE /subscriptions/{id}/cancel

**Mục đích:** Hủy gói thành viên đang sử dụng.

### Request

**Method:** `DELETE`  
**URL:** `{{base_url}}/subscriptions/1/cancel`

**Headers:**
```
Content-Type: application/json
Accept: application/json
Authorization: Bearer {{token}}
```

**Body → raw → JSON (optional):**
```json
{
  "reason": "Không sử dụng nhiều",
  "feedback": "Gói quá đắt so với nhu cầu của tôi"
}
```

### Response mẫu

**Success (200):**
```json
{
  "message": "Subscription cancelled successfully",
  "data": {
    "id": 1,
    "status": "cancelled",
    "cancelled_at": "2025-12-01T15:00:00.000000Z",
    "refund_amount": 500000,
    "refund_note": "Hoàn 50% vì hủy giữa kỳ"
  }
}
```

**Lưu ý về hoàn tiền:**
- Hủy trong 7 ngày đầu: Hoàn 100%
- Hủy từ ngày 8-15: Hoàn 50%
- Hủy sau ngày 15: Không hoàn tiền

---

## Subscription Status

- `pending` - Chờ thanh toán
- `active` - Đang hoạt động
- `expired` - Đã hết hạn
- `cancelled` - Đã hủy

---

## Postman Tests Script

### Lưu subscription info

```javascript
// Cho POST /subscriptions
if (pm.response.code === 201) {
    var jsonData = pm.response.json();
    if (jsonData.data) {
        pm.environment.set("subscription_id", jsonData.data.id);
        pm.environment.set("payment_url", jsonData.data.payment_url);
        console.log("Subscription ID:", jsonData.data.id);
        console.log("Payment URL:", jsonData.data.payment_url);
    }
}

// Cho GET /subscriptions/current
if (pm.response.code === 200) {
    var jsonData = pm.response.json();
    pm.environment.set("current_plan_id", jsonData.plan_id);
    pm.environment.set("days_remaining", jsonData.days_remaining);
    console.log("Current plan:", jsonData.plan.name);
    console.log("Days remaining:", jsonData.days_remaining);
}
```

---

## Test Flow trong Postman

1. **GET /plans** → Xem danh sách gói (không cần auth)
2. **GET /plans/3** → Xem chi tiết gói Pro
3. **Login as Seller** → Lưu token
4. **POST /subscriptions** → Đăng ký gói Pro
5. **GET /subscriptions/current** → Xem gói hiện tại
6. **GET /subscriptions/history** → Xem lịch sử
7. **PUT /subscriptions/1/renew** → Gia hạn gói
8. **DELETE /subscriptions/1/cancel** → Hủy gói (nếu cần)

---

## Lưu ý

- Free plan tự động kích hoạt khi đăng ký
- Chỉ có thể có 1 gói active tại một thời điểm
- Gia hạn nhiều tháng có giảm giá
- Hủy sớm có thể được hoàn tiền
- Usage tracking để giới hạn tính năng
- Auto-renew có thể bật/tắt
- Payment method: VNPay, Momo, Bank Transfer
- Coupon code áp dụng khi đăng ký
