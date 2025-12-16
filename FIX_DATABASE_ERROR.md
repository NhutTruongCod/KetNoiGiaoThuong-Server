# HƯỚNG DẪN SỬA LỖI DATABASE

## 🔴 VẤN ĐỀ HIỆN TẠI

Lỗi: `SQLSTATE[42S02]: Base table or view not found: 1932 Table 'tradehub.users' doesn't exist in engine`

**Nguyên nhân:** 
- Database `tradehub` có 51 bảng nhưng MySQL engine không nhận ra
- Có thể do:
  1. Bảng bị corrupt
  2. MySQL 8.0 có vấn đề với InnoDB
  3. File `.ibd` bị mất hoặc không đồng bộ

---

## ✅ GIẢI PHÁP 1: DROP VÀ TẠO LẠI DATABASE (KHUYẾN NGHỊ)

### Bước 1: Kết nối MySQL
```bash
mysql -u duy -p
# Nhập password: duy1580@
```

### Bước 2: Drop database cũ và tạo mới
```sql
DROP DATABASE IF EXISTS tradehub;
CREATE DATABASE tradehub CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE tradehub;
exit;
```

### Bước 3: Chạy migration lại
```bash
php artisan migrate:fresh
```

### Bước 4: Seed dữ liệu test
```bash
php artisan db:seed
```

---

## ✅ GIẢI PHÁP 2: SỬA CHỮA BẢNG (NẾU MUỐN GIỮ DỮ LIỆU)

### Bước 1: Kết nối MySQL
```bash
mysql -u duy -p
USE tradehub;
```

### Bước 2: Kiểm tra và sửa chữa từng bảng
```sql
-- Kiểm tra bảng users
CHECK TABLE users;

-- Sửa chữa bảng users
REPAIR TABLE users;

-- Hoặc rebuild bảng
ALTER TABLE users ENGINE=InnoDB;
```

### Bước 3: Sửa chữa tất cả bảng
```sql
-- Lấy danh sách tất cả bảng
SELECT CONCAT('REPAIR TABLE ', table_name, ';') 
FROM information_schema.tables 
WHERE table_schema = 'tradehub';

-- Copy kết quả và chạy từng lệnh REPAIR TABLE
```

---

## ✅ GIẢI PHÁP 3: KIỂM TRA VÀ SỬA FILE SYSTEM

### Bước 1: Kiểm tra quyền truy cập
```bash
# Trên Windows, kiểm tra MySQL data directory
# Thường ở: C:\ProgramData\MySQL\MySQL Server 8.0\Data\tradehub
```

### Bước 2: Kiểm tra file .ibd
```bash
# Đảm bảo các file .ibd tồn tại cho mỗi bảng
# Ví dụ: users.ibd, shops.ibd, listings.ibd, etc.
```

---

## ✅ GIẢI PHÁP 4: TẠO USER TEST BẰNG SEEDER

Sau khi database hoạt động, tạo user test:

### File: database/seeders/TestUserSeeder.php
```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class TestUserSeeder extends Seeder
{
    public function run()
    {
        // Admin user
        User::create([
            'email' => 'admin@tradehub.com',
            'full_name' => 'Admin User',
            'password_hash' => Hash::make('admin123'),
            'role' => 'admin',
            'status' => 'active',
            'is_verified' => true,
            'is_active' => true,
            'provider' => 'local',
        ]);

        // Seller user
        User::create([
            'email' => 'seller@tradehub.com',
            'full_name' => 'Seller User',
            'password_hash' => Hash::make('seller123'),
            'role' => 'seller',
            'status' => 'active',
            'is_verified' => true,
            'is_active' => true,
            'provider' => 'local',
        ]);

        // Buyer user
        User::create([
            'email' => 'buyer@tradehub.com',
            'full_name' => 'Buyer User',
            'password_hash' => Hash::make('buyer123'),
            'role' => 'buyer',
            'status' => 'active',
            'is_verified' => true,
            'is_active' => true,
            'provider' => 'local',
        ]);
    }
}
```

### Chạy seeder:
```bash
php artisan db:seed --class=TestUserSeeder
```

---

## 🧪 KIỂM TRA SAU KHI SỬA

### 1. Kiểm tra kết nối database
```bash
php artisan db:show
```

### 2. Kiểm tra số lượng users
```bash
php artisan tinker --execute="echo 'Total users: ' . User::count();"
```

### 3. Test đăng nhập qua API
```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@tradehub.com",
    "password": "admin123"
  }'
```

### 4. Hoặc test qua Postman
```
POST http://localhost:8000/api/auth/login
Content-Type: application/json

{
  "email": "admin@tradehub.com",
  "password": "admin123"
}
```

---

## 📋 THÔNG TIN TÀI KHOẢN TEST

Sau khi chạy seeder, bạn có thể đăng nhập với:

### Admin Account
- Email: `admin@tradehub.com`
- Password: `admin123`
- Role: admin

### Seller Account
- Email: `seller@tradehub.com`
- Password: `seller123`
- Role: seller

### Buyer Account
- Email: `buyer@tradehub.com`
- Password: `buyer123`
- Role: buyer

---

## 🔍 DEBUG THÊM

### Kiểm tra MySQL error log
```bash
# Windows: C:\ProgramData\MySQL\MySQL Server 8.0\Data\*.err
# Linux: /var/log/mysql/error.log
```

### Kiểm tra Laravel log
```bash
# Windows
type storage\logs\laravel.log | Select-Object -Last 50

# Linux
tail -f storage/logs/laravel.log
```

### Kiểm tra MySQL version
```bash
mysql --version
```

### Kiểm tra InnoDB status
```sql
SHOW ENGINE INNODB STATUS;
```

---

## 💡 KHUYẾN NGHỊ

1. **Backup trước khi sửa** (nếu có dữ liệu quan trọng)
   ```bash
   mysqldump -u duy -p tradehub > backup_tradehub.sql
   ```

2. **Sử dụng Giải pháp 1** (Drop và tạo lại) nếu:
   - Đây là môi trường development
   - Chưa có dữ liệu quan trọng
   - Muốn bắt đầu sạch sẽ

3. **Sử dụng Giải pháp 2** (Repair) nếu:
   - Có dữ liệu cần giữ lại
   - Đang ở môi trường production

4. **Sau khi sửa xong:**
   - Chạy `php artisan config:cache`
   - Chạy `php artisan route:cache`
   - Restart web server nếu cần

---

## 📞 HỖ TRỢ

Nếu vẫn gặp lỗi, cung cấp thông tin sau:
- MySQL version: `mysql --version`
- Laravel version: `php artisan --version`
- Error log: `storage/logs/laravel.log`
- MySQL error log
- Output của: `SHOW CREATE TABLE users;`

