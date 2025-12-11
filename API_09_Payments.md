# API Payments - Thanh toán

**Base URL:** `{{base_url}}` = `http://localhost:8000/api`

---

## 1. Danh sách thanh toán – GET /payments

**Mục đích:** Lấy danh sách các giao dịch thanh toán của user.

### Request

**Method:** `GET`  
**URL:** `{{base_url}}/payments`

**Headers:**
```
Accept: application/json
Authorization: Bearer {{token}}
```

**Query Parameters (optional):**
```
?page=1
&per_page=20
&status=completed
&payment_method=vnpay
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
      "user_id": 5,
      "order_id": 1,
      "order": {
        "id": 1,
        "order_number": "ORD-20251201-0001",
        "total_amount": 29990000
      },
      "transaction_id": "TXN-20251201-0001",
      "payment_method": "vnpay",
      "amount": 29990000,
      "currency": "VND",
      "status": "completed",
      "payment_gateway_response": {
        "vnp_TransactionNo": "14123456",
        "vnp_BankCode": "NCB"
      },
      "paid_at": "2025-12-01T10:30:00.000000Z",
      "created_at": "2025-12-01T10:00:00.000000Z",
      "updated_at": "2025-12-01T10:30:00.000000Z"
    },
    {
      "id": 2,
      "user_id": 5,
      "order_id": 2,
      "order": {
        "id": 2,
        "order_number": "ORD-20251201-0002",
        "total_amount": 15000000
      },
      "transaction_id": "TXN-20251201-0002",
      "payment_method": "cod",
      "amount": 15000000,
      "currency": "VND",
      "status": "pending",
      "paid_at": null,
      "created_at": "2025-12-01T11:00:00.000000Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 20,
    "total": 25,
    "last_page": 2
  }
}
```

**Payment Status:**
- `pending` - Chờ thanh toán
- `processing` - Đang xử lý
- `completed` - Đã thanh toán
- `failed` - Thất bại
- `cancelled` - Đã hủy
- `refunded` - Đã hoàn tiền

**Payment Methods:**
- `cod` - Cash on Delivery (Tiền mặt khi nhận hàng)
- `vnpay` - VNPay
- `momo` - Momo
- `zalopay` - ZaloPay
- `bank_transfer` - Chuyển khoản ngân hàng

---

## 2. Chi tiết thanh toán – GET /payments/{id}

**Mục đích:** Xem chi tiết một giao dịch thanh toán.

### Request

