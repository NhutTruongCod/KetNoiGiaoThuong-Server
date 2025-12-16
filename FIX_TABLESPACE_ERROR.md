# SỬA LỖI TABLESPACE EXISTS

## 🔴 LỖI HIỆN TẠI

```
SQLSTATE[HY000]: General error: 1813 
Tablespace for table '`tradehub`.`migrations`' exists. 
Please DISCARD the tablespace before IMPORT
```

**Nguyên nhân:** 
- Các file `.ibd` (InnoDB data files) vẫn còn tồn tại sau khi drop tables
- MySQL không thể tạo bảng mới vì tablespace cũ chưa được xóa

---

## ✅ GIẢI PHÁP - THỰC HIỆN THEO THỨ TỰ

### CÁCH 1: Sử dụng MySQL Command Line (KHUYẾN NGHỊ)

#### Bước 1: Mở MySQL Command Line
```bash
mysql -u duy -p
# Nhập password: duy1580@
```

#### Bước 2: Chạy các lệnh sau
```sql
-- Drop database hoàn toàn
DROP DATABASE IF EXISTS tradehub;

-- Tạo lại database
CREATE DATABASE tradehub CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Kiểm tra
USE tradehub;
SHOW TABLES;

-- Thoát
exit;
```

#### Bước 3: Quay lại PowerShell và chạy migration
```bash
php artisan migrate
```

#### Bước 4: Seed dữ liệu test
```bash
php artisan db:seed --class=TestUserSeeder
```

---

### CÁCH 2: Xóa thủ công MySQL Data Directory (NẾU CÁCH 1 KHÔNG THÀNH CÔNG)

#### Bước 1: Dừng MySQL Service
```powershell
# Mở PowerShell với quyền Administrator
Stop-Service MySQL80
# Hoặc
net stop MySQL80
```

#### Bước 2: Xóa thư mục database
```powershell
# Thư mục MySQL data thường ở:
# C:\ProgramData\MySQL\MySQL Server 8.0\Data\tradehub

# Xóa thư mục tradehub
Remove-Item -Path "C:\ProgramData\MySQL\MySQL Server 8.0\Data\tradehub" -Recurse -Force
```

**LƯU Ý:** Đường dẫn có thể khác tùy cách cài đặt MySQL. Kiểm tra trong MySQL Workbench:
```sql
SHOW VARIABLES LIKE 'datadir';
```

#### Bước 3: Khởi động lại MySQL Service
```powershell
Start-Service MySQL80
# Hoặc
net start MySQL80
```

#### Bước 4: Tạo lại database
```bash
mysql -u duy -p
```
```sql
CREATE DATABASE tradehub CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
exit;
```

#### Bước 5: Chạy migration
```bash
php artisan migrate
php artisan db:seed --class=TestUserSeeder
```

---

### CÁCH 3: Sử dụng Script SQL có sẵn

#### Bước 1: Chạy script SQL
```bash
mysql -u duy -p < fix_tablespace_error.sql
```

#### Bước 2: Chạy migration
```bash
php artisan migrate
php artisan db:seed --class=TestUserSeeder
```

---

### CÁCH 4: Sử dụng MySQL Workbench (GUI)

#### Bước 1: Mở MySQL Workbench

#### Bước 2: Kết nối đến server
- Host: 127.0.0.1
- Port: 3306
- Username: duy
- Password: duy1580@

#### Bước 3: Drop database
```sql
DROP DATABASE IF EXISTS tradehub;
```

#### Bước 4: Tạo lại database
```sql
CREATE DATABASE tradehub CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

#### Bước 5: Quay lại PowerShell
```bash
php artisan migrate
php artisan db:seed --class=TestUserSeeder
```

---

## 🧪 KIỂM TRA SAU KHI SỬA

### 1. Kiểm tra database
```bash
php artisan db:show
```

**Kết quả mong đợi:**
```
Database ........................ tradehub
Host ............................ 127.0.0.1
Port ............................ 3306
Username ........................ duy
Tables .......................... 51
```

### 2. Kiểm tra users
```bash
php artisan tinker --execute="echo 'Total users: ' . User::count();"
```

**Kết quả mong đợi:**
```
Total users: 3
```

### 3. Liệt kê users
```bash
php artisan tinker --execute="User::all(['email', 'role'])->each(fn(\$u) => print(\$u->email . ' (' . \$u->role . ')' . PHP_EOL));"
```

**Kết quả mong đợi:**
```
admin@tradehub.com (admin)
seller@tradehub.com (seller)
buyer@tradehub.com (buyer)
```

### 4. Test đăng nhập qua API

**Postman:**
```
POST http://localhost:8000/api/auth/login
Content-Type: application/json

