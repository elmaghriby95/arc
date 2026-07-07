<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use App\Services\BrandingStorage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\File;
use Illuminate\View\View;

class GeneralSettingsController extends Controller
{
    public function index(): View
    {
        return view('settings.general.index', [
            'settings' => SystemSetting::instance(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'app_name' => ['nullable', 'string', 'max:191'],
            'support_email' => ['nullable', 'email', 'max:191'],
            'support_phone' => ['nullable', 'string', 'max:50'],
            'logo' => ['nullable', 'file', 'max:2048', File::types(['jpg', 'jpeg', 'png', 'webp', 'svg'])],
            'favicon' => ['nullable', 'file', 'max:1024', File::types(['jpg', 'jpeg', 'png', 'webp', 'ico'])],
            'remove_logo' => ['sometimes', 'boolean'],
            'remove_favicon' => ['sometimes', 'boolean'],
        ]);

        $settings = SystemSetting::instance();

        if ($request->boolean('remove_logo')) {
            BrandingStorage::delete($settings->logo_path);
            $settings->logo_path = null;
        }

        if ($request->boolean('remove_favicon')) {
            BrandingStorage::delete($settings->favicon_path);
            $settings->favicon_path = null;
        }

        if ($request->hasFile('logo')) {
            $settings->logo_path = BrandingStorage::store($request->file('logo'), 'logo', $settings->logo_path);
        }

        if ($request->hasFile('favicon')) {
            $settings->favicon_path = BrandingStorage::store($request->file('favicon'), 'favicon', $settings->favicon_path);
        }

        $settings->fill([
            'app_name' => $validated['app_name'] ?? null,
            'support_email' => $validated['support_email'] ?? null,
            'support_phone' => $validated['support_phone'] ?? null,
        ]);

        $settings->save();
        SystemSetting::clearCache();

        return redirect()
            ->route('settings.general.index')
            ->with('success', __('messages.general.saved'));
    }
}
