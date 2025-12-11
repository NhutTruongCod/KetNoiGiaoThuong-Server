# API Moderation - Kiểm duyệt và báo cáo vi phạm

**Base URL:** `{{base_url}}` = `http://localhost:8000/api`

---

## 1. Gửi báo cáo vi phạm – POST /moderation/report

**Mục đích:** User báo cáo listing, shop, user vi phạm.

### Request

**Method:** `POST`  
**URL:** `{{base_url}}/moderation/report`

**Headers:**
```
Content-Type: application/json
Accept: application/json
Authorization: Bearer {{token}}
```

**Body → raw → JSON:**
```json
{
  "reportable_type": "listing",
  "reportable_id": 123,
  "reason": "spam",
  "description": "Tin đăng lừa đảo, sản phẩm giả mạo",
  "evidence_images": [
    "https://example.com/evidence1.jpg",
    "https://example.com/evidence2.jpg"
  ]
}
```

**Required fields:**
- `reportable_type` - Loại: `listing`, `shop`, `user`, `review`
- `reportable_id` - ID của đối tượng bị báo cáo
- `reason` - Lý do: `spam`, `fraud`, `inappropriate`, `fake`, `other`
- `description` - Mô tả chi tiết

**Optional fields:**
- `evidence_images` - Ảnh chứng cứ (array URLs)

**Reason types:**
- `spam` - Spam, quảng cáo rác
- `fraud` - Lừa đảo
- `inappropriate` - Nội dung không phù hợp
- `fake` - Hàng giả, hàng nhái
- `copyright` - Vi phạm bản quyền
- `other` - Lý do khác

### Response mẫu

**Success (201):**
```json
{
  "message": "Report submitted successfully. We will review it soon.",
  "data": {
    "id": 1,
    "reporter_id": 5,
    "reportable_type": "listing",
    "reportable_id": 123,
    "reason": "spam",
    "description": "Tin đăng lừa đảo, sản phẩm giả mạo",
    "status": "pending",
    "created_at": "2025-12-01T10:00:00.000000Z"
  }
}
```

**Error - Đã báo cáo rồi (400):**
```json
{
  "message": "You have already reported this item"
}
```

---

## 2. Xem báo cáo đã gửi – GET /moderation/my-reports

**Mục đích:** User xem các báo cáo mình đã gửi.

### Request

**Method:** `GET`  
**URL:** `{{base_url}}/moderation/my-reports`

**Headers:**
```
Accept: application/json
Authorization: Bearer {{token}}
```

**Query Parameters (optional):**
```
?page=1
&per_page=20
&status=pending
&reportable_type=listing
```

**Body:** Không cần

### Response mẫu

**Success (200):**
```json
{
  "data": [
    {
      "id": 1,
      "reportable_type": "listing",
      "reportable_id": 123,
      "reportable": {
        "id": 123,
        "title": "iPhone 15 Pro Max",
        "status": "active"
      },
      "reason": "spam",
      "description": "Tin đăng lừa đảo, sản phẩm giả mạo",
      "status": "pending",
      "admin_notes": null,
      "resolved_at": null,
      "created_at": "2025-12-01T10:00:00.000000Z"
    },
    {
      "id": 2,
      "reportable_type": "shop",
      "reportable_id": 5,
      "reportable": {
        "id": 5,
        "name": "Fake Store",
        "is_active": false
      },
      "reason": "fraud",
      "description": "Gian hàng lừa đảo",
      "status": "resolved",
      "admin_notes": "Đã xử lý, shop bị khóa",
      "resolved_at": "2025-12-01T15:00:00.000000Z",
      "created_at": "2025-11-30T10:00:00.000000Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 20,
    "total": 5,
    "last_page": 1
  }
}
```

---

## ADMIN APIs

## 3. Danh sách tất cả báo cáo (Admin) – GET /moderation/reports

**Mục đích:** Admin xem tất cả báo cáo vi phạm.

### Request

**Method:** `GET`  
**URL:** `{{base_url}}/moderation/reports`

**Headers:**
```
Accept: application/json
Authorization: Bearer {{admin_token}}
```

**Query Parameters (optional):**
```
?page=1
&per_page=20
&status=pending
&reportable_type=listing
&reason=spam
&date_from=2025-01-01
&date_to=2025-12-31
&sort=created_at
&order=desc
```

**Body:** Không cần

### Response mẫu

**Success (200):**
```json
{
  "data": [
    {
      "id": 1,
      "reporter_id": 5,
      "reporter": {
        "id": 5,
        "name": "Nguyễn Văn B",
        "email": "buyer1@tradehub.com"
      },
      "reportable_type": "listing",
      "reportable_id": 123,
      "reportable": {
        "id": 123,
        "title": "iPhone 15 Pro Max",
        "shop_id": 1,
        "status": "active"
      },
      "reason": "spam",
      "description": "Tin đăng lừa đảo, sản phẩm giả mạo",
      "evidence_images": [
        "https://example.com/evidence1.jpg"
      ],
      "status": "pending",
      "admin_notes": null,
      "resolved_by": null,
      "resolved_at": null,
      "created_at": "2025-12-01T10:00:00.000000Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 20,
    "total": 50,
    "last_page": 3
  },
  "summary": {
    "total_reports": 50,
    "pending": 20,
    "resolved": 25,
    "dismissed": 5
  }
}
```

---

## 4. Chi tiết báo cáo (Admin) – GET /moderation/reports/{id}

**Mục đích:** Admin xem chi tiết một báo cáo.

### Request

**Method:** `GET`  
**URL:** `{{base_url}}/moderation/reports/1`

**Headers:**
```
Accept: application/json
Authorization: Bearer {{admin_token}}
```

**Body:** Không cần

### Response mẫu

