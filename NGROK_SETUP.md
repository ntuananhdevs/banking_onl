# Hướng dẫn sử dụng Ngrok với Laravel

## Vấn đề khi dùng Ngrok

Khi sử dụng ngrok để expose local server, có thể gặp các lỗi:
- Laravel không nhận diện đúng IP và headers từ ngrok
- Webhook không hoạt động đúng
- SSL/HTTPS issues

## Giải pháp

### 1. TrustProxies Middleware

Đã cấu hình `TrustProxies` middleware để trust tất cả proxies (bao gồm ngrok).

File: `app/Http/Middleware/TrustProxies.php`

### 2. Cấu hình trong bootstrap/app.php

Đã đăng ký middleware trong `bootstrap/app.php`:
```php
$middleware->trustProxies(at: '*');
```

## Cách sử dụng Ngrok

### Bước 1: Cài đặt Ngrok

```bash
# macOS
brew install ngrok

# Linux
# Download từ https://ngrok.com/download
```

### Bước 2: Chạy Ngrok

```bash
# Expose local server
ngrok http banking.test

# Hoặc nếu dùng Laravel serve
ngrok http 8000
```

### Bước 3: Lấy URL từ Ngrok

Sau khi chạy ngrok, bạn sẽ nhận được URL dạng:
```
Forwarding: https://xxxx-xxx-xxx-xxx-xxx.ngrok-free.app -> http://banking.test
```

### Bước 4: Cấu hình Webhook URL trong SePay

1. Vào SePay Dashboard → IPN Settings
2. Nhập IPN URL: `https://xxxx-xxx-xxx-xxx-xxx.ngrok-free.app/api/webhooks/sepay`
3. Lưu cấu hình

### Bước 5: Test Webhook

```bash
# Test webhook với ngrok URL
curl -X POST https://xxxx-xxx-xxx-xxx-xxx.ngrok-free.app/api/webhooks/sepay \
  -H "Content-Type: application/json" \
  -H "X-Sepay-Signature: [signature]" \
  -d '{
    "order_invoice_number": "TEST_123",
    "order_amount": 100000,
    "order_status": "CAPTURED",
    "order_description": "NAPTIEN user_id_1"
  }'
```

## Lưu ý quan trọng

### 1. Ngrok Free Plan
- URL sẽ thay đổi mỗi lần restart ngrok
- Cần cập nhật lại IPN URL trong SePay dashboard mỗi lần

### 2. Ngrok Paid Plan
- Có thể sử dụng custom domain
- URL cố định, không cần cập nhật lại

### 3. Ngrok Warning Page
- Ngrok free plan có warning page khi truy cập lần đầu
- Có thể bỏ qua hoặc upgrade lên paid plan

### 4. Headers từ Ngrok
- Ngrok tự động thêm các headers như `X-Forwarded-For`, `X-Forwarded-Proto`
- TrustProxies middleware đã được cấu hình để trust các headers này

## Troubleshooting

### Lỗi: "Invalid signature"
- ✅ Kiểm tra `SEPAY_WEBHOOK_SECRET` trong `.env` đã đúng
- ✅ Kiểm tra ngrok URL đã được cấu hình đúng trong SePay dashboard
- ✅ Xem logs: `tail -f storage/logs/laravel.log`

### Lỗi: "Connection refused"
- ✅ Kiểm tra local server đang chạy (banking.test hoặc port 8000)
- ✅ Kiểm tra ngrok đang chạy và forwarding đúng port

### Lỗi: "404 Not Found"
- ✅ Kiểm tra route: `php artisan route:list --path=webhooks`
- ✅ Đảm bảo URL là: `https://ngrok-url/api/webhooks/sepay` (có `/api` prefix)

### Lỗi: "CSRF token mismatch"
- ✅ Route webhook đã được cấu hình `withoutMiddleware(['csrf'])`
- ✅ Kiểm tra route trong `routes/api.php`

## Kiểm tra Logs

```bash
# Xem logs real-time
tail -f storage/logs/laravel.log

# Tìm webhook requests
grep "Webhook received" storage/logs/laravel.log
```

## Alternative: Sử dụng Valet Share

Nếu không muốn dùng ngrok, có thể dùng Valet Share:

```bash
valet share
```

Valet Share sẽ tạo URL public tương tự ngrok.
