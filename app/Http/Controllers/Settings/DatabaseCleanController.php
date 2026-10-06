<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Services\DatabaseCleanBootstrap;
use App\Services\DatabaseCleanService;
use App\Services\UserActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DatabaseCleanController extends Controller
{
    public function index(DatabaseCleanService $cleaner): View
    {
        abort_unless(request()->user()?->isAdmin(), 403, __('messages.admin_access_denied'));

        DatabaseCleanBootstrap::ensure();

        return view('settings.database-clean.index', [
            'counts' => $cleaner->previewCounts(),
            'confirmationPhrase' => DatabaseCleanService::CONFIRMATION_PHRASE,
        ]);
    }

    public function destroy(Request $request, DatabaseCleanService $cleaner, UserActivityLogger $logger): RedirectResponse
    {
        abort_unless($request->user()?->isAdmin(), 403, __('messages.admin_access_denied'));

        $request->validate([
            'confirmation' => ['required', 'string', 'in:'.DatabaseCleanService::CONFIRMATION_PHRASE],
            'password' => ['required', 'current_password'],
        ], [
            'confirmation.required' => __('validation.database_clean.confirmation_required'),
            'confirmation.in' => __('validation.database_clean.confirmation_mismatch'),
            'password.required' => __('validation.database_clean.password_required'),
            'password.current_password' => __('validation.database_clean.password_incorrect'),
        ]);

        $result = $cleaner->clean();

        if ($actor = $request->user()) {
            $tables = collect($result['tables'])
                ->map(fn (int $count, string $table) => $table.': '.$count)
                ->implode('، ');

            $logger->log($actor, 'database.cleaned', $actor, null, [
                'tables' => mb_substr($tables, 0, 180),
            ], $request);
        }

        return redirect()
            ->route('settings.database-clean.index')
            ->with('success', __('messages.database_clean.completed'));
    }
}
