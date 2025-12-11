# 📚 TradeHub B2B - API Documentation Index

**Base URL:** `http://localhost:8000/api`  
**Version:** 1.0.0  
**Last Updated:** 01/12/2025

---

## 📋 Danh Sách Tất Cả API Files

### Core APIs

1. **[API_01_Authentication.md](API_01_Authentication.md)** - Xác thực
   - POST /auth/register
   - POST /auth/verify-email
   - POST /auth/login
   - POST /auth/logout
   - POST /auth/refresh
   - POST /auth/forgot-password
   - POST /auth/reset-password
   - POST /auth/resend-verification-otp

2. **[API_02_User_Management.md](API_02_User_Management.md)** - Quản lý người dùng
   - GET /user
   - GET /admin/users

3. **[API_03_Shops.md](API_03_Shops.md)** - Quản lý gian hàng
   - GET /shops
   - GET /shops/{shop}
   - POST /shops
   - PUT /shops/{shop}
   - DELETE /shops/{shop}

4. **[API_04_Categories.md](API_04_Categories.md)** - Danh mục
   - GET /categories
   - GET /categories/simple-list
   - GET /categories/{category}
   - POST /categories
   - PUT /categories/{category}
   - DELETE /categories/{category}

5. **[API_14_Listings.md](API_14_Listings.md)** - Tin đăng ⭐
   - GET /listings
   - GET /listings/{listing}
   - POST /listings
   - PUT /listings/{listing}
   - DELETE /listings/{listing}
   - PUT /admin/listings/{listing}/approve

### Business Features

6. **[API_07_Orders.md](API_07_Orders.md)** - Đơn hàng
   - GET /orders
   - GET /orders/{id}
   - POST /orders
   - PUT /orders/{id}
   - DELETE /orders/{id}

7. **[API_09_Payments.md](API_09_Payments.md)** - Thanh toán
   - GET /payments
   - GET /payments/{id}
   - POST /payments

8. **[API_08_Reviews.md](API_08_Reviews.md)** - Đánh giá
   - GET /reviews
   - GET /reviews/{id}
   - POST /reviews
   - PUT /reviews/{id}
   - DELETE /reviews/{id}

9. **[API_15_Auctions.md](API_15_Auctions.md)** - Đấu giá
   - GET /auctions
   - GET /auctions/{auction}
   - POST /auctions
   - PUT /auctions/{auction}
   - DELETE /auctions/{auction}
   - POST /auctions/{auction}/bids
   - GET /auctions/{auction}/bids
   - GET /auctions/my-bids

### Social & Communication

10. **[API_16_Chat_Messages.md](API_16_Chat_Messages.md)** - Chat
    - GET /chat/conversations
    - GET /chat/messages/{user_id}
    - POST /chat/messages
    - PUT /chat/messages/{user_id}/read

11. **[API_17_Bookmarks.md](API_17_Bookmarks.md)** - Yêu thích
    - GET /bookmarks
    - POST /bookmarks
    - DELETE /bookmarks/{listing_id}

12. **[API_18_Social_Features.md](API_18_Social_Features.md)** - Like & Comment
    - POST /listings/{listing}/like
    - DELETE /listings/{listing}/like
    - POST /listings/{listing}/comments
    - GET /listings/{listing}/comments

13. **[API_19_Inquiries.md](API_19_Inquiries.md)** - Yêu cầu tư vấn
    - POST /inquiries
    - GET /inquiries

### Support & Admin

14. **[API_05_Identity_Verification.md](API_05_Identity_Verification.md)** - Xác minh danh tính
    - GET /identity/profile
    - PUT /identity/profile
    - POST /identity/verify-request
    - GET /identity/verify-history
    - GET /identity/verify-requests (Admin)
    - PUT /identity/verify-request/{id}/approve (Admin)
    - PUT /identity/verify-request/{id}/reject (Admin)

15. **[API_06_Moderation.md](API_06_Moderation.md)** - Kiểm duyệt
    - POST /moderation/report
    - GET /moderation/my-reports
    - GET /moderation/reports (Admin)
    - PUT /moderation/reports/{id}/resolve (Admin)
    - DELETE /moderation/reports/{id} (Admin)

16. **[API_20_Support_FAQ.md](API_20_Support_FAQ.md)** - Hỗ trợ
    - GET /faqs
    - GET /support/tickets
    - POST /support/tickets
    - GET /support/tickets/{ticket}
    - POST /support/tickets/{ticket}/messages
    - PUT /support/tickets/{ticket}/close

