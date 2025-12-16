# HƯỚNG DẪN TEST NHANH - THANH TOÁN

## 🚀 BƯỚC 1: RESTART SERVER

```bash
# Dừng server hiện tại (Ctrl+C trong terminal đang chạy php artisan serve)
# Sau đó chạy lại:
php artisan serve
```

## 🧹 BƯỚC 2: CLEAR CACHE

```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
```

## 💰 BƯỚC 3: NẠP TIỀN VÀO VÍ TEST

```bash
php artisan tinker
```

Trong tinker, chạy lệnh sau:

```php
$wallet = App\Models\Wallet::where('user_id', 5)->first();
$wallet->credit(150000000, 'deposit', null, null, 'Test deposit');
echo "Balance: " . $wallet->available_balance . " VND\n";
exit
```

## 🧪 BƯỚC 4: TEST TỪ FRONTEND

### Test Case 1: Không đủ tiền (trước khi nạp)

1. Login với user: `buyer1@example.com` / password: `password`
2. Tạo đơn hàng với listing_id = 11
3. Thử thanh toán
4. **Kết quả mong đợi**: Nhận response 422 với thông báo "So du vi khong du"

### Test Case 2: Đủ tiền (sau khi nạp)

1. Nạp tiền theo BƯỚC 3
2. Tạo đơn hàng mới với listing_id = 11
3. Thanh toán
4. **Kết quả mong đợi**: Thanh toán thành công, nhận response 200

## 📊 KIỂM TRA KẾT QUẢ

### Xem log:

```bash
Get-Content storage/logs/laravel.log -Tail 50
```

### Kiểm tra số dư ví:

```bash
php test_wallet_data.php
```

## ❓ NẾU VẪN LỖI

1. **Kiểm tra code đã được sửa chưa:**
   - Mở `app/Models/Wallet.php` line 113-135
   - Phải thấy `return false;` thay vì `throw new \Exception()`

2. **Kiểm tra log chi tiết:**
   ```bash
   # Xóa log cũ
   echo. > storage/logs/laravel.log
   
   # Test lại
   # Xem log mới:
   Get-Content storage/logs/laravel.log
   ```

3. **Kiểm tra database:**
   ```bash
   php test_wallet_data.php
   ```

## 📞 THÔNG TIN TEST

- **Backend**: http://localhost:8000
- **Frontend**: http://localhost:5173
- **Test User**: buyer1@example.com / password
- **Test Listing**: ID = 11 (Giá: 123,123,123 VND)
- **Số tiền cần nạp**: Tối thiểu 150,000,000 VND

## ✅ CHECKLIST

- [ ] Đã restart PHP server
- [ ] Đã clear cache
- [ ] Đã nạp tiền vào ví test
- [ ] Đã test từ frontend
- [ ] Nhận được response 422 khi không đủ tiền
- [ ] Thanh toán thành công khi đủ tiền
