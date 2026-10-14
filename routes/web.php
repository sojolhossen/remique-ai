<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ReminderController;
use App\Http\Controllers\SettingsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes - Remi AI Reminder SaaS
|--------------------------------------------------------------------------
*/

// Public landing & Guest Authentication Routes
Route::get('/', function () {
    return auth()->check() ? redirect()->route('dashboard') : view('welcome');
})->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');

    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.post');

    Route::get('/forgot-password', [PasswordResetController::class, 'showLinkRequestForm'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLinkEmail'])->middleware('throttle:5,1')->name('password.email');

    Route::get('/reset-password/{token}', [PasswordResetController::class, 'showResetForm'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'reset'])->middleware('throttle:5,1')->name('password.update');
});

// Authenticated SaaS Dashboard & Reminder Routes
Route::middleware(['auth'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Dashboard Overview
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Reminders Management
    Route::get('/reminders', [ReminderController::class, 'index'])->name('reminders.index');
    Route::get('/reminders/create', [ReminderController::class, 'create'])->name('reminders.create');
    Route::post('/reminders', [ReminderController::class, 'store'])->name('reminders.store');
    Route::post('/reminders/ai-interpret', [ReminderController::class, 'interpretAi'])->middleware('throttle:30,1')->name('reminders.ai-interpret');

    Route::get('/reminders/{reminder}/edit', [ReminderController::class, 'edit'])->name('reminders.edit');
    Route::put('/reminders/{reminder}', [ReminderController::class, 'update'])->name('reminders.update');
    Route::delete('/reminders/{reminder}', [ReminderController::class, 'destroy'])->name('reminders.destroy');

    // State Transitions
    Route::post('/reminders/{reminder}/complete', [ReminderController::class, 'complete'])->name('reminders.complete');
    Route::post('/reminders/{reminder}/cancel', [ReminderController::class, 'cancel'])->name('reminders.cancel');
    Route::post('/reminders/{reminder}/reopen', [ReminderController::class, 'reopen'])->name('reminders.reopen');

    // Settings & Profile
    Route::get('/settings', [SettingsController::class, 'edit'])->name('settings.edit');
    Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');
    Route::put('/settings/password', [SettingsController::class, 'updatePassword'])->name('settings.password');
});

/*
|--------------------------------------------------------------------------
| Official Meta WhatsApp Cloud API Webhook
|--------------------------------------------------------------------------
*/
Route::get('/whatsapp/webhook', [\App\Http\Controllers\WhatsApp\WhatsAppWebhookController::class, 'verify'])->name('whatsapp.webhook.verify');
Route::post('/whatsapp/webhook', [\App\Http\Controllers\WhatsApp\WhatsAppWebhookController::class, 'handle'])->middleware('throttle:120,1')->name('whatsapp.webhook.handle');

/*
|--------------------------------------------------------------------------
| Telegram Bot API Webhook
|--------------------------------------------------------------------------
*/
Route::post('/telegram/webhook', [\App\Http\Controllers\TelegramWebhookController::class, 'handle'])->middleware('throttle:120,1')->name('telegram.webhook');

