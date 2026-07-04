<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\ReferenceNumberSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReferenceNumberSettingsController extends Controller
{
    public function index(): View
    {
        return view('settings.reference-numbers.index', [
            'settings' => ReferenceNumberSetting::instance(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'auto_assign_department' => ['nullable', 'boolean'],
            'allow_previous_years' => ['nullable', 'boolean'],
            'month_optional' => ['nullable', 'boolean'],
            'original_document_number_optional' => ['nullable', 'boolean'],
            'operational_number_enabled' => ['nullable', 'boolean'],
            'prevent_duplicate_numbers' => ['nullable', 'boolean'],
            'audit_number_changes' => ['nullable', 'boolean'],
            'allow_free_format_reference' => ['nullable', 'boolean'],
            'support_multilingual_characters' => ['nullable', 'boolean'],
            'operational_number_separator' => ['required', 'string', 'max:5'],
            'operational_number_format' => ['required', 'string', 'max:255'],
            'operational_number_disclaimer' => ['nullable', 'string', 'max:1000'],
        ]);

        $settings = ReferenceNumberSetting::instance();

        $settings->update([
            'auto_assign_department' => $request->boolean('auto_assign_department'),
            'allow_previous_years' => $request->boolean('allow_previous_years'),
            'month_optional' => $request->boolean('month_optional'),
            'original_document_number_optional' => $request->boolean('original_document_number_optional'),
            'operational_number_enabled' => $request->boolean('operational_number_enabled'),
            'prevent_duplicate_numbers' => $request->boolean('prevent_duplicate_numbers'),
            'audit_number_changes' => $request->boolean('audit_number_changes'),
            'allow_free_format_reference' => $request->boolean('allow_free_format_reference'),
            'support_multilingual_characters' => $request->boolean('support_multilingual_characters'),
            'operational_number_separator' => $validated['operational_number_separator'],
            'operational_number_format' => $validated['operational_number_format'],
            'operational_number_disclaimer' => $validated['operational_number_disclaimer'] ?? null,
        ]);

        return redirect()
            ->route('settings.reference-numbers.index')
            ->with('success', 'تم حفظ إعدادات الرقم الإشاري بنجاح.');
    }
}
