<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class TransactionController extends Controller
{
    /**
     * Hiển thị trang log giao dịch
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        // Lấy các filter từ request
        $type = $request->get('type'); // deposit, withdrawal, transfer
        $status = $request->get('status'); // pending, completed, failed
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');
        $search = $request->get('search'); // Tìm theo transaction_id hoặc transfer_content
        
        // Query builder
        $query = Transaction::where('user_id', $user->id);
        
        // Filter theo type
        if ($type && in_array($type, ['deposit', 'withdrawal', 'transfer'])) {
            $query->where('type', $type);
        }
        
        // Filter theo status
        if ($status && in_array($status, ['pending', 'completed', 'failed'])) {
            $query->where('status', $status);
        }
        
        // Filter theo date range
        if ($dateFrom) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }
        
        if ($dateTo) {
            $query->whereDate('created_at', '<=', $dateTo);
        }
        
        // Search theo transaction_id hoặc transfer_content
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('transaction_id', 'LIKE', "%{$search}%")
                  ->orWhere('transfer_content', 'LIKE', "%{$search}%");
            });
        }
        
        // Sắp xếp theo thời gian mới nhất
        $query->orderBy('created_at', 'desc');
        
        // Pagination
        $transactions = $query->paginate(20)->withQueryString();
        
        // Thống kê
        $stats = [
            'total' => Transaction::where('user_id', $user->id)->count(),
            'deposit' => Transaction::where('user_id', $user->id)->where('type', 'deposit')->count(),
            'withdrawal' => Transaction::where('user_id', $user->id)->where('type', 'withdrawal')->count(),
            'pending' => Transaction::where('user_id', $user->id)->where('status', 'pending')->count(),
            'completed' => Transaction::where('user_id', $user->id)->where('status', 'completed')->count(),
        ];
        
        return view('transactions.index', [
            'transactions' => $transactions,
            'stats' => $stats,
            'filters' => [
                'type' => $type,
                'status' => $status,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'search' => $search,
            ],
        ]);
    }

    /**
     * API endpoint để check status của transaction
     * Sử dụng cho auto-refresh (polling)
     * URL: /transactions/status/{id}
     */
    public function checkStatus($id)
    {
        $user = Auth::user();
        
        // Validate transaction ID
        $transactionId = (int) $id;
        
        if (empty($transactionId) || $transactionId <= 0) {
            Log::warning('TransactionController::checkStatus - Invalid transaction ID', [
                'transaction_id' => $id,
                'user_id' => $user->id,
            ]);
            return response()->json([
                'success' => false,
                'error' => 'Invalid transaction ID',
                'transactions' => [],
            ], 400);
        }
        
        // Lấy thông tin mới nhất của transaction
        $transaction = Transaction::where('user_id', $user->id)
            ->where('id', $transactionId)
            ->first(['id', 'status', 'transaction_id', 'metadata', 'amount', 'type']);
        
        if (!$transaction) {
            Log::warning('TransactionController::checkStatus - Transaction not found', [
                'transaction_id' => $transactionId,
                'user_id' => $user->id,
            ]);
            return response()->json([
                'success' => false,
                'error' => 'Transaction not found',
                'transactions' => [],
            ], 404);
        }
        
        $transactionData = [
            'id' => $transaction->id,
            'status' => $transaction->status,
            'transaction_id' => $transaction->transaction_id,
            'metadata' => $transaction->metadata,
            'amount' => $transaction->amount,
            'type' => $transaction->type,
        ];
        
        Log::debug('TransactionController::checkStatus', [
            'user_id' => $user->id,
            'transaction_id' => $transactionId,
            'status' => $transaction->status,
        ]);
        
        return response()->json([
            'success' => true,
            'transactions' => [$transactionData],
        ]);
    }
}
