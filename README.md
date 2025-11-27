# TradeHub / Kết Nối Giao Thương — Backend (Laravel) + Docker

Tài liệu này hướng dẫn **chạy Backend bằng Docker**, **xử lý lỗi thường gặp**, và **kết nối với Frontend (Vite + React) — cả chạy ngoài máy và chạy bằng Docker**. Mặc định viết cho **Windows + PowerShell** (chạy được tương tự trên macOS/Linux).

> ✅ Mục tiêu: `docker compose up -d` là xong, API chạy ở `http://localhost:8010`, DB chạy trong container MariaDB, có **Adminer** để xem bảng, và có hướng dẫn nối FE.

---

## 0) Yêu cầu tối thiểu

- Docker Desktop
- Git, VS Code (khuyến nghị)
- Không cần cài PHP/MySQL/Composer trên máy host

> **Thư mục giả định**: bạn đang đứng tại thư mục backend (Laravel) `project_ketNoi_giaoThuong` (cùng nơi chứa `composer.json`).

---

## 1) Cấu trúc Docker

### 1.1. `Dockerfile` (đặt ở thư mục gốc Laravel)
```Dockerfile
FROM php:8.2-cli

RUN apt-get update && apt-get install -y \
    git unzip libzip-dev libicu-dev libpng-dev libonig-dev libxml2-dev default-mysql-client \
 && docker-php-ext-install pdo_mysql zip intl gd bcmath

# Composer chính chủ
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
EXPOSE 8000
```

### 1.2. `docker-compose.yml`
# TradeHub – Backend API (Laravel) cho website kết nối giao thương

Dự án này là backend API (Laravel) phục vụ đồ án **kết nối giao thương**, tập trung vào nhóm chức năng:

- **Discovery & Listings**: tìm kiếm, xem tin công khai, tìm “gần tôi”.
- **Interactions**: lưu bookmark, chat/liên hệ, yêu cầu liên hệ (inquiry), đấu giá, tương tác xã hội (like/comment).
- **Support**: FAQ, ticket hỗ trợ; chatbot đơn giản có thể xây trên dữ liệu FAQ.

Toàn bộ phần khác (orders/payments/shipments/contracts…) **không thuộc phạm vi đồ án này** và không được sử dụng trong hướng dẫn bên dưới.

---

## 1. Công nghệ & cấu trúc chính

### 1.1. Công nghệ

- PHP 8.x + Laravel
- MySQL (database: `tradehub`)
- Docker / docker compose (xapp)
- Laravel Sanctum cho xác thực API

### 1.2. Cấu trúc thư mục liên quan nhiệm vụ

- `routes/api.php`
  - Các route nhóm discovery, nearby, listings, bookmarks, chat, inquiries, auctions, social, support.
- `app/Models`
  - `Listing` – tin công khai, nguồn dữ liệu cho search / nearby / listings.
  - `Bookmark` – lưu bookmark của người dùng.
  - `ChatMessage` – tin nhắn chat nội bộ giữa 2 user.
  - `Inquiry` – yêu cầu liên hệ công khai theo listing.
  - `Auction`, `AuctionBid` – phiên đấu giá và lượt đặt giá.
  - `ListingLike`, `ListingComment` – tương tác xã hội.
  - `Faq`, `SupportTicket`, `SupportMessage` – FAQ và ticket hỗ trợ.
- `app/Http/Controllers/Discovery`
  - `DiscoveryController` – search, nearby, public listings, bookmarks (view).
  - `BookmarkController` – thêm/xóa bookmark.
  - `ChatController` – chat nội bộ.
  - `InquiryController` – tạo inquiry công khai.
  - `AuctionController` – xem auction, đặt giá thầu.
  - `SocialController` – like/comment.
  - `SupportController` – FAQ, ticket.
- `app/Http/Controllers/Auth`
  - `AuthController`, `SocialLoginController` – đăng nhập/email + Google.
