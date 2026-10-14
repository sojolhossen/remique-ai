<?php
/**
 * Remique AI Environment & Database Setup Wizard
 * Access via: https://ai.shebafind.site/setup-env.php
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

$base = dirname(__DIR__);
$envPath = $base . '/.env';
$message = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dbHost = trim($_POST['db_host'] ?? 'localhost');
    $dbName = trim($_POST['db_name'] ?? '');
    $dbUser = trim($_POST['db_user'] ?? '');
    $dbPass = trim($_POST['db_pass'] ?? '');

    // Test DB connection
    try {
        $dsn = "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4";
        $pdo = new PDO($dsn, $dbUser, $dbPass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

        // Generate base64 APP_KEY
        $appKey = 'base64:' . base64_encode(random_bytes(32));

        $envContent = <<<ENV
APP_NAME="Remi AI Reminder"
APP_ENV=production
APP_KEY={$appKey}
APP_DEBUG=true
APP_URL=https://ai.shebafind.site

APP_LOCALE=en
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=en_US

LOG_CHANNEL=stack
LOG_STACK=single
LOG_LEVEL=debug

DB_CONNECTION=mysql
DB_HOST={$dbHost}
DB_PORT=3306
DB_DATABASE={$dbName}
DB_USERNAME={$dbUser}
DB_PASSWORD="{$dbPass}"

SESSION_DRIVER=file
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null
SESSION_SECURE_COOKIE=true

BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local
QUEUE_CONNECTION=sync
CACHE_STORE=file

MAIL_MAILER=log
MAIL_FROM_ADDRESS="support@shebafind.site"
MAIL_FROM_NAME="Remique AI"

# Google Gemini AI Configuration
GEMINI_API_KEY=AIzaSyD2NN8Jo5m-9FYTNcmsIvzPODVPYlpD2jY
GEMINI_MODEL=gemini-2.5-flash
GEMINI_TIMEOUT=30
GEMINI_MAX_RETRIES=2
AI_DEFAULT_PROVIDER=gemini

# NVIDIA NIM AI Configuration
NVIDIA_NIM_API_KEY=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
NVIDIA_NIM_BASE_URL=https://integrate.api.nvidia.com/v1
NVIDIA_NIM_MODEL=openai/gpt-oss-20b
NVIDIA_NIM_TIMEOUT=30
NVIDIA_NIM_MAX_RETRIES=2

# Telegram Bot API Configuration
TELEGRAM_BOT_TOKEN=8996815344:AAFVZRvVx7LuDqrPvnjaNM735dgpG9chf2g
TELEGRAM_BOT_USERNAME=Remique_bot
TELEGRAM_WEBHOOK_SECRET=remique_telegram_secret_token_change_me
TELEGRAM_API_BASE_URL=https://api.telegram.org
TELEGRAM_TIMEOUT=15

DEPLOY_TOKEN=remi_deploy_secret_token_123
ENV;

        file_put_contents($envPath, $envContent);
        chmod($envPath, 0644);

        // Run migrations
        require $base . '/vendor/autoload.php';
        $app = require_once $base . '/bootstrap/app.php';
        $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

        \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
        $migrateOut = \Illuminate\Support\Facades\Artisan::output();

        \Illuminate\Support\Facades\Artisan::call('optimize:clear');

        $message = "🎉 SUCCESS! .env created and database migrations executed successfully!<br><pre style='background:#f4f4f4;padding:10px;margin-top:10px;'>" . htmlspecialchars($migrateOut) . "</pre>";

    } catch (\Throwable $e) {
        $error = "❌ Failed: " . $e->getMessage();
    }
}

$envExists = file_exists($envPath);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Remique AI Server Setup</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: system-ui, -apple-system, sans-serif; background: #0f172a; color: #f8fafc; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; padding: 20px; }
        .card { background: #1e293b; border: 1px solid #334155; border-radius: 12px; padding: 28px; width: 100%; max-width: 540px; box-shadow: 0 10px 25px rgba(0,0,0,0.5); }
        h1 { font-size: 20px; margin-top: 0; color: #38bdf8; display: flex; align-items: center; gap: 8px; }
        .status-badge { display: inline-block; padding: 4px 10px; border-radius: 9999px; font-size: 12px; font-weight: bold; margin-bottom: 16px; }
        .status-badge.missing { background: #ef4444; color: white; }
        .status-badge.present { background: #10b981; color: white; }
        .alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; }
        .alert-success { background: #064e3b; border: 1px solid #059669; color: #a7f3d0; }
        .alert-error { background: #7f1d1d; border: 1px solid #dc2626; color: #fecaca; }
        label { display: block; font-size: 13px; font-weight: 500; margin-bottom: 6px; color: #cbd5e1; }
        input[type="text"], input[type="password"] { width: 100%; box-sizing: border-box; padding: 10px 14px; background: #0f172a; border: 1px solid #475569; border-radius: 6px; color: white; margin-bottom: 16px; font-size: 14px; }
        input:focus { outline: none; border-color: #38bdf8; }
        button { width: 100%; padding: 12px; background: #2563eb; color: white; border: none; border-radius: 8px; font-size: 15px; font-weight: 600; cursor: pointer; transition: background 0.2s; }
        button:hover { background: #1d4ed8; }
        .hint { font-size: 12px; color: #94a3b8; margin-top: -10px; margin-bottom: 14px; }
        a { color: #38bdf8; text-decoration: none; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="card">
        <h1>⚙️ Remique AI cPanel Setup Wizard</h1>
        
        <?php if ($envExists): ?>
            <span class="status-badge present">✓ .env file exists</span>
        <?php else: ?>
            <span class="status-badge missing">⚠️ .env file missing on server</span>
        <?php endif; ?>

        <?php if ($message): ?>
            <div class="alert alert-success"><?= $message ?></div>
            <p><a href="/">👉 Click here to open Remique Dashboard</a></p>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <label>MySQL Host</label>
            <input type="text" name="db_host" value="localhost" required>
            
            <label>cPanel MySQL Database Name</label>
            <input type="text" name="db_name" value="shebafin_remi" placeholder="e.g. shebafin_remi" required>
            
            <label>cPanel MySQL User</label>
            <input type="text" name="db_user" value="shebafin_remi" placeholder="e.g. shebafin_remi" required>
            
            <label>cPanel MySQL Password</label>
            <input type="password" name="db_pass" placeholder="Enter your cPanel DB user password" required>
            <div class="hint">Enter the password you created for this MySQL user in cPanel.</div>

            <button type="submit">💾 Save .env & Run Migrations</button>
        </form>
    </div>
</body>
</html>
