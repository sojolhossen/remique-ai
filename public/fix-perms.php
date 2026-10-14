<?php
/**
 * Auto-fix file & directory permissions on cPanel / shared hosting.
 * Visit: https://ai.shebafind.site/fix-perms.php
 */

header('Content-Type: text/plain; charset=utf-8');

$base = dirname(__DIR__);

function fixRecursive(string $dir, int $dirPerm = 0755, int $filePerm = 0644): int
{
    if (! is_dir($dir)) {
        return 0;
    }

    $count = 0;
    @chmod($dir, $dirPerm);

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($iterator as $item) {
        $path = $item->getPathname();
        if ($item->isDir()) {
            @chmod($path, $dirPerm);
        } else {
            @chmod($path, $filePerm);
        }
        $count++;
    }

    return $count;
}

echo "Fixing permissions on: {$base}\n\n";

// 1. Fix vendor directory permissions
if (is_dir($base . '/vendor')) {
    echo "Fixing vendor folder permissions (0755 dirs, 0644 files)...\n";
    $fixed = fixRecursive($base . '/vendor', 0755, 0644);
    echo "✓ Fixed {$fixed} items in vendor/\n";
} else {
    echo "⚠️ vendor folder not found.\n";
}

// 2. Fix storage and bootstrap/cache permissions (writable)
echo "Fixing storage and bootstrap/cache permissions...\n";
fixRecursive($base . '/storage', 0777, 0666);
fixRecursive($base . '/bootstrap/cache', 0777, 0666);
echo "✓ Storage and cache permissions set to writable.\n\n";

echo "========================================================\n";
echo "SUCCESS! Permissions have been restored.\n";
echo "You can now open: https://ai.shebafind.site\n";
echo "(For security, please delete this fix-perms.php file afterwards)\n";
echo "========================================================\n";