**Method:** `GET`  
**URL:** `{{base_url}}/payments/1`

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
  "user_id": 5,
  "user": {
    "id": 5,
    "name": "Nguyễn Văn B",
    "email": "buyer1@tradehub.com"
  },
  "order_id": 1,
  "order": {
    "id": 1,
    "order_number": "ORD-20251201-0001",
    "total_amount": 29990000,
    "status": "confirmed"
  },
  "transaction_id": "TXN-20251201-0001",
  "payment_method": "vnpay",
  "amount": 29990000,
  "currency": "VND",
  "status": "completed",
  "payment_gateway": "vnpay",
  "payment_gateway_response": {
    "vnp_TransactionNo": "14123456",
    "vnp_BankCode": "NCB",
    "vnp_CardType": "ATM",
    "vnp_ResponseCode": "00",
    "vnp_TransactionStatus": "00"
  },
  "paid_at": "2025-12-01T10:30:00.000000Z",
  "created_at": "2025-12-01T10:00:00.000000Z",
  "updated_at": "2025-12-01T10:30:00.000000Z"
}
```

**Error - Không có quyền (403):**
```json
{
  "message": "You can only view your own payments"
}
```

---

## 3. Tạo thanh toán mới – POST /payments

**Mục đích:** Tạo giao dịch thanh toán cho đơn hàng.

### Request

**Method:** `POST`  
**URL:** `{{base_url}}/payments`

**Headers:**
```
Content-Type: application/json
Accept: application/json
Authorization: Bearer {{token}}
```

**Body → raw → JSON:**

### 3.1. Thanh toán COD (Cash on Delivery)

```json
{
  "order_id": 1,
  "payment_method": "cod",
  "amount": 29990000
}
```

### 3.2. Thanh toán VNPay

```json
{
  "order_id": 1,
  "payment_method": "vnpay",
  "amount": 29990000,
  "return_url": "https://yourapp.com/payment/callback",
  "bank_code": "NCB"
}
```

**Optional fields cho VNPay:**
- `bank_code` - Mã ngân hàng: `NCB`, `VIETCOMBANK`, `TECHCOMBANK`, etc
- `return_url` - URL callback sau khi thanh toán

### 3.3. Thanh toán Momo

```json
{
  "order_id": 1,
  "payment_method": "momo",
  "amount": 29990000,
  "return_url": "https://yourapp.com/payment/callback"
}
```

### 3.4. Chuyển khoản ngân hàng

```json
{
  "order_id": 1,
  "payment_method": "bank_transfer",
  "amount": 29990000,
  "bank_info": {
    "bank_name": "Vietcombank",
    "account_number": "1234567890",
    "account_name": "NGUYEN VAN A"
  }
}
```

**Required fields:**
- `order_id` - ID đơn hàng
- `payment_method` - Phương thức thanh toán
- `amount` - Số tiền

### Response mẫu

**Success - COD (201):**
```json
{
  "message": "Payment created successfully",
  "data": {
    "id": 1,
    "order_id": 1,
    "transaction_id": "TXN-20251201-0001",
    "payment_method": "cod",
    "amount": 29990000,
    "status": "pending",
    "created_at": "2025-12-01T10:00:00.000000Z"
  }
}
```

**Success - VNPay (201):**
```json
{
  "message": "Payment created successfully",
  "data": {
    "id": 1,
    "order_id": 1,
    "transaction_id": "TXN-20251201-0001",
    "payment_method": "vnpay",
    "amount": 29990000,
    "status": "pending",
    "payment_url": "https://sandbox.vnpayment.vn/paymentv2/vpcpay.html?vnp_Amount=2999000000&vnp_Command=pay&...",
    "qr_code": "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAA...",
    "created_at": "2025-12-01T10:00:00.000000Z"
  }
}
```

**Lưu ý VNPay:**
- `payment_url` - URL để redirect user đến trang thanh toán VNPay
- `qr_code` - QR code để quét thanh toán (base64 image)
- User cần redirect đến `payment_url` để hoàn tất thanh toán

**Success - Momo (201):**
```json
{
  "message": "Payment created successfully",
  "data": {
    "id": 1,
    "order_id": 1,
    "transaction_id": "TXN-20251201-0001",
    "payment_method": "momo",
    "amount": 29990000,
    "status": "pending",
    "payment_url": "https://test-payment.momo.vn/gw_payment/transactionProcessor?partnerCode=...",
    "deeplink": "momo://app?action=payWithApp&...",
    "qr_code": "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAA...",
    "created_at": "2025-12-01T10:00:00.000000Z"
  }
}
```

**Lưu ý Momo:**
- `payment_url` - URL web thanh toán
- `deeplink` - Deep link mở app Momo (mobile)
- `qr_code` - QR code quét bằng app Momo

**Success - Bank Transfer (201):**
```json
{
  "message": "Payment created successfully. Please transfer to the bank account below.",
  "data": {
    "id": 1,
    "order_id": 1,
    "transaction_id": "TXN-20251201-0001",
    "payment_method": "bank_transfer",
    "amount": 29990000,
    "status": "pending",
    "bank_info": {
      "bank_name": "Vietcombank",
      "account_number": "1234567890",
      "account_name": "CONG TY TRADEHUB",
      "transfer_content": "TXN-20251201-0001 Thanh toan don hang ORD-20251201-0001"
    },
    "created_at": "2025-12-01T10:00:00.000000Z"
  }
}
```

**Error - Đơn hàng đã thanh toán (400):**
```json
{
  "message": "Order has already been paid"
}
```

**Error - Số tiền không khớp (400):**
```json
{
  "message": "Payment amount does not match order total",
  "order_total": 29990000,
  "payment_amount": 25000000
}
```

---

## 4. Payments của tôi – GET /payments/my-payments

**Mục đích:** Lấy danh sách payments của user hiện tại (đơn giản hơn endpoint /payments).

### Request

**Method:** `GET`  
**URL:** `{{base_url}}/payments/my-payments`

**Headers:**
```
Accept: application/json
Authorization: Bearer {{token}}
```

**Query Parameters (optional):**
```
?page=1
&per_page=20
&status=completed
&payment_type=order
```

**Body:** Không cần

### Response mẫu

**Success (200):**
```json
{
  "success": true,
  "data": {
    "data": [
      {
        "id": 1,
        "user_id": 5,
        "order_id": 1,
        "transaction_id": "TXN-20251201-0001",
        "method": "vnpay",
        "amount": 29990000,
        "status": "completed",
        "paid_at": "2025-12-01T10:30:00.000000Z"
      }
    ],
    "current_page": 1,
    "per_page": 20,
    "total": 10
  }
}
```

---

## 5. Hoàn tiền – POST /payments/{id}/refund

**Mục đích:** Hoàn tiền cho một payment đã completed.

### Request

**Method:** `POST`  
**URL:** `{{base_url}}/payments/1/refund`

**Headers:**
```
Content-Type: application/json
Accept: application/json
Authorization: Bearer {{token}}
```

**Body → raw → JSON:**
```json
{
  "reason": "Khách hàng yêu cầu hoàn tiền do sản phẩm lỗi"
}
```

**Optional fields:**
- `reason` - Lý do hoàn tiền

### Response mẫu

**Success (200):**
```json
{
  "success": true,
  "message": "Payment refunded successfully",
  "data": {
    "id": 1,
    "status": "refunded",
    "metadata": {
      "refund_reason": "Khách hàng yêu cầu hoàn tiền do sản phẩm lỗi",
      "refunded_at": "2025-12-04T10:00:00.000000Z"
    }
  }
}
```

**Error - Không thể hoàn tiền (400):**
```json
{
  "success": false,
  "message": "Can only refund completed payments"
}
```

---

## 6. Hủy thanh toán – POST /payments/{id}/cancel

**Mục đích:** Hủy payment đang pending hoặc processing.

### Request

**Method:** `POST`  
**URL:** `{{base_url}}/payments/1/cancel`

**Headers:**
```
Content-Type: application/json
Accept: application/json
Authorization: Bearer {{token}}
```

**Body → raw → JSON:**
```json
{
  "reason": "Khách hàng không muốn mua nữa"
}
```

**Optional fields:**
- `reason` - Lý do hủy

### Response mẫu

**Success (200):**
```json
{
  "success": true,
  "message": "Payment cancelled successfully",
  "data": {
    "id": 1,
    "status": "cancelled",
    "metadata": {
      "cancel_reason": "Khách hàng không muốn mua nữa"
    }
  }
}
```

**Error - Không thể hủy (400):**
```json
{
  "success": false,
  "message": "Can only cancel pending or processing payments"
}
```

---

## Payment Flow

### COD Flow:
1. User tạo order với `payment_method: cod`
2. POST /payments → Tạo payment record
3. Payment status = `pending`
4. Khi nhận hàng, seller xác nhận → Payment status = `completed`

### VNPay Flow:
1. User tạo order với `payment_method: vnpay`
2. POST /payments → Nhận `payment_url`
3. Redirect user đến `payment_url`
4. User thanh toán trên VNPay
5. VNPay callback về `return_url`
6. System cập nhật payment status = `completed`

### Momo Flow:
1. User tạo order với `payment_method: momo`
2. POST /payments → Nhận `payment_url` hoặc `deeplink`
3. Web: Redirect đến `payment_url`
   Mobile: Mở `deeplink` để mở app Momo
4. User thanh toán trên Momo
5. Momo callback về system
6. System cập nhật payment status = `completed`

### Bank Transfer Flow:
1. User tạo order với `payment_method: bank_transfer`
2. POST /payments → Nhận thông tin tài khoản
3. User chuyển khoản theo thông tin
4. Admin xác nhận đã nhận tiền
5. System cập nhật payment status = `completed`

---

## Query Parameters Chi Tiết

| Parameter | Type | Description | Example |
|-----------|------|-------------|---------|
| `status` | string | pending, completed, failed, etc | `?status=completed` |
| `payment_method` | string | cod, vnpay, momo, etc | `?payment_method=vnpay` |
| `order_id` | integer | Lọc theo đơn hàng | `?order_id=1` |
| `date_from` | date | Từ ngày | `?date_from=2025-01-01` |
| `date_to` | date | Đến ngày | `?date_to=2025-12-31` |
| `page` | integer | Số trang | `?page=1` |
| `per_page` | integer | Số item/trang | `?per_page=20` |

---

## Postman Tests Script

### Lưu payment info

```javascript
// Cho POST /payments
if (pm.response.code === 201) {
    var jsonData = pm.response.json();
    if (jsonData.data) {
        pm.environment.set("payment_id", jsonData.data.id);
        pm.environment.set("transaction_id", jsonData.data.transaction_id);
        
        if (jsonData.data.payment_url) {
            pm.environment.set("payment_url", jsonData.data.payment_url);
            console.log("Payment URL:", jsonData.data.payment_url);
        }
        
        console.log("Payment ID saved:", jsonData.data.id);
    }
}
```

---

## Test Flow trong Postman

1. **Tạo order trước** (xem API_07_Orders.md)
2. **POST /payments** → Tạo payment cho order
3. **GET /payments** → Xem danh sách payments
4. **GET /payments/my-payments** → Xem payments của mình
5. **GET /payments/{id}** → Xem chi tiết payment
6. **POST /payments/{id}/cancel** → Hủy payment (nếu pending)
7. **POST /payments/{id}/refund** → Hoàn tiền (nếu completed)

**Lưu ý:** 
- Với VNPay/Momo, cần test trên sandbox environment
- COD và Bank Transfer dễ test hơn
- Cần có callback URL để nhận kết quả từ payment gateway

---

## Lưu ý

- Mỗi order chỉ có 1 payment
- COD không cần thanh toán trước
- VNPay/Momo cần redirect đến payment gateway
- Bank transfer cần admin xác nhận thủ công
- Payment amount phải khớp với order total
- Không thể tạo payment cho order đã thanh toán
- Chỉ có thể refund payment đã completed
- Chỉ có thể cancel payment đang pending/processing

---

## Tổng kết Endpoints

| Method | Endpoint | Mô tả | Auth |
|--------|----------|-------|------|
| GET | /payments | Danh sách payments (admin xem tất cả, user xem của mình) | ✅ |
| GET | /payments/my-payments | Payments của user hiện tại | ✅ |
| GET | /payments/{id} | Chi tiết payment | ✅ |
| POST | /payments | Tạo payment mới | ✅ |
| POST | /payments/{id}/refund | Hoàn tiền | ✅ |
| POST | /payments/{id}/cancel | Hủy payment | ✅ |
| POST | /payments/vnpay/callback | VNPay callback | ❌ |
| POST | /payments/momo/callback | Momo callback | ❌ |
| POST | /payments/zalopay/callback | ZaloPay callback | ❌ |
