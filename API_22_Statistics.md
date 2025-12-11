# API Statistics - Thống kê

**Base URL:** `{{base_url}}` = `http://localhost:8000/api`

---

## 1. Tổng quan thống kê – GET /stats/overview

**Mục đích:** Seller xem tổng quan thống kê kinh doanh.

### Request

**Method:** `GET`  
**URL:** `{{base_url}}/stats/overview`

**Headers:**
```
Accept: application/json
Authorization: Bearer {{seller_token}}
```

**Query Parameters (optional):**
```
?date_from=2025-01-01
&date_to=2025-12-31
&shop_id=1
```

**Body:** Không cần

### Response mẫu

**Success (200):**
```json
{
  "period": {
    "from": "2025-01-01",
    "to": "2025-12-31"
  },
  "summary": {
    "total_listings": 150,
    "active_listings": 120,
    "total_views": 45000,
    "total_orders": 350,
    "total_revenue": 450000000,
    "avg_order_value": 1285714,
    "total_customers": 280,
    "repeat_customers": 70,
    "avg_rating": 4.5,
    "total_reviews": 120
  },
  "growth": {
    "views": 15.5,
    "orders": 12.3,
    "revenue": 18.7,
    "customers": 10.2
  },
  "charts": {
    "views_by_month": [
      {"month": "2025-01", "views": 3500},
      {"month": "2025-02", "views": 4200},
      {"month": "2025-03", "views": 3800}
    ],
    "revenue_by_month": [
      {"month": "2025-01", "revenue": 35000000},
      {"month": "2025-02", "revenue": 42000000},
      {"month": "2025-03", "revenue": 38000000}
    ],
    "orders_by_status": {
      "pending": 15,
      "confirmed": 25,
      "shipping": 30,
      "delivered": 250,
      "cancelled": 30
    }
  },
  "top_listings": [
    {
      "id": 123,
      "title": "iPhone 15 Pro Max",
      "views": 5000,
      "orders": 50,
      "revenue": 150000000,
      "rating": 4.8
    },
    {
      "id": 124,
      "title": "Samsung Galaxy S24",
      "views": 4200,
      "orders": 45,
      "revenue": 125000000,
      "rating": 4.6
    }
  ],
  "top_categories": [
    {
      "category_id": 1,
      "category_name": "Điện tử",
      "orders": 200,
      "revenue": 300000000
    }
  ]
}
```

---

## 2. Thống kê lượt xem – GET /stats/views

**Mục đích:** Thống kê chi tiết lượt xem.

### Request

**Method:** `GET`  
**URL:** `{{base_url}}/stats/views`

**Headers:**
```
Accept: application/json
Authorization: Bearer {{seller_token}}
```

**Query Parameters (optional):**
```
?period=month
&listing_id=123
&date_from=2025-01-01
&date_to=2025-12-31
```

**Body:** Không cần

### Response mẫu

**Success (200):**
```json
{
  "period": "month",
  "total_views": 45000,
  "unique_visitors": 32000,
  "avg_views_per_day": 1500,
  "peak_day": {
    "date": "2025-11-15",
    "views": 2500
  },
  "views_by_day": [
    {"date": "2025-12-01", "views": 1450, "unique": 1200},
    {"date": "2025-12-02", "views": 1520, "unique": 1250}
  ],
  "views_by_hour": [
    {"hour": 0, "views": 50},
    {"hour": 1, "views": 30},
    {"hour": 9, "views": 250},
    {"hour": 10, "views": 300}
  ],
  "views_by_source": {
    "direct": 15000,
    "search": 18000,
    "social": 8000,
    "referral": 4000
  },
  "views_by_device": {
    "desktop": 20000,
    "mobile": 22000,
    "tablet": 3000
  },
  "top_listings_by_views": [
    {
      "listing_id": 123,
      "title": "iPhone 15 Pro Max",
      "views": 5000,
      "unique_visitors": 4200
    }
  ]
}
```

---

## 3. Thống kê doanh thu – GET /stats/revenue

**Mục đích:** Thống kê chi tiết doanh thu.

### Request

**Method:** `GET`  
**URL:** `{{base_url}}/stats/revenue`

**Headers:**
```
Accept: application/json
Authorization: Bearer {{seller_token}}
```

**Query Parameters (optional):**
```
?date_from=2025-01-01
&date_to=2025-12-31
&shop_id=1
&group_by=month
```

**Body:** Không cần

### Response mẫu

