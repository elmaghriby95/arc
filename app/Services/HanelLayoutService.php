<?php

namespace App\Services;

use App\Models\HanelStorageSetting;
use Illuminate\Support\Facades\Http;
use Throwable;

class HanelLayoutService
{
    private ?\GuzzleHttp\Cookie\CookieJar $cookieJar = null;

    public function __construct(
        private readonly HanelStorageSetting $settings,
        private readonly ?HanelStorageClient $client = null,
    ) {}

    /** @return array<string, mixed> */
    public function resolve(bool $refresh = false): array
    {
        $cached = $this->settings->shelf_layout;

        if (! $refresh && is_array($cached) && ($cached['shelves'] ?? []) !== []) {
            return $cached;
        }

        if ($this->settings->is_enabled && $this->client !== null) {
            $fetched = $this->fetchFromUnit();

            if ($fetched['ok']) {
                $this->persistLayout($fetched['layout']);

                return $fetched['layout'];
            }

            if (is_array($cached) && ($cached['shelves'] ?? []) !== []) {
                return array_merge($cached, [
                    'stale' => true,
                    'sync_error' => $fetched['message'],
                ]);
            }
        }

        return $this->fallbackLayout();
    }

    /** @return array{ok: bool, message: string, layout: array<string, mixed>} */
    public function fetchFromUnit(): array
    {
        try {
            $this->cookieJar = new \GuzzleHttp\Cookie\CookieJar;
            $liftNumber = max(1, (int) $this->settings->lift_number);
            $liftHtml = $this->fetchInfoPage("storloc.mp?action=liftselect&navtype=0&number={$liftNumber}");

            if ($liftHtml === null) {
                return [
                    'ok' => false,
                    'message' => __('messages.hanel.layout_fetch_failed'),
                    'layout' => [],
                ];
            }

            $shelfNumbers = $this->parseShelfNumbersFromLiftSelect($liftHtml);

            if ($shelfNumbers === []) {
                return [
                    'ok' => false,
                    'message' => __('messages.hanel.layout_no_shelves'),
                    'layout' => [],
                ];
            }

            $shelves = [];

            foreach ($shelfNumbers as $shelfNumber) {
                $shelfHtml = $this->fetchInfoPage("storloc.mp?action=shelfselect&number={$shelfNumber}&navtype=0");
                $shelves[$shelfNumber] = $this->parseShelfLayoutFromHtml($shelfHtml, $shelfNumber);
            }

            $this->applyArticleHints($shelves);

            $layout = [
                'source' => 'unit_storloc',
                'lift_number' => $liftNumber,
                'shelves' => $shelves,
                'synced_at' => now()->toIso8601String(),
            ];

            return [
                'ok' => true,
                'message' => __('messages.hanel.layout_synced', [
                    'shelves' => count($shelves),
                ]),
                'layout' => $layout,
            ];
        } catch (Throwable $exception) {
            return [
                'ok' => false,
                'message' => $exception->getMessage(),
                'layout' => [],
            ];
        }
    }

    /** @param  array<int, array{compartments: int, depths: int, source: string}>  $shelves */
    private function applyArticleHints(array &$shelves): void
    {
        if ($this->client === null) {
            return;
        }

        foreach ($this->client->fetchArticlesFromUnitPublic() as $article) {
            $shelfNumber = (int) ($article['shelfNumber'] ?? 0);

            if ($shelfNumber < 1 || ! isset($shelves[$shelfNumber])) {
                continue;
            }

            $compartment = max(1, (int) ($article['compartmentNumber'] ?? 1));
            $depth = max(1, (int) ($article['compartmentDepthNumber'] ?? 1));
            $footprint = $this->decodeContainerSize($article['containerSize'] ?? null);

            $shelves[$shelfNumber]['compartments'] = max(
                $shelves[$shelfNumber]['compartments'],
                $compartment + $footprint['compartments'] - 1,
                $compartment,
            );
            $shelves[$shelfNumber]['depths'] = max(
                $shelves[$shelfNumber]['depths'],
                $depth + $footprint['depths'] - 1,
                $depth,
            );
        }
    }

