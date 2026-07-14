<?php

namespace App\Support\Reports;

use Carbon\Carbon;
use Illuminate\Http\Request;

readonly class ReportFilter
{
    public function __construct(
        public ?Carbon $dateFrom,
        public ?Carbon $dateTo,
        public ?int $departmentId,
        public ?int $transactionTypeId,
        public ?int $transactionStatusId,
        public int $staleDays = 7,
        public ?int $userId = null,
        public ?int $folderId = null,
        public ?string $transactionSearch = null,
        public ?string $eventType = null,
        public string $viewMode = 'timeline',
    ) {}

    public static function fromRequest(Request $request): self
    {
        $dateFrom = $request->filled('date_from')
            ? Carbon::parse($request->string('date_from'))->startOfDay()
            : null;

        $dateTo = $request->filled('date_to')
            ? Carbon::parse($request->string('date_to'))->endOfDay()
            : null;

        $viewMode = (string) $request->string('view_mode', 'timeline');
        if (! in_array($viewMode, ['timeline', 'by_user', 'by_transaction'], true)) {
            $viewMode = 'timeline';
        }

        $eventType = $request->filled('event_type')
            ? (string) $request->string('event_type')
            : null;

        if ($eventType !== null && ! in_array($eventType, [
            'created', 'workflow', 'attachment', 'audit', 'access', 'lending',
        ], true)) {
            $eventType = null;
        }

        $transactionSearch = $request->filled('transaction_search')
            ? trim((string) $request->string('transaction_search'))
            : null;

        return new self(
            dateFrom: $dateFrom,
            dateTo: $dateTo,
            departmentId: $request->filled('department_id') ? $request->integer('department_id') : null,
            transactionTypeId: $request->filled('transaction_type_id') ? $request->integer('transaction_type_id') : null,
            transactionStatusId: $request->filled('transaction_status_id') ? $request->integer('transaction_status_id') : null,
            staleDays: max(1, $request->integer('stale_days', 7)),
            userId: $request->filled('user_id') ? $request->integer('user_id') : null,
            folderId: $request->filled('folder_id') ? $request->integer('folder_id') : null,
            transactionSearch: $transactionSearch !== '' ? $transactionSearch : null,
            eventType: $eventType,
            viewMode: $viewMode,
        );
    }

    /** @return array<string, mixed> */
    public function toQueryArray(): array
    {
        return array_filter([
            'date_from' => $this->dateFrom?->toDateString(),
            'date_to' => $this->dateTo?->toDateString(),
            'department_id' => $this->departmentId,
            'transaction_type_id' => $this->transactionTypeId,
            'transaction_status_id' => $this->transactionStatusId,
            'stale_days' => $this->staleDays,
            'user_id' => $this->userId,
            'folder_id' => $this->folderId,
            'transaction_search' => $this->transactionSearch,
            'event_type' => $this->eventType,
            'view_mode' => $this->viewMode !== 'timeline' ? $this->viewMode : null,
        ], fn ($value) => $value !== null && $value !== '');
    }

    /** @return list<string> */
    public function summaryLines(): array
    {
        $lines = [];

        if ($this->dateFrom || $this->dateTo) {
            $from = $this->dateFrom?->format('Y-m-d') ?? '—';
            $to = $this->dateTo?->format('Y-m-d') ?? '—';
            $lines[] = __('reports.scope.period', ['from' => $from, 'to' => $to]);
        } else {
            $lines[] = __('reports.scope.period_all');
        }

        if ($this->departmentId) {
            $lines[] = __('reports.scope.department', ['name' => '#'.$this->departmentId]);
        }

        if ($this->transactionTypeId) {
            $lines[] = __('reports.scope.transaction_type', ['name' => '#'.$this->transactionTypeId]);
        }

        if ($this->transactionStatusId) {
            $lines[] = __('reports.scope.status', ['name' => '#'.$this->transactionStatusId]);
        }

        return $lines;
    }
}
