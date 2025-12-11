# HƯỚNG DẪN TÌM KIẾM SẢN PHẨM - FRONTEND

## 📋 TỔNG QUAN

Hệ thống hiện tại đã có **3 API tìm kiếm** sản phẩm với các mục đích khác nhau:

### 1. **API Tìm kiếm Đơn giản** - `/api/listings`
- ✅ Tìm theo tên sản phẩm (title)
- ✅ Tìm theo mô tả (description)
- ✅ Lọc theo category, shop, type, status
- ✅ Phân trang
- 🎯 **Dùng cho**: Trang danh sách sản phẩm, trang shop

### 2. **API Tìm kiếm Nâng cao** - `/api/discovery/search`
- ✅ Tìm theo tên, mô tả, location
- ✅ Lọc theo giá, category, shop
- ✅ Sắp xếp theo giá, ngày đăng
- ✅ **Ưu tiên hiển thị** theo gói VIP (search_boost)
- 🎯 **Dùng cho**: Trang tìm kiếm chính, trang khám phá

### 3. **API Tìm kiếm Toàn diện** - `/api/discovery/search-all`
- ✅ Tìm cả sản phẩm (listings) VÀ gian hàng (shops)
- ✅ Ưu tiên theo gói VIP
- 🎯 **Dùng cho**: Thanh search bar toàn trang

---

## 🔍 CHI TIẾT TỪNG API

### API 1: Tìm kiếm Đơn giản - `/api/listings`

#### Request:
```http
GET /api/listings?search=iphone&category=dien-thoai&page=1&limit=20
```

#### Query Parameters:
| Tham số | Kiểu | Mô tả | Ví dụ |
|---------|------|-------|-------|
| `search` | string | Tìm theo tên hoặc mô tả | `?search=iphone` |
| `category` | string | Lọc theo danh mục | `?category=dien-thoai` |
| `shop_id` | integer | Lọc theo shop | `?shop_id=1` |
| `type` | string | Loại sản phẩm | `?type=product` |
| `status` | string | Trạng thái | `?status=published` |
| `page` | integer | Trang hiện tại | `?page=1` |
| `limit` | integer | Số item/trang | `?limit=20` |

#### Response:
```json
{
  "status": "success",
  "data": [
    {
      "id": 1,
      "title": "iPhone 15 Pro Max 256GB",
      "slug": "iphone-15-pro-max-256gb",
      "description": "Hàng chính hãng VN/A",
      "price": 29990000,
      "price_cents": 2999000000,
      "currency": "VND",
      "images": ["https://example.com/image1.jpg"],
      "main_image": "https://example.com/image1.jpg",
      "category": "dien-thoai",
      "status": "published",
      "shop": {
        "id": 1,
        "name": "Tech Store",
        "logo": "https://example.com/logo.jpg"
      },
      "views_count": 1250,
      "likes_count": 45,
      "comments_count": 12,
      "bookmarks_count": 8
    }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 20,
    "total": 150,
    "last_page": 8
  }
}
```

---

### API 2: Tìm kiếm Nâng cao - `/api/discovery/search`

#### Request:
```http
GET /api/discovery/search?query=iphone&category=dien-thoai&min_price_cents=2000000000&max_price_cents=3000000000&sort=price_asc&page=1&per_page=20
```

#### Query Parameters:
| Tham số | Kiểu | Mô tả | Ví dụ |
|---------|------|-------|-------|
| `query` | string | Từ khóa tìm kiếm | `?query=iphone` |
| `category` | string | Danh mục | `?category=dien-thoai` |
| `shop_id` | integer | ID shop | `?shop_id=1` |
| `min_price_cents` | integer | Giá tối thiểu (VND × 100) | `?min_price_cents=2000000000` (20tr) |
| `max_price_cents` | integer | Giá tối đa (VND × 100) | `?max_price_cents=3000000000` (30tr) |
| `sort` | string | Sắp xếp: `latest`, `price_asc`, `price_desc` | `?sort=price_asc` |
| `page` | integer | Trang | `?page=1` |
| `per_page` | integer | Số item/trang | `?per_page=20` |

