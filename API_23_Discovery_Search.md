# API Discovery & Search - Tìm kiếm

**Base URL:** `{{base_url}}` = `http://localhost:8000/api`

---

## 1. Tìm kiếm toàn hệ thống – GET /discovery/search

**Mục đích:** Tìm kiếm listings, shops, auctions (public).

### Request

**Method:** `GET`  
**URL:** `{{base_url}}/discovery/search`

**Headers:**
```
Accept: application/json
```

**Query Parameters:**
```
?q=iphone
&type=listings
&category_id=1
&min_price=10000000
&max_price=30000000
&location=ho-chi-minh
&sort=relevance
&page=1
&per_page=20
```

**Body:** Không cần

### Response mẫu

**Success (200):**
```json
{
  "query": "iphone",
  "type": "listings",
  "data": [
    {
      "id": 123,
      "type": "listing",
      "title": "iPhone 15 Pro Max 256GB",
      "slug": "iphone-15-pro-max-256gb",
      "description": "Hàng chính hãng, mới 100%",
      "price": 29990000,
      "images": [
        "https://example.com/image1.jpg"
      ],
      "shop": {
        "id": 1,
        "name": "Tech Store",
        "rating": 4.5
      },
      "category": {
        "id": 1,
        "name": "Điện tử"
      },
      "location": {
        "city": "Ho Chi Minh",
        "district": "District 1"
      },
      "views_count": 1250,
      "likes_count": 45,
      "status": "active",
      "relevance_score": 95.5,
      "created_at": "2025-11-01T10:00:00.000000Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 20,
    "total": 150,
    "last_page": 8
  },
  "filters": {
    "categories": [
      {"id": 1, "name": "Điện tử", "count": 120},
      {"id": 2, "name": "Thời trang", "count": 30}
    ],
    "price_ranges": [
      {"min": 0, "max": 10000000, "count": 50},
      {"min": 10000000, "max": 20000000, "count": 60},
      {"min": 20000000, "max": 30000000, "count": 40}
    ],
    "locations": [
      {"city": "Ho Chi Minh", "count": 80},
      {"city": "Ha Noi", "count": 50}
    ]
  },
  "suggestions": [
    "iphone 15 pro max",
    "iphone 15 plus",
    "iphone 14 pro max"
  ]
}
```

---

## 2. Tìm kiếm listings gần đây – GET /listings (with search)

**Mục đích:** Tìm kiếm listings với nhiều filters.

### Request

**Method:** `GET`  
**URL:** `{{base_url}}/listings`

**Headers:**
```
Accept: application/json
```

**Query Parameters:**
```
?search=iphone
&category_id=1
&shop_id=1
&type=product
&min_price=10000000
&max_price=30000000
&status=active
&location=ho-chi-minh
&is_featured=true
&sort=price_asc
&page=1
&per_page=20
```

**Body:** Không cần

### Response mẫu

Xem API_14_Listings.md

---

## 3. Tìm kiếm listings gần vị trí – GET /discovery/nearby

**Mục đích:** Tìm listings gần vị trí hiện tại.

### Request

**Method:** `GET`  
**URL:** `{{base_url}}/discovery/nearby`

**Headers:**
```
Accept: application/json
```

**Query Parameters:**
```
?lat=10.8231
&lng=106.6297
&radius=10
&category_id=1
&page=1
&per_page=20
```

**Body:** Không cần

### Response mẫu

**Success (200):**
```json
{
  "location": {
    "latitude": 10.8231,
    "longitude": 106.6297,
    "radius": 10,
    "unit": "km"
  },
  "data": [
    {
      "id": 123,
      "title": "iPhone 15 Pro Max 256GB",
      "price": 29990000,
      "images": ["https://example.com/image1.jpg"],
      "shop": {
        "id": 1,
        "name": "Tech Store",
        "address": "123 Nguyễn Huệ, Q1, TP.HCM"
      },
      "location": {
        "latitude": 10.8235,
        "longitude": 106.6300,
        "distance": 0.5,
        "unit": "km"
      },
      "created_at": "2025-11-01T10:00:00.000000Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 20,
    "total": 25
  }
}
```

---

## Query Parameters Chi Tiết

### Search Parameters

| Parameter | Type | Description | Example |
|-----------|------|-------------|---------|
| `q` | string | Từ khóa tìm kiếm | `?q=iphone` |
| `type` | string | listings, shops, auctions, all | `?type=listings` |
| `search` | string | Tìm theo title, description | `?search=iphone` |

### Filter Parameters

| Parameter | Type | Description | Example |
|-----------|------|-------------|---------|
| `category_id` | integer | Lọc theo danh mục | `?category_id=1` |
| `shop_id` | integer | Lọc theo shop | `?shop_id=1` |
| `min_price` | integer | Giá tối thiểu | `?min_price=10000000` |
| `max_price` | integer | Giá tối đa | `?max_price=30000000` |
| `location` | string | Vị trí | `?location=ho-chi-minh` |
| `is_featured` | boolean | Tin nổi bật | `?is_featured=true` |
| `status` | string | active, inactive | `?status=active` |

