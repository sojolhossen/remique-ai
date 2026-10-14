<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use App\Models\VaultFile;
use App\Services\Vault\VaultService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VaultOversightController extends Controller
{
    public function __construct(
        protected VaultService $vaultService
    ) {}

    public function index(Request $request): View
    {
        $search = trim($request->get('search', ''));
        $mime = $request->get('mime_type', '');

        // Global metrics
        $globalMetrics = $this->vaultService->getGlobalStorageMetrics();

        // Paginated files metadata
        $query = VaultFile::with('user');

        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('original_filename', 'like', "%{$search}%")
                  ->orWhere('caption', 'like', "%{$search}%")
                  ->orWhere('category', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('name', 'like', "%{$search}%")
                         ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        if (! empty($mime)) {
            $query->where('mime_type', $mime);
        }

        $files = $query->orderByDesc('created_at')->paginate(20)->withQueryString();

        return view('admin.vault.index', compact('globalMetrics', 'files', 'search', 'mime'));
    }

    public function deleteFile(Request $request, VaultFile $file): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => 'required|string|min:5|max:500',
        ]);

        $file->update([
            'status' => 'deleted',
            'deleted_at' => now(),
        ]);

        AuditLog::record(
            'vault.admin_file_deleted',
            $file,
            $validated['reason'],
            [
                'filename' => $file->original_filename,
                'customer_id' => $file->user_id,
            ],
            auth()->id()
        );

        return back()->with('success', "File record #{$file->id} marked as deleted.");
    }
}
