# API Business Reports - Báo cáo kinh doanh & Xuất dữ liệu

**Base URL:** `{{base_url}}` = `http://localhost:8000/api`

---

## OVERVIEW

API này cho phép seller xuất các báo cáo kinh doanh như doanh thu, đơn hàng, sản phẩm, khách hàng, v.v. dưới dạng CSV, Excel hoặc PDF.

---

## 1. Xuất báo cáo doanh thu – POST /reports/revenue/export

**Mục đích:** Xuất báo cáo doanh thu theo thời gian.

### Request

**Method:** `POST`  
**URL:** `{{base_url}}/reports/revenue/export`

**Headers:**
```
Content-Type: application/json
Accept: application/json
Authorization: Bearer {{token}}
```

**Body → raw → JSON:**
```json
{
  "date_from": "2025-01-01",
  "date_to": "2025-12-31",
  "format": "xlsx",
  "group_by": "month"
}
```

**Required fields:**
- `date_from` - Từ ngày
- `date_to` - Đến ngày
- `format` - Định dạng: `csv`, `xlsx`, `pdf`

**Optional fields:**
- `group_by` - Nhóm theo: `day`, `week`, `month`, `year` (default: `month`)

### Response mẫu

**Success (200):**
```json
{
  "message": "Revenue report generated successfully",
  "data": {
    "file_name": "revenue-report-2025.xlsx",
    "file_size": 45678,
    "file_size_human": "44.6 KB",
    "download_url": "https://example.com/downloads/revenue-report-2025.xlsx",
    "expires_at": "2025-12-05T10:00:00.000000Z",
    "summary": {
      "total_revenue": 150000000,
      "total_orders": 245,
      "average_order_value": 612244,
      "period": "2025-01-01 to 2025-12-31"
    }
  }
}
```

---

## 2. Xuất báo cáo đơn hàng – POST /reports/orders/export

**Mục đích:** Xuất danh sách đơn hàng chi tiết.

### Request

**Method:** `POST`  
**URL:** `{{base_url}}/reports/orders/export`

**Headers:**
```
Content-Type: application/json
Accept: application/json
Authorization: Bearer {{token}}
```

**Body → raw → JSON:**
```json
{
  "date_from": "2025-01-01",
  "date_to": "2025-12-31",
  "format": "csv",
  "status": "completed",
  "include_items": true
}
```

**Required fields:**
- `date_from` - Từ ngày
- `date_to` - Đến ngày
- `format` - Định dạng: `csv`, `xlsx`, `pdf`

**Optional fields:**
- `status` - Lọc theo trạng thái: `pending`, `confirmed`, `completed`, `cancelled`
- `include_items` - Bao gồm chi tiết sản phẩm (default: false)

### Response mẫu

**Success (200):**
```json
{
  "message": "Orders report generated successfully",
  "data": {
    "file_name": "orders-report-2025.csv",
    "file_size": 123456,
    "file_size_human": "120.6 KB",
    "download_url": "https://example.com/downloads/orders-report-2025.csv",
    "expires_at": "2025-12-05T10:00:00.000000Z",
    "summary": {
      "total_orders": 245,
      "completed_orders": 230,
      "cancelled_orders": 15,
      "total_amount": 150000000
    }
  }
}
```

**CSV Columns:**
```
Order ID, Order Number, Customer Name, Customer Email, Status, Total Amount, Payment Method, Created At, Completed At
```

---

## 3. Xuất báo cáo sản phẩm – POST /reports/products/export

**Mục đích:** Xuất báo cáo sản phẩm bán chạy, tồn kho.

### Request

**Method:** `POST`  
**URL:** `{{base_url}}/reports/products/export`

**Headers:**
```
Content-Type: application/json
Accept: application/json
Authorization: Bearer {{token}}
```

**Body → raw → JSON:**
```json
{
  "date_from": "2025-01-01",
  "date_to": "2025-12-31",
  "format": "xlsx",
  "sort_by": "revenue",
  "limit": 100
}
```

**Required fields:**
- `date_from` - Từ ngày
- `date_to` - Đến ngày
- `format` - Định dạng: `csv`, `xlsx`, `pdf`

**Optional fields:**
- `sort_by` - Sắp xếp theo: `revenue` (doanh thu), `quantity` (số lượng), `views` (lượt xem)
- `limit` - Giới hạn số sản phẩm (default: 100)

### Response mẫu

**Success (200):**
```json
{
  "message": "Products report generated successfully",
  "data": {
    "file_name": "products-report-2025.xlsx",
    "file_size": 67890,
    "file_size_human": "66.3 KB",
    "download_url": "https://example.com/downloads/products-report-2025.xlsx",
    "expires_at": "2025-12-05T10:00:00.000000Z",
    "summary": {
      "total_products": 150,
      "total_sold": 1250,
      "total_revenue": 150000000,
      "top_product": {
        "id": 123,
        "title": "iPhone 15 Pro Max",
        "sold": 45,
        "revenue": 45000000
      }
    }
  }
}
```

