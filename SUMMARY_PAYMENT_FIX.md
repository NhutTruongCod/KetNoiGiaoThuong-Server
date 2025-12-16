# TÓM TẮT: SỬA LỖI THANH TOÁN KHI KHÔNG ĐỦ TIỀN

## ✅ VẤN ĐỀ ĐÃ ĐƯỢC SỬA

### Vấn đề ban đầu:
- Khi user không đủ tiền trong ví, hệ thống trả về **lỗi 500** (Internal Server Error)
- Frontend không biết được lý do cụ thể và không thể xử lý đúng

### Nguyên nhân:
- Method `deduct()` trong `Wallet` Model throw exception khi không đủ tiền
- Exception này không được catch, dẫn đến lỗi 500

### Giải pháp đã triển khai:
1. **Sửa `app/Models/Wallet.php`** (line 113-135):
   - Thay `throw new \Exception()` bằng `return false`
   - Thêm log warning để debug

2. **Sửa `app/Http/Controllers/Api/OrderController.php`** (line 615-640):
   - Kiểm tra kết quả của `deduct()`
   - Nếu `deduct()` trả về `false`, rollback transaction và trả về response 422 với thông báo rõ ràng

## ⚠️ QUAN TRỌNG: CÁCH TEST LẠI

### Nếu bạn vẫn thấy lỗi 500, hãy làm theo các bước sau:

1. **Restart PHP server:**
   ```bash
   # Dừng server hiện tại (Ctrl+C)
   # Sau đó chạy lại:
   php artisan serve
   ```

2. **Clear cache:**
   ```bash
   php artisan cache:clear
   php artisan config:clear
   php artisan route:clear
   ```

3. **Kiểm tra code đã được apply:**
   - Mở file `app/Models/Wallet.php` line 113-135
   - Phải thấy `return false;` thay vì `throw new \Exception()`
   - Mở file `app/Http/Controllers/Api/OrderController.php` line 615-640
   - Phải thấy `if (!$buyerTransaction) { ... return 422 }`

4. **Xem log mới nhất:**
   ```bash
   # Xóa log cũ
   echo. > storage/logs/laravel.log
   
   # Test lại từ frontend
   # Sau đó xem log:
   Get-Content storage/logs/laravel.log -Tail 50
   ```

## 📊 KIỂM TRA DỮ LIỆU HIỆN TẠI

### Dữ liệu test hiện có:
- **Listing 11**: "abc" - Giá: 123,123,123 VND (123 triệu)
- **User 5** (Nguyễn Văn A - buyer1@example.com): Số dư ví = 0 VND
- **User 3** (seller2@example.com): Người bán của listing 11

### Để test thành công, cần nạp tiền trước:

```bash
# Chạy script nạp tiền test:
php artisan tinker

# Trong tinker:
$wallet = App\Models\Wallet::where('user_id', 5)->first();
$wallet->credit(150000000, 'deposit', null, null, 'Test deposit');
echo "Balance: " . $wallet->available_balance;
exit
```

## 📋 RESPONSE MỚI KHI KHÔNG ĐỦ TIỀN

### HTTP Status: 422 (Unprocessable Entity)

```json
{
  "status": "error",
  "message": "So du vi khong du. Vui long nap them tien.",
  "wallet_balance": 0,
  "order_amount": 123123123,
  "need_more": 123123123,
  "requires_deposit": true
}
```

## 🎯 HƯỚNG DẪN CHO FRONTEND

### 1. Xử lý response khi thanh toán:

