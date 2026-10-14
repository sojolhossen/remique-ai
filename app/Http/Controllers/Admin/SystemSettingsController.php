<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SystemSettingsController extends Controller
{
    public function index(): View
    {
        $mysqlVersion = 'Unknown';
        try {
            $mysqlVersion = DB::selectOne('select version() as ver')->ver ?? 'Unknown';
        } catch (\Throwable) {}

        $systemInfo = [
            'app_name' => config('app.name'),
            'app_env' => config('app.env'),
            'app_debug' => config('app.debug') ? 'Enabled' : 'Disabled',
            'php_version' => PHP_VERSION,
            'mysql_version' => $mysqlVersion,
            'default_timezone' => config('app.timezone'),
            'session_driver' => config('session.driver'),
            'queue_connection' => config('queue.default'),
            'storage_disk' => config('filesystems.default'),
        ];

        return view('admin.system.index', compact('systemInfo'));
    }

    public function clearCache(Request $request): RedirectResponse
    {
        Artisan::call('cache:clear');

        AuditLog::record(
            'system.cache_cleared',
            'Cache',
            'Super Admin cleared application cache',
            [],
            auth()->id()
        );

        return back()->with('success', 'Application cache cleared successfully.');
    }
}
