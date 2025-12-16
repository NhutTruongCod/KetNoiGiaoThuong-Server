# HƯỚNG DẪN FRONTEND: XỬ LÝ KHI KHÔNG ĐỦ TIỀN TRONG VÍ

## 📋 TỔNG QUAN

Khi user không đủ tiền trong ví để thanh toán, Backend sẽ trả về **HTTP 422** với thông tin chi tiết. Frontend cần:
1. Hiển thị thông báo rõ ràng
2. Cho phép user chuyển đến trang nạp tiền
3. Hiển thị số tiền cần nạp thêm

---

## 🔄 FLOW XỬ LÝ

```
User click "Đặt hàng"
        ↓
    Gọi API tạo đơn hàng
        ↓
    Gọi API thanh toán
        ↓
┌───────────────────────────────────┐
│  Kiểm tra response status code    │
└───────────────────────────────────┘
        ↓
   ┌────┴────┐
   │         │
 200 OK    422 Error
   │         │
   ↓         ↓
Thành công  Kiểm tra requires_deposit
   │         │
   │    ┌────┴────┐
   │    │         │
   │  true      false
   │    │         │
   │    ↓         ↓
   │  Hiển thị   Hiển thị
   │  Dialog     lỗi khác
   │  Nạp tiền
   │    │
   │    ↓
   │  User click
   │  "Nạp tiền"
   │    │
   │    ↓
   │  Chuyển đến
   │  /wallet/deposit
   │    │
   └────┴────→ Hoàn thành
```

---

## 📡 API RESPONSE

### Khi không đủ tiền (HTTP 422):

```json
{
  "status": "error",
  "message": "So du vi khong du. Vui long nap them tien.",
  "wallet_balance": 0,
  "order_amount": 123145123,
  "need_more": 123145123,
  "requires_deposit": true
}
```

### Giải thích các field:
| Field | Mô tả |
|-------|-------|
| `wallet_balance` | Số dư hiện tại trong ví (VND) |
| `order_amount` | Tổng tiền đơn hàng cần thanh toán (VND) |
| `need_more` | Số tiền cần nạp thêm (VND) |
| `requires_deposit` | `true` = cần nạp tiền, dùng để phân biệt với lỗi khác |

---

## 💻 CODE MẪU FRONTEND

### 1. React/Next.js

```jsx
// CheckoutPage.jsx
import { useState } from 'react';
import { useRouter } from 'next/router';
import axios from 'axios';

const CheckoutPage = () => {
  const router = useRouter();
  const [showDepositModal, setShowDepositModal] = useState(false);
  const [depositInfo, setDepositInfo] = useState(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);

  const handleCheckout = async (orderData) => {
    setLoading(true);
    setError(null);

    try {
      // Bước 1: Tạo đơn hàng
      const orderResponse = await axios.post('/api/orders', orderData);
      const orderId = orderResponse.data.data.id;

      // Bước 2: Thanh toán
      const payResponse = await axios.post(`/api/orders/${orderId}/pay`);
      
      if (payResponse.data.status === 'success') {
        // Thanh toán thành công
        router.push(`/orders/${orderId}/success`);
      }
    } catch (err) {
      if (err.response?.status === 422) {
        const data = err.response.data;
        
        // Kiểm tra nếu là lỗi không đủ tiền
        if (data.requires_deposit) {
          setDepositInfo({
            currentBalance: data.wallet_balance,
            orderAmount: data.order_amount,
            needMore: data.need_more,
            message: data.message
          });
          setShowDepositModal(true);
        } else {
          // Lỗi validation khác (shop chưa xác minh, etc.)
          setError(data.message);
        }
      } else {
        setError('Có lỗi xảy ra. Vui lòng thử lại.');
      }
    } finally {
      setLoading(false);
    }
  };

  const handleGoToDeposit = () => {
    // Lưu thông tin để sau khi nạp tiền quay lại
    sessionStorage.setItem('pendingCheckout', JSON.stringify({
      returnUrl: window.location.href,
      needMore: depositInfo.needMore
    }));
    
    // Chuyển đến trang nạp tiền với số tiền gợi ý
    router.push(`/wallet/deposit?amount=${depositInfo.needMore}`);
  };

  return (
    <div>
      {/* Form checkout */}
      <button onClick={() => handleCheckout(orderData)} disabled={loading}>
        {loading ? 'Đang xử lý...' : 'Đặt hàng'}
      </button>

      {error && <div className="error-message">{error}</div>}

      {/* Modal nạp tiền */}
      {showDepositModal && (
        <DepositRequiredModal
          depositInfo={depositInfo}
          onDeposit={handleGoToDeposit}
          onClose={() => setShowDepositModal(false)}
        />
      )}
    </div>
  );
};

// Component Modal
const DepositRequiredModal = ({ depositInfo, onDeposit, onClose }) => {
  const formatMoney = (amount) => {
    return new Intl.NumberFormat('vi-VN').format(amount) + ' đ';
  };

  return (
    <div className="modal-overlay">
      <div className="modal-content">
        <h2>💰 Số dư không đủ</h2>
        
        <div className="deposit-info">
          <div className="info-row">
            <span>Số dư hiện tại:</span>
            <span className="amount">{formatMoney(depositInfo.currentBalance)}</span>
          </div>
          <div className="info-row">
            <span>Số tiền cần thanh toán:</span>
            <span className="amount">{formatMoney(depositInfo.orderAmount)}</span>
          </div>
          <div className="info-row highlight">
            <span>Cần nạp thêm:</span>
            <span className="amount need-more">{formatMoney(depositInfo.needMore)}</span>
          </div>
        </div>

        <p className="message">
          Vui lòng nạp thêm tiền vào ví để hoàn tất thanh toán.
        </p>

        <div className="modal-actions">
          <button className="btn-primary" onClick={onDeposit}>
            💳 Nạp tiền ngay
          </button>
          <button className="btn-secondary" onClick={onClose}>
            Đóng
          </button>
        </div>
      </div>
    </div>
  );
};

export default CheckoutPage;
```

