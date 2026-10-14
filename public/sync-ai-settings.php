<?php
/**
 * Synchronize AI Engine settings on live production server.
 * URL: https://ai.shebafind.site/sync-ai-settings.php
 */

header('Content-Type: text/html; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', '1');

$base = dirname(__DIR__);

if (! file_exists($base . '/vendor/autoload.php')) {
    die("Composer vendor/autoload.php not found.");
}

require $base . '/vendor/autoload.php';
$app = require_once $base . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "<!DOCTYPE html><html><head><title>Sync AI Engine Settings</title>";
echo "<style>body{background:#0f172a;color:#f8fafc;font-family:sans-serif;padding:30px;} pre{background:#1e293b;padding:15px;border-radius:8px;border:1px solid #334155;color:#38bdf8;}</style></head><body>";
echo "<h2>⚡ Syncing AI Engine Settings on Production...</h2>";

try {
    $newKey = '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';
    $model = 'meta/llama-3.2-11b-vision-instruct';

    \App\Models\SystemSetting::set('ai_nvidia_api_key', $newKey);
    \App\Models\SystemSetting::set('ai_nvidia_model', $model);
    \App\Models\SystemSetting::set('ai_active_model', $model);
    \App\Models\SystemSetting::set('ai_active_provider', 'nvidia_nim');

    \Illuminate\Support\Facades\Artisan::call('optimize:clear');
    \Illuminate\Support\Facades\Artisan::call('config:clear');

    echo "<p style='color:#4ade80;'><b>✓ AI API Key updated to New Fast Key!</b></p>";
    echo "<p style='color:#4ade80;'><b>✓ Active Model updated to: " . htmlspecialchars($model) . "!</b></p>";
    echo "<p style='color:#4ade80;'><b>✓ Application cache cleared successfully!</b></p>";
    echo "<p><a href='/admin/ai' style='color:#38bdf8;'>Go to Admin AI Settings</a></p>";
} catch (\Throwable $e) {
    echo "<p style='color:#f87171;'><b>Error: " . htmlspecialchars($e->getMessage()) . "</b></p>";
}

echo "</body></html>";