### Location Parameters

| Parameter | Type | Description | Example |
|-----------|------|-------------|---------|
| `lat` | float | Latitude | `?lat=10.8231` |
| `lng` | float | Longitude | `?lng=106.6297` |
| `radius` | integer | Bán kính (km) | `?radius=10` |

### Sort Parameters

| Parameter | Type | Description | Example |
|-----------|------|-------------|---------|
| `sort` | string | Sắp xếp | `?sort=price_asc` |

**Sort Options:**
- `relevance` - Liên quan nhất (default)
- `newest` - Mới nhất
- `oldest` - Cũ nhất
- `price_asc` - Giá tăng dần
- `price_desc` - Giá giảm dần
- `popular` - Phổ biến nhất (views + likes)
- `best_selling` - Bán chạy nhất
- `rating` - Đánh giá cao nhất
- `distance` - Gần nhất (với nearby search)

### Pagination Parameters

| Parameter | Type | Description | Example |
|-----------|------|-------------|---------|
| `page` | integer | Số trang | `?page=1` |
| `per_page` | integer | Số item/trang (max 100) | `?per_page=20` |

---

## Search Types

### 1. Listings Search
```
GET /discovery/search?q=iphone&type=listings
```

### 2. Shops Search
```
GET /discovery/search?q=tech+store&type=shops
```

### 3. Auctions Search
```
GET /discovery/search?q=iphone&type=auctions
```

### 4. All Search
```
GET /discovery/search?q=iphone&type=all
```

---

## Advanced Search Features

### 1. Autocomplete/Suggestions
```json
{
  "suggestions": [
    "iphone 15 pro max",
    "iphone 15 plus",
    "iphone 14 pro max",
    "iphone 13"
  ]
}
```

### 2. Did You Mean
```json
{
  "query": "ipone",
  "did_you_mean": "iphone",
  "corrected_query": "iphone"
}
```

### 3. Filters Summary
```json
{
  "filters": {
    "categories": [
      {"id": 1, "name": "Điện tử", "count": 120}
    ],
    "price_ranges": [
      {"min": 0, "max": 10000000, "count": 50}
    ],
    "locations": [
      {"city": "Ho Chi Minh", "count": 80}
    ]
  }
}
```

### 4. Relevance Score
```json
{
  "relevance_score": 95.5,
  "match_fields": ["title", "description", "tags"]
}
```

---

## Search Algorithm

### Relevance Scoring
```
Score = (Title Match × 3) + (Description Match × 2) + (Tags Match × 1)
```

### Boosting Factors
- Featured listings: +20%
- High rating (4.5+): +10%
- Recent (< 7 days): +5%
- Popular (high views): +5%

---

## Postman Tests Script

```javascript
// Cho GET /discovery/search
if (pm.response.code === 200) {
    var jsonData = pm.response.json();
    console.log("Total results:", jsonData.meta.total);
    console.log("Query:", jsonData.query);
    
    if (jsonData.suggestions && jsonData.suggestions.length > 0) {
        console.log("Suggestions:", jsonData.suggestions);
    }
}
```

---

## Test Flow trong Postman

1. **GET /discovery/search?q=iphone** → Tìm kiếm cơ bản
2. **GET /discovery/search?q=iphone&type=listings&category_id=1** → Tìm với filters
3. **GET /discovery/search?q=iphone&min_price=20000000&max_price=30000000** → Lọc giá
4. **GET /discovery/search?q=iphone&location=ho-chi-minh** → Lọc vị trí
5. **GET /discovery/search?q=iphone&sort=price_asc** → Sắp xếp
6. **GET /discovery/nearby?lat=10.8231&lng=106.6297&radius=10** → Tìm gần đây

---

## UI Components

### Search Bar
```html
<input type="text" placeholder="Tìm kiếm sản phẩm, dịch vụ..." />
<button>Tìm kiếm</button>
```

### Filters Sidebar
- Categories (checkboxes)
- Price range (slider)
- Location (dropdown)
- Rating (stars)
- Condition (new/used)

### Sort Dropdown
- Liên quan nhất
- Mới nhất
- Giá: Thấp đến cao
- Giá: Cao đến thấp
- Phổ biến nhất

### Results Grid/List
- Thumbnail image
- Title
- Price
- Shop name
- Rating
- Location

---

## Lưu ý

- Search không cần auth
- Hỗ trợ tiếng Việt có dấu
- Fuzzy search (tìm gần đúng)
- Autocomplete real-time
- Search history (logged in users)
- Popular searches
- Related searches
- Filters persist across pages
- Mobile-friendly UI
- Fast response time (< 200ms)
