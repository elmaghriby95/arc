<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Language;
use App\Services\TranslationCache;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LanguageController extends Controller
{
    public function index(): View
    {
        return view('settings.languages.index', [
            'languages' => Language::orderByDesc('is_default')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:10', 'unique:languages,code'],
            'native_name' => ['required', 'string', 'max:255'],
            'direction' => ['required', 'in:rtl,ltr'],
            'is_active' => ['nullable', 'boolean'],
            'is_default' => ['nullable', 'boolean'],
        ]);

        if ($request->boolean('is_default')) {
            Language::query()->update(['is_default' => false]);
        }

        Language::create([
            ...$validated,
            'is_active' => $request->boolean('is_active', true),
            'is_default' => $request->boolean('is_default'),
        ]);

        return redirect()
            ->route('settings.languages.index')
            ->with('success', __('messages.language_added'));
    }

    public function update(Request $request, Language $language): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:10', 'unique:languages,code,'.$language->id],
            'native_name' => ['required', 'string', 'max:255'],
            'direction' => ['required', 'in:rtl,ltr'],
            'is_active' => ['nullable', 'boolean'],
            'is_default' => ['nullable', 'boolean'],
        ]);

        if ($request->boolean('is_default')) {
            Language::where('id', '!=', $language->id)->update(['is_default' => false]);
        }

        $oldCode = $language->code;

        $language->update([
            ...$validated,
            'is_active' => $request->boolean('is_active'),
            'is_default' => $request->boolean('is_default'),
        ]);

        TranslationCache::forgetLocale($oldCode);
        TranslationCache::forgetLocale($language->code);

        return redirect()
            ->route('settings.languages.index')
            ->with('success', __('messages.language_updated'));
    }

    public function destroy(Language $language): RedirectResponse
    {
        if ($language->is_default) {
            return redirect()
                ->route('settings.languages.index')
                ->with('error', __('messages.cannot_delete_default_language'));
        }

        $language->delete();

        return redirect()
            ->route('settings.languages.index')
            ->with('success', __('messages.language_deleted'));
    }
}
