-- Script sửa lỗi Tablespace exists
-- Chạy script này trong MySQL command line hoặc MySQL Workbench

-- Bước 1: Drop database hoàn toàn
DROP DATABASE IF EXISTS tradehub;

-- Bước 2: Tạo lại database mới
CREATE DATABASE tradehub CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Bước 3: Sử dụng database
USE tradehub;

-- Bước 4: Kiểm tra
SELECT 'Database created successfully!' AS status;
SHOW TABLES;
