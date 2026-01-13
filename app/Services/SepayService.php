<?php

namespace App\Services;

use SePay\SePayClient;
use SePay\Builders\CheckoutBuilder;
use SePay\Exceptions\SePayException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class SepayService
{
    protected SePayClient $client;
    protected ?string $accessToken = null;
    protected string $apiBaseUrl;
    protected string $userApiBaseUrl = 'https://my.sepay.vn/userapi';

    public function __construct()
    {
        $merchantId = config('sepay.merchant_id');
        $secretKey = config('sepay.secret_key');
        $accessToken = config('sepay.access_token');
        $environment = config('sepay.environment', 'sandbox');

        // Lưu access token để dùng làm Bearer token
        $this->accessToken = $accessToken;
        
        // Log để debug (masked)
        if (!empty($accessToken)) {
            Log::debug('SePay access token configured', [
                'token_length' => strlen($accessToken),
                'token_prefix' => substr($accessToken, 0, 8) . '...',
                'environment' => $environment,
            ]);
        } else {
            Log::warning('SePay access token not configured, will use Basic Auth for API calls');
        }

        // Set API base URL
        if ($environment === 'production') {
            $this->apiBaseUrl = 'https://pgapi.sepay.vn';
        } else {
            $this->apiBaseUrl = 'https://pgapi-sandbox.sepay.vn';
        }

        // Vẫn cần merchant_id và secret_key cho checkout form generation
        if (empty($merchantId) || empty($secretKey)) {
            throw new \RuntimeException('SePay credentials not configured. Please check your .env file.');
        }

        $this->client = new SePayClient(
            $merchantId,
            $secretKey,
            $environment === 'production' ? SePayClient::ENVIRONMENT_PRODUCTION : SePayClient::ENVIRONMENT_SANDBOX
        );

        // Enable debug mode nếu đang development
        if (config('app.debug')) {
            $this->client->enableDebugMode();
        }
    }

    /**
     * Tạo checkout order cho thanh toán chuyển khoản với QR code
     *
     * @param int $amount Số tiền (VNĐ)
     * @param string $orderInvoiceNumber Mã hóa đơn (unique)
     * @param int $userId User ID để tạo nội dung chuyển khoản
     * @return array Thông tin checkout bao gồm form fields và checkout URL
     */
    public function createBankTransferCheckout(int $amount, string $orderInvoiceNumber, int $userId): array
    {
        try {
            $transferContent = $this->generateTransferContent($userId);
            
            // Tạo checkout data với BANK_TRANSFER method để có QR code
            $checkoutData = CheckoutBuilder::make()
                ->currency('VND')
                ->orderAmount($amount)
                ->operation('PURCHASE')
                ->orderDescription($transferContent)
                ->orderInvoiceNumber($orderInvoiceNumber)
                ->customerId((string) $userId)
                ->paymentMethod('BANK_TRANSFER') // Sử dụng BANK_TRANSFER để có QR code
                ->successUrl($this->getSuccessUrl())
                ->errorUrl($this->getErrorUrl())
                ->cancelUrl($this->getCancelUrl())
                ->build();

            // Generate form fields với signature
            $formFields = $this->client->checkout()->generateFormFields($checkoutData);
            
            // Lấy checkout URL
            $checkoutUrl = $this->client->checkout()->getCheckoutUrl($this->client->getEnvironment());

            Log::info('SePay checkout created', [
                'order_invoice_number' => $orderInvoiceNumber,
                'amount' => $amount,
                'user_id' => $userId,
            ]);

            return [
                'success' => true,
                'form_fields' => $formFields,
                'checkout_url' => $checkoutUrl,
                'transfer_content' => $transferContent,
            ];
        } catch (SePayException $e) {
            Log::error('SePay checkout creation failed', [
                'error' => $e->getMessage(),
                'order_invoice_number' => $orderInvoiceNumber,
                'amount' => $amount,
            ]);

            throw new \RuntimeException('Không thể tạo yêu cầu thanh toán: ' . $e->getMessage());
        }
    }

    /**
     * Lấy thông tin order từ SePay API
     * Thử Bearer token trước, nếu fail thì fallback về Basic Auth
     *
     * @param string $orderInvoiceNumber Mã hóa đơn
     * @return array|null Thông tin order hoặc null nếu không tìm thấy
     */
    public function getOrderDetails(string $orderInvoiceNumber): ?array
    {
        // Thử Bearer token nếu có (nhưng có thể không hoạt động)
        if (!empty($this->accessToken)) {
            $result = $this->getOrderDetailsWithBearerToken($orderInvoiceNumber);
            // Nếu Bearer token thành công, return ngay
            if ($result !== null) {
                return $result;
            }
            // Nếu Bearer token fail, log và tiếp tục với Basic Auth
            Log::info('Bearer token authentication failed, falling back to Basic Auth');
        }

        // Fallback về Basic Auth (luôn hoạt động)
        try {
            $order = $this->client->orders()->retrieve($orderInvoiceNumber);
            
            Log::info('SePay order retrieved (Basic Auth)', [
                'order_invoice_number' => $orderInvoiceNumber,
                'order_status' => $order['order_status'] ?? null,
            ]);

            return $order;
        } catch (SePayException $e) {
            Log::warning('SePay order not found or error', [
                'error' => $e->getMessage(),
                'order_invoice_number' => $orderInvoiceNumber,
                'auth_method' => 'Basic Auth',
            ]);

            return null;
        }
    }

    /**
     * Lấy thông tin order từ SePay API sử dụng Bearer token
     *
     * @param string $orderInvoiceNumber Mã hóa đơn
     * @return array|null Thông tin order hoặc null nếu không tìm thấy
     */
    protected function getOrderDetailsWithBearerToken(string $orderInvoiceNumber): ?array
    {
        if (empty($this->accessToken)) {
            Log::warning('Access token not configured, falling back to Basic Auth');
            return null;
        }

        try {
            // Thử nhiều cách xác thực khác nhau
            $authMethods = [
                'Bearer ' . $this->accessToken,
                'Token ' . $this->accessToken,
                $this->accessToken, // Direct token
            ];

            $lastError = null;
            
            foreach ($authMethods as $authHeader) {
                try {
                    $client = new Client([
                        'timeout' => 30,
                        'headers' => [
                            'Authorization' => $authHeader,
                            'Accept' => 'application/json',
                            'Content-Type' => 'application/json',
                        ],
                    ]);

                    $url = $this->apiBaseUrl . '/v1/order/detail/' . $orderInvoiceNumber;
                    
                    Log::debug('SePay API request with token', [
                        'url' => $url,
                        'order_invoice_number' => $orderInvoiceNumber,
                        'auth_method' => substr($authHeader, 0, 20) . '...',
                    ]);

                    $response = $client->get($url);
                    $body = $response->getBody()->getContents();
                    $order = json_decode($body, true);

                    if (json_last_error() !== JSON_ERROR_NONE) {
                        throw new \RuntimeException('Invalid JSON response: ' . json_last_error_msg());
                    }

                    Log::info('SePay order retrieved (Token Auth)', [
                        'order_invoice_number' => $orderInvoiceNumber,
                        'order_status' => $order['order_status'] ?? null,
                        'auth_method' => substr($authHeader, 0, 20) . '...',
                    ]);

                    return $order;
                } catch (GuzzleException $e) {
                    $lastError = $e;
                    $statusCode = null;
                    
                    // Lấy status code từ exception
                    if ($e instanceof \GuzzleHttp\Exception\RequestException && $e->hasResponse()) {
                        $statusCode = $e->getResponse()->getStatusCode();
                    }
                    
                    // Nếu là 401, thử method tiếp theo
                    if ($statusCode === 401) {
                        Log::debug('Auth method failed, trying next', [
                            'auth_method' => substr($authHeader, 0, 20) . '...',
                            'error' => $e->getMessage(),
                            'status_code' => $statusCode,
                        ]);
                        continue;
                    }
                    // Nếu không phải 401, throw ngay
                    throw $e;
                }
            }

            // Nếu tất cả đều fail, log và return null
            Log::warning('SePay order API call failed with all auth methods', [
                'error' => $lastError ? $lastError->getMessage() : 'All auth methods failed',
                'order_invoice_number' => $orderInvoiceNumber,
                'auth_method' => 'Token (multiple attempts)',
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('SePay order retrieval error', [
                'error' => $e->getMessage(),
                'order_invoice_number' => $orderInvoiceNumber,
            ]);

            return null;
        }
    }

    /**
     * Tạo nội dung chuyển khoản theo format: "NAPTIEN user_id_XXX"
     *
     * @param int $userId
     * @return string
     */
    public function generateTransferContent(int $userId): string
    {
        $prefix = config('sepay.transfer_content_prefix', 'NAPTIEN');
        return "{$prefix} user_id_{$userId}";
    }

    /**
     * Lấy success URL
     */
    protected function getSuccessUrl(): string
    {
        $url = config('sepay.success_url', '/deposit/success');
        return URL::to($url);
    }

    /**
     * Lấy error URL
     */
    protected function getErrorUrl(): string
    {
        $url = config('sepay.error_url', '/deposit/error');
        return URL::to($url);
    }

    /**
     * Lấy cancel URL
     */
    protected function getCancelUrl(): string
    {
        $url = config('sepay.cancel_url', '/deposit');
        return URL::to($url);
    }

    /**
     * Tạo QR code URL từ SePay QR service
     * Sử dụng: https://qr.sepay.vn/img?acc=SO_TAI_KHOAN&bank=NGAN_HANG&amount=SO_TIEN&des=NOI_DUNG
     * Thông tin ngân hàng sẽ được lấy từ order details hoặc truyền vào
     *
     * @param int $amount Số tiền (VNĐ)
     * @param string $transferContent Nội dung chuyển khoản
     * @param string|null $accountNumber Số tài khoản
     * @param string|null $bankName Tên ngân hàng hoặc mã ngân hàng
     * @param string|null $template Template QR code (optional)
     * @param bool $download Có download QR code không (default: false)
     * @return string|null QR code URL hoặc null nếu thiếu thông tin
     */
    public function generateQrCodeUrl(
        int $amount,
        string $transferContent,
        ?string $accountNumber = null,
        ?string $bankName = null,
        ?string $template = null,
        bool $download = false
    ): ?string {
        // Validate required fields
        if (empty($accountNumber) || empty($bankName)) {
            Log::warning('Cannot generate QR code: missing bank account information', [
                'account_number_set' => !empty($accountNumber),
                'bank_name_set' => !empty($bankName),
            ]);
            return null;
        }

        // Tạo URL với các tham số
        $params = [
            'acc' => urlencode($accountNumber),
            'bank' => urlencode($bankName),
            'amount' => $amount,
            'des' => urlencode($transferContent),
        ];

        // Thêm template nếu có
        if ($template) {
            $params['template'] = urlencode($template);
        }

        // Thêm download nếu cần
        if ($download) {
            $params['download'] = '1';
        }

        // Build URL
        $qrCodeUrl = 'https://qr.sepay.vn/img?' . http_build_query($params);

        Log::debug('QR code URL generated', [
            'account_number' => substr($accountNumber, 0, 4) . '***',
            'bank_name' => $bankName,
            'amount' => $amount,
        ]);

        return $qrCodeUrl;
    }

    /**
     * Lấy QR code URL từ order details
     * SePay có thể trả về QR code trong order details
     *
     * @param array $orderDetails
     * @return string|null
     */
    public function extractQrCodeUrl(array $orderDetails): ?string
    {
        // SePay có thể trả về QR code ở các field khác nhau
        return $orderDetails['qr_code_url'] 
            ?? $orderDetails['qr_code'] 
            ?? $orderDetails['payment_qr_code'] 
            ?? $orderDetails['bank_transfer_qr_code']
            ?? null;
    }

    /**
     * Lấy thông tin ngân hàng từ order details
     *
     * @param array $orderDetails
     * @return array|null
     */
    public function extractBankInfo(array $orderDetails): ?array
    {
        // SePay có thể trả về thông tin ngân hàng ở các field khác nhau
        if (isset($orderDetails['bank_info'])) {
            return $orderDetails['bank_info'];
        }

        // Hoặc các field riêng lẻ
        if (isset($orderDetails['account_number']) || isset($orderDetails['bank_name'])) {
            return [
                'account_number' => $orderDetails['account_number'] ?? null,
                'account_name' => $orderDetails['account_name'] ?? $orderDetails['bank_account_name'] ?? null,
                'bank_name' => $orderDetails['bank_name'] ?? $orderDetails['bank_code'] ?? null,
            ];
        }

        return null;
    }

    /**
     * Lấy thông tin tài khoản ngân hàng từ SePay User API
     * Sử dụng Bearer token để authenticate
     *
     * @param string|null $bankAccountId ID tài khoản ngân hàng (nếu null sẽ lấy từ config)
     * @return array|null Thông tin tài khoản ngân hàng hoặc null nếu không tìm thấy
     */
    public function getBankAccountDetails(?string $bankAccountId = null): ?array
    {
        if (empty($this->accessToken)) {
            Log::warning('Access token not configured, cannot fetch bank account details');
            return null;
        }

        $bankAccountId = $bankAccountId ?? config('sepay.bank_account_id');
        
        if (empty($bankAccountId)) {
            Log::warning('Bank account ID not configured');
            return null;
        }

        try {
            $client = new Client([
                'timeout' => 30,
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->accessToken,
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ],
            ]);

            $url = $this->userApiBaseUrl . '/bankaccounts/details/' . $bankAccountId;
            
            Log::debug('SePay User API request for bank account', [
                'url' => $url,
                'bank_account_id' => $bankAccountId,
            ]);

            $response = $client->get($url);
            $body = $response->getBody()->getContents();
            $data = json_decode($body, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \RuntimeException('Invalid JSON response: ' . json_last_error_msg());
            }

            // Kiểm tra status
            if (isset($data['status']) && $data['status'] !== 200) {
                Log::warning('SePay User API returned error', [
                    'status' => $data['status'] ?? null,
                    'error' => $data['error'] ?? null,
                    'bank_account_id' => $bankAccountId,
                ]);
                return null;
            }

            // Extract bank account info
            $bankAccount = $data['bankaccount'] ?? null;
            
            if (!$bankAccount) {
                Log::warning('Bank account not found in response', [
                    'bank_account_id' => $bankAccountId,
                    'response' => $data,
                ]);
                return null;
            }

            Log::info('Bank account details retrieved successfully', [
                'bank_account_id' => $bankAccountId,
                'account_number' => substr($bankAccount['account_number'] ?? '', 0, 4) . '***',
                'bank_name' => $bankAccount['bank_short_name'] ?? $bankAccount['bank_short_name'] ?? null,
            ]);

            return [
                'account_number' => $bankAccount['account_number'] ?? null,
                'account_name' => $bankAccount['account_holder_name'] ?? null,
                'bank_name' => $bankAccount['bank_short_name'] ?? $bankAccount['bank_short_name'] ?? null,
                'bank_code' => $bankAccount['bank_code'] ?? null,
                'bank_bin' => $bankAccount['bank_bin'] ?? null,
                'raw_data' => $bankAccount, // Lưu toàn bộ data để dùng sau
            ];
        } catch (GuzzleException $e) {
            Log::error('Failed to fetch bank account details from SePay User API', [
                'error' => $e->getMessage(),
                'bank_account_id' => $bankAccountId,
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Error fetching bank account details', [
                'error' => $e->getMessage(),
                'bank_account_id' => $bankAccountId,
            ]);

            return null;
        }
    }
}
