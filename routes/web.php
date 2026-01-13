<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\DepositController;
use App\Http\Controllers\TransactionController;
use Illuminate\Support\Facades\Route;

// Authentication routes (guest only)
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
    Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'register']);
});

// All other routes require authentication
Route::middleware('auth')->group(function () {
    // Home page (deposit index)
    Route::get('/', [DepositController::class, 'index'])->name('home');
    
    // Deposit routes
    Route::get('/deposit', [DepositController::class, 'index'])->name('deposit.index');
    Route::post('/deposit', [DepositController::class, 'create'])->name('deposit.create');
    Route::get('/deposit/{id}', [DepositController::class, 'show'])->name('deposit.show');
    
    // Transaction log routes
    Route::get('/transactions', [TransactionController::class, 'index'])->name('transactions.index');
    Route::get('/transactions/status/{id}', [TransactionController::class, 'checkStatus'])->name('transactions.check-status');
    
    // Logout route
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
});

// Webhook endpoint (public, no authentication required)
// CSRF protection is disabled for this route
Route::post('/webhooks/sepay', [\App\Http\Controllers\WebhookController::class, 'handle'])
    ->name('webhooks.sepay')
    ->withoutMiddleware(['csrf']);
