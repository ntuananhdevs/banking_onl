<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Services\SepayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DepositController extends Controller
{
    protected SepayService $sepayService;

    public function __construct(SepayService $sepayService)
    {
        $this->sepayService = $sepayService;
    }

    /**
     * Hiển thị trang nạp tiền với form và lịch sử giao dịch
     */
    public function index()
    {
        $user = Auth::user();
        
        // Lấy 10 giao dịch gần nhất
        $recentTransactions = Transaction::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return view('deposit.index', [
            'user' => $user,
            'recentTransactions' => $recentTransactions,
        ]);
    }

    /**
     * Xử lý tạo yêu cầu nạp tiền
     */
    public function create(Request $request)
    {
        $request->validate([
            'amount' => 'required|integer|min:1000|max:100000000',
        ], [
            'amount.required' => 'Vui lòng nhập số tiền nạp',
            'amount.integer' => 'Số tiền phải là số nguyên',
            'amount.min' => 'Số tiền tối thiểu là 1,000 VNĐ',
            'amount.max' => 'Số tiền tối đa là 100,000,000 VNĐ',
        ]);

        $user = Auth::user();
        $amount = (int) $request->input('amount');

        try {
            // Tạo mã hóa đơn unique
            $orderInvoiceNumber = 'DEPOSIT_' . $user->id . '_' . time() . '_' . Str::random(6);
            
            // Tạo deposit_code unique (format: uppercase alphanumeric, 8-12 ký tự)
            $depositCode = strtoupper(Str::random(10));

            // Tạo transaction record với status pending
            $transaction = Transaction::create([
                'user_id' => $user->id,
                'deposit_code' => $depositCode,
                'amount' => $amount,
                'type' => 'deposit',
                'status' => 'pending',
                'transfer_content' => $this->sepayService->generateTransferContent($depositCode),
                'transaction_id' => $orderInvoiceNumber,
                'metadata' => [
                    'created_via' => 'web',
                    'ip_address' => $request->ip(),
                ],
            ]);

            // Tạo checkout với SePay
            $checkoutResult = $this->sepayService->createBankTransferCheckout(
                $amount,
                $orderInvoiceNumber,
                $depositCode
            );

            // Lưu thông tin checkout và QR code vào metadata
            $metadata = $transaction->metadata ?? [];
            $metadata['sepay_checkout_url'] = $checkoutResult['checkout_url'];
            $metadata['sepay_form_fields'] = $checkoutResult['form_fields'];
            
            // Tạo QR code ngay nếu có thông tin ngân hàng từ SePay User API
            $bankInfo = $this->sepayService->getBankAccountDetails();
            
            // Tạo QR code nếu có thông tin ngân hàng
            if ($bankInfo) {
                $accountNumber = $bankInfo['account_number'] ?? null;
                $bankName = $bankInfo['bank_name'] ?? $bankInfo['bank_code'] ?? null;
                
                if ($accountNumber && $bankName) {
                    $qrCodeUrl = $this->sepayService->generateQrCodeUrl(
                        $amount,
                        $checkoutResult['transfer_content'],
                        $accountNumber,
                        $bankName
                    );
                    
                    if ($qrCodeUrl) {
                        $metadata['qr_code_url'] = $qrCodeUrl;
                        $metadata['bank_info'] = $bankInfo;
                        
                        Log::info('QR code generated', [
                            'transaction_id' => $transaction->id,
                            'qr_code_url' => $qrCodeUrl,
                            'source' => 'SePay User API',
                        ]);
                    }
                }
            }
            
            $transaction->update(['metadata' => $metadata]);

            Log::info('Deposit request created', [
                'transaction_id' => $transaction->id,
                'user_id' => $user->id,
                'amount' => $amount,
                'order_invoice_number' => $orderInvoiceNumber,
            ]);

            // Redirect đến trang chi tiết giao dịch
            return redirect()->route('deposit.show', ['deposit_code' => $transaction->deposit_code])
                ->with('success', 'Yêu cầu nạp tiền đã được tạo thành công. Vui lòng quét QR code để thanh toán.');

        } catch (\Exception $e) {
            Log::error('Failed to create deposit request', [
                'user_id' => $user->id,
                'amount' => $amount,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()
                ->withInput()
                ->withErrors(['error' => 'Không thể tạo yêu cầu nạp tiền. Vui lòng thử lại sau. ' . $e->getMessage()]);
        }
    }

    /**
     * Hiển thị chi tiết giao dịch với QR code
     */
    public function show($depositCode)
    {
        $user = Auth::user();
        
        $transaction = Transaction::where('deposit_code', $depositCode)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $qrCodeUrl = null;
        $bankInfo = null;

        // Lấy từ metadata trước
        $metadata = $transaction->metadata ?? [];
        $qrCodeUrl = $metadata['qr_code_url'] ?? null;
        $bankInfo = $metadata['bank_info'] ?? null;

        // Nếu chưa có QR code và transaction đang pending, thử tạo từ SePay User API
        if (!$qrCodeUrl && $transaction->status === 'pending') {
            // Lấy từ SePay User API
            if (!$bankInfo) {
                $bankInfo = $this->sepayService->getBankAccountDetails();
            }
            
            // Tạo QR code nếu có thông tin ngân hàng
            if ($bankInfo && !$qrCodeUrl) {
                $accountNumber = $bankInfo['account_number'] ?? null;
                $bankName = $bankInfo['bank_name'] ?? $bankInfo['bank_code'] ?? null;
                
                if ($accountNumber && $bankName) {
                    $qrCodeUrl = $this->sepayService->generateQrCodeUrl(
                        (int) $transaction->amount,
                        $transaction->transfer_content,
                        $accountNumber,
                        $bankName
                    );
                    
                    if ($qrCodeUrl) {
                        $metadata['qr_code_url'] = $qrCodeUrl;
                        $metadata['bank_info'] = $bankInfo;
                        $transaction->update(['metadata' => $metadata]);
                        
                        Log::info('QR code generated in show method', [
                            'transaction_id' => $transaction->id,
                            'source' => 'SePay User API',
                        ]);
                    }
                }
            }
            
            // Nếu vẫn chưa có, thử lấy từ SePay Order API
            if (!$qrCodeUrl) {
                try {
                $orderDetails = $this->sepayService->getOrderDetails($transaction->transaction_id);
                
                if ($orderDetails) {
                    // Thử lấy QR code URL từ API
                    if (!$qrCodeUrl) {
                        $qrCodeUrl = $this->sepayService->extractQrCodeUrl($orderDetails);
                    }
                    
                    // Thử lấy thông tin ngân hàng từ API
                    if (!$bankInfo) {
                        $bankInfo = $this->sepayService->extractBankInfo($orderDetails);
                    }
                    
                    // Nếu có thông tin ngân hàng nhưng chưa có QR code, tạo QR code từ thông tin đó
                    if ($bankInfo && !$qrCodeUrl) {
                        $accountNumber = $bankInfo['account_number'] ?? null;
                        $bankName = $bankInfo['bank_name'] ?? $bankInfo['bank_code'] ?? null;
                        
                        if ($accountNumber && $bankName) {
                            $qrCodeUrl = $this->sepayService->generateQrCodeUrl(
                                (int) $transaction->amount,
                                $transaction->transfer_content,
                                $accountNumber,
                                $bankName
                            );
                        }
                    }
                    
                    // Cập nhật metadata nếu có thông tin mới
                    if ($qrCodeUrl || $bankInfo) {
                        $metadata = $transaction->metadata ?? [];
                        if ($qrCodeUrl) {
                            $metadata['qr_code_url'] = $qrCodeUrl;
                        }
                        if ($bankInfo) {
                            $metadata['bank_info'] = $bankInfo;
                        }
                        $transaction->update(['metadata' => $metadata]);
                    }
                }
                } catch (\Exception $e) {
                    Log::warning('Failed to get order details from SePay', [
                        'transaction_id' => $transaction->id,
                        'order_invoice_number' => $transaction->transaction_id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        // Nếu vẫn chưa có QR code, tạo checkout URL để redirect hoặc embed
        $checkoutUrl = null;
        if (!$qrCodeUrl && $transaction->status === 'pending') {
            $metadata = $transaction->metadata ?? [];
            $checkoutUrl = $metadata['sepay_checkout_url'] ?? null;
        }

        return view('deposit.show', [
            'transaction' => $transaction,
            'qrCodeUrl' => $qrCodeUrl,
            'bankInfo' => $bankInfo,
            'checkoutUrl' => $checkoutUrl,
        ]);
    }

}
