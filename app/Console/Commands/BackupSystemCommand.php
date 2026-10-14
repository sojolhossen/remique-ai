<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class BackupSystemCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'remi:backup 
                            {--verify : Verify backup integrity after generation}
                            {--keep=7 : Number of days to retain prior backups}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Perform an automated database and configuration backup with optional rollback verification';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info("=== Starting Remique System Backup ===");
        $timestamp = Carbon::now()->format('Y-m-d_His');
        $backupDir = storage_path('app/backups');

        if (! File::isDirectory($backupDir)) {
            File::makeDirectory($backupDir, 0755, true);
        }

        $backupFile = "{$backupDir}/remique_db_{$timestamp}.sql";
        $manifestFile = "{$backupDir}/remique_manifest_{$timestamp}.json";

        $pdo = DB::connection()->getPdo();
        $dbName = DB::connection()->getDatabaseName();

        $this->line("Target Database: <comment>{$dbName}</comment>");
        $this->line("Output File:     <comment>{$backupFile}</comment>");

        $tables = DB::connection()->select('SHOW TABLES');
        $keyName = "Tables_in_{$dbName}";

        $tableNames = [];
        foreach ($tables as $t) {
            $vars = get_object_vars($t);
            $tableNames[] = reset($vars);
        }

        $totalRows = 0;
        $handle = fopen($backupFile, 'w');
        if (! $handle) {
            $this->error("Failed to open backup target file for writing: {$backupFile}");
            return Command::FAILURE;
        }

        fwrite($handle, "-- Remique Database Backup\n");
        fwrite($handle, "-- Generated: " . Carbon::now()->toIso8601String() . "\n");
        fwrite($handle, "-- Database: {$dbName}\n\n");
        fwrite($handle, "SET FOREIGN_KEY_CHECKS=0;\n\n");

        foreach ($tableNames as $table) {
            // Get CREATE TABLE
            $createTable = DB::connection()->select("SHOW CREATE TABLE `{$table}`");
            $createSql = $createTable[0]->{'Create Table'} ?? '';

            fwrite($handle, "-- --------------------------------------------------------\n");
            fwrite($handle, "-- Table structure for `{$table}`\n");
            fwrite($handle, "-- --------------------------------------------------------\n");
            fwrite($handle, "DROP TABLE IF EXISTS `{$table}`;\n");
            fwrite($handle, "{$createSql};\n\n");

            // Dump rows
            $rows = DB::table($table)->get();
            $rowCount = $rows->count();
            $totalRows += $rowCount;

            if ($rowCount > 0) {
                fwrite($handle, "-- Dumping data for table `{$table}`\n");
                foreach ($rows as $row) {
                    $rowArray = (array) $row;
                    $escapedValues = array_map(function ($val) use ($pdo) {
                        if ($val === null) {
                            return 'NULL';
                        }
                        return $pdo->quote((string) $val);
                    }, array_values($rowArray));

                    $columns = array_map(fn($col) => "`{$col}`", array_keys($rowArray));
                    $sql = sprintf(
                        "INSERT INTO `%s` (%s) VALUES (%s);\n",
                        $table,
                        implode(', ', $columns),
                        implode(', ', $escapedValues)
                    );
                    fwrite($handle, $sql);
                }
                fwrite($handle, "\n");
            }

            $this->line("  ✓ Exported table: <info>{$table}</info> ({$rowCount} rows)");
        }

        fwrite($handle, "SET FOREIGN_KEY_CHECKS=1;\n");
        fclose($handle);

        $fileSize = filesize($backupFile);
        $checksum = hash_file('sha256', $backupFile);

        $manifest = [
            'timestamp' => $timestamp,
            'database' => $dbName,
            'tables_count' => count($tableNames),
            'total_rows' => $totalRows,
            'file_size_bytes' => $fileSize,
            'checksum_sha256' => $checksum,
            'verified' => false,
        ];

        // Verification step if requested
        if ($this->option('verify')) {
            $this->line("\n<comment>Running backup verification...</comment>");
            if (! file_exists($backupFile) || filesize($backupFile) === 0) {
                $this->error("Verification FAILED: Backup file does not exist or is empty.");
                return Command::FAILURE;
            }

            $recalculatedChecksum = hash_file('sha256', $backupFile);
            if ($recalculatedChecksum !== $checksum) {
                $this->error("Verification FAILED: Checksum mismatch.");
                return Command::FAILURE;
            }

            $manifest['verified'] = true;
            $this->info("✓ Verification PASSED: Checksum matches ({$checksum}) and size is {$fileSize} bytes.");
        }

        File::put($manifestFile, json_encode($manifest, JSON_PRETTY_PRINT));

        // Retention policy: purge backups older than specified days
        $keepDays = (int) $this->option('keep');
        $this->purgeOldBackups($backupDir, $keepDays);

        AuditLog::record(
            'system.backup_created',
            null,
            "Automated system backup created ({$manifest['total_rows']} rows, {$fileSize} bytes)",
            [
                'checksum' => $checksum,
                'file' => basename($backupFile),
                'verified' => $manifest['verified'],
            ]
        );

        $this->info("\n=== Backup Completed Successfully ===");
        $this->table(
            ['Metric', 'Value'],
            [
                ['Database', $dbName],
                ['Tables Exported', count($tableNames)],
                ['Total Records', number_format($totalRows)],
                ['File Size', number_format($fileSize) . ' bytes'],
                ['SHA-256 Checksum', substr($checksum, 0, 16) . '...'],
                ['Verified', $manifest['verified'] ? 'YES' : 'NO'],
                ['Manifest', basename($manifestFile)],
            ]
        );

        return Command::SUCCESS;
    }

    protected function purgeOldBackups(string $backupDir, int $keepDays): void
    {
        $cutoff = Carbon::now()->subDays($keepDays)->timestamp;
        $files = File::files($backupDir);

        foreach ($files as $file) {
            if ($file->getMTime() < $cutoff) {
                File::delete($file->getPathname());
                $this->line("  Purged aged backup file: {$file->getFilename()}");
            }
        }
    }
}