#### Response:
```json
{
  "status": "success",
  "data": [
    {
      "id": 1,
      "title": "iPhone 15 Pro Max 256GB",
      "price_cents": 2999000000,
      "images": ["https://example.com/image1.jpg"],
      "shop": {
        "id": 1,
        "name": "Tech Store",
        "logo": "https://example.com/logo.jpg",
        "is_verified": true,
        "rating": 4.8
      },
      "search_boost": 10
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 20,
    "total": 150,
    "last_page": 8
  }
}
```

**⚠️ LƯU Ý VỀ GIÁ:**
- Backend lưu giá dạng `price_cents` (VND × 100)
- Ví dụ: 29.990.000 VND = 2.999.000.000 cents
- FE cần nhân giá với 100 khi gửi lên API
- FE nhận về `price` (đã chia 100) hoặc `price_cents` (giá gốc)

---

### API 3: Tìm kiếm Toàn diện - `/api/discovery/search-all`

#### Request:
```http
GET /api/discovery/search-all?query=iphone&type=all&per_page=10
```

#### Query Parameters:
| Tham số | Kiểu | Mô tả | Ví dụ |
|---------|------|-------|-------|
| `query` | string | **Bắt buộc** - Từ khóa | `?query=iphone` |
| `type` | string | `all`, `shops`, `listings` | `?type=all` |
| `per_page` | integer | Số item/loại (max 50) | `?per_page=10` |

#### Response:
```json
{
  "status": "success",
  "data": {
    "shops": [
      {
        "id": 1,
        "name": "iPhone Store Official",
        "slug": "iphone-store-official",
        "logo": "https://example.com/logo.jpg",
        "description": "Cửa hàng chính hãng Apple",
        "is_verified": true,
        "rating": 4.9,
        "listings_count": 125,
        "owner": {
          "id": 10,
          "full_name": "Nguyễn Văn A"
        }
      }
    ],
    "listings": [
      {
        "id": 1,
        "title": "iPhone 15 Pro Max 256GB",
        "slug": "iphone-15-pro-max-256gb",
        "price_cents": 2999000000,
        "currency": "VND",
        "images": ["https://example.com/image1.jpg"],
        "category": "dien-thoai",
        "shop": {
          "id": 1,
          "name": "Tech Store",
          "slug": "tech-store",
          "logo": "https://example.com/logo.jpg",
          "is_verified": true
        }
      }
    ]
  }
}
```

---

## 💻 CODE MẪU CHO FRONTEND

### 1. React/Next.js - Tìm kiếm đơn giản

```jsx
import { useState, useEffect } from 'react';
import axios from 'axios';

function ProductSearch() {
  const [searchTerm, setSearchTerm] = useState('');
  const [products, setProducts] = useState([]);
  const [loading, setLoading] = useState(false);
  const [pagination, setPagination] = useState({});

  const searchProducts = async (page = 1) => {
    setLoading(true);
    try {
      const response = await axios.get('/api/listings', {
        params: {
          search: searchTerm,
          page: page,
          limit: 20
        }
      });
      
      setProducts(response.data.data);
      setPagination(response.data.pagination);
    } catch (error) {
      console.error('Search error:', error);
    } finally {
      setLoading(false);
    }
  };

  // Debounce search
  useEffect(() => {
    const timer = setTimeout(() => {
      if (searchTerm) {
        searchProducts();
      }
    }, 500);

    return () => clearTimeout(timer);
  }, [searchTerm]);

  return (
    <div>
      <input
        type="text"
        placeholder="Tìm kiếm sản phẩm..."
        value={searchTerm}
        onChange={(e) => setSearchTerm(e.target.value)}
      />
      
      {loading && <p>Đang tìm kiếm...</p>}
      
      <div className="products-grid">
        {products.map(product => (
          <div key={product.id} className="product-card">
            <img src={product.main_image} alt={product.title} />
            <h3>{product.title}</h3>
            <p>{product.price.toLocaleString('vi-VN')} đ</p>
            <p>Shop: {product.shop?.name}</p>
          </div>
        ))}
      </div>
      
      {/* Pagination */}
      <div className="pagination">
        {Array.from({ length: pagination.last_page }, (_, i) => (
          <button
            key={i + 1}
            onClick={() => searchProducts(i + 1)}
            className={pagination.current_page === i + 1 ? 'active' : ''}
          >
            {i + 1}
          </button>
        ))}
      </div>
    </div>
  );
}
```



