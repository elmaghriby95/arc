<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\TransactionQrSetting;
use App\Services\UserActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TransactionQrSettingsController extends Controller
{
    public function index(): View
    {
        return view('settings.qr-code.index', [
            'settings' => TransactionQrSetting::instance(),
        ]);
    }

    public function update(Request $request, UserActivityLogger $logger): RedirectResponse
    {
        $validated = $request->validate([
            'display_size' => ['required', 'integer', 'min:80', 'max:500'],
            'print_size' => ['required', 'integer', 'min:80', 'max:500'],
        ]);

        $settings = TransactionQrSetting::instance();
        $before = $settings->only(['display_size', 'print_size']);
        $settings->update($validated);

        if ($actor = $request->user()) {
            $logger->logModelChange($actor, 'qr_settings.updated', $settings, $before, $settings->only(['display_size', 'print_size']), $request);
        }

        return redirect()
            ->route('settings.qr-code.index')
            ->with('success', __('messages.transaction_qr.saved'));
    }
}