### 2. Vue.js

```vue
<!-- CheckoutPage.vue -->
<template>
  <div class="checkout-page">
    <!-- Form checkout -->
    <button @click="handleCheckout" :disabled="loading">
      {{ loading ? 'Đang xử lý...' : 'Đặt hàng' }}
    </button>

    <div v-if="error" class="error-message">{{ error }}</div>

    <!-- Modal nạp tiền -->
    <div v-if="showDepositModal" class="modal-overlay">
      <div class="modal-content">
        <h2>💰 Số dư không đủ</h2>
        
        <div class="deposit-info">
          <div class="info-row">
            <span>Số dư hiện tại:</span>
            <span class="amount">{{ formatMoney(depositInfo.currentBalance) }}</span>
          </div>
          <div class="info-row">
            <span>Số tiền cần thanh toán:</span>
            <span class="amount">{{ formatMoney(depositInfo.orderAmount) }}</span>
          </div>
          <div class="info-row highlight">
            <span>Cần nạp thêm:</span>
            <span class="amount need-more">{{ formatMoney(depositInfo.needMore) }}</span>
          </div>
        </div>

        <p class="message">
          Vui lòng nạp thêm tiền vào ví để hoàn tất thanh toán.
        </p>

        <div class="modal-actions">
          <button class="btn-primary" @click="goToDeposit">
            💳 Nạp tiền ngay
          </button>
          <button class="btn-secondary" @click="showDepositModal = false">
            Đóng
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  data() {
    return {
      loading: false,
      error: null,
      showDepositModal: false,
      depositInfo: null
    };
  },
  methods: {
    formatMoney(amount) {
      return new Intl.NumberFormat('vi-VN').format(amount) + ' đ';
    },
    
    async handleCheckout() {
      this.loading = true;
      this.error = null;

      try {
        // Bước 1: Tạo đơn hàng
        const orderResponse = await axios.post('/api/orders', this.orderData);
        const orderId = orderResponse.data.data.id;

        // Bước 2: Thanh toán
        const payResponse = await axios.post(`/api/orders/${orderId}/pay`);
        
        if (payResponse.data.status === 'success') {
          this.$router.push(`/orders/${orderId}/success`);
        }
      } catch (err) {
        if (err.response?.status === 422) {
          const data = err.response.data;
          
          if (data.requires_deposit) {
            this.depositInfo = {
              currentBalance: data.wallet_balance,
              orderAmount: data.order_amount,
              needMore: data.need_more,
              message: data.message
            };
            this.showDepositModal = true;
          } else {
            this.error = data.message;
          }
        } else {
          this.error = 'Có lỗi xảy ra. Vui lòng thử lại.';
        }
      } finally {
        this.loading = false;
      }
    },
    
    goToDeposit() {
      // Lưu thông tin để sau khi nạp tiền quay lại
      sessionStorage.setItem('pendingCheckout', JSON.stringify({
        returnUrl: window.location.href,
        needMore: this.depositInfo.needMore
      }));
      
      // Chuyển đến trang nạp tiền
      this.$router.push({
        path: '/wallet/deposit',
        query: { amount: this.depositInfo.needMore }
      });
    }
  }
};
</script>
```