### 2. React/Next.js - Tìm kiếm nâng cao với filter

```jsx
import { useState } from 'react';
import axios from 'axios';

function AdvancedSearch() {
  const [filters, setFilters] = useState({
    query: '',
    category: '',
    shop_id: '',
    min_price: '',
    max_price: '',
    sort: 'latest'
  });
  const [products, setProducts] = useState([]);
  const [loading, setLoading] = useState(false);

  const handleSearch = async () => {
    setLoading(true);
    try {
      // Chuyển đổi giá sang cents (VND × 100)
      const params = {
        query: filters.query,
        category: filters.category,
        shop_id: filters.shop_id,
        min_price_cents: filters.min_price ? filters.min_price * 100 : undefined,
        max_price_cents: filters.max_price ? filters.max_price * 100 : undefined,
        sort: filters.sort,
        per_page: 20
      };

      const response = await axios.get('/api/discovery/search', { params });
      setProducts(response.data.data);
    } catch (error) {
      console.error('Search error:', error);
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="advanced-search">
      {/* Search Input */}
      <input
        type="text"
        placeholder="Tìm kiếm..."
        value={filters.query}
        onChange={(e) => setFilters({...filters, query: e.target.value})}
      />

      {/* Category Filter */}
      <select
        value={filters.category}
        onChange={(e) => setFilters({...filters, category: e.target.value})}
      >
        <option value="">Tất cả danh mục</option>
        <option value="dien-thoai">Điện thoại</option>
        <option value="laptop">Laptop</option>
        <option value="thoi-trang">Thời trang</option>
      </select>

      {/* Price Range */}
      <div className="price-filter">
        <input
          type="number"
          placeholder="Giá từ"
          value={filters.min_price}
          onChange={(e) => setFilters({...filters, min_price: e.target.value})}
        />
        <span>-</span>
        <input
          type="number"
          placeholder="Giá đến"
          value={filters.max_price}
          onChange={(e) => setFilters({...filters, max_price: e.target.value})}
        />
      </div>

      {/* Sort */}
      <select
        value={filters.sort}
        onChange={(e) => setFilters({...filters, sort: e.target.value})}
      >
        <option value="latest">Mới nhất</option>
        <option value="price_asc">Giá: Thấp đến cao</option>
        <option value="price_desc">Giá: Cao đến thấp</option>
      </select>

      <button onClick={handleSearch}>Tìm kiếm</button>

      {/* Results */}
      {loading ? (
        <p>Đang tìm kiếm...</p>
      ) : (
        <div className="products-grid">
          {products.map(product => (
            <div key={product.id} className="product-card">
              <img src={product.images?.[0]} alt={product.title} />
              <h3>{product.title}</h3>
              <p className="price">
                {(product.price_cents / 100).toLocaleString('vi-VN')} đ
              </p>
              {product.shop && (
                <div className="shop-info">
                  <img src={product.shop.logo} alt={product.shop.name} />
                  <span>{product.shop.name}</span>
                  {product.shop.is_verified && <span className="verified">✓</span>}
                </div>
              )}
            </div>
          ))}
        </div>
      )}
    </div>
  );
}
```

### 3. Vue.js - Tìm kiếm toàn diện

