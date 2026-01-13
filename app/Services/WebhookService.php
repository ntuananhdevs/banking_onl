<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WebhookService
{
    protected ?string $webhookSecret;

    public function __construct()
    {
        $this->webhookSecret = config('sepay.webhook_secret') ?? '';
    }

    /**
     * Validate webhook signature từ Sepay
     * Hỗ trợ cả signature validation và Bearer token validation
     */
    public function validateWebhook(array $payload, ?string $signature, ?string $bearerToken = null): bool
    {
        // Nếu có Bearer token, validate bằng Bearer token (cho Bank API)
        if (!empty($bearerToken)) {
            $expectedToken = config('sepay.access_token');
            if (!empty($expectedToken) && hash_equals($expectedToken, $bearerToken)) {
                Log::info('Webhook validated using Bearer token');
                return true;
            }
            Log::warning('Invalid Bearer token', [
                'token_length' => strlen($bearerToken ?? ''),
                'expected_length' => strlen($expectedToken ?? ''),
            ]);
        }

        // Nếu có signature, validate bằng signature (cho Payment Gateway API)
        if (!empty($signature)) {
            if (empty($this->webhookSecret)) {
                Log::warning('Webhook secret not configured but signature provided');
                return false;
            }

            // Tạo expected signature từ payload
            $expectedSignature = hash_hmac('sha256', json_encode($payload), $this->webhookSecret);

            // So sánh signature
            if (hash_equals($expectedSignature, $signature)) {
                Log::info('Webhook validated using signature');
                return true;
            }
            
            Log::warning('Invalid signature', [
                'signature_length' => strlen($signature),
            ]);
            return false;
        }

        if (empty($this->webhookSecret)) {
            Log::warning('No signature or Bearer token provided, and webhook secret not configured. Allowing request (development mode)');
            return true; // Cho phép trong development mode
        }

        Log::warning('No signature or Bearer token provided');
        return false;
    }


    public function parseTransferContent(string $content): ?string
    {
        $prefix = config('sepay.transfer_content_prefix', 'NAPTIEN');
        
        $pattern1 = '/^' . preg_quote($prefix, '/') . '\s+([A-Za-z0-9_-]+)/i';
        if (preg_match($pattern1, $content, $matches)) {
            $code = trim($matches[1]);
            // Kiểm tra nếu không phải format cũ (user_id_XXX)
            if (!preg_match('/^user_id_\d+$/i', $code)) {
                return $code;
            }
        }
        
        $pattern2 = '/^' . preg_quote($prefix, '/') . '([A-Z0-9]{6,20})(?:\s|$)/i';
        if (preg_match($pattern2, $content, $matches)) {
            $code = $matches[1];
            // Kiểm tra nếu không phải format cũ (userid hoặc user_id)
            if (!preg_match('/^userid\d+$/i', $code) && !preg_match('/^user_id_\d+$/i', $code)) {
                return $code;
            }
        }
        
        $pattern3 = '/^' . preg_quote($prefix, '/') . 'userid(\d+)/i';
        if (preg_match($pattern3, $content, $matches)) {
            // Trả về null để xử lý theo cách cũ (parse user_id)
            return null;
        }
        
        $pattern4 = '/^' . preg_quote($prefix, '/') . 'user_id_(\d+)/i';
        if (preg_match($pattern4, $content, $matches)) {
            // Trả về null để xử lý theo cách cũ (parse user_id)
            return null;
        }
        
        $pattern5 = '/^' . preg_quote($prefix, '/') . '\s+user_id_(\d+)/i';
        if (preg_match($pattern5, $content, $matches)) {
            // Trả về null để xử lý theo cách cũ (parse user_id)
            return null;
        }

        Log::warning('Failed to parse deposit_code from transfer content', [
            'content' => $content,
            'prefix' => $prefix,
        ]);

        return null;
    }

    public function parseUserIdFromTransferContent(string $content): ?int
    {
        $prefix = config('sepay.transfer_content_prefix', 'NAPTIEN');
        
        // Pattern 1: "NAPTIEN user_id_123" (có space)
        $pattern1 = '/^' . preg_quote($prefix, '/') . '\s+user_id_(\d+)/i';
        if (preg_match($pattern1, $content, $matches)) {
            return (int) $matches[1];
        }
        
        // Pattern 2: "NAPTIENuserid2" (không có space, không có underscore)
        $pattern2 = '/^' . preg_quote($prefix, '/') . 'userid(\d+)/i';
        if (preg_match($pattern2, $content, $matches)) {
            return (int) $matches[1];
        }
        
        // Pattern 3: "NAPTIENuser_id_2" (không có space, có underscore)
        $pattern3 = '/^' . preg_quote($prefix, '/') . 'user_id_(\d+)/i';
        if (preg_match($pattern3, $content, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }

    /**
     * Xử lý logic nạp tiền từ webhook/IPN
     */
    public function processDeposit(array $webhookData): array
    {
        try {

            $amount = $webhookData['transferAmount'] 
                   ?? $webhookData['amount'] 
                   ?? $webhookData['order_amount'] 
                   ?? $webhookData['transaction_amount']
                   ?? null;
            
            $transferContent = $webhookData['content']
                            ?? $webhookData['transfer_content']
                            ?? $webhookData['order_description']
                            ?? $webhookData['description']
                            ?? null;
            
            $transactionId = $webhookData['referenceCode']
                          ?? $webhookData['id']
                          ?? $webhookData['transaction_id']
                          ?? $webhookData['order_invoice_number']
                          ?? $webhookData['order_id']
                          ?? $webhookData['invoice_number']
                          ?? null;
            
            if (empty($transferContent)) {
                Log::warning('IPN missing transfer_content', ['data' => $webhookData]);
                return [
                    'success' => false,
                    'error' => 'Transfer content is required',
                ];
            }

            if (empty($amount) || $amount <= 0) {
                Log::warning('IPN missing or invalid amount', ['data' => $webhookData]);
                return [
                    'success' => false,
                    'error' => 'Invalid amount',
                ];
            }

            $depositCode = $this->parseTransferContent($transferContent);
            
            $userId = null;
            if (!$depositCode) {
                $userId = $this->parseUserIdFromTransferContent($transferContent);
                if (!$userId) {
                    Log::warning('Could not parse deposit_code or user_id from transfer content', [
                        'transfer_content' => $transferContent,
                        'data' => $webhookData,
                    ]);
                    return [
                        'success' => false,
                        'error' => 'Could not parse deposit_code or user_id from transfer content',
                    ];
                }
                
                $user = User::find($userId);
                
                if (!$user) {
                    return [
                        'success' => false,
                        'error' => 'User not found',
                    ];
                }
                
                $pendingTransaction = Transaction::where('user_id', $userId)
                    ->where('amount', $amount)
                    ->where('status', 'pending')
                    ->where('type', 'deposit')
                    ->orderBy('created_at', 'desc')
                    ->first();
            } else {
                $pendingTransaction = Transaction::where('deposit_code', $depositCode)
                    ->where('amount', $amount)
                    ->where('status', 'pending')
                    ->where('type', 'deposit')
                    ->orderBy('created_at', 'desc')
                    ->first();
                
                if ($pendingTransaction) {
                    $userId = $pendingTransaction->user_id;
                    $user = User::find($userId);
                    
                    if (!$user) {
                        return [
                            'success' => false,
                            'error' => 'User not found',
                        ];
                    }
                } else {
                    Log::warning('Could not find transaction with deposit_code', [
                        'deposit_code' => $depositCode,
                        'transfer_content' => $transferContent,
                        'amount' => $amount,
                    ]);
                    return [
                        'success' => false,
                        'error' => 'Transaction not found with deposit_code',
                    ];
                }
            }
            
            if ($pendingTransaction) {
                Log::info('Found pending transaction, updating status', [
                    'transaction_id' => $pendingTransaction->id,
                    'old_status' => $pendingTransaction->status,
                    'sepay_reference_code' => $transactionId,
                ]);
                
                return DB::transaction(function () use ($user, $webhookData, $userId, $transactionId, $amount, $transferContent, $pendingTransaction) {
                    // Cập nhật transaction
                    $metadata = $pendingTransaction->metadata ?? [];
                    $metadata['sepay_reference_code'] = $transactionId;
                    $metadata['webhook_data'] = $webhookData;
                    $metadata['completed_at'] = now()->toDateTimeString();
                    
                    $pendingTransaction->update([
                        'status' => 'completed',
                        'transaction_id' => $transactionId ?? $pendingTransaction->transaction_id,
                        'metadata' => $metadata,
                        
                    ]);
                    
                    if (!isset($metadata['balance_updated'])) {
                        $user->incrementBalance($amount);
                        $pendingTransaction->update([
                            'metadata' => array_merge($metadata, ['balance_updated' => true]),
                        ]);
                    }
                    
                    Log::info('Pending transaction updated to completed', [
                        'transaction_id' => $pendingTransaction->id,
                        'user_id' => $userId,
                        'amount' => $amount,
                        'sepay_reference_code' => $transactionId,
                    ]);
                    
                    return [
                        'success' => true,
                        'message' => 'Transaction status updated successfully',
                        'transaction' => $pendingTransaction->fresh(),
                    ];
                });
            }
            

            if ($transactionId) {
                $existingTransaction = Transaction::where('user_id', $userId)
                    ->where(function ($query) use ($transactionId) {
                        $query->where('transaction_id', $transactionId)
                              ->orWhere('metadata->sepay_reference_code', $transactionId);
                    })
                    ->where('status', 'completed')
                    ->first();
                
                if ($existingTransaction) {
                    Log::info('Duplicate transaction detected (already completed by referenceCode)', [
                        'transaction_id' => $transactionId,
                        'user_id' => $userId,
                        'existing_transaction_id' => $existingTransaction->id,
                    ]);

                    return [
                        'success' => true,
                        'message' => 'Transaction already processed',
                        'transaction' => $existingTransaction,
                    ];
                }
            }
            
            // Kiểm tra duplicate theo user_id và amount (nếu đã completed)
            // Không cần match transfer_content vì webhook có thể gửi content khác
            $existingCompleted = Transaction::where('user_id', $userId)
                ->where('amount', $amount)
                ->where('status', 'completed')
                ->where('type', 'deposit')
                ->where('created_at', '>=', now()->subHours(24)) // Chỉ kiểm tra trong 24h gần đây
                ->orderBy('created_at', 'desc')
                ->first();
            
            if ($existingCompleted) {
                Log::info('Duplicate transaction detected (same user_id and amount already completed)', [
                    'user_id' => $userId,
                    'amount' => $amount,
                    'existing_transaction_id' => $existingCompleted->id,
                    'existing_transfer_content' => $existingCompleted->transfer_content,
                    'webhook_transfer_content' => $transferContent,
                ]);

                return [
                    'success' => true,
                    'message' => 'Transaction already processed',
                    'transaction' => $existingCompleted,
                ];
            }

            // Kiểm tra order_status nếu có (chỉ xử lý khi thành công)
            // Format Bank API: transferType = "in" là thành công
            $orderStatus = $webhookData['order_status'] 
                        ?? $webhookData['status'] 
                        ?? ($webhookData['transferType'] === 'in' ? 'completed' : null)
                        ?? 'completed';
            
            // Nếu có transferType, chỉ xử lý khi là "in" (tiền vào)
            if (isset($webhookData['transferType']) && $webhookData['transferType'] !== 'in') {
                Log::info('IPN received but transferType is not "in"', [
                    'transfer_type' => $webhookData['transferType'],
                    'transaction_id' => $transactionId,
                ]);
                
                return [
                    'success' => false,
                    'error' => 'Transfer type is not incoming',
                    'transfer_type' => $webhookData['transferType'],
                ];
            }
            
            // Kiểm tra order_status nếu không có transferType
            if (!isset($webhookData['transferType']) && !in_array(strtolower($orderStatus), ['completed', 'captured', 'success', 'paid'])) {
                Log::info('IPN received but order status is not completed', [
                    'order_status' => $orderStatus,
                    'transaction_id' => $transactionId,
                ]);
                
                return [
                    'success' => false,
                    'error' => 'Order status is not completed',
                    'order_status' => $orderStatus,
                ];
            }

            // Sử dụng database transaction để đảm bảo tính nhất quán
            return DB::transaction(function () use ($user, $webhookData, $userId, $transactionId, $amount, $transferContent) {
                // Tạo transaction record
                $transaction = Transaction::create([
                    'user_id' => $userId,
                    'amount' => $amount,
                    'type' => 'deposit',
                    'status' => 'completed',
                    'transfer_content' => $transferContent,
                    'transaction_id' => $transactionId,
                    'metadata' => $webhookData,
                ]);

                // Cập nhật balance
                $user->incrementBalance($amount);

                Log::info('Deposit processed successfully from IPN', [
                    'user_id' => $userId,
                    'amount' => $amount,
                    'transaction_id' => $transaction->id,
                    'sepay_transaction_id' => $transactionId,
                ]);

                return [
                    'success' => true,
                    'message' => 'Deposit processed successfully',
                    'transaction' => $transaction,
                ];
            });
        } catch (\Exception $e) {
            Log::error('Error processing deposit', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'webhook_data' => $webhookData,
            ]);

            return [
                'success' => false,
                'error' => 'Failed to process deposit',
                'message' => $e->getMessage(),
            ];
        }
    }
}