```javascript
// API: POST /api/orders/{id}/pay
try {
  const response = await axios.post(`/api/orders/${orderId}/pay`);
  
  if (response.data.status === 'success') {
    // Thanh toán thành công
    showSuccess('Thanh toán thành công!');
    navigateTo('/orders/' + orderId);
  }
} catch (error) {
  if (error.response?.status === 422) {
    const data = error.response.data;
    
    // Kiểm tra nếu là lỗi không đủ tiền
    if (data.requires_deposit) {
      showDepositDialog({
        currentBalance: data.wallet_balance,
        orderAmount: data.order_amount,
        needMore: data.need_more,
        message: data.message
      });
    } else {
      // Lỗi validation khác
      showError(data.message);
    }
  } else if (error.response?.status === 500) {
    // Lỗi server thật sự
    showError('Lỗi server. Vui lòng thử lại sau.');
  }
}
```

### 2. Component hiển thị dialog nạp tiền:

```javascript
function showDepositDialog({ currentBalance, orderAmount, needMore, message }) {
  // Hiển thị modal/dialog
  const dialog = {
    title: 'Số dư không đủ',
    message: message,
    details: [
      `Số dư hiện tại: ${formatMoney(currentBalance)} VND`,
      `Số tiền cần thanh toán: ${formatMoney(orderAmount)} VND`,
      `Cần nạp thêm: ${formatMoney(needMore)} VND`
    ],
    actions: [
      {
        label: 'Nạp tiền ngay',
        onClick: () => navigateTo('/wallet/deposit')
      },
      {
        label: 'Đóng',
        onClick: () => closeDialog()
      }
    ]
  };
  
  showModal(dialog);
}
```

### 3. Kiểm tra trước khi thanh toán (Preview):

```javascript
// API: POST /api/orders/preview
const preview = await axios.post('/api/orders/preview', {
  listing_id: listingId,
  quantity: quantity
});

if (!preview.data.data.wallet.can_pay) {
  // Hiển thị cảnh báo trước
  showWarning({
    message: 'Số dư không đủ để thanh toán',
    currentBalance: preview.data.data.wallet.balance,
    needMore: preview.data.data.wallet.need_more
  });
}
```

## 🧪 CÁCH TEST

### Test 1: User không có ví hoặc số dư = 0

```bash
# Tạo đơn hàng
POST /api/orders
{
  "listing_id": 11,
  "quantity": 1
}

# Thanh toán (sẽ nhận 422)
POST /api/orders/{order_id}/pay
```

**Expected Response:**
```json
{
  "status": "error",
  "message": "So du vi khong du. Vui long nap them tien.",
  "wallet_balance": 0,
  "order_amount": 123123123,
  "need_more": 123123123,
  "requires_deposit": true
}
```

### Test 2: User có ví nhưng không đủ tiền

```bash
# Giả sử user có 50 triệu, đơn hàng 123 triệu
POST /api/orders/{order_id}/pay
```

**Expected Response:**
```json
{
  "status": "error",
  "message": "So du vi khong du. Vui long nap them tien.",
  "wallet_balance": 50000000,
  "order_amount": 123123123,
  "need_more": 73123123,
  "requires_deposit": true
}
```

### Test 3: User có đủ tiền

```bash
# Nạp tiền trước
POST /api/wallet/deposit
{
  "amount": 150000000
}

# Thanh toán (sẽ thành công)
POST /api/orders/{order_id}/pay
```

**Expected Response:**
```json
{
  "status": "success",
  "message": "Thanh toan thanh cong!",
  "data": {
    "order": { ... },
    "seller_contact": { ... }
  }
}
```

## 📝 GHI CHÚ

- ✅ Code đã được sửa và test
- ✅ Không còn trả về lỗi 500 khi không đủ tiền
- ✅ Response rõ ràng với status 422 và thông tin chi tiết
- ✅ Frontend có thể xử lý và hiển thị dialog nạp tiền
- ⚠️ **Cần restart PHP server để code mới có hiệu lực**
- ⚠️ **Cần nạp tiền vào ví test trước khi test thanh toán thành công**

## 🔍 FILES ĐÃ SỬA

1. `app/Models/Wallet.php` - Method `deduct()` line 113-135
2. `app/Http/Controllers/Api/OrderController.php` - Method `payOrder()` line 615-640