```vue
<template>
  <div class="search-all">
    <input
      v-model="searchQuery"
      @input="debouncedSearch"
      type="text"
      placeholder="Tìm kiếm sản phẩm, cửa hàng..."
    />

    <div v-if="loading">Đang tìm kiếm...</div>

    <div v-else class="search-results">
      <!-- Shops Results -->
      <div v-if="results.shops && results.shops.length > 0" class="shops-section">
        <h3>Cửa hàng</h3>
        <div class="shops-list">
          <div v-for="shop in results.shops" :key="shop.id" class="shop-card">
            <img :src="shop.logo" :alt="shop.name" />
            <div>
              <h4>{{ shop.name }}</h4>
              <p>{{ shop.description }}</p>
              <span>{{ shop.listings_count }} sản phẩm</span>
              <span v-if="shop.is_verified" class="verified">✓ Đã xác minh</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Listings Results -->
      <div v-if="results.listings && results.listings.length > 0" class="listings-section">
        <h3>Sản phẩm</h3>
        <div class="products-grid">
          <div v-for="product in results.listings" :key="product.id" class="product-card">
            <img :src="product.images[0]" :alt="product.title" />
            <h4>{{ product.title }}</h4>
            <p class="price">{{ formatPrice(product.price_cents) }}</p>
            <p class="shop">{{ product.shop?.name }}</p>
          </div>
        </div>
      </div>

      <!-- No Results -->
      <div v-if="!results.shops?.length && !results.listings?.length && searchQuery">
        <p>Không tìm thấy kết quả cho "{{ searchQuery }}"</p>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import { debounce } from 'lodash';

export default {
  data() {
    return {
      searchQuery: '',
      results: {
        shops: [],
        listings: []
      },
      loading: false
    };
  },
  methods: {
    async searchAll() {
      if (!this.searchQuery) {
        this.results = { shops: [], listings: [] };
        return;
      }

      this.loading = true;
      try {
        const response = await axios.get('/api/discovery/search-all', {
          params: {
            query: this.searchQuery,
            type: 'all',
            per_page: 10
          }
        });
        
        this.results = response.data.data;
      } catch (error) {
        console.error('Search error:', error);
      } finally {
        this.loading = false;
      }
    },
    
    formatPrice(priceCents) {
      return (priceCents / 100).toLocaleString('vi-VN') + ' đ';
    }
  },
  created() {
    // Debounce search để tránh gọi API quá nhiều
    this.debouncedSearch = debounce(this.searchAll, 500);
  }
};
</script>
```

### 4. Vanilla JavaScript - Fetch API

```javascript
// Tìm kiếm đơn giản
async function searchProducts(searchTerm, page = 1) {
  try {
    const params = new URLSearchParams({
      search: searchTerm,
      page: page,
      limit: 20
    });

    const response = await fetch(`/api/listings?${params}`);
    const data = await response.json();

    if (data.status === 'success') {
      displayProducts(data.data);
      displayPagination(data.pagination);
    }
  } catch (error) {
    console.error('Search error:', error);
  }
}

// Tìm kiếm nâng cao
async function advancedSearch(filters) {
  try {
    const params = new URLSearchParams({
      query: filters.query || '',
      category: filters.category || '',
      min_price_cents: filters.minPrice ? filters.minPrice * 100 : '',
      max_price_cents: filters.maxPrice ? filters.maxPrice * 100 : '',
      sort: filters.sort || 'latest',
      per_page: 20
    });

    const response = await fetch(`/api/discovery/search?${params}`);
    const data = await response.json();

    if (data.status === 'success') {
      displayProducts(data.data);
    }
  } catch (error) {
    console.error('Search error:', error);
  }
}

// Display functions
function displayProducts(products) {
  const container = document.getElementById('products-container');
  container.innerHTML = products.map(product => `
    <div class="product-card">
      <img src="${product.main_image || product.images[0]}" alt="${product.title}">
      <h3>${product.title}</h3>
      <p class="price">${(product.price_cents / 100).toLocaleString('vi-VN')} đ</p>
      <p class="shop">${product.shop?.name || ''}</p>
    </div>
  `).join('');
}

function displayPagination(pagination) {
  const container = document.getElementById('pagination-container');
  const pages = [];
  
  for (let i = 1; i <= pagination.last_page; i++) {
    pages.push(`
      <button 
        onclick="searchProducts('${currentSearchTerm}', ${i})"
        class="${pagination.current_page === i ? 'active' : ''}"
      >
        ${i}
      </button>
    `);
  }
  
  container.innerHTML = pages.join('');
}

// Debounce helper
function debounce(func, wait) {
  let timeout;
  return function executedFunction(...args) {
    const later = () => {
      clearTimeout(timeout);
      func(...args);
    };
    clearTimeout(timeout);
    timeout = setTimeout(later, wait);
  };
}

// Setup search input with debounce
const searchInput = document.getElementById('search-input');
const debouncedSearch = debounce((value) => {
  searchProducts(value);
}, 500);

searchInput.addEventListener('input', (e) => {
  debouncedSearch(e.target.value);
});
```

---

## 🎨 UI/UX RECOMMENDATIONS

