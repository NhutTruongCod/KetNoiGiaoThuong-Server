# PowerShell script test API thanh toán

$BASE_URL = "http://localhost:8000/api"

Write-Host "=== TEST THANH TOÁN API ===" -ForegroundColor Cyan
Write-Host ""

# 1. Đăng nhập
Write-Host "1. Đăng nhập..." -ForegroundColor Yellow
$loginBody = @{
    email = "admin@tradehub.com"
    password = "admin123"
} | ConvertTo-Json

try {
    $loginResponse = Invoke-RestMethod -Uri "$BASE_URL/auth/login" `
        -Method Post `
        -Body $loginBody `
        -ContentType "application/json"
    
    $token = $loginResponse.data.access_token
    Write-Host "✅ Đăng nhập thành công" -ForegroundColor Green
    Write-Host "Token: $($token.Substring(0, 50))..."
    Write-Host ""
} catch {
    Write-Host "❌ Đăng nhập thất bại" -ForegroundColor Red
    Write-Host $_.Exception.Message
    exit 1
}

# 2. Kiểm tra ví
Write-Host "2. Kiểm tra số dư ví..." -ForegroundColor Yellow
try {
    $walletResponse = Invoke-RestMethod -Uri "$BASE_URL/wallet" `
        -Method Get `
        -Headers @{ Authorization = "Bearer $token" }
    
    $balance = if ($walletResponse.data.balance) { $walletResponse.data.balance } else { $walletResponse.data.available_balance }
    Write-Host "Số dư hiện tại: $balance VND" -ForegroundColor Cyan
    Write-Host ""
} catch {
    Write-Host "⚠️ Không lấy được thông tin ví" -ForegroundColor Yellow
    Write-Host ""
}

# 3. Tạo đơn hàng
Write-Host "3. Tạo đơn hàng test..." -ForegroundColor Yellow
$orderBody = @{
    listing_id = 11
    quantity = 1
    shipping_address = @{
        name = "Test User"
        phone = "0123456789"
        address = "123 Test Street"
        district = "District 1"
        city = "Ho Chi Minh"
    }
    note = "Test order"
} | ConvertTo-Json

try {
    $orderResponse = Invoke-RestMethod -Uri "$BASE_URL/orders" `
        -Method Post `
        -Body $orderBody `
        -ContentType "application/json" `
        -Headers @{ Authorization = "Bearer $token" }
    
    $orderId = $orderResponse.data.id
    Write-Host "✅ Tạo đơn hàng thành công - ID: $orderId" -ForegroundColor Green
    Write-Host ""
} catch {
    Write-Host "❌ Tạo đơn hàng thất bại" -ForegroundColor Red
    Write-Host $_.Exception.Message
    exit 1
}

# 4. Thanh toán
Write-Host "4. Thanh toán đơn hàng..." -ForegroundColor Yellow
try {
    $paymentResponse = Invoke-RestMethod -Uri "$BASE_URL/orders/$orderId/pay" `
        -Method Post `
        -ContentType "application/json" `
        -Headers @{ Authorization = "Bearer $token" }
    
    Write-Host ""
    Write-Host "=== KẾT QUẢ ===" -ForegroundColor Cyan
    Write-Host "Status: $($paymentResponse.status)"
    Write-Host "Message: $($paymentResponse.message)"
    
    if ($paymentResponse.status -eq "success") {
        Write-Host "✅ Thanh toán thành công" -ForegroundColor Green
    }
} catch {
    $errorResponse = $_.ErrorDetails.Message | ConvertFrom-Json
    
    Write-Host ""
    Write-Host "=== KẾT QUẢ ===" -ForegroundColor Cyan
    Write-Host "Status: $($errorResponse.status)" -ForegroundColor Red
    Write-Host "Message: $($errorResponse.message)" -ForegroundColor Red
    
    if ($errorResponse.requires_deposit -eq $true) {
        Write-Host ""
        Write-Host "✅ API trả về đúng: Yêu cầu nạp tiền" -ForegroundColor Green
        Write-Host "Số dư ví: $($errorResponse.wallet_balance) VND" -ForegroundColor Yellow
        Write-Host "Số tiền đơn hàng: $($errorResponse.order_amount) VND" -ForegroundColor Yellow
        Write-Host "Cần nạp thêm: $($errorResponse.need_more) VND" -ForegroundColor Yellow
    } else {
        Write-Host "❌ Lỗi khác" -ForegroundColor Red
        Write-Host ($errorResponse | ConvertTo-Json -Depth 5)
    }
}

Write-Host ""
Write-Host "=== HOÀN THÀNH ===" -ForegroundColor Cyan
