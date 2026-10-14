<?php
/**
 * Auto-link Telegram chat ID to live user account
 * URL: https://ai.shebafind.site/public/sync-telegram.php
 */

header('Content-Type: text/html; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', '1');

$base = dirname(__DIR__);

require $base . '/vendor/autoload.php';
$app = require_once $base . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\TelegramIdentity;
use Carbon\Carbon;

echo "<!DOCTYPE html><html><head><title>Telegram Account Sync</title>";
echo "<style>body{background:#0f172a;color:#f8fafc;font-family:sans-serif;padding:30px;} pre{background:#1e293b;padding:15px;border-radius:8px;border:1px solid #334155;color:#38bdf8;}</style></head><body>";
echo "<h2>🔗 Linking Telegram to Live Database...</h2>";

try {
    $user = User::first();
    if (! $user) {
        die("<p style='color:#f87171;'>No user found in live database. Please register an account first.</p>");
    }

    $chatId = '5443234556';
    $username = 'SOJOL_DEV';

    $identity = TelegramIdentity::updateOrCreate(
        ['chat_id' => $chatId],
        [
            'user_id' => $user->id,
            'username' => $username,
            'first_name' => 'Sojol',
            'status' => 'active',
            'verified_at' => Carbon::now(),
            'last_inbound_at' => Carbon::now(),
        ]
    );

    echo "<p style='color:#4ade80;'><b>✓ Successfully linked Telegram Account!</b></p>";
    echo "<pre>User: {$user->email} (ID: {$user->id})\nTelegram Chat ID: {$chatId}\nUsername: @{$username}\nStatus: ACTIVE</pre>";
    echo "<p>Now send any message to @Remique_bot on Telegram — it will respond from the live server!</p>";
} catch (\Throwable $e) {
    echo "<p style='color:#f87171;'><b>Error: " . htmlspecialchars($e->getMessage()) . "</b></p>";
}

echo "</body></html>";