**Success (200):**
```json
{
  "period": {
    "from": "2025-01-01",
    "to": "2025-12-31"
  },
  "summary": {
    "total_revenue": 450000000,
    "total_orders": 350,
    "avg_order_value": 1285714,
    "total_profit": 135000000,
    "profit_margin": 30,
    "total_refunds": 5000000,
    "refund_rate": 1.11
  },
  "revenue_by_month": [
    {
      "month": "2025-01",
      "revenue": 35000000,
      "orders": 28,
      "avg_order_value": 1250000,
      "profit": 10500000
    },
    {
      "month": "2025-02",
      "revenue": 42000000,
      "orders": 32,
      "avg_order_value": 1312500,
      "profit": 12600000
    }
  ],
  "revenue_by_category": [
    {
      "category_id": 1,
      "category_name": "Điện tử",
      "revenue": 300000000,
      "orders": 200,
      "percentage": 66.67
    }
  ],
  "revenue_by_payment_method": {
    "cod": 150000000,
    "vnpay": 200000000,
    "momo": 100000000
  },
  "top_customers": [
    {
      "user_id": 5,
      "name": "Nguyễn Văn B",
      "total_orders": 15,
      "total_spent": 45000000,
      "avg_order_value": 3000000
    }
  ]
}
```

---

## 4. Thống kê quảng cáo – GET /stats/promotions

**Mục đích:** Thống kê hiệu quả quảng cáo.

### Request

**Method:** `GET`  
**URL:** `{{base_url}}/stats/promotions`

**Headers:**
```
Accept: application/json
Authorization: Bearer {{seller_token}}
```

**Query Parameters (optional):**
```
?status=active
&date_from=2025-01-01
&date_to=2025-12-31
```

**Body:** Không cần

### Response mẫu

**Success (200):**
```json
{
  "summary": {
    "total_promotions": 10,
    "active_promotions": 3,
    "total_budget": 5000000,
    "total_spent": 3500000,
    "total_impressions": 150000,
    "total_clicks": 4500,
    "total_conversions": 250,
    "avg_ctr": 3.0,
    "avg_conversion_rate": 5.56,
    "avg_cost_per_click": 777.78,
    "avg_cost_per_conversion": 14000,
    "roi": 285.71
  },
  "promotions_by_type": [
    {
      "type": "featured",
      "count": 5,
      "spent": 2000000,
      "impressions": 80000,
      "clicks": 2400,
      "conversions": 150,
      "roi": 300
    },
    {
      "type": "top_search",
      "count": 3,
      "spent": 1000000,
      "impressions": 50000,
      "clicks": 1500,
      "conversions": 80,
      "roi": 250
    }
  ],
  "performance_by_day": [
    {
      "date": "2025-12-01",
      "impressions": 5000,
      "clicks": 150,
      "conversions": 8,
      "spent": 116667
    }
  ],
  "top_performing_promotions": [
    {
      "promotion_id": 1,
      "listing_id": 123,
      "listing_title": "iPhone 15 Pro Max",
      "type": "featured",
      "impressions": 15000,
      "clicks": 450,
      "conversions": 25,
      "spent": 350000,
      "revenue": 75000000,
      "roi": 21328.57
    }
  ]
}
```

---

## Query Parameters Chi Tiết

| Parameter | Type | Description | Example |
|-----------|------|-------------|---------|
| `date_from` | date | Từ ngày | `?date_from=2025-01-01` |
| `date_to` | date | Đến ngày | `?date_to=2025-12-31` |
| `shop_id` | integer | Lọc theo shop | `?shop_id=1` |
| `listing_id` | integer | Lọc theo listing | `?listing_id=123` |
| `period` | string | day, week, month, year | `?period=month` |
| `group_by` | string | day, week, month | `?group_by=month` |
| `status` | string | Lọc theo status | `?status=active` |

---

## Metrics Explained

### CTR (Click-Through Rate)
```
CTR = (Clicks / Impressions) × 100
```

### Conversion Rate
```
Conversion Rate = (Conversions / Clicks) × 100
```

### ROI (Return on Investment)
```
ROI = ((Revenue - Cost) / Cost) × 100
```

### Profit Margin
```
Profit Margin = (Profit / Revenue) × 100
```

---

## Postman Tests Script

```javascript
// Cho GET /stats/overview
if (pm.response.code === 200) {
    var jsonData = pm.response.json();
    console.log("Total Revenue:", jsonData.summary.total_revenue);
    console.log("Total Orders:", jsonData.summary.total_orders);
    console.log("Avg Rating:", jsonData.summary.avg_rating);
}
```

---

## Test Flow trong Postman

1. **Login as Seller** → Lưu seller_token
2. **GET /stats/overview** → Xem tổng quan
3. **GET /stats/views?period=month** → Thống kê lượt xem
4. **GET /stats/revenue?group_by=month** → Thống kê doanh thu
5. **GET /stats/promotions** → Thống kê quảng cáo

---

## Dashboard Widgets

### Key Metrics Cards
- Total Revenue
- Total Orders
- Avg Order Value
- Total Customers
- Avg Rating

### Charts
- Revenue by Month (Line chart)
- Orders by Status (Pie chart)
- Views by Day (Bar chart)
- Top Listings (Table)

### Filters
- Date range picker
- Shop selector
- Category filter
- Period selector

---

## Lưu ý

- Chỉ Seller xem stats của mình
- Admin xem stats tất cả shops
- Real-time updates mỗi 5 phút
- Export to CSV/Excel
- Compare periods (vs last month)
- Growth percentage indicators
- Mobile-responsive dashboard