### 3. Vanilla JavaScript

```javascript
// checkout.js
async function handleCheckout(orderData) {
  const submitBtn = document.getElementById('submit-btn');
  const errorDiv = document.getElementById('error-message');
  
  submitBtn.disabled = true;
  submitBtn.textContent = 'Đang xử lý...';
  errorDiv.style.display = 'none';

  try {
    // Bước 1: Tạo đơn hàng
    const orderResponse = await fetch('/api/orders', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${getToken()}`
      },
      body: JSON.stringify(orderData)
    });
    
    const orderResult = await orderResponse.json();
    
    if (!orderResponse.ok) {
      throw { response: { status: orderResponse.status, data: orderResult } };
    }
    
    const orderId = orderResult.data.id;

    // Bước 2: Thanh toán
    const payResponse = await fetch(`/api/orders/${orderId}/pay`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${getToken()}`
      }
    });
    
    const payResult = await payResponse.json();
    
    if (payResponse.ok && payResult.status === 'success') {
      // Thành công
      window.location.href = `/orders/${orderId}/success`;
    } else {
      throw { response: { status: payResponse.status, data: payResult } };
    }
  } catch (err) {
    if (err.response?.status === 422) {
      const data = err.response.data;
      
      if (data.requires_deposit) {
        showDepositModal({
          currentBalance: data.wallet_balance,
          orderAmount: data.order_amount,
          needMore: data.need_more
        });
      } else {
        errorDiv.textContent = data.message;
        errorDiv.style.display = 'block';
      }
    } else {
      errorDiv.textContent = 'Có lỗi xảy ra. Vui lòng thử lại.';
      errorDiv.style.display = 'block';
    }
  } finally {
    submitBtn.disabled = false;
    submitBtn.textContent = 'Đặt hàng';
  }
}

function showDepositModal(info) {
  const formatMoney = (amount) => {
    return new Intl.NumberFormat('vi-VN').format(amount) + ' đ';
  };

  const modal = document.createElement('div');
  modal.className = 'modal-overlay';
  modal.innerHTML = `
    <div class="modal-content">
      <h2>💰 Số dư không đủ</h2>
      
      <div class="deposit-info">
        <div class="info-row">
          <span>Số dư hiện tại:</span>
          <span class="amount">${formatMoney(info.currentBalance)}</span>
        </div>
        <div class="info-row">
          <span>Số tiền cần thanh toán:</span>
          <span class="amount">${formatMoney(info.orderAmount)}</span>
        </div>
        <div class="info-row highlight">
          <span>Cần nạp thêm:</span>
          <span class="amount need-more">${formatMoney(info.needMore)}</span>
        </div>
      </div>

      <p class="message">
        Vui lòng nạp thêm tiền vào ví để hoàn tất thanh toán.
      </p>

      <div class="modal-actions">
        <button class="btn-primary" onclick="goToDeposit(${info.needMore})">
          💳 Nạp tiền ngay
        </button>
        <button class="btn-secondary" onclick="closeModal()">
          Đóng
        </button>
      </div>
    </div>
  `;
  
  document.body.appendChild(modal);
}

function goToDeposit(amount) {
  // Lưu thông tin để sau khi nạp tiền quay lại
  sessionStorage.setItem('pendingCheckout', JSON.stringify({
    returnUrl: window.location.href,
    needMore: amount
  }));
  
  // Chuyển đến trang nạp tiền
  window.location.href = `/wallet/deposit?amount=${amount}`;
}

function closeModal() {
  const modal = document.querySelector('.modal-overlay');
  if (modal) {
    modal.remove();
  }
}
```

---

## 🎨 CSS MẪU

```css
/* Modal styles */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.modal-content {
  background: white;
  border-radius: 12px;
  padding: 24px;
  max-width: 400px;
  width: 90%;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
}

.modal-content h2 {
  margin: 0 0 20px;
  font-size: 20px;
  color: #333;
}

.deposit-info {
  background: #f8f9fa;
  border-radius: 8px;
  padding: 16px;
  margin-bottom: 16px;
}

.info-row {
  display: flex;
  justify-content: space-between;
  padding: 8px 0;
  border-bottom: 1px solid #e9ecef;
}

.info-row:last-child {
  border-bottom: none;
}

.info-row.highlight {
  background: #fff3cd;
  margin: 8px -16px -16px;
  padding: 12px 16px;
  border-radius: 0 0 8px 8px;
}

.amount {
  font-weight: 600;
  color: #333;
}

.amount.need-more {
  color: #dc3545;
  font-size: 18px;
}

.message {
  color: #666;
  font-size: 14px;
  margin-bottom: 20px;
}

.modal-actions {
  display: flex;
  gap: 12px;
}

.btn-primary {
  flex: 1;
  background: #007bff;
  color: white;
  border: none;
  padding: 12px 20px;
  border-radius: 8px;
  font-size: 16px;
  cursor: pointer;
  transition: background 0.2s;
}

.btn-primary:hover {
  background: #0056b3;
}

.btn-secondary {
  flex: 1;
  background: #f8f9fa;
  color: #333;
  border: 1px solid #dee2e6;
  padding: 12px 20px;
  border-radius: 8px;
  font-size: 16px;
  cursor: pointer;
  transition: background 0.2s;
}

.btn-secondary:hover {
  background: #e9ecef;
}

.error-message {
  background: #f8d7da;
  color: #721c24;
  padding: 12px 16px;
  border-radius: 8px;
  margin-top: 16px;
}
```

---

## 📱 TRANG NẠP TIỀN

### API nạp tiền: `POST /api/wallet/deposit`

```json
// Request
{
  "amount": 123145123,
  "payment_method": "bank_transfer"
}

// Response
{
  "status": "success",
  "message": "Yêu cầu nạp tiền đã được tạo",
  "data": {
    "deposit_id": 1,
    "amount": 123145123,
    "status": "pending",
    "bank_info": {
      "bank_name": "Vietcombank",
      "account_number": "1234567890",
      "account_name": "CONG TY TRADEHUB",
      "content": "NAP TIEN USER5 DEP1"
    }
  }
}
```

### Trang nạp tiền nên:
1. Nhận `amount` từ query string để pre-fill số tiền
2. Hiển thị thông tin chuyển khoản
3. Sau khi nạp xong, redirect về trang checkout

```jsx
// DepositPage.jsx
import { useRouter } from 'next/router';
import { useEffect, useState } from 'react';

const DepositPage = () => {
  const router = useRouter();
  const { amount } = router.query;
  const [depositAmount, setDepositAmount] = useState(amount || '');

  useEffect(() => {
    if (amount) {
      setDepositAmount(amount);
    }
  }, [amount]);

  const handleDepositSuccess = () => {
    // Kiểm tra có pending checkout không
    const pending = sessionStorage.getItem('pendingCheckout');
    if (pending) {
      const { returnUrl } = JSON.parse(pending);
      sessionStorage.removeItem('pendingCheckout');
      router.push(returnUrl);
    } else {
      router.push('/wallet');
    }
  };

  return (
    <div className="deposit-page">
      <h1>Nạp tiền vào ví</h1>
      
      {amount && (
        <div className="suggested-amount">
          💡 Bạn cần nạp tối thiểu <strong>{formatMoney(amount)}</strong> để hoàn tất thanh toán
        </div>
      )}

      <input
        type="number"
        value={depositAmount}
        onChange={(e) => setDepositAmount(e.target.value)}
        placeholder="Nhập số tiền cần nạp"
      />

      {/* Form nạp tiền */}
    </div>
  );
};
```

---

## ✅ CHECKLIST FRONTEND

- [ ] Xử lý response 422 từ API thanh toán
- [ ] Kiểm tra field `requires_deposit` để phân biệt lỗi
- [ ] Hiển thị modal/dialog với thông tin số dư và số tiền cần nạp
- [ ] Nút "Nạp tiền ngay" chuyển đến trang `/wallet/deposit`
- [ ] Truyền `amount` qua query string để pre-fill số tiền
- [ ] Lưu `returnUrl` để sau khi nạp tiền quay lại checkout
- [ ] Format số tiền theo định dạng VND (123.456.789 đ)

---

## 📞 HỖ TRỢ

Nếu có thắc mắc về API, xem thêm:
- `API_07_Orders.md` - API đơn hàng
- `API_09_Payments.md` - API thanh toán
- `app/Http/Controllers/Api/OrderController.php` - Code backend
