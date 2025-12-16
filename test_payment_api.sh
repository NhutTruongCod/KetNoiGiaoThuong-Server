#!/bin/bash

# Script test API thanh toán

BASE_URL="http://localhost:8000/api"

echo "=== TEST THANH TOÁN API ==="
echo ""

# 1. Đăng nhập để lấy token
echo "1. Đăng nhập..."
LOGIN_RESPONSE=$(curl -s -X POST "$BASE_URL/auth/login" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@tradehub.com",
    "password": "admin123"
  }')

TOKEN=$(echo $LOGIN_RESPONSE | jq -r '.data.access_token')

if [ "$TOKEN" == "null" ] || [ -z "$TOKEN" ]; then
  echo "❌ Đăng nhập thất bại"
  echo "$LOGIN_RESPONSE" | jq '.'
  exit 1
fi

echo "✅ Đăng nhập thành công"
echo "Token: ${TOKEN:0:50}..."
echo ""

# 2. Kiểm tra ví
echo "2. Kiểm tra số dư ví..."
WALLET_RESPONSE=$(curl -s -X GET "$BASE_URL/wallet" \
  -H "Authorization: Bearer $TOKEN")

echo "$WALLET_RESPONSE" | jq '.'
BALANCE=$(echo $WALLET_RESPONSE | jq -r '.data.balance // .data.available_balance // 0')
echo "Số dư hiện tại: $BALANCE VND"
echo ""

# 3. Tạo đơn hàng test
echo "3. Tạo đơn hàng test..."
ORDER_RESPONSE=$(curl -s -X POST "$BASE_URL/orders" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "listing_id": 11,
    "quantity": 1,
    "shipping_address": {
      "name": "Test User",
      "phone": "0123456789",
      "address": "123 Test Street",
      "district": "District 1",
      "city": "Ho Chi Minh"
    },
    "note": "Test order"
  }')

echo "$ORDER_RESPONSE" | jq '.'
ORDER_ID=$(echo $ORDER_RESPONSE | jq -r '.data.id')

if [ "$ORDER_ID" == "null" ] || [ -z "$ORDER_ID" ]; then
  echo "❌ Tạo đơn hàng thất bại"
  exit 1
fi

echo "✅ Tạo đơn hàng thành công - ID: $ORDER_ID"
echo ""

# 4. Thanh toán đơn hàng
echo "4. Thanh toán đơn hàng..."
PAYMENT_RESPONSE=$(curl -s -X POST "$BASE_URL/orders/$ORDER_ID/pay" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json")

echo "$PAYMENT_RESPONSE" | jq '.'

STATUS=$(echo $PAYMENT_RESPONSE | jq -r '.status')
MESSAGE=$(echo $PAYMENT_RESPONSE | jq -r '.message')

echo ""
echo "=== KẾT QUẢ ==="
echo "Status: $STATUS"
echo "Message: $MESSAGE"

if [ "$STATUS" == "error" ]; then
  REQUIRES_DEPOSIT=$(echo $PAYMENT_RESPONSE | jq -r '.requires_deposit')
  if [ "$REQUIRES_DEPOSIT" == "true" ]; then
    echo "✅ API trả về đúng: Yêu cầu nạp tiền"
    echo "Số dư ví: $(echo $PAYMENT_RESPONSE | jq -r '.wallet_balance')"
    echo "Số tiền đơn hàng: $(echo $PAYMENT_RESPONSE | jq -r '.order_amount')"
    echo "Cần nạp thêm: $(echo $PAYMENT_RESPONSE | jq -r '.need_more')"
  else
    echo "❌ Lỗi khác: $MESSAGE"
  fi
else
  echo "✅ Thanh toán thành công"
fi