{
  "email": "admin@tradehub.com",
  "password": "admin123"
}
```

**cURL:**
```bash
curl -X POST http://localhost:8000/api/auth/login ^
  -H "Content-Type: application/json" ^
  -d "{\"email\":\"admin@tradehub.com\",\"password\":\"admin123\"}"
```

**Response mong đợi:**
```json
{
  "status": "success",
  "message": "Login successful",
  "data": {
    "access_token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
    "refresh_token": "abc123...",
    "token_type": "bearer",
    "expires_in": 3600
  }
}
```

---

## 🔧 NẾU VẪN GẶP LỖI

### Lỗi: "Access denied for user"
```bash
# Kiểm tra lại thông tin đăng nhập trong .env
DB_USERNAME=duy
DB_PASSWORD=duy1580@
```

### Lỗi: "Connection refused"
```bash
# Kiểm tra MySQL service đang chạy
Get-Service MySQL80

# Khởi động nếu cần
Start-Service MySQL80
```

### Lỗi: "Unknown database"
```bash
# Tạo lại database
mysql -u duy -p -e "CREATE DATABASE tradehub CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

### Lỗi migration khác
```bash
# Xem chi tiết lỗi
php artisan migrate --verbose

# Hoặc xem log
Get-Content storage\logs\laravel.log -Tail 50
```

---

## 📋 CHECKLIST

- [ ] Dừng tất cả các ứng dụng đang kết nối đến database
- [ ] Chạy `DROP DATABASE IF EXISTS tradehub;` trong MySQL
- [ ] Chạy `CREATE DATABASE tradehub ...;`
- [ ] Kiểm tra database đã được tạo: `SHOW DATABASES;`
- [ ] Chạy `php artisan migrate`
- [ ] Chạy `php artisan db:seed --class=TestUserSeeder`
- [ ] Kiểm tra `php artisan db:show`
- [ ] Kiểm tra `User::count()` = 3
- [ ] Test đăng nhập qua Postman
- [ ] Test đăng nhập qua Frontend
- [ ] Clear cache: `php artisan config:cache`

---

## 💡 LƯU Ý QUAN TRỌNG

1. **Backup trước khi xóa** (nếu có dữ liệu quan trọng)
   ```bash
   mysqldump -u duy -p tradehub > backup_before_fix.sql
   ```

2. **Đảm bảo không có ứng dụng nào đang kết nối** đến database khi drop

3. **Kiểm tra quyền truy cập** MySQL data directory nếu cần xóa thủ công

4. **Sau khi sửa xong:**
   - Clear cache Laravel
   - Restart web server nếu cần
   - Test toàn bộ chức năng

---

## 🎯 TÓM TẮT LỆNH NHANH

```bash
# 1. Drop và tạo lại database trong MySQL
mysql -u duy -p
DROP DATABASE IF EXISTS tradehub;
CREATE DATABASE tradehub CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
exit;

# 2. Migrate và seed
php artisan migrate
php artisan db:seed --class=TestUserSeeder

# 3. Kiểm tra
php artisan db:show
php artisan tinker --execute="echo User::count();"

# 4. Test đăng nhập
# Email: admin@tradehub.com
# Password: admin123
```

---

## 📞 HỖ TRỢ

Nếu vẫn gặp vấn đề, cung cấp:
1. Output của `php artisan migrate --verbose`
2. Output của `SHOW VARIABLES LIKE 'datadir';` trong MySQL
3. Nội dung file `storage/logs/laravel.log`
4. MySQL version: `mysql --version`
5. Screenshot lỗi chi tiết

