# SỬA LỖI THANH TOÁN - KHÔNG ĐỦ TIỀN

## 🔍 PHÂN TÍCH VẤN ĐỀ

### Hiện trạng:
- Khi user không đủ tiền trong ví, hệ thống báo "Lỗi server" thay vì yêu cầu nạp tiền
- Frontend nhận được status 500 thay vì 422

### Nguyên nhân có thể:
1. ❌ **Lỗi trong Wallet Model** - Method `deduct()` throw exception khi không đủ tiền
2. ❌ **Lỗi trong catch block** - Catch tất cả exception và trả về 500
3. ✅ **Logic đúng** - Code đã kiểm tra số dư trước khi deduct

---

## ✅ CODE HIỆN TẠI (ĐÚNG)

### Trong OrderController::payOrder() - Dòng 600-615

```php
// Kiểm tra ví của buyer
$wallet = Wallet::where('user_id', $user->id)->first();

if (!$wallet || $wallet->available_balance < $order->final_amount) {
    $currentBalance = $wallet ? $wallet->available_balance : 0;
    $needMore = $order->final_amount - $currentBalance;
    
    return response()->json([
        'status' => 'error',
        'message' => 'So du vi khong du. Vui long nap them tien.',
        'wallet_balance' => $currentBalance,
        'order_amount' => $order->final_amount,
        'need_more' => $needMore,
        'requires_deposit' => true,
    ], 422);
}
```

**✅ Logic này ĐÚNG** - Kiểm tra số dư trước khi thực hiện giao dịch

---

## 🐛 VẤN ĐỀ CÓ THỂ XẢY RA

### 1. Wallet Model throw exception

Kiểm tra method `deduct()` trong `app/Models/Wallet.php`:

```php
public function deduct($amount, $type, $related_type = null, $related_id = null, $description = null)
{
    // ❌ NẾU CÓ ĐOẠN NÀY THÌ SẼ LỖI
    if ($this->available_balance < $amount) {
        throw new \Exception('Insufficient balance');
    }
    
    // ✅ NÊN KIỂM TRA VÀ TRẢ VỀ FALSE THAY VÌ THROW EXCEPTION
    if ($this->available_balance < $amount) {
        return false;
    }
    
    // ... rest of code
}
```

### 2. Database transaction lỗi

Nếu có lỗi trong quá trình transaction, catch block sẽ bắt và trả về 500.

---

## 🔧 GIẢI PHÁP

### Giải pháp 1: Kiểm tra Wallet Model

Mở file `app/Models/Wallet.php` và tìm method `deduct()`:

```php
public function deduct($amount, $type, $related_type = null, $related_id = null, $description = null)
{
    // ✅ THÊM KIỂM TRA NÀY
    if ($this->available_balance < $amount) {
        \Log::warning('Insufficient wallet balance', [
            'user_id' => $this->user_id,
            'balance' => $this->available_balance,
            'amount' => $amount
        ]);
        return false; // Trả về false thay vì throw exception
    }
    
    DB::beginTransaction();
    try {
        // Trừ tiền
        $this->decrement('balance', $amount);
        $this->decrement('available_balance', $amount);
        
        // Tạo transaction record
        $transaction = WalletTransaction::create([
            'wallet_id' => $this->id,
            'user_id' => $this->user_id,
            'type' => $type,
            'amount' => $amount,
            'balance_after' => $this->fresh()->balance,
            'related_type' => $related_type,
            'related_id' => $related_id,
            'description' => $description,
            'status' => 'completed',
        ]);
        
        DB::commit();
        return $transaction;
    } catch (\Exception $e) {
        DB::rollBack();
        \Log::error('Wallet deduct error: ' . $e->getMessage());
        throw $e;
    }
}
```

### Giải pháp 2: Cải thiện Error Handling trong OrderController

Thêm try-catch cụ thể hơn:

```php
public function payOrder(Request $request, $id)
{
    try {
        // ... existing code ...
        
        // Kiểm tra ví của buyer
        $wallet = Wallet::where('user_id', $user->id)->first();
        
        if (!$wallet || $wallet->available_balance < $order->final_amount) {
            $currentBalance = $wallet ? $wallet->available_balance : 0;
            $needMore = $order->final_amount - $currentBalance;
            
            return response()->json([
                'status' => 'error',
                'message' => 'So du vi khong du. Vui long nap them tien.',
                'wallet_balance' => $currentBalance,
                'order_amount' => $order->final_amount,
                'need_more' => $needMore,
                'requires_deposit' => true,
            ], 422);
        }

        DB::beginTransaction();

        // ✅ THÊM TRY-CATCH CHO WALLET DEDUCT
        try {
            $buyerTransaction = $wallet->deduct(
                $order->final_amount, 
                'payment', 
                'order', 
                $order->id, 
                'Thanh toan don hang #' . $order->order_number
            );
            
            // ✅ KIỂM TRA KẾT QUẢ
            if (!$buyerTransaction) {
                DB::rollBack();
                return response()->json([
                    'status' => 'error',
                    'message' => 'Khong the tru tien tu vi. Vui long thu lai.',
                    'requires_deposit' => true,
                ], 422);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Wallet deduct failed', [
                'user_id' => $user->id,
                'order_id' => $order->id,
                'amount' => $order->final_amount,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Loi khi tru tien tu vi. Vui long lien he ho tro.',
                'error_detail' => $e->getMessage()
            ], 500);
        }

        // ... rest of code ...
        
        DB::commit();
        
        return response()->json([
            'status' => 'success',
            'message' => 'Thanh toan thanh cong!',
            // ... data ...
        ]);
        
    } catch (\Exception $e) {
        DB::rollBack();
        
        // ✅ LOG CHI TIẾT HƠN
        \Log::error('Payment error', [
            'user_id' => $user->id ?? null,
            'order_id' => $id,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        return response()->json([
            'status' => 'error',
            'message' => 'Loi server khi thanh toan',
            'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
        ], 500);
    }
}
```