---

## 4. Xuất báo cáo khách hàng – POST /reports/customers/export

**Mục đích:** Xuất danh sách khách hàng và lịch sử mua hàng.

### Request

**Method:** `POST`  
**URL:** `{{base_url}}/reports/customers/export`

**Headers:**
```
Content-Type: application/json
Accept: application/json
Authorization: Bearer {{token}}
```

**Body → raw → JSON:**
```json
{
  "date_from": "2025-01-01",
  "date_to": "2025-12-31",
  "format": "csv",
  "min_orders": 1
}
```

**Required fields:**
- `date_from` - Từ ngày
- `date_to` - Đến ngày
- `format` - Định dạng: `csv`, `xlsx`

**Optional fields:**
- `min_orders` - Số đơn hàng tối thiểu (default: 1)

### Response mẫu

**Success (200):**
```json
{
  "message": "Customers report generated successfully",
  "data": {
    "file_name": "customers-report-2025.csv",
    "file_size": 34567,
    "file_size_human": "33.8 KB",
    "download_url": "https://example.com/downloads/customers-report-2025.csv",
    "expires_at": "2025-12-05T10:00:00.000000Z",
    "summary": {
      "total_customers": 180,
      "new_customers": 45,
      "returning_customers": 135,
      "average_order_value": 612244
    }
  }
}
```

---

## 5. Xuất báo cáo traffic – POST /reports/traffic/export

**Mục đích:** Xuất báo cáo lượt truy cập, lượt xem sản phẩm.

### Request

**Method:** `POST`  
**URL:** `{{base_url}}/reports/traffic/export`

**Headers:**
```
Content-Type: application/json
Accept: application/json
Authorization: Bearer {{token}}
```

**Body → raw → JSON:**
```json
{
  "date_from": "2025-01-01",
  "date_to": "2025-12-31",
  "format": "xlsx",
  "group_by": "day"
}
```

**Required fields:**
- `date_from` - Từ ngày
- `date_to` - Đến ngày
- `format` - Định dạng: `csv`, `xlsx`

**Optional fields:**
- `group_by` - Nhóm theo: `day`, `week`, `month` (default: `day`)

### Response mẫu

**Success (200):**
```json
{
  "message": "Traffic report generated successfully",
  "data": {
    "file_name": "traffic-report-2025.xlsx",
    "file_size": 23456,
    "file_size_human": "22.9 KB",
    "download_url": "https://example.com/downloads/traffic-report-2025.xlsx",
    "expires_at": "2025-12-05T10:00:00.000000Z",
    "summary": {
      "total_views": 15000,
      "unique_visitors": 8500,
      "average_daily_views": 41,
      "most_viewed_product": {
        "id": 123,
        "title": "iPhone 15 Pro Max",
        "views": 450
      }
    }
  }
}
```

---

## Report Formats

### CSV Format
- Dễ mở bằng Excel
- Nhẹ, tải nhanh
- Phù hợp cho dữ liệu lớn

### XLSX Format
- Excel format chuyên nghiệp
- Hỗ trợ formatting, charts
- Multiple sheets

### PDF Format
- Dễ in ấn
- Professional layout
- Chỉ dùng cho summary reports

---

## File Expiry

- File tự động xóa sau **24 giờ**
- Download ngay sau khi tạo
- Không giới hạn số lần download trong 24h

---

## Rate Limiting

- Tối đa **10 reports/ngày** cho mỗi user
- Tối đa **3 reports/giờ**

---

## Postman Tests Script

```javascript
// Lưu download URL
if (pm.response.code === 200) {
    var jsonData = pm.response.json();
    if (jsonData.data && jsonData.data.download_url) {
        pm.environment.set("report_download_url", jsonData.data.download_url);
        console.log("Download URL:", jsonData.data.download_url);
        console.log("File size:", jsonData.data.file_size_human);
    }
}
```

---

## Test Flow trong Postman

1. **Login as Seller** → Lưu token
2. **POST /reports/revenue/export** → Xuất báo cáo doanh thu
3. **POST /reports/orders/export** → Xuất báo cáo đơn hàng
4. **POST /reports/products/export** → Xuất báo cáo sản phẩm
5. **POST /reports/customers/export** → Xuất báo cáo khách hàng
6. **POST /reports/traffic/export** → Xuất báo cáo traffic

---

## Processing Time

Thời gian xử lý phụ thuộc vào:
- Số lượng dữ liệu
- Loại dữ liệu
- Định dạng file

**Ước tính:**
- Profile only: ~1 phút
- Listings + Orders: ~5 phút
- All data: ~10-15 phút

---

## Lưu ý

- Chỉ seller mới có quyền xuất báo cáo
- Báo cáo chỉ bao gồm dữ liệu của shop mình
- File tự động xóa sau 24 giờ
- Format CSV nhẹ nhất, XLSX đẹp nhất
- PDF chỉ dùng cho summary
- Xử lý real-time (không cần queue)
- Download URL có token bảo mật