**Success (200):**
```json
{
  "id": 1,
  "reporter_id": 5,
  "reporter": {
    "id": 5,
    "name": "Nguyễn Văn B",
    "email": "buyer1@tradehub.com",
    "phone": "0987654321"
  },
  "reportable_type": "listing",
  "reportable_id": 123,
  "reportable": {
    "id": 123,
    "title": "iPhone 15 Pro Max",
    "description": "Hàng chính hãng...",
    "price": 29990000,
    "shop_id": 1,
    "shop": {
      "id": 1,
      "name": "Tech Store",
      "owner_id": 2
    },
    "status": "active",
    "created_at": "2025-11-01T10:00:00.000000Z"
  },
  "reason": "spam",
  "description": "Tin đăng lừa đảo, sản phẩm giả mạo. Tôi đã mua nhưng nhận được hàng fake.",
  "evidence_images": [
    "https://example.com/evidence1.jpg",
    "https://example.com/evidence2.jpg"
  ],
  "status": "pending",
  "admin_notes": null,
  "resolved_by": null,
  "resolved_at": null,
  "created_at": "2025-12-01T10:00:00.000000Z",
  "updated_at": "2025-12-01T10:00:00.000000Z"
}
```

---

## 5. Xử lý báo cáo (Admin) – PUT /moderation/reports/{id}/resolve

**Mục đích:** Admin xử lý và giải quyết báo cáo.

### Request

**Method:** `PUT`  
**URL:** `{{base_url}}/moderation/reports/1/resolve`

**Headers:**
```
Content-Type: application/json
Accept: application/json
Authorization: Bearer {{admin_token}}
```

**Body → raw → JSON:**
```json
{
  "resolution": "resolved",
  "admin_notes": "Đã xác minh báo cáo đúng. Tin đăng đã bị xóa và shop bị cảnh cáo.",
  "action_taken": "listing_deleted"
}
```

**Required fields:**
- `resolution` - Kết quả: `resolved`, `dismissed`
- `admin_notes` - Ghi chú của admin

**Optional fields:**
- `action_taken` - Hành động: `listing_deleted`, `shop_suspended`, `user_banned`, `warning_sent`, `no_action`

**Resolution types:**
- `resolved` - Đã xử lý (báo cáo đúng)
- `dismissed` - Bỏ qua (báo cáo sai/không đủ cơ sở)

**Action taken types:**
- `listing_deleted` - Xóa tin đăng
- `shop_suspended` - Tạm khóa shop
- `user_banned` - Khóa tài khoản
- `warning_sent` - Gửi cảnh cáo
- `no_action` - Không hành động

### Response mẫu

**Success (200):**
```json
{
  "message": "Report resolved successfully",
  "data": {
    "id": 1,
    "status": "resolved",
    "resolution": "resolved",
    "admin_notes": "Đã xác minh báo cáo đúng. Tin đăng đã bị xóa và shop bị cảnh cáo.",
    "action_taken": "listing_deleted",
    "resolved_by": 1,
    "resolved_at": "2025-12-01T15:00:00.000000Z"
  }
}
```

---

## 6. Xóa báo cáo (Admin) – DELETE /moderation/reports/{id}

**Mục đích:** Admin xóa báo cáo (spam reports).

### Request

**Method:** `DELETE`  
**URL:** `{{base_url}}/moderation/reports/1`

**Headers:**
```
Accept: application/json
Authorization: Bearer {{admin_token}}
```

**Body:** Không cần

### Response mẫu

**Success (200):**
```json
{
  "message": "Report deleted successfully"
}
```

**Lưu ý:** Chỉ nên xóa các báo cáo spam/không hợp lệ. Nên dùng "dismiss" thay vì xóa.

---

## Query Parameters Chi Tiết (Admin)

| Parameter | Type | Description | Example |
|-----------|------|-------------|---------|
| `status` | string | pending, resolved, dismissed | `?status=pending` |
| `reportable_type` | string | listing, shop, user, review | `?reportable_type=listing` |
| `reason` | string | spam, fraud, inappropriate, etc | `?reason=spam` |
| `date_from` | date | Từ ngày | `?date_from=2025-01-01` |
| `date_to` | date | Đến ngày | `?date_to=2025-12-31` |
| `page` | integer | Số trang | `?page=1` |
| `per_page` | integer | Số item/trang | `?per_page=20` |
| `sort` | string | created_at, updated_at | `?sort=created_at` |
| `order` | string | asc, desc | `?order=desc` |

---

## Postman Tests Script

### Lưu report ID

```javascript
// Cho POST /moderation/report
if (pm.response.code === 201) {
    var jsonData = pm.response.json();
    if (jsonData.data && jsonData.data.id) {
        pm.environment.set("report_id", jsonData.data.id);
        console.log("Report ID saved:", jsonData.data.id);
    }
}
```

---

## Test Flow trong Postman

### User Flow:
1. **Login as Buyer** → Lưu token
2. **POST /moderation/report** → Báo cáo listing vi phạm
3. **GET /moderation/my-reports** → Xem báo cáo đã gửi

### Admin Flow:
1. **Login as Admin** → Lưu admin_token
2. **GET /moderation/reports?status=pending** → Xem báo cáo chờ xử lý
3. **GET /moderation/reports/1** → Xem chi tiết báo cáo
4. **PUT /moderation/reports/1/resolve** → Xử lý báo cáo
5. **DELETE /moderation/reports/1** → Xóa báo cáo spam (nếu cần)

---

## Lưu ý

- User không thể báo cáo cùng một item nhiều lần
- Admin nên xem chi tiết trước khi xử lý
- Nên ghi rõ action_taken để tracking
- Evidence images giúp admin xác minh nhanh hơn
- Sau khi resolve, user sẽ nhận notification
