<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Language;
use App\Models\Translation;
use App\Models\TranslationKey;
use App\Services\TranslationCache;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TranslationController extends Controller
{
    public function index(Language $language): View
    {
        $keys = TranslationKey::query()
            ->orderBy('group')
            ->orderBy('key')
            ->with(['translations' => fn ($query) => $query->where('language_id', $language->id)])
            ->get()
            ->groupBy('group');

        return view('settings.languages.translations', [
            'language' => $language,
            'groupedKeys' => $keys,
        ]);
    }

    public function storeKey(Request $request, Language $language): RedirectResponse
    {
        $validated = $request->validate([
            'group' => ['required', 'string', 'max:100'],
            'key' => ['required', 'string', 'max:191'],
            'value' => ['required', 'string'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $translationKey = TranslationKey::query()->firstOrCreate(
            ['group' => $validated['group'], 'key' => $validated['key']],
            ['description' => $validated['description'] ?? null],
        );

        Translation::query()->updateOrCreate(
            ['translation_key_id' => $translationKey->id, 'language_id' => $language->id],
            ['value' => $validated['value']],
        );

        TranslationCache::forgetLocale($language->code);

        return redirect()
            ->route('settings.languages.translations', $language)
            ->with('success', __('messages.translation_key_saved'));
    }

    public function update(Request $request, Language $language): RedirectResponse
    {
        $validated = $request->validate([
            'translations' => ['required', 'array'],
            'translations.*' => ['nullable', 'string'],
        ]);

        foreach ($validated['translations'] as $keyId => $value) {
            $translationKey = TranslationKey::query()->find($keyId);

            if (! $translationKey) {
                continue;
            }

            if ($value === null || $value === '') {
                Translation::query()
                    ->where('translation_key_id', $translationKey->id)
                    ->where('language_id', $language->id)
                    ->delete();

                continue;
            }

            Translation::query()->updateOrCreate(
                ['translation_key_id' => $translationKey->id, 'language_id' => $language->id],
                ['value' => $value],
            );
        }

        TranslationCache::forgetLocale($language->code);

        return redirect()
            ->route('settings.languages.translations', $language)
            ->with('success', __('messages.translations_saved'));
    }

    public function destroyKey(Language $language, TranslationKey $translationKey): RedirectResponse
    {
        $translationKey->delete();
        TranslationCache::forgetAll();

        return redirect()
            ->route('settings.languages.translations', $language)
            ->with('success', __('messages.translation_key_deleted'));
    }
}