---

## 🧪 KIỂM TRA

### Test Case 1: Không đủ tiền

**Request:**
```bash
POST /api/orders/123/pay
Authorization: Bearer {token}
```

**Expected Response (422):**
```json
{
  "status": "error",
  "message": "So du vi khong du. Vui long nap them tien.",
  "wallet_balance": 50000,
  "order_amount": 123145123,
  "need_more": 123095123,
  "requires_deposit": true
}
```

### Test Case 2: Đủ tiền

**Expected Response (200):**
```json
{
  "status": "success",
  "message": "Thanh toan thanh cong!",
  "data": {
    "order": {
      "id": 123,
      "order_number": "ORD-20251211-001",
      "status": "confirmed",
      "payment_status": "paid"
    }
  }
}
```

### Test Case 3: Lỗi server thật

**Expected Response (500):**
```json
{
  "status": "error",
  "message": "Loi server khi thanh toan",
  "error": "Database connection failed"
}
```

---

## 📝 CHECKLIST SỬA LỖI

- [ ] Kiểm tra method `deduct()` trong `Wallet` model
- [ ] Đảm bảo không throw exception khi không đủ tiền
- [ ] Thêm logging chi tiết trong catch block
- [ ] Test với user không có ví
- [ ] Test với user có ví nhưng số dư = 0
- [ ] Test với user có ví nhưng không đủ tiền
- [ ] Test với user có đủ tiền
- [ ] Kiểm tra log file: `storage/logs/laravel.log`

---

## 🔍 DEBUG

### Xem log chi tiết:

```bash
# Windows PowerShell
Get-Content storage\logs\laravel.log -Tail 100 | Select-String "Payment error|Wallet"

# Hoặc mở file trực tiếp
notepad storage\logs\laravel.log
```

### Test qua Postman:

```
POST http://localhost:8000/api/orders/{order_id}/pay
Authorization: Bearer {your_token}
Content-Type: application/json
```

### Kiểm tra wallet balance:

```bash
php artisan tinker
$user = User::find(1);
$wallet = $user->wallet;
echo "Balance: " . $wallet->available_balance;
```

---

## 💡 KHUYẾN NGHỊ

### 1. Thêm validation sớm hơn

Trong method `store()` (tạo đơn hàng), đã kiểm tra số dư:

```php
// Kiểm tra ví của buyer
$wallet = Wallet::where('user_id', $user->id)->first();
$walletBalance = $wallet ? $wallet->available_balance : 0;
$canPay = $walletBalance >= $finalAmount;

return response()->json([
    'wallet' => [
        'balance' => $walletBalance,
        'can_pay' => $canPay,
        'need_more' => $canPay ? 0 : ($finalAmount - $walletBalance),
    ],
]);
```

### 2. Frontend nên kiểm tra trước

Frontend nên gọi `/api/orders/preview` trước để kiểm tra:
- Số dư ví
- Có đủ tiền không
- Cần nạp thêm bao nhiêu

Nếu không đủ, hiển thị nút "Nạp tiền" thay vì "Thanh toán".

### 3. Thêm middleware kiểm tra wallet

Tạo middleware `CheckWalletBalance`:

```php
public function handle($request, Closure $next)
{
    $user = $request->user();
    $wallet = $user->wallet;
    
    if (!$wallet) {
        return response()->json([
            'status' => 'error',
            'message' => 'Chua co vi. Vui long tao vi truoc.',
            'requires_wallet_creation' => true
        ], 422);
    }
    
    return $next($request);
}
```

---

## 🎯 TÓM TẮT

**Vấn đề:** Lỗi server (500) khi không đủ tiền thay vì yêu cầu nạp tiền (422)

**Nguyên nhân:** 
- Wallet Model có thể throw exception trong method `deduct()`
- Catch block bắt tất cả exception và trả về 500

**Giải pháp:**
1. Kiểm tra và sửa method `deduct()` trong Wallet Model
2. Thêm try-catch cụ thể cho wallet operations
3. Thêm logging chi tiết
4. Frontend kiểm tra số dư trước khi cho phép thanh toán

**Files cần kiểm tra:**
- `app/Models/Wallet.php` - Method `deduct()`
- `app/Http/Controllers/Api/OrderController.php` - Method `payOrder()`
- `storage/logs/laravel.log` - Xem log lỗi chi tiết