- `app/Http/Controllers/IdentityController`
  - Lấy thông tin hồ sơ / `/api/identity/profile`, `/api/me`.

Các module khác nếu có (orders, payments, shipments, contracts…) không dùng trong scope này.

---

## 2. Cấu hình database và môi trường

### 2.1. Thông số database

- Tên database: **`tradehub`**
- MySQL user: **`duy`**
- MySQL password: **`duy1580@`**

### 2.2. File `.env`

File `.env` đã được cấu hình sẵn:

```env
DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3307
DB_DATABASE=tradehub
DB_USERNAME=duy
DB_PASSWORD=duy1580@
```

Nếu bạn đổi port MySQL trong Docker/xapp, cập nhật lại `DB_PORT` cho khớp.

### 2.3. `docker-compose.yml`

Service `app` và `db` được định nghĩa trong `docker-compose.yml`. Phần môi trường ứng dụng:

```yaml
services:
  app:
    build:
      context: .
      dockerfile: Dockerfile
    container_name: tradehub-app
    working_dir: /var/www/html
    volumes:
      - ./:/var/www/html
    ports:
      - "8010:8000"     # API: http://localhost:8010
      - "8010:8000"  # http://localhost:8010
    depends_on:
      - db
    environment:
      APP_ENV: local
      APP_DEBUG: "true"
      APP_URL: http://localhost:8010
      DB_CONNECTION: mysql
      DB_HOST: db
      DB_PORT: 3307
      DB_DATABASE: tradehub
      DB_USERNAME: duy
      DB_PASSWORD: duy1580@
```

Phần MySQL:

```yaml
  db:
    image: mysql:8.0
    container_name: tradehub-db
    environment:
      MYSQL_DATABASE: tradehub
      MYSQL_USER: duy
      MYSQL_PASSWORD: "duy1580@"
      MYSQL_ROOT_PASSWORD: "rootpass"
    volumes:
      - db_data:/var/lib/mysql
    networks: [appnet]
```

Adminer (GUI DB) nếu bật: `http://localhost:8081`.

---

## 3. Khởi động dự án bằng Docker

### 3.1. Yêu cầu

- Docker Desktop
- PowerShell (Windows) / terminal (macOS/Linux)

Không cần cài PHP/MySQL/Composer trực tiếp trên máy host.

### 3.2. Các bước khởi động

Đứng tại thư mục backend `project_ketNoi_giaoThuong` (cùng nơi chứa `composer.json`):

```powershell
# 1) Bật Docker Desktop
# 2) Build + start containers
docker compose up -d --build

# 3) Cài vendor PHP (lần đầu)
docker compose run --rm app composer install --no-interaction --prefer-dist

# 4) Clear config + generate app key
docker compose run --rm app php artisan config:clear
docker compose run --rm app php artisan key:generate

# 5) Migrate + seed (tạo bảng và user mặc định)
docker compose exec app php artisan migrate --seed
```

- API backend: `http://localhost:8010`
- Adminer: `http://localhost:8081` (login bằng user `duy` / `duy1580@`, database `tradehub`)

Seeder tạo user mặc định:

- Email: `duy@example.com`
- Mật khẩu: `duy1580@`

Bạn có thể dùng tài khoản này để đăng nhập API.

---

## 4. Các API trong phạm vi nhiệm vụ

### 4.1. Discovery & Listings (BA 3.4)

- `GET /api/discovery/search`
  - Tìm kiếm tin theo từ khóa, danh mục, khoảng giá…
  - Query params ví dụ:
    - `query` – chuỗi tìm kiếm
    - `category` – danh mục
    - `min_price`, `max_price`
    - `page`
  - Tất cả dữ liệu lấy từ bảng `listings`.

- `GET /api/discovery/nearby`
  - Tìm tin “gần tôi” theo toạ độ.
  - Query params:
    - `lat`, `lng` – toạ độ
    - `radius_km` – bán kính (km)

- `GET /api/listings`
  - Trả về danh sách tin công khai (status published, public).

