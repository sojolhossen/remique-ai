<?php
/**
 * Direct Migration Runner for cPanel
 * Visit: https://ai.shebafind.site/run-migrate.php
 */

header('Content-Type: text/plain; charset=utf-8');

error_reporting(E_ALL);
ini_set('display_errors', '1');

$base = dirname(__DIR__);

echo "=== Running Laravel Database Migrations ===\n\n";

try {
    require $base . '/vendor/autoload.php';
    $app = require_once $base . '/bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();

    echo "Running: php artisan migrate --force...\n\n";
    \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
    $migrateOut = \Illuminate\Support\Facades\Artisan::output();
    echo $migrateOut . "\n";

    echo "--- Migration Status ---\n";
    \Illuminate\Support\Facades\Artisan::call('migrate:status');
    echo \Illuminate\Support\Facades\Artisan::output() . "\n";
    try {
        \Illuminate\Support\Facades\Artisan::call('db:seed', ['--force' => true]);
        echo \Illuminate\Support\Facades\Artisan::output();
    } catch (\Throwable $se) {
        echo "Seeder notice: " . $se->getMessage() . "\n";
    }

    echo "\nClearing caches...\n";
    \Illuminate\Support\Facades\Artisan::call('optimize:clear');
    echo \Illuminate\Support\Facades\Artisan::output();

    echo "\n======================================================\n";
    echo "🎉 SUCCESS! All tables have been created successfully!\n";
    echo "You can now visit: https://ai.shebafind.site\n";
    echo "======================================================\n";

} catch (\Throwable $e) {
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
}