### Subscription & Notifications

17. **[API_11_Notifications.md](API_11_Notifications.md)** - Thông báo
    - GET /notifications
    - GET /notifications/{id}
    - PUT /notifications/{id}/read
    - PUT /notifications/read-all
    - DELETE /notifications/{id}
    - DELETE /notifications/delete-all

18. **[API_12_Subscription_Plans.md](API_12_Subscription_Plans.md)** - Gói thành viên
    - GET /plans
    - GET /plans/{id}
    - POST /subscriptions
    - GET /subscriptions/current
    - GET /subscriptions/history
    - PUT /subscriptions/{id}/renew
    - DELETE /subscriptions/{id}/cancel

### Marketing & Analytics

19. **[API_21_Promotions.md](API_21_Promotions.md)** - Quảng cáo
    - GET /promotion
    - GET /promotion/active
    - GET /promotion/{id}
    - POST /promotion
    - PUT /promotion/{id}
    - PATCH /promotion/{id}/featured
    - DELETE /promotion/{id}

20. **[API_22_Statistics.md](API_22_Statistics.md)** - Thống kê
    - GET /stats/overview
    - GET /stats/views
    - GET /stats/revenue
    - GET /stats/promotions

### Utilities

21. **[API_10_Login_History.md](API_10_Login_History.md)** - Lịch sử đăng nhập
    - GET /login-history
    - GET /admin/login-history (Admin)
    - GET /admin/users/{userId}/login-history (Admin)

22. **[API_13_Data_Export.md](API_13_Data_Export.md)** - Xuất dữ liệu cá nhân (GDPR)
    - POST /data/export/request
    - GET /data/export/status/{id}
    - GET /data/export/download/{id}
    - DELETE /data/export/cancel/{id}
    - GET /data/export/history

23. **[API_23_Discovery_Search.md](API_23_Discovery_Search.md)** - Tìm kiếm
    - GET /discovery/search

24. **[API_24_Business_Reports.md](API_24_Business_Reports.md)** - Báo cáo kinh doanh
    - POST /reports/revenue/export
    - POST /reports/orders/export
    - POST /reports/products/export
    - POST /reports/customers/export
    - POST /reports/traffic/export

---

## 📊 Tổng Kết

**Tổng số API endpoints:** 100+

**Phân loại theo Authentication:**
- Public (không cần auth): ~25 endpoints
- Authenticated: ~60 endpoints
- Seller only: ~20 endpoints
- Admin only: ~15 endpoints

**Phân loại theo HTTP Method:**
- GET: 48 endpoints (48%)
- POST: 30 endpoints (30%)
- PUT: 14 endpoints (14%)
- PATCH: 1 endpoint (1%)
- DELETE: 9 endpoints (9%)

**Phân loại theo Chức năng:**
- Core Business: 60 endpoints
- User Management: 25 endpoints
- Admin/Moderation: 20 endpoints
- Analytics/Reports: 15 endpoints
- Support/Utilities: 30 endpoints

---

## 🚀 Quick Start

### 1. Setup Environment
```bash
cp .env.example .env
php artisan key:generate
php artisan jwt:secret
php artisan migrate:fresh --seed
php artisan serve
```

### 2. Test với Postman
1. Import environment variables
2. Register → Verify → Login
3. Test các endpoints theo role

### 3. Test Data
```
Admin: admin@tradehub.com / password
Seller: seller1@tradehub.com / password
Buyer: buyer1@tradehub.com / password
```

---

## 📖 Hướng Dẫn Sử Dụng

### Buyer Flow
1. Register & Login
2. Browse listings
3. Add to bookmarks
4. Chat with seller
5. Create order
6. Make payment
7. Write review

### Seller Flow
1. Register & Login
2. Create shop
3. Create listings
4. Manage orders
5. View statistics
6. Create promotions

### Admin Flow
1. Login as admin
2. Manage users
3. Approve listings
4. Handle reports
5. View all statistics

---

## 🔗 Related Files

- `routes/api.php` - Route definitions
- `POSTMAN_TEST_GUIDE.md` - Postman testing guide
- `DATABASE_SCHEMA.md` - Database structure
- `SETUP_GUIDE.md` - Setup instructions

---

**Cập nhật:** 01/12/2025  
**Version:** 1.0.0