### 4.2. Bookmark (API Bookmarks)

Yêu cầu đăng nhập (Bearer token / Sanctum):

- `POST /api/bookmarks`
  - Body:
    ```json
    { "listing_id": 1 }
    ```
  - Lưu bookmark cho listing.

- `GET /api/discovery/bookmarks`
  - Danh sách bookmark của user hiện tại.

- `DELETE /api/bookmarks/{id}`
  - Xoá một bookmark theo id (chỉ xoá được bookmark của chính mình).

### 4.3. Chat / Liên hệ (BA 3.4, API Chat)

Yêu cầu đăng nhập:

- `POST /api/chat/messages`
  - Body:
    ```json
    {
      "to_user_id": 2,
      "listing_id": 1,   // tùy chọn
      "body": "Xin chào, tôi quan tâm listing này"
    }
    ```

- `GET /api/chat/messages?with_user_id=2&listing_id=1`
  - Lấy lịch sử chat giữa user hiện tại và user khác (theo `with_user_id`), có thể filter theo `listing_id`.

### 4.4. Yêu cầu liên hệ (BA 3.4, API Inquiry)

Không yêu cầu đăng nhập, có throttle:

- `POST /api/inquiries`
  - Body:
    ```json
    {
      "listing_id": 1,
      "name": "Người A",
      "email": "a@example.com",
      "phone": "0909xxxxxx",
      "message": "Cho tôi xin báo giá chi tiết."
    }
    ```

Bảng `inquiries` lưu lại nội dung + IP của người gửi.

### 4.5. Đấu giá (BA 3.6, API Auctions)

Yêu cầu đăng nhập:

- `GET /api/auctions`
  - Danh sách phiên đấu giá (có thể filter active/upcoming/ended tùy logic trong controller).

- `GET /api/auctions/{id}`
  - Chi tiết 1 phiên đấu giá + lịch sử bids.

- `POST /api/auctions/{id}/bids`
  - Body:
    ```json
    { "amount_cents": 500000 }
    ```
  - Đặt giá thầu mới (cao hơn current_price, trong thời gian active).

### 4.6. Tương tác xã hội (BA 3.4, API Social/Interactions)

Yêu cầu đăng nhập:

- `POST /api/social/listings/{id}/like`
  - Like/unlike listing (một user chỉ like một lần; gọi lại để unlike tùy implement).

- `GET /api/social/listings/{id}/comments`
  - Lấy danh sách comment của 1 listing.

- `POST /api/social/listings/{id}/comments`
  - Body:
    ```json
    { "body": "Tin rất hữu ích." }
    ```

---

## 5. Ticket / FAQ / Chatbot (BA 3.8)

### 5.1. FAQ

- `GET /api/support/faqs`
  - Trả về danh sách câu hỏi thường gặp (FAQ) đang public.
  - Frontend/Chatbot có thể dùng endpoint này để trả lời tự động các câu hỏi.

### 5.2. Ticket hỗ trợ

Yêu cầu đăng nhập:

- `POST /api/support/tickets`
  - Body:
    ```json
    {
      "subject": "Không đăng được tin",
      "message": "Mô tả chi tiết lỗi..."
    }
    ```

- `GET /api/support/tickets`
  - Danh sách ticket của user hiện tại.

- `GET /api/support/tickets/{id}`
  - Chi tiết 1 ticket + toàn bộ conversation.

- `POST /api/support/tickets/{id}/reply`
  - Body:
    ```json
    { "message": "Mình bổ sung thêm thông tin..." }
    ```

---

## 6. Ghi chú về phạm vi

- Tất cả các API discovery & interactions trong đồ án **đều depend vào bảng `listings`** làm nguồn dữ liệu tin đăng.
- Các module khác (orders, payments, shipments, contracts, v.v.) nếu có trong DB/code là phần mở rộng, **không sử dụng** trong bài thực tập hiện tại và không mô tả trong README này.
