<?php

use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

// Webhook endpoint (public, no authentication required)
// CSRF protection is disabled for this route
Route::post('/webhooks/sepay', [WebhookController::class, 'handle'])->name('webhooks.sepay')->withoutMiddleware(['csrf']);