/*
|--------------------------------------------------------------------------
| Enterprise Super Admin Dashboard Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'super_admin'])->prefix('admin')->name('admin.')->group(function () {
    // Overview
    Route::get('/', [\App\Http\Controllers\Admin\AdminDashboardController::class, 'index'])->name('dashboard');

    // Customer Management & Authorized Numbers
    Route::get('/customers', [\App\Http\Controllers\Admin\CustomerManagementController::class, 'index'])->name('customers.index');
    Route::get('/customers/create', [\App\Http\Controllers\Admin\CustomerManagementController::class, 'create'])->name('customers.create');
    Route::post('/customers', [\App\Http\Controllers\Admin\CustomerManagementController::class, 'store'])->name('customers.store');
    Route::get('/customers/{customer}', [\App\Http\Controllers\Admin\CustomerManagementController::class, 'show'])->name('customers.show');
    Route::post('/customers/{customer}/link-whatsapp', [\App\Http\Controllers\Admin\CustomerManagementController::class, 'linkWhatsApp'])->name('customers.link-whatsapp');
    Route::post('/customers/{customer}/status', [\App\Http\Controllers\Admin\CustomerManagementController::class, 'updateStatus'])->name('customers.status');
    Route::post('/identities/{identity}/manual-verify', [\App\Http\Controllers\Admin\CustomerManagementController::class, 'manualVerify'])->name('customers.manual-verify');
    Route::post('/identities/{identity}/revoke', [\App\Http\Controllers\Admin\CustomerManagementController::class, 'revokeIdentity'])->name('customers.revoke-identity');

    // WhatsApp Cloud API Management
    Route::get('/whatsapp', [\App\Http\Controllers\Admin\WhatsAppManagementController::class, 'index'])->name('whatsapp.index');
    Route::post('/whatsapp/test-connectivity', [\App\Http\Controllers\Admin\WhatsAppManagementController::class, 'testConnectivity'])->name('whatsapp.test-connectivity');
    Route::post('/whatsapp/retry/{attempt}', [\App\Http\Controllers\Admin\WhatsAppManagementController::class, 'retryDelivery'])->name('whatsapp.retry');

    // Reminder Oversight & Queue
    Route::get('/reminders', [\App\Http\Controllers\Admin\ReminderOversightController::class, 'index'])->name('reminders.index');
    Route::post('/reminders/{reminder}/reschedule', [\App\Http\Controllers\Admin\ReminderOversightController::class, 'reschedule'])->name('reminders.reschedule');
    Route::post('/reminders/{reminder}/cancel', [\App\Http\Controllers\Admin\ReminderOversightController::class, 'cancel'])->name('reminders.cancel');

    // Memory Vault Governance
    Route::get('/vault', [\App\Http\Controllers\Admin\VaultOversightController::class, 'index'])->name('vault.index');
    Route::delete('/vault/{file}', [\App\Http\Controllers\Admin\VaultOversightController::class, 'deleteFile'])->name('vault.delete-file');

    // AI Engine (Gemini & NVIDIA NIM) Settings, Switching & Playground
    Route::get('/ai', [\App\Http\Controllers\Admin\AiSettingsController::class, 'index'])->name('ai.index');
    Route::post('/ai/settings', [\App\Http\Controllers\Admin\AiSettingsController::class, 'updateSettings'])->name('ai.update-settings');
    Route::post('/ai/test-model', [\App\Http\Controllers\Admin\AiSettingsController::class, 'testModel'])->name('ai.test-model');
    Route::post('/ai/playground', [\App\Http\Controllers\Admin\AiSettingsController::class, 'testPlayground'])->name('ai.test-playground');

    // System Environment & Maintenance
    Route::get('/system', [\App\Http\Controllers\Admin\SystemSettingsController::class, 'index'])->name('system.index');
    Route::post('/system/clear-cache', [\App\Http\Controllers\Admin\SystemSettingsController::class, 'clearCache'])->name('system.clear-cache');

    // Security & Audit Logs
    Route::get('/audit-logs', [\App\Http\Controllers\Admin\AuditLogController::class, 'index'])->name('audit.index');
});

/*
|--------------------------------------------------------------------------
| Automated Deployment & Remote Database Migration Webhook
|--------------------------------------------------------------------------
| Triggered by GitHub Actions after FTP sync or opened directly with ?token=
| Executes 'php artisan migrate --force' and clears framework caches.
*/
Route::match(['get', 'post'], '/deploy/migrate', function (\Illuminate\Http\Request $request) {
    $token = $request->input('token') ?? $request->header('X-Deploy-Token');
    $secret = config('app.deploy_token') ?: env('DEPLOY_TOKEN') ?: env('APP_KEY');

    if (empty($token) || empty($secret) || ! hash_equals((string) $secret, (string) $token)) {
        return response()->json([
            'status' => 'error',
            'message' => 'Unauthorized deploy token. Please configure DEPLOY_TOKEN in your .env or pass valid token.',
        ], 403);
    }

    try {
        \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
        $migrateOutput = \Illuminate\Support\Facades\Artisan::output();

        \Illuminate\Support\Facades\Artisan::call('optimize:clear');

        return response()->json([
            'status' => 'success',
            'message' => 'Database migrations completed successfully!',
            'database' => config('database.connections.mysql.database'),
            'tables_migrated' => trim($migrateOutput),
            'timestamp' => now()->toIso8601String(),
        ]);
    } catch (\Throwable $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Migration failed: ' . $e->getMessage(),
        ], 500);
    }
})->name('deploy.migrate');