### 1. Search Bar Component
```jsx
// Thanh tìm kiếm với autocomplete
<div className="search-bar">
  <input
    type="text"
    placeholder="Tìm kiếm sản phẩm, cửa hàng..."
    value={searchTerm}
    onChange={handleSearch}
  />
  <button>
    <SearchIcon />
  </button>
  
  {/* Autocomplete dropdown */}
  {showSuggestions && (
    <div className="suggestions-dropdown">
      <div className="recent-searches">
        <h4>Tìm kiếm gần đây</h4>
        {recentSearches.map(term => (
          <div onClick={() => setSearchTerm(term)}>{term}</div>
        ))}
      </div>
      <div className="popular-searches">
        <h4>Tìm kiếm phổ biến</h4>
        {popularSearches.map(term => (
          <div onClick={() => setSearchTerm(term)}>{term}</div>
        ))}
      </div>
    </div>
  )}
</div>
```

### 2. Filters Sidebar
```jsx
<div className="filters-sidebar">
  {/* Category Filter */}
  <div className="filter-group">
    <h3>Danh mục</h3>
    <div className="category-list">
      {categories.map(cat => (
        <label key={cat.id}>
          <input
            type="checkbox"
            checked={selectedCategories.includes(cat.id)}
            onChange={() => toggleCategory(cat.id)}
          />
          {cat.name} ({cat.count})
        </label>
      ))}
    </div>
  </div>

  {/* Price Range Filter */}
  <div className="filter-group">
    <h3>Khoảng giá</h3>
    <div className="price-range">
      <input
        type="number"
        placeholder="Từ"
        value={minPrice}
        onChange={(e) => setMinPrice(e.target.value)}
      />
      <span>-</span>
      <input
        type="number"
        placeholder="Đến"
        value={maxPrice}
        onChange={(e) => setMaxPrice(e.target.value)}
      />
    </div>
    {/* Or use slider */}
    <input
      type="range"
      min="0"
      max="100000000"
      value={priceRange}
      onChange={handlePriceChange}
    />
  </div>

  {/* Shop Filter */}
  <div className="filter-group">
    <h3>Cửa hàng</h3>
    <select value={selectedShop} onChange={(e) => setSelectedShop(e.target.value)}>
      <option value="">Tất cả cửa hàng</option>
      {shops.map(shop => (
        <option key={shop.id} value={shop.id}>{shop.name}</option>
      ))}
    </select>
  </div>

  <button onClick={applyFilters}>Áp dụng</button>
  <button onClick={clearFilters}>Xóa bộ lọc</button>
</div>
```

### 3. Sort Dropdown
```jsx
<select value={sortBy} onChange={(e) => setSortBy(e.target.value)}>
  <option value="latest">Mới nhất</option>
  <option value="price_asc">Giá: Thấp đến cao</option>
  <option value="price_desc">Giá: Cao đến thấp</option>
</select>
```

### 4. Product Card
```jsx
<div className="product-card">
  <div className="product-image">
    <img src={product.main_image} alt={product.title} />
    {product.shop?.is_verified && (
      <span className="verified-badge">✓ Xác minh</span>
    )}
  </div>
  
  <div className="product-info">
    <h3 className="product-title">{product.title}</h3>
    <p className="product-price">
      {product.price.toLocaleString('vi-VN')} đ
    </p>
    
    <div className="shop-info">
      <img src={product.shop?.logo} alt={product.shop?.name} />
      <span>{product.shop?.name}</span>
    </div>
    
    <div className="product-stats">
      <span>👁 {product.views_count}</span>
      <span>❤️ {product.likes_count}</span>
      <span>💬 {product.comments_count}</span>
    </div>
  </div>
</div>
```

---

## ⚡ PERFORMANCE TIPS

### 1. Debounce Search Input
```javascript
// Tránh gọi API quá nhiều khi user đang gõ
const debouncedSearch = debounce((value) => {
  searchProducts(value);
}, 500); // Đợi 500ms sau khi user ngừng gõ
```

### 2. Cache Results
```javascript
const searchCache = new Map();

async function searchWithCache(query) {
  if (searchCache.has(query)) {
    return searchCache.get(query);
  }
  
  const results = await searchProducts(query);
  searchCache.set(query, results);
  return results;
}
```

### 3. Lazy Loading Images
```jsx
<img
  src={product.main_image}
  loading="lazy"
  alt={product.title}
/>
```