    /** @return array{compartments: int, depths: int, source: string} */
    private function parseShelfLayoutFromHtml(?string $html, int $shelfNumber): array
    {
        $layout = [
            'compartments' => 1,
            'depths' => 1,
            'source' => 'default',
        ];

        if ($html === null || $html === '') {
            return $layout;
        }

        if (preg_match('/var\s+compCountShowMax_GUI2\s*=\s*(\d+)/', $html, $match)) {
            $layout['compartments'] = max($layout['compartments'], (int) $match[1]);
            $layout['source'] = 'unit_storloc';
        }

        if (preg_match('/var\s+compDepthCountShowMax_GUI2\s*=\s*(\d+)/', $html, $match)) {
            $layout['depths'] = max($layout['depths'], (int) $match[1]);
            $layout['source'] = 'unit_storloc';
        }

        if (preg_match('/var\s+content_comp_compDepth_existent\s*=\s*jQuery\.parseJSON\(\'(.*?)\'\)/s', $html, $match)) {
            $matrix = json_decode(str_replace(["\\'"], ["'"], $match[1]), true);

            if (is_array($matrix)) {
                $maxCompartment = 0;
                $maxDepth = 0;

                foreach ($matrix as $depthIndex => $row) {
                    if (! is_array($row)) {
                        continue;
                    }

                    foreach ($row as $compartmentIndex => $exists) {
                        if ($exists) {
                            $maxCompartment = max($maxCompartment, $compartmentIndex + 1);
                            $maxDepth = max($maxDepth, $depthIndex + 1);
                        }
                    }
                }

                if ($maxCompartment > 0) {
                    $layout['compartments'] = $maxCompartment;
                    $layout['source'] = 'unit_matrix';
                }

                if ($maxDepth > 0) {
                    $layout['depths'] = $maxDepth;
                    $layout['source'] = 'unit_matrix';
                }
            }
        }

        return $layout;
    }

    /** @return list<int> */
    private function parseShelfNumbersFromLiftSelect(string $html): array
    {
        preg_match_all('/shelfselect&number=(\d+)/', $html, $matches);

        $numbers = array_values(array_unique(array_map('intval', $matches[1] ?? [])));
        sort($numbers, SORT_NUMERIC);

        return array_values(array_filter($numbers, fn (int $number) => $number > 0));
    }

    private function fetchInfoPage(string $path): ?string
    {
        $base = rtrim($this->settings->baseUrl(), '/').'/info/';
        $timeout = max(10, (int) $this->settings->timeout_seconds);

        if ($this->cookieJar === null) {
            $this->cookieJar = new \GuzzleHttp\Cookie\CookieJar;
            Http::timeout($timeout)
                ->connectTimeout(min(10, $timeout))
                ->withOptions(['verify' => false, 'cookies' => $this->cookieJar])
                ->get($base.'home.pc');
        }

        $response = Http::timeout($timeout)
            ->connectTimeout(min(10, $timeout))
            ->withOptions(['verify' => false, 'cookies' => $this->cookieJar])
            ->get($base.ltrim($path, '/'));

        if ($response->failed()) {
            return null;
        }

        return $response->body();
    }

    /** @return array{compartments: int, depths: int} */
    private function decodeContainerSize(mixed $containerSize): array
    {
        if ($containerSize === null || $containerSize === '') {
            return ['compartments' => 1, 'depths' => 1];
        }

        $value = str_pad((string) $containerSize, 5, '0', STR_PAD_LEFT);

        return [
            'compartments' => max(1, (int) substr($value, 0, 3)),
            'depths' => max(1, (int) substr($value, 3, 2)),
        ];
    }

    /** @param  array<string, mixed>  $layout */
    private function persistLayout(array $layout): void
    {
        $shelves = $layout['shelves'] ?? [];
        $compartmentCounts = array_map(
            fn (array $shelf) => (int) ($shelf['compartments'] ?? 1),
            $shelves,
        );

        $this->settings->fill([
            'shelf_layout' => $layout,
            'layout_synced_at' => now(),
            'total_shelves' => count($shelves),
            'compartments_per_shelf' => $compartmentCounts !== []
                ? max($compartmentCounts)
                : $this->settings->compartments_per_shelf,
        ]);
        $this->settings->save();
    }

    /** @return array<string, mixed> */
    private function fallbackLayout(): array
    {
        $totalShelves = max(1, (int) ($this->settings->total_shelves ?: 8));
        $compartmentsPerShelf = max(1, (int) ($this->settings->compartments_per_shelf ?: 8));
        $shelves = [];

        for ($shelf = 1; $shelf <= $totalShelves; $shelf++) {
            $shelves[$shelf] = [
                'compartments' => $compartmentsPerShelf,
                'depths' => 1,
                'source' => 'settings_fallback',
            ];
        }

        return [
            'source' => 'settings_fallback',
            'lift_number' => $this->settings->lift_number,
            'shelves' => $shelves,
            'synced_at' => null,
        ];
    }
}
