<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\HanelStorageSetting;
use App\Services\HanelLayoutService;
use App\Services\HanelOccupancyService;
use App\Services\HanelStorageClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HanelStorageOccupancyController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        $settings = HanelStorageSetting::instance();
        $client = $settings->is_enabled ? HanelStorageClient::fromSettings($settings) : null;
        $layoutService = new HanelLayoutService($settings, $client);
        $refreshLayout = $request->boolean('refresh_layout');

        if ($refreshLayout && $settings->is_enabled && $client !== null) {
            $sync = $layoutService->fetchFromUnit();

            return redirect()
                ->route('settings.hanel-storage.occupancy.index')
                ->with($sync['ok'] ? 'success' : 'error', $sync['message']);
        }

        $map = (new HanelOccupancyService($settings, $client, $layoutService))
            ->build($settings->is_enabled, ! $settings->shelf_layout);

        return view('settings.hanel-storage.occupancy.index', [
            'settings' => $settings,
            'map' => $map,
        ]);
    }
}
