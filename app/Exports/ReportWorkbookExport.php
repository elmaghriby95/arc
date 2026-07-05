<?php

namespace App\Exports;

use App\Enums\ReportType;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class ReportWorkbookExport implements WithMultipleSheets
{
    /** @param list<ReportSheetExport> $sheets */
    public function __construct(
        private readonly array $sheets,
    ) {}

    /** @return list<ReportSheetExport> */
    public function sheets(): array
    {
        return $this->sheets;
    }

    /** @param array<string, mixed> $data */
    public static function forType(ReportType $type, array $data): self
    {
        return match ($type) {
            ReportType::TransactionPipeline => self::pipeline($data),
            ReportType::StaleTransactions => self::stale($data),
            ReportType::DepartmentProductivity => self::productivity($data),
            ReportType::DocumentsAttachments => self::documents($data),
            ReportType::StatusHistory => self::history($data),
        };
    }

    /** @param array<string, mixed> $data */
    private static function pipeline(array $data): self
    {
        $summaryRows = collect($data['summary'])->map(fn ($row) => [
            $row['name'],
            $row['code'],
            $row['count'],
        ])->all();

        $detailRows = collect($data['details'])->map(fn ($row) => [
            $row['reference_number'],
            $row['title'],
            $row['department'],
            $row['type'],
            $row['status'],
            $row['creator'],
            $row['date'],
        ])->all();

        return new self([
            new ReportSheetExport('ملخص الحالات', ['الحالة', 'الرمز', 'العدد'], $summaryRows),
            new ReportSheetExport('التفاصيل', ['الرقم المرجعي', 'العنوان', 'القسم', 'النوع', 'الحالة', 'المنشئ', 'التاريخ'], $detailRows),
        ]);
    }

    /** @param array<string, mixed> $data */
    private static function stale(array $data): self
    {
        $deptRows = collect($data['by_department'])->map(fn ($row) => [
            $row['department'],
            $row['count'],
            $row['avg_days'],
        ])->all();

        $detailRows = collect($data['details'])->map(fn ($row) => [
            $row['reference_number'],
            $row['title'],
            $row['department'],
            $row['type'],
            $row['status'],
            $row['days_stale'],
            $row['creator'],
            $row['last_activity'],
        ])->all();

        return new self([
            new ReportSheetExport('حسب القسم', ['القسم', 'العدد', 'متوسط الأيام'], $deptRows),
            new ReportSheetExport('التفاصيل', ['الرقم المرجعي', 'العنوان', 'القسم', 'النوع', 'الحالة', 'أيام التوقف', 'المنشئ', 'آخر نشاط'], $detailRows),
        ]);
    }

    /** @param array<string, mixed> $data */
    private static function productivity(array $data): self
    {
        $rows = collect($data['details'])->map(fn ($row) => [
            $row['department'],
            $row['created'],
            $row['draft'],
            $row['in_progress'],
            $row['archived'],
            $row['completed_in_period'],
            $row['completion_rate'].'%',
        ])->all();

        return new self([
            new ReportSheetExport('إنتاجية الأقسام', ['القسم', 'منشأة', 'مسودة', 'قيد الإجراء', 'مؤرشفة', 'مكتملة بالفترة', 'نسبة الإنجاز'], $rows),
        ]);
    }

    /** @param array<string, mixed> $data */
    private static function documents(array $data): self
    {
        $kindRows = collect($data['by_kind'])->map(fn ($row) => [
            $row['label'],
            $row['count'],
            $row['size_formatted'],
        ])->all();

        $detailRows = collect($data['details'])->map(fn ($row) => [
            $row['title'],
            $row['transaction'],
            $row['department'],
            $row['type'],
            $row['kind'],
            $row['mime'],
            $row['size'],
            $row['uploader'],
            $row['date'],
        ])->all();

        return new self([
            new ReportSheetExport('حسب النوع', ['نوع الملف', 'العدد', 'الحجم'], $kindRows),
            new ReportSheetExport('التفاصيل', ['العنوان', 'المعاملة', 'القسم', 'نوع المعاملة', 'نوع الملف', 'MIME', 'الحجم', 'الرافع', 'التاريخ'], $detailRows),
        ]);
    }

    /** @param array<string, mixed> $data */
    private static function history(array $data): self
    {
        $actionRows = collect($data['by_action'])->map(fn ($row) => [
            $row['label'],
            $row['count'],
        ])->all();

        $detailRows = collect($data['details'])->map(fn ($row) => [
            $row['date'],
            $row['reference_number'],
            $row['title'],
            $row['department'],
            $row['from_status'],
            $row['to_status'],
            $row['action'],
            $row['changed_by'],
            $row['notes'],
        ])->all();

        return new self([
            new ReportSheetExport('حسب الإجراء', ['الإجراء', 'العدد'], $actionRows),
            new ReportSheetExport('التفاصيل', ['التاريخ', 'الرقم المرجعي', 'العنوان', 'القسم', 'من حالة', 'إلى حالة', 'الإجراء', 'بواسطة', 'ملاحظات'], $detailRows),
        ]);
    }
}
