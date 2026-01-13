# Hướng dẫn cấu hình IPN (Instant Payment Notification) cho SePay

## IPN là gì?

**IPN (Instant Payment Notification)** là cơ chế webhook mà SePay sử dụng để gửi thông báo tự động đến server của bạn khi có giao dịch thanh toán thành công.

### Cách hoạt động:

1. **Khách hàng thanh toán** → SePay xử lý giao dịch
2. **Giao dịch thành công** → SePay gửi POST request đến IPN URL của bạn
3. **Server của bạn nhận IPN** → Xử lý và cập nhật số dư tự động
4. **Trả về response** → SePay xác nhận đã nhận được thông báo

## Cấu hình IPN URL trong SePay Dashboard

### Bước 1: Đăng nhập SePay Dashboard
1. Truy cập: https://sepay.vn (hoặc https://sepay.com.vn)
2. Đăng nhập vào tài khoản của bạn

### Bước 2: Vào phần IPN Settings
1. Vào **Settings** → **IPN Configuration** hoặc **Webhook Settings**
2. Hoặc vào **Developer** → **IPN/Webhook**

### Bước 3: Cấu hình IPN URL
1. Nhập IPN URL của bạn:
   ```
   https://banking.test/webhooks/sepay
   ```
   (Thay `banking.test` bằng domain production của bạn)

2. Chọn các sự kiện muốn nhận thông báo:
   - ✅ Payment Success (Thanh toán thành công)
   - ✅ Payment Failed (Thanh toán thất bại)
   - ✅ Order Status Changed (Trạng thái đơn hàng thay đổi)

3. Lưu cấu hình

### Bước 4: Lấy Webhook Secret
1. Sau khi cấu hình IPN URL, SePay sẽ tạo **Webhook Secret**
2. Copy secret này và thêm vào file `.env`:
   ```env
   SEPAY_WEBHOOK_SECRET=your_webhook_secret_here
   ```

## IPN Endpoint đã được cấu hình

Endpoint webhook đã được tạo tại:
- **URL**: `https://banking.test/webhooks/sepay` (hoặc domain của bạn)
- **Method**: POST
- **Route**: `/api/webhooks/sepay` (hoặc `/webhooks/sepay` nếu dùng web routes)

## Format IPN từ SePay

SePay có thể gửi IPN với các format khác nhau. Service đã được cấu hình để hỗ trợ:

### Format 1: SePay SDK Standard
```json
{
    "order_invoice_number": "DEPOSIT_1_1234567890",
    "order_amount": 100000,
    "order_status": "CAPTURED",
    "order_description": "NAPTIEN user_id_1",
    "customer_id": "1",
    "currency": "VND",
    "transaction_id": "TXN_123456",
    "created_at": "2024-01-12T10:00:00Z"
}
```

### Format 2: Custom Format
```json
{
    "transaction_id": "TXN_123456",
    "amount": 100000,
    "transfer_content": "NAPTIEN user_id_1",
    "status": "completed",
    "metadata": {}
}
```

## Xử lý IPN trong hệ thống

### 1. Validation
- ✅ Kiểm tra signature để đảm bảo request đến từ SePay
- ✅ Validate các trường bắt buộc (amount, transfer_content)
- ✅ Kiểm tra order_status (chỉ xử lý khi thành công)

### 2. Parse User ID
- Parse `user_id` từ `transfer_content` với format: `"NAPTIEN user_id_123"`
- Tìm user trong database

### 3. Xử lý giao dịch
- Kiểm tra duplicate transaction (tránh xử lý 2 lần)
- Tạo transaction record trong database
- Cập nhật số dư (balance) của user
- Log lại toàn bộ quá trình

### 4. Response
- Trả về HTTP 200 nếu xử lý thành công
- Trả về HTTP 400/500 nếu có lỗi

## Test IPN

### Cách 1: Sử dụng SePay Sandbox
1. Tạo giao dịch test trong SePay Sandbox
2. SePay sẽ gửi IPN đến URL đã cấu hình
3. Kiểm tra logs trong `storage/logs/laravel.log`

### Cách 2: Test thủ công với curl
```bash
curl -X POST https://banking.test/webhooks/sepay \
  -H "Content-Type: application/json" \
  -H "X-Sepay-Signature: your_signature_here" \
  -d '{
    "order_invoice_number": "TEST_123",
    "order_amount": 100000,
    "order_status": "CAPTURED",
    "order_description": "NAPTIEN user_id_1",
    "customer_id": "1"
  }'
```

### Cách 3: Sử dụng Postman
1. Method: POST
2. URL: `https://banking.test/webhooks/sepay`
3. Headers:
   - `Content-Type: application/json`
   - `X-Sepay-Signature: [signature]`
4. Body (raw JSON):
```json
{
    "order_invoice_number": "TEST_123",
    "order_amount": 100000,
    "order_status": "CAPTURED",
    "order_description": "NAPTIEN user_id_1"
}
```

## Lưu ý quan trọng

1. **IPN URL phải là HTTPS** (không hỗ trợ HTTP)
   - Sử dụng `valet secure banking` để tạo SSL certificate

2. **IPN URL phải accessible từ internet**
   - Nếu đang development local, cần dùng ngrok hoặc tunnel

3. **Response phải nhanh (< 5 giây)**
   - SePay sẽ retry nếu không nhận được response trong thời gian quy định

4. **Idempotency**
   - Hệ thống đã có cơ chế kiểm tra duplicate transaction
   - Mỗi transaction chỉ được xử lý 1 lần

5. **Logging**
   - Tất cả IPN requests đều được log trong `storage/logs/laravel.log`
   - Kiểm tra logs để debug nếu có vấn đề

## Troubleshooting

### IPN không được nhận
- ✅ Kiểm tra IPN URL đã được cấu hình đúng trong SePay dashboard
- ✅ Kiểm tra SSL certificate (phải là HTTPS)
- ✅ Kiểm tra firewall/security không chặn request từ SePay
- ✅ Kiểm tra logs: `tail -f storage/logs/laravel.log`

### Signature validation failed
- ✅ Kiểm tra `SEPAY_WEBHOOK_SECRET` trong `.env` đã đúng
- ✅ Kiểm tra header `X-Sepay-Signature` có được gửi không
- ✅ Xem logs để biết signature nào đang được gửi

### Transaction không được xử lý
- ✅ Kiểm tra `transfer_content` có đúng format: `"NAPTIEN user_id_XXX"`
- ✅ Kiểm tra user_id có tồn tại trong database
- ✅ Kiểm tra `order_status` có phải là "CAPTURED" hoặc "completed" không

## Liên hệ hỗ trợ

- Email: support@sepay.vn
- Website: https://sepay.vn
- Documentation: https://developer.sepay.vn
