<?php
/**
 * Standalone Remote Migration Webhook for Shared Hosting & FTP Deployments.
 * Can be triggered via curl by GitHub Actions or visited with ?token=
 */

header('Content-Type: application/json; charset=utf-8');

define('LARAVEL_START', microtime(true));

// Autoloader check
if (! file_exists(__DIR__ . '/../vendor/autoload.php')) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Composer vendor/autoload.php not found. Please ensure vendor is deployed or composer install is run.',
    ]);
    exit;
}

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

// Read token
$token = $_GET['token'] ?? $_POST['token'] ?? $_SERVER['HTTP_X_DEPLOY_TOKEN'] ?? null;
$expectedToken = env('DEPLOY_TOKEN') ?: env('APP_KEY');

if (empty($token) || empty($expectedToken) || ! hash_equals((string) $expectedToken, (string) $token)) {
    http_response_code(403);
    echo json_encode([
        'status' => 'error',
        'message' => 'Unauthorized deploy token. Please configure DEPLOY_TOKEN in your .env file.',
    ]);
    exit;
}

try {
    \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
    $output = \Illuminate\Support\Facades\Artisan::output();

    \Illuminate\Support\Facades\Artisan::call('optimize:clear');

    echo json_encode([
        'status' => 'success',
        'message' => 'Database migrations executed successfully!',
        'database' => config('database.connections.mysql.database'),
        'details' => trim($output),
        'timestamp' => date('Y-m-d H:i:s'),
    ], JSON_PRETTY_PRINT);
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Migration failed: ' . $e->getMessage(),
    ]);
}