### 4. Pagination vs Infinite Scroll
```jsx
// Infinite scroll
const handleScroll = () => {
  if (window.innerHeight + window.scrollY >= document.body.offsetHeight - 500) {
    loadMoreProducts();
  }
};

useEffect(() => {
  window.addEventListener('scroll', handleScroll);
  return () => window.removeEventListener('scroll', handleScroll);
}, []);
```

---

## 🐛 XỬ LÝ LỖI

### 1. No Results Found
```jsx
{products.length === 0 && searchTerm && (
  <div className="no-results">
    <h3>Không tìm thấy kết quả cho "{searchTerm}"</h3>
    <p>Thử tìm kiếm với từ khóa khác hoặc:</p>
    <ul>
      <li>Kiểm tra chính tả</li>
      <li>Sử dụng từ khóa chung hơn</li>
      <li>Thử các bộ lọc khác</li>
    </ul>
  </div>
)}
```

### 2. API Error Handling
```javascript
async function searchProducts(query) {
  try {
    const response = await axios.get('/api/listings', {
      params: { search: query }
    });
    return response.data;
  } catch (error) {
    if (error.response) {
      // Server responded with error
      console.error('Server error:', error.response.data);
      showError('Lỗi tìm kiếm. Vui lòng thử lại.');
    } else if (error.request) {
      // No response from server
      console.error('Network error:', error.request);
      showError('Lỗi kết nối. Kiểm tra internet của bạn.');
    } else {
      console.error('Error:', error.message);
      showError('Đã xảy ra lỗi. Vui lòng thử lại.');
    }
  }
}
```

### 3. Loading States
```jsx
{loading ? (
  <div className="loading">
    <Spinner />
    <p>Đang tìm kiếm...</p>
  </div>
) : (
  <ProductsList products={products} />
)}
```

---

## 📱 RESPONSIVE DESIGN

### Mobile Search
```css
@media (max-width: 768px) {
  .search-bar {
    width: 100%;
    padding: 10px;
  }
  
  .filters-sidebar {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    transform: translateY(100%);
    transition: transform 0.3s;
  }
  
  .filters-sidebar.open {
    transform: translateY(0);
  }
  
  .products-grid {
    grid-template-columns: repeat(2, 1fr);
    gap: 10px;
  }
}
```

---

## 🔐 BẢO MẬT

### 1. Sanitize Input
```javascript
function sanitizeSearchQuery(query) {
  // Remove special characters that could cause issues
  return query.replace(/[<>]/g, '').trim();
}
```

### 2. Rate Limiting
```javascript
// Giới hạn số lần search trong 1 phút
const searchRateLimit = {
  count: 0,
  resetTime: Date.now() + 60000
};

function checkRateLimit() {
  if (Date.now() > searchRateLimit.resetTime) {
    searchRateLimit.count = 0;
    searchRateLimit.resetTime = Date.now() + 60000;
  }
  
  if (searchRateLimit.count >= 30) {
    throw new Error('Quá nhiều yêu cầu. Vui lòng thử lại sau.');
  }
  
  searchRateLimit.count++;
}
```

---

## 📊 ANALYTICS

### Track Search Events
```javascript
// Google Analytics
function trackSearch(query, resultsCount) {
  gtag('event', 'search', {
    search_term: query,
    results_count: resultsCount
  });
}

// Custom analytics
function logSearch(query, filters, resultsCount) {
  axios.post('/api/analytics/search', {
    query,
    filters,
    results_count: resultsCount,
    timestamp: new Date().toISOString()
  });
}
```

---

## ✅ CHECKLIST TRIỂN KHAI

- [ ] Implement search bar với debounce
- [ ] Thêm filters sidebar (category, price, shop)
- [ ] Implement sort dropdown
- [ ] Hiển thị kết quả dạng grid/list
- [ ] Thêm pagination hoặc infinite scroll
- [ ] Handle loading states
- [ ] Handle error states
- [ ] Handle no results state
- [ ] Implement search history
- [ ] Add autocomplete/suggestions
- [ ] Responsive design cho mobile
- [ ] Optimize images (lazy loading)
- [ ] Cache search results
- [ ] Track analytics
- [ ] Test với nhiều từ khóa khác nhau
- [ ] Test với filters khác nhau
- [ ] Test performance với nhiều kết quả

