<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Language;
use App\Models\Translation;
use App\Models\TranslationKey;
use App\Services\TranslationCache;
use App\Services\UserActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
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

    public function storeKey(Request $request, Language $language, UserActivityLogger $logger): RedirectResponse
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

        if ($actor = $request->user()) {
            $logger->log($actor, 'translation.created', $translationKey, null, [
                'name' => $language->name.' · '.$translationKey->fullKey(),
                'value' => mb_substr($validated['value'], 0, 180),
            ], $request);
        }

        return redirect()
            ->route('settings.languages.translations', $language)
            ->with('success', __('messages.translation_key_saved'));
    }

    public function update(Request $request, Language $language, UserActivityLogger $logger): RedirectResponse
    {
        // One JSON field. A named input per sentence exceeds PHP max_input_vars,
        // so groups at the end of the list (workflow and others) were dropped on save.
        $translations = $this->submittedTranslations($request);

        $keyIds = array_map('intval', array_keys($translations));
        $existing = Translation::query()
            ->where('language_id', $language->id)
            ->whereIn('translation_key_id', $keyIds)
            ->pluck('value', 'translation_key_id');
        $keys = TranslationKey::query()->whereIn('id', $keyIds)->get()->keyBy('id');
        $changed = [];

        foreach ($translations as $keyId => $value) {
            $translationKey = $keys->get((int) $keyId);

            if (! $translationKey) {
                continue;
            }

            $previous = $existing->get($translationKey->id);
            $next = ($value === null || $value === '') ? null : $value;

            if (trim((string) ($previous ?? '')) === trim((string) ($next ?? ''))) {
                continue;
            }

            $changed[] = $translationKey->fullKey();

            if ($next === null) {
                Translation::query()
                    ->where('translation_key_id', $translationKey->id)
                    ->where('language_id', $language->id)
                    ->delete();

                continue;
            }

            Translation::query()->updateOrCreate(
                ['translation_key_id' => $translationKey->id, 'language_id' => $language->id],
                ['value' => $next],
            );
        }

        TranslationCache::forgetLocale($language->code);

        if ($changed !== [] && ($actor = $request->user())) {
            $shown = array_slice($changed, 0, 12);
            $extra = count($changed) - count($shown);

            $logger->log($actor, 'translation.updated', $language, null, [
                'language' => $language->name,
                'changed_count' => count($changed),
                'changed_keys' => implode('، ', $shown).($extra > 0 ? ' +'.$extra : ''),
            ], $request);
        }

        return redirect()
            ->route('settings.languages.translations', $language)
            ->with('success', __('messages.translations_saved'));
    }

    /**
     * @return array<int, string|null>
     */
    private function submittedTranslations(Request $request): array
    {
        if ($request->has('translations_json')) {
            $validated = $request->validate([
                'translations_json' => ['required', 'string', 'max:5000000'],
            ]);

            $decoded = json_decode($validated['translations_json'], true);

            if (! is_array($decoded)) {
                throw ValidationException::withMessages([
                    'translations_json' => __('validation.json', ['attribute' => 'translations']),
                ]);
            }

            $translations = [];

            foreach ($decoded as $keyId => $value) {
                if (is_array($value) || is_object($value) || ! preg_match('/^\d+$/', (string) $keyId)) {
                    continue;
                }

                $translations[(int) $keyId] = $value === null ? null : (string) $value;
            }

            return $translations;
        }

        return $request->validate([
            'translations' => ['required', 'array'],
            'translations.*' => ['nullable', 'string'],
        ])['translations'];
    }

    public function destroyKey(Request $request, Language $language, TranslationKey $translationKey, UserActivityLogger $logger): RedirectResponse
    {
        if ($actor = $request->user()) {
            $logger->log($actor, 'translation.deleted', $translationKey, [
                'name' => $language->name.' · '.$translationKey->fullKey(),
            ], null, $request);
        }

        $translationKey->delete();
        TranslationCache::forgetAll();

        return redirect()
            ->route('settings.languages.translations', $language)
            ->with('success', __('messages.translation_key_deleted'));
    }
}
