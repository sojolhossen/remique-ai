<?php
header('Content-Type: text/plain; charset=utf-8');

error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "=== System Diagnostic ===\n";
echo "PHP Version: " . phpversion() . "\n";
echo "Doc Root: " . $_SERVER['DOCUMENT_ROOT'] . "\n";
echo "Current Dir: " . __DIR__ . "\n";

$base = dirname(__DIR__);
echo "Base Dir: " . $base . "\n";

echo "\n--- Checking Files ---\n";
echo ".env exists? " . (file_exists($base . '/.env') ? 'YES' : 'NO') . "\n";
echo "vendor/autoload.php exists? " . (file_exists($base . '/vendor/autoload.php') ? 'YES' : 'NO') . "\n";
echo "bootstrap/app.php exists? " . (file_exists($base . '/bootstrap/app.php') ? 'YES' : 'NO') . "\n";

echo "\n--- Storage permissions ---\n";
echo "storage writable? " . (is_writable($base . '/storage') ? 'YES' : 'NO') . "\n";
echo "storage/logs writable? " . (is_writable($base . '/storage/logs') ? 'YES' : 'NO') . "\n";
echo "bootstrap/cache writable? " . (is_writable($base . '/bootstrap/cache') ? 'YES' : 'NO') . "\n";

echo "\n--- Bootstrapping Laravel ---\n";
try {
    require $base . '/vendor/autoload.php';
    echo "✓ Composer autoload loaded successfully\n";

    $app = require_once $base . '/bootstrap/app.php';
    echo "✓ bootstrap/app.php loaded successfully\n";

    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    echo "✓ Http Kernel instantiated\n";

    // Test DB connection
    echo "\n--- DB Connection Test ---\n";
    \Illuminate\Support\Facades\DB::connection()->getPdo();
    echo "✓ DB connection successful to: " . config('database.connections.mysql.database') . "\n";

} catch (\Throwable $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "In " . $e->getFile() . " on line " . $e->getLine() . "\n";
    echo "\nStack Trace:\n" . $e->getTraceAsString() . "\n";
}

if (file_exists($base . '/storage/logs/laravel.log')) {
    echo "\n--- Last 20 lines of laravel.log ---\n";
    $lines = file($base . '/storage/logs/laravel.log');
    $lastLines = array_slice($lines, -20);
    echo implode('', $lastLines);
}
