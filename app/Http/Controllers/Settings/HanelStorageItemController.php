<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\HanelStorageItem;
use App\Models\HanelStorageSetting;
use App\Services\HanelStorageClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class HanelStorageItemController extends Controller
{
    public function index(Request $request): View
    {
        $query = HanelStorageItem::query()->with('registrar')->latest();

        if ($search = trim((string) $request->query('q'))) {
            $like = '%'.$search.'%';
            $query->where(function ($builder) use ($like, $search) {
                $builder
                    ->where('title', 'like', $like)
                    ->orWhere('description', 'like', $like)
                    ->orWhere('reference_number', 'like', $like)
                    ->orWhere('article_number', 'like', $like)
                    ->orWhere('file_name', 'like', $like)
                    ->orWhere('original_name', 'like', $like);

                if (ctype_digit($search)) {
                    $builder->orWhere('shelf_number', (int) $search);
                }
            });
        }

        $settings = HanelStorageSetting::instance();
        $unitArticles = [];

        if ($settings->is_enabled) {
            $unitArticles = HanelStorageClient::fromSettings($settings)->fetchArticlesFromUnitPublic();
        }

        return view('settings.hanel-storage.items.index', [
            'items' => $query->paginate(20)->withQueryString(),
            'search' => $search ?? '',
            'settings' => $settings,
            'unitArticles' => $unitArticles,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $settings = HanelStorageSetting::instance();
        $hanelEnabled = $settings->is_enabled;

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'reference_number' => ['nullable', 'string', 'max:64'],
            'article_number' => array_filter([
                $hanelEnabled ? 'required' : 'nullable',
                'string',
                'max:64',
                'regex:/^[A-Za-z0-9]+$/',
            ]),
            'shelf_number' => ['required', 'integer', 'min:1', 'max:999'],
            'compartment_number' => ['nullable', 'integer', 'min:1', 'max:255'],
            'compartment_depth' => ['nullable', 'integer', 'min:1', 'max:99'],
            'file' => ['nullable', 'file', 'max:51200'],
            'retrieve_shelf' => ['sometimes', 'boolean'],
        ]);

        $articleNumber = $this->normalizeArticleNumber($validated['article_number'] ?? null);

        if ($hanelEnabled && $articleNumber === null) {
            throw ValidationException::withMessages([
                'article_number' => __('messages.hanel.article_number_required_for_unit'),
            ]);
        }

        if ($hanelEnabled) {
            $client = HanelStorageClient::fromSettings($settings);
            $ping = $client->pingHttp();

            if (! $ping['ok']) {
                throw ValidationException::withMessages([
                    'article_number' => $client->formatConnectionErrorPublic($ping['message']),
                ]);
            }

            if (! $client->articleExistsOnUnit($articleNumber)) {
                throw ValidationException::withMessages([
                    'article_number' => __('messages.hanel.article_must_exist_on_unit', [
                        'article' => $articleNumber,
                    ]),
                ]);
            }
        }

        $storedPath = null;

        try {
            return DB::transaction(function () use ($request, $validated, $articleNumber, $hanelEnabled, $settings, &$storedPath) {
                $item = new HanelStorageItem([
                    'title' => $validated['title'],
                    'description' => $validated['description'] ?? null,
                    'reference_number' => $validated['reference_number'] ?? null,
                    'article_number' => $articleNumber,
                    'shelf_number' => $validated['shelf_number'],
                    'compartment_number' => $validated['compartment_number'] ?? null,
                    'compartment_depth' => $validated['compartment_depth'] ?? null,
                    'registered_by' => $request->user()?->id,
                    'hanel_sync_status' => $hanelEnabled ? 'pending' : 'skipped',
                ]);

                if ($request->hasFile('file')) {
                    $file = $request->file('file');
                    $storedPath = $file->store('hanel-storage-items', 'local');

                    $item->fill([
                        'file_path' => $storedPath,
                        'file_name' => basename($storedPath),
                        'original_name' => $file->getClientOriginalName(),
                        'file_size' => $file->getSize(),
                        'mime_type' => $file->getClientMimeType(),
                    ]);
                }

                $item->save();

                $message = __('messages.hanel.item_created');

                if ($hanelEnabled) {
                    $sync = $this->syncItemToUnit($item);

                    if (! $sync['ok']) {
                        throw ValidationException::withMessages([
                            'article_number' => $sync['message'],
                        ]);
                    }

                    $message = __('messages.hanel.item_created_and_synced');
                } else {
                    $item->update([
                        'hanel_sync_status' => 'skipped',
                        'hanel_sync_message' => __('messages.hanel.item_sync_skipped'),
                    ]);
                }

                if ($request->boolean('retrieve_shelf')) {
                    $result = $this->retrieveShelfForItem($item->fresh());

                    if ($result['ok']) {
                        $message = __('messages.hanel.item_created_with_retrieve', [
                            'job' => $item->fresh()->last_job_number ?? '?',
                        ]);
                    } else {
                        return redirect()
                            ->route('settings.hanel-storage.items.index')
                            ->with('warning', __('messages.hanel.item_created_retrieve_failed', [
                                'error' => $result['message'],
                            ]));
                    }
                }

                return redirect()
                    ->route('settings.hanel-storage.items.index')
                    ->with('success', $message);
            });
        } catch (ValidationException $exception) {
            if ($storedPath !== null) {
                Storage::disk('local')->delete($storedPath);
            }

            throw $exception;
        }
    }

    public function retrieve(HanelStorageItem $item): RedirectResponse
    {
        $result = $this->retrieveShelfForItem($item);

        if ($result['ok']) {
            return redirect()
                ->route('settings.hanel-storage.items.index', ['q' => request('q')])
                ->with('success', $result['message']);
        }

        return redirect()
            ->route('settings.hanel-storage.items.index', ['q' => request('q')])
            ->with('error', $result['message']);
    }

    public function download(HanelStorageItem $item): StreamedResponse
    {
        abort_unless($item->fileExists(), 404);

        return Storage::disk('local')->download(
            $item->file_path,
            $item->displayFileName(),
        );
    }

    public function destroy(HanelStorageItem $item): RedirectResponse
    {
        if ($item->file_path !== null) {
            Storage::disk('local')->delete($item->file_path);
        }

        $item->delete();

        return redirect()
            ->route('settings.hanel-storage.items.index', ['q' => request('q')])
            ->with('success', __('messages.hanel.item_deleted'));
    }

    public function sync(HanelStorageItem $item): RedirectResponse
    {
        $result = $this->syncItemToUnit($item);

        return redirect()
            ->route('settings.hanel-storage.items.index', ['q' => request('q')])
            ->with($result['ok'] ? 'success' : 'error', $result['message']);
    }

    /** @return array{ok: bool, message: string} */
    private function syncItemToUnit(HanelStorageItem $item): array
    {
        $settings = HanelStorageSetting::instance();

        if (! $settings->is_enabled) {
            $item->update([
                'hanel_sync_status' => 'failed',
                'hanel_sync_message' => __('messages.hanel.integration_disabled'),
            ]);

            return [
                'ok' => false,
                'message' => __('messages.hanel.integration_disabled'),
            ];
        }

        if ($item->article_number === null || $item->article_number === '') {
            return [
                'ok' => false,
                'message' => __('messages.hanel.article_number_required_for_unit'),
            ];
        }

        $client = HanelStorageClient::fromSettings($settings);
        $ping = $client->pingHttp();

        if (! $ping['ok']) {
            $message = $client->formatConnectionErrorPublic($ping['message']);

            $item->update([
                'hanel_sync_status' => 'failed',
                'hanel_sync_message' => $message,
            ]);

            return [
                'ok' => false,
                'message' => $message,
            ];
        }

        if (! $client->articleExistsOnUnit((string) $item->article_number)) {
            $message = __('messages.hanel.article_must_exist_on_unit', [
                'article' => $item->article_number,
            ]);

            $item->update([
                'hanel_sync_status' => 'failed',
                'hanel_sync_message' => $message,
            ]);

            return [
                'ok' => false,
                'message' => $message,
            ];
        }

        $result = $client->syncStorageArticle(
            (string) $item->article_number,
            $item->title,
            $item->shelf_number,
            $item->compartment_number,
            $item->compartment_depth,
        );

        $updates = [
            'hanel_sync_status' => $result['ok'] ? 'synced' : 'failed',
            'hanel_sync_message' => $result['message'],
            'hanel_synced_at' => $result['ok'] ? now() : null,
        ];

        if ($result['ok']) {
            $details = $result['details'] ?? [];

            if (isset($details['unit_shelf'])) {
                $updates['shelf_number'] = (int) $details['unit_shelf'];
            }

            if (isset($details['unit_compartment'])) {
                $updates['compartment_number'] = (int) $details['unit_compartment'];
            }

            if (isset($details['unit_depth'])) {
                $updates['compartment_depth'] = (int) $details['unit_depth'];
            }
        }

        $item->update($updates);

        return [
            'ok' => $result['ok'],
            'message' => $result['message'],
        ];
    }

    private function normalizeArticleNumber(?string $articleNumber): ?string
    {
        $articleNumber = trim((string) $articleNumber);

        if ($articleNumber === '') {
            return null;
        }

        if (! preg_match('/^[A-Za-z0-9]+$/', $articleNumber)) {
            return null;
        }

        return $articleNumber;
    }

    /** @return array{ok: bool, message: string} */
    private function retrieveShelfForItem(HanelStorageItem $item): array
    {
        $settings = HanelStorageSetting::instance();

        if (! $settings->is_enabled) {
            return [
                'ok' => false,
                'message' => __('messages.hanel.integration_disabled'),
            ];
        }

        $result = HanelStorageClient::fromSettings($settings)->moveShelf(
            $item->shelf_number,
            $item->compartment_number,
            $item->compartment_depth,
            $item->article_number,
        );

        if ($result['ok']) {
            $jobNumber = $result['details']['job_number'] ?? null;

            if ($jobNumber) {
                $item->update(['last_job_number' => $jobNumber]);
            }

            return [
                'ok' => true,
                'message' => $result['message'],
            ];
        }

        return [
            'ok' => false,
            'message' => $result['message'],
        ];
    }
}
