# Hướng dẫn cấu hình Sepay API

## Cách lấy API Key từ Sepay

### Bước 1: Đăng ký tài khoản Sepay
1. Truy cập website chính thức của Sepay: https://sepay.vn (hoặc https://sepay.com.vn)
2. Đăng ký tài khoản mới hoặc đăng nhập nếu đã có tài khoản

### Bước 2: Truy cập Developer Portal / API Dashboard
1. Sau khi đăng nhập, vào phần **Developer** hoặc **API Settings** trong dashboard
2. Hoặc truy cập trực tiếp: https://sepay.vn/developer (hoặc đường dẫn tương tự)

### Bước 3: Tạo API Key
1. Trong phần API Settings, tìm mục **API Keys** hoặc **Tạo API Key mới**
2. Click vào nút **Tạo API Key** hoặc **Generate API Key**
3. Điền thông tin:
   - **Tên API Key**: Tên mô tả (ví dụ: "Banking App Production")
   - **Quyền truy cập**: Chọn các quyền cần thiết (Deposit, Webhook, etc.)
   - **Môi trường**: Production hoặc Sandbox (cho testing)

### Bước 4: Cấu hình IPN (Instant Payment Notification)
1. Vào phần **IPN Settings** hoặc **Webhook Configuration** trong SePay dashboard
2. Nhập IPN URL: `https://banking.test/webhooks/sepay` (hoặc domain production của bạn)
   - ⚠️ **Lưu ý**: IPN URL phải là HTTPS (không hỗ trợ HTTP)
   - Chạy `valet secure banking` để tạo SSL certificate
3. Chọn các sự kiện muốn nhận thông báo:
   - ✅ Payment Success (Thanh toán thành công)
   - ✅ Payment Failed (Thanh toán thất bại)
4. Lưu cấu hình

### Bước 5: Lấy Webhook Secret
1. Sau khi cấu hình IPN URL, SePay sẽ tạo **Webhook Secret**
2. Copy secret này để validate IPN requests

### Bước 6: Cấu hình trong Laravel

Thêm các biến môi trường vào file `.env`:

```env
SEPAY_API_URL=https://api.sepay.vn
SEPAY_API_KEY=your_api_key_here
SEPAY_WEBHOOK_SECRET=your_webhook_secret_here
SEPAY_TRANSFER_CONTENT_PREFIX=NAPTIEN
```

### Lưu ý quan trọng:
- ⚠️ **KHÔNG** commit file `.env` lên Git
- ⚠️ Bảo mật API Key và Webhook Secret
- ⚠️ Sử dụng Sandbox API Key cho môi trường development
- ⚠️ Webhook URL phải là HTTPS (cần SSL certificate)

## Cấu hình SSL cho IPN/Webhook (Valet)

IPN URL **PHẢI** là HTTPS. Nếu đang sử dụng Laravel Valet, chạy lệnh sau để thiết lập SSL:

```bash
valet secure banking
```

Sau đó IPN URL sẽ là: `https://banking.test/webhooks/sepay`

**Lưu ý**: Nếu đang development local và cần test IPN, bạn có thể:
- Sử dụng ngrok để expose local server: `ngrok http banking.test`
- Hoặc sử dụng SePay Sandbox environment

## IPN (Instant Payment Notification)

**IPN là gì?**
- SePay sẽ tự động gửi POST request đến IPN URL của bạn khi có giao dịch thanh toán thành công
- Đây là cơ chế webhook để cập nhật số dư tự động

**Cách hoạt động:**
1. Khách hàng thanh toán → SePay xử lý
2. Giao dịch thành công → SePay gửi IPN đến server của bạn
3. Server xử lý IPN → Cập nhật số dư và tạo transaction record
4. Trả về response → SePay xác nhận đã nhận được

Xem file `IPN_SETUP.md` để biết chi tiết về cách cấu hình và test IPN.

## Test IPN/Webhook

Sau khi cấu hình xong, bạn có thể test IPN bằng cách:

1. Tạo giao dịch test trong SePay Sandbox
2. Hoặc sử dụng tool như Postman hoặc curl:
```bash
curl -X POST https://banking.test/webhooks/sepay \
  -H "Content-Type: application/json" \
  -H "X-Sepay-Signature: [signature]" \
  -d '{
    "order_invoice_number": "TEST_123",
    "order_amount": 100000,
    "order_status": "CAPTURED",
    "order_description": "NAPTIEN user_id_1"
  }'
```

## Liên hệ hỗ trợ

Nếu gặp vấn đề, liên hệ:
- Email: support@sepay.vn
- Website: https://sepay.vn
- Documentation: https://sepay.vn/docs (nếu có)
