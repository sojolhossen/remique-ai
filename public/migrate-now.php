<?php
/**
 * One-click migration and table check runner for cPanel deployments.
 * URL: https://ai.shebafind.site/migrate-now.php
 */

header('Content-Type: text/html; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', '1');

$base = dirname(__DIR__);

if (! file_exists($base . '/vendor/autoload.php')) {
    die("Composer vendor/autoload.php not found. Please check deployment.");
}

require $base . '/vendor/autoload.php';
$app = require_once $base . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "<!DOCTYPE html><html><head><title>Remique AI Migration Runner</title>";
echo "<style>body{background:#0f172a;color:#f8fafc;font-family:sans-serif;padding:30px;} pre{background:#1e293b;padding:15px;border-radius:8px;border:1px solid #334155;color:#38bdf8;}</style></head><body>";
echo "<h2>🚀 Executing Database Migrations...</h2>";

try {
    \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
    $out = \Illuminate\Support\Facades\Artisan::output();
    echo "<pre>" . htmlspecialchars($out) . "</pre>";

    \Illuminate\Support\Facades\Artisan::call('optimize:clear');
    echo "<p style='color:#4ade80;'><b>✓ Cache cleared and database updated successfully!</b></p>";
    echo "<p><a href='/public/admin/ai' style='color:#38bdf8;'>Go back to AI Engine Settings</a></p>";
} catch (\Throwable $e) {
    echo "<p style='color:#f87171;'><b>Error: " . htmlspecialchars($e->getMessage()) . "</b></p>";
}

echo "</body></html>";
