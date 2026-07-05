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
    ) {}

    public static function fromRequest(Request $request): self
    {
        $dateFrom = $request->filled('date_from')
            ? Carbon::parse($request->string('date_from'))->startOfDay()
            : null;

        $dateTo = $request->filled('date_to')
            ? Carbon::parse($request->string('date_to'))->endOfDay()
            : null;

        return new self(
            dateFrom: $dateFrom,
            dateTo: $dateTo,
            departmentId: $request->filled('department_id') ? $request->integer('department_id') : null,
            transactionTypeId: $request->filled('transaction_type_id') ? $request->integer('transaction_type_id') : null,
            transactionStatusId: $request->filled('transaction_status_id') ? $request->integer('transaction_status_id') : null,
            staleDays: max(1, $request->integer('stale_days', 7)),
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
        ], fn ($value) => $value !== null && $value !== '');
    }

    /** @return list<string> */
    public function summaryLines(): array
    {
        $lines = [];

        if ($this->dateFrom || $this->dateTo) {
            $from = $this->dateFrom?->format('Y-m-d') ?? '—';
            $to = $this->dateTo?->format('Y-m-d') ?? '—';
            $lines[] = "الفترة: {$from} → {$to}";
        } else {
            $lines[] = 'الفترة: الكل';
        }

        if ($this->departmentId) {
            $lines[] = 'القسم: #'.$this->departmentId;
        }

        if ($this->transactionTypeId) {
            $lines[] = 'نوع المعاملة: #'.$this->transactionTypeId;
        }

        if ($this->transactionStatusId) {
            $lines[] = 'الحالة: #'.$this->transactionStatusId;
        }

        return $lines;
    }
}
