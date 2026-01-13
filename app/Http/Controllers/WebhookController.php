<?php

namespace App\Http\Controllers;

use App\Services\WebhookService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    protected WebhookService $webhookService;

    public function __construct(WebhookService $webhookService)
    {
        $this->webhookService = $webhookService;
    }

    /**
     * Nhận và xử lý webhook từ Sepay
     */
    public function handle(Request $request)
    {
        // Log webhook request để debug (trước khi xử lý)
        Log::info('Webhook received from Sepay', [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'headers' => $request->headers->all(),
            'payload' => $request->all(),
            'raw_content' => $request->getContent(),
        ]);

        try {

            // Lấy signature từ header (SePay có thể dùng các header khác nhau)
            $signature = $request->header('X-Sepay-Signature') 
                      ?? $request->header('X-Webhook-Signature')
                      ?? $request->header('X-SePay-Signature')
                      ?? $request->header('Signature')
                      ?? $request->input('signature');

            // Lấy Bearer token từ Authorization header (cho Bank API)
            $bearerToken = null;
            $authHeader = $request->header('Authorization');
            if ($authHeader && str_starts_with($authHeader, 'Bearer ')) {
                $bearerToken = substr($authHeader, 7); // Remove "Bearer " prefix
            }

            // Lấy payload
            $payload = $request->all();

            // Validate webhook signature hoặc Bearer token
            if (!$this->webhookService->validateWebhook($payload, $signature, $bearerToken)) {
                Log::warning('Invalid webhook signature', [
                    'signature' => $signature,
                    'payload' => $payload,
                ]);

                return response()->json([
                    'success' => false,
                    'error' => 'Invalid signature',
                ], 401);
            }

            // Xử lý deposit
            $result = $this->webhookService->processDeposit($payload);

            if ($result['success']) {
                Log::info('Webhook processed successfully', [
                    'transaction_id' => $result['transaction']->id ?? null,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Webhook processed successfully',
                ], 200);
            }

            Log::warning('Webhook processing failed', [
                'error' => $result['error'] ?? 'Unknown error',
                'payload' => $payload,
            ]);

            return response()->json([
                'success' => false,
                'error' => $result['error'] ?? 'Processing failed',
            ], 400);
        } catch (\Exception $e) {
            Log::error('Webhook exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'payload' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Internal server error',
            ], 500);
        }
    }
}
