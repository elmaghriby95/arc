<?php

namespace App\Services;

use App\Models\HanelStorageItem;
use App\Models\HanelStorageSetting;

class HanelOccupancyService
{
    public function __construct(
        private readonly HanelStorageSetting $settings,
        private readonly ?HanelStorageClient $client = null,
        private readonly ?HanelLayoutService $layoutService = null,
    ) {}

    /** @return array<string, mixed> */
    public function build(bool $includeUnit = true, bool $refreshLayout = false): array
    {
        $layout = ($this->layoutService ?? new HanelLayoutService($this->settings, $this->client))
            ->resolve($refreshLayout);

        $shelfLayout = $layout['shelves'] ?? [];
        ksort($shelfLayout, SORT_NUMERIC);

        if ($shelfLayout === []) {
            $shelfLayout = [1 => ['compartments' => 8, 'depths' => 1]];
        }

        /** @var array<int, array<int, array<string, mixed>>> $grid */
        $grid = [];
        $totalSlots = 0;

        foreach ($shelfLayout as $shelfNumber => $shelfConfig) {
            $shelfNumber = (int) $shelfNumber;
            $compartmentsPerShelf = max(1, (int) ($shelfConfig['compartments'] ?? 1));
            $grid[$shelfNumber] = [];
            $totalSlots += $compartmentsPerShelf;

            for ($compartment = 1; $compartment <= $compartmentsPerShelf; $compartment++) {
                $grid[$shelfNumber][$compartment] = [
                    'status' => 'free',
                    'title' => null,
                    'article_number' => null,
                    'source' => null,
                    'item_id' => null,
                    'hanel_sync_status' => null,
                ];
            }
        }

        $maxShelf = max(array_keys($grid));

        foreach (HanelStorageItem::query()->orderBy('id')->get() as $item) {
            $this->markSlot($grid, $maxShelf, [
                'shelf' => $item->shelf_number,
                'compartment' => $item->compartment_number,
                'status' => 'occupied',
                'title' => $item->title,
                'article_number' => $item->article_number,
                'source' => 'arc',
                'item_id' => $item->id,
                'hanel_sync_status' => $item->hanel_sync_status,
            ], $shelfLayout);
        }

        $unitCount = 0;

        if ($includeUnit && $this->settings->is_enabled && $this->client !== null) {
            foreach ($this->client->fetchArticlesFromUnitPublic() as $article) {
                $shelf = (int) ($article['shelfNumber'] ?? $article['shelfNo'] ?? 0);
                $compartment = (int) ($article['compartmentNumber'] ?? $article['compartmentNo'] ?? 0);

                if ($shelf < 1) {
                    continue;
                }

                $unitCount++;

                if (($grid[$shelf][$compartment]['status'] ?? 'free') === 'free') {
                    $this->markSlot($grid, $maxShelf, [
                        'shelf' => $shelf,
                        'compartment' => $compartment > 0 ? $compartment : null,
                        'status' => 'occupied',
                        'title' => $article['articleName'] ?? $article['articleDescription'] ?? null,
                        'article_number' => $article['articleNumber'] ?? null,
                        'source' => 'unit',
                        'item_id' => null,
                        'hanel_sync_status' => 'synced',
                    ], $shelfLayout);
                }
            }
        }

        $occupied = 0;

        foreach ($grid as $shelfRow) {
            foreach ($shelfRow as $slot) {
                if (($slot['status'] ?? 'free') === 'occupied') {
                    $occupied++;
                }
            }
        }

        return [
            'layout' => $layout,
            'total_shelves' => count($grid),
            'compartments_per_shelf' => max(array_map(
                fn (array $shelf) => (int) ($shelf['compartments'] ?? 1),
                $shelfLayout,
            )),
            'grid' => $grid,
            'stats' => [
                'total_slots' => $totalSlots,
                'occupied' => $occupied,
                'free' => max(0, $totalSlots - $occupied),
                'unit_articles' => $unitCount,
            ],
        ];
    }

    /**
     * @param  array<int, array<int, array<string, mixed>>>  $grid
     * @param  array<int, array{compartments?: int, depths?: int}>  $shelfLayout
     * @param  array{shelf: int, compartment: int|null, status: string, title: ?string, article_number: ?string, source: string, item_id: ?int, hanel_sync_status: ?string}  $data
     */
    private function markSlot(array &$grid, int $maxShelf, array $data, array $shelfLayout): void
    {
        $shelf = $data['shelf'];

        if ($shelf < 1 || $shelf > $maxShelf || ! isset($grid[$shelf])) {
            return;
        }

        $compartmentsPerShelf = max(1, (int) ($shelfLayout[$shelf]['compartments'] ?? count($grid[$shelf])));
        $compartment = $data['compartment'];

        if ($compartment === null || $compartment < 1 || $compartment > $compartmentsPerShelf) {
            for ($c = 1; $c <= $compartmentsPerShelf; $c++) {
                if (($grid[$shelf][$c]['status'] ?? 'free') === 'free') {
                    $compartment = $c;
                    break;
                }
            }
        }

        if ($compartment === null || $compartment < 1 || $compartment > $compartmentsPerShelf) {
            return;
        }

        $grid[$shelf][$compartment] = [
            'status' => $data['status'],
            'title' => $data['title'],
            'article_number' => $data['article_number'],
            'source' => $data['source'],
            'item_id' => $data['item_id'],
            'hanel_sync_status' => $data['hanel_sync_status'],
        ];
    }
}
