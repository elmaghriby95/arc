<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\DocumentAccessAudit;
use App\Models\WatermarkSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WatermarkSettingsController extends Controller
{
    public function index(Request $request): View
    {
        $lookup = null;

        if ($request->filled('transaction_id')) {
            $lookup = DocumentAccessAudit::query()
                ->with(['user.department', 'attachment.transaction'])
                ->where('transaction_id', $request->string('transaction_id')->toString())
                ->first();
        }

        return view('settings.watermark.index', [
            'settings' => WatermarkSetting::instance(),
            'lookup' => $lookup,
            'transactionIdQuery' => $request->string('transaction_id')->toString(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'is_enabled' => ['nullable', 'boolean'],
            'opacity' => ['required', 'integer', 'min:5', 'max:60'],
            'font_size' => ['required', 'integer', 'min:10', 'max:72'],
            'angle' => ['required', 'integer', 'min:-90', 'max:90'],
            'show_center_text' => ['nullable', 'boolean'],
            'show_footer' => ['nullable', 'boolean'],
            'show_qr_code' => ['nullable', 'boolean'],
            'show_user_name' => ['nullable', 'boolean'],
            'show_user_id' => ['nullable', 'boolean'],
            'show_department' => ['nullable', 'boolean'],
            'show_datetime' => ['nullable', 'boolean'],
            'show_action_type' => ['nullable', 'boolean'],
            'show_transaction_id' => ['nullable', 'boolean'],
            'apply_on_view' => ['nullable', 'boolean'],
            'apply_on_download' => ['nullable', 'boolean'],
            'apply_on_print' => ['nullable', 'boolean'],
        ]);

        $booleans = [
            'is_enabled',
            'show_center_text',
            'show_footer',
            'show_qr_code',
            'show_user_name',
            'show_user_id',
            'show_department',
            'show_datetime',
            'show_action_type',
            'show_transaction_id',
            'apply_on_view',
            'apply_on_download',
            'apply_on_print',
        ];

        foreach ($booleans as $field) {
            $validated[$field] = $request->boolean($field);
        }

        WatermarkSetting::instance()->update($validated);

        return redirect()
            ->route('settings.watermark.index')
            ->with('success', __('messages.watermark.saved'));
    }
}
