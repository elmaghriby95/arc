<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\HanelStorageSetting;
use App\Services\HanelLayoutService;
use App\Services\HanelStorageClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class HanelStorageSettingsController extends Controller
{
    public function index(): View
    {
        return view('settings.hanel-storage.index', [
            'settings' => HanelStorageSetting::instance(),
            'protocols' => HanelStorageSetting::PROTOCOLS,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'is_enabled' => ['sometimes', 'boolean'],
            'protocol' => ['required', Rule::in(HanelStorageSetting::PROTOCOLS)],
            'host' => ['required', 'string', 'max:191'],
            'tcp_port' => ['required', 'integer', 'min:1', 'max:65535'],
            'http_port' => ['required', 'integer', 'min:1', 'max:65535'],
            'use_https' => ['sometimes', 'boolean'],
            'lift_number' => ['required', 'integer', 'min:1', 'max:999'],
            'access_point' => ['required', 'integer', 'min:1', 'max:9'],
            'timeout_seconds' => ['required', 'integer', 'min:10', 'max:120'],
        ]);

        $settings = HanelStorageSetting::instance();
        $settings->fill([
            'is_enabled' => $request->boolean('is_enabled'),
            'protocol' => $validated['protocol'],
            'host' => $validated['host'],
            'tcp_port' => $validated['tcp_port'],
            'http_port' => $validated['http_port'],
            'use_https' => $request->boolean('use_https'),
            'lift_number' => $validated['lift_number'],
            'access_point' => $validated['access_point'],
            'timeout_seconds' => $validated['timeout_seconds'],
        ]);
        $settings->save();

        return redirect()
            ->route('settings.hanel-storage.index')
            ->with('success', __('messages.hanel.saved'));
    }

    public function test(): JsonResponse
    {
        $settings = HanelStorageSetting::instance();
        $result = HanelStorageClient::fromSettings($settings)->testConnection();

        $settings->fill([
            'last_test_status' => $result['status'],
            'last_test_message' => $result['message'],
            'last_tested_at' => now(),
        ]);
        $settings->save();

        return response()->json($result);
    }

    public function command(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'command' => ['required', 'string', Rule::in([
                'read_articles',
                'read_status',
                'get_shelf',
                'send_pick_job',
            ])],
            'shelf_number' => ['nullable', 'integer', 'min:1', 'max:999'],
            'compartment_number' => ['nullable', 'integer', 'min:1', 'max:255'],
            'compartment_depth' => ['nullable', 'integer', 'min:1', 'max:99'],
            'article_number' => ['nullable', 'string', 'max:64'],
            'operation' => ['nullable', 'string', 'max:4'],
            'quantity' => ['nullable', 'string', 'max:16'],
            'job_number' => ['nullable', 'string', 'regex:/^\d{1,3}$/'],
        ]);

        $settings = HanelStorageSetting::instance();

        if (! $settings->is_enabled) {
            return response()->json([
                'ok' => false,
                'message' => __('messages.hanel.integration_disabled'),
            ], 422);
        }

        $result = HanelStorageClient::fromSettings($settings)->executeCommand(
            $validated['command'],
            $validated,
        );

        return response()->json($result);
    }

    public function syncLayout(): JsonResponse
    {
        $settings = HanelStorageSetting::instance();

        if (! $settings->is_enabled) {
            return response()->json([
                'ok' => false,
                'message' => __('messages.hanel.integration_disabled'),
            ], 422);
        }

        $client = HanelStorageClient::fromSettings($settings);
        $ping = $client->pingHttp();

        if (! $ping['ok']) {
            return response()->json([
                'ok' => false,
                'message' => $client->formatConnectionErrorPublic($ping['message']),
            ], 422);
        }

        $result = (new HanelLayoutService($settings, $client))->fetchFromUnit();

        return response()->json([
            'ok' => $result['ok'],
            'message' => $result['message'],
            'layout' => $result['layout'] ?? [],
        ]);
    }

    public function moveShelf(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'shelf_number' => ['required', 'integer', 'min:1', 'max:999'],
            'compartment_number' => ['nullable', 'integer', 'min:1', 'max:255'],
            'compartment_depth' => ['nullable', 'integer', 'min:1', 'max:99'],
            'article_number' => ['nullable', 'string', 'max:64'],
        ]);

        $settings = HanelStorageSetting::instance();

        if (! $settings->is_enabled) {
            return response()->json([
                'ok' => false,
                'message' => __('messages.hanel.integration_disabled'),
            ], 422);
        }

        $result = HanelStorageClient::fromSettings($settings)->moveShelf(
            $validated['shelf_number'],
            $validated['compartment_number'] ?? null,
            $validated['compartment_depth'] ?? null,
            $validated['article_number'] ?? null,
        );

        return response()->json($result);
    }
}
