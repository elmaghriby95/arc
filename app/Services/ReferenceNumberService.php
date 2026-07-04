<?php

namespace App\Services;

use App\Models\Department;
use App\Models\Document;
use App\Models\ReferenceNumberSetting;
use App\Models\TransactionAttachment;
use App\Models\TransactionType;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class ReferenceNumberService
{
    public function settings(): ReferenceNumberSetting
    {
        return ReferenceNumberSetting::instance();
    }

    /** @return array<string, mixed> */
    public function formConfig(User $user): array
    {
        $settings = $this->settings();

        return [
            'auto_assign_department' => $settings->auto_assign_department,
            'allow_previous_years' => $settings->allow_previous_years,
            'month_optional' => $settings->month_optional,
            'original_document_number_optional' => $settings->original_document_number_optional,
            'operational_number_enabled' => $settings->operational_number_enabled,
            'prevent_duplicate_numbers' => $settings->prevent_duplicate_numbers,
            'allow_free_format_reference' => $settings->allow_free_format_reference,
            'operational_number_disclaimer' => $settings->operational_number_disclaimer,
            'operational_number_preview' => $settings->formatPreview(),
            'default_department_id' => $settings->auto_assign_department ? $user->department_id : null,
            'current_year' => now()->year,
            'can_override_duplicate' => $user->hasPermission('documents.reference-number.duplicate-override'),
        ];
    }

    public function generateOperationalNumber(
        Department $department,
        ?string $documentTypeCode,
        ?int $year = null,
    ): string {
        $settings = $this->settings();

        if (! $settings->operational_number_enabled) {
            throw ValidationException::withMessages([
                'files' => 'الرقم التشغيلي غير مفعّل في إعدادات النظام.',
            ]);
        }

        $year ??= now()->year;
        $separator = $settings->operational_number_separator ?: '/';
        $typeCode = $documentTypeCode ?: 'DOC';
        $sequence = $this->nextSequence($department->id, $year, $typeCode);

        return str_replace(
            ['{department_code}', '{year}', '{document_type}', '{sequence}', '{separator}'],
            [
                $department->code ?: (string) $department->id,
                (string) $year,
                $typeCode,
                str_pad((string) $sequence, 3, '0', STR_PAD_LEFT),
                $separator,
            ],
            $settings->operational_number_format
        );
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array{
     *     reference_number: string,
     *     reference_year: int|null,
     *     reference_month: int|null,
     *     original_document_number: string|null,
     *     is_operational_number: bool
     * }
     */
    public function resolveForAttachment(
        array $input,
        Department $department,
        ?TransactionType $transactionType,
        User $user,
    ): array {
        $settings = $this->settings();
        $useOperational = filter_var($input['use_operational'] ?? false, FILTER_VALIDATE_BOOL);
        $year = isset($input['reference_year']) && $input['reference_year'] !== ''
            ? (int) $input['reference_year']
            : now()->year;

        if ($useOperational) {
            $referenceNumber = $this->generateOperationalNumber(
                $department,
                $transactionType?->code,
                $year,
            );

            return [
                'reference_number' => $referenceNumber,
                'reference_year' => $year,
                'reference_month' => null,
                'original_document_number' => null,
                'is_operational_number' => true,
            ];
        }

        $referenceNumber = trim((string) ($input['reference_number'] ?? ''));

        if ($referenceNumber === '') {
            throw ValidationException::withMessages([
                'files' => 'يجب إدخال الرقم الإشاري لكل مستند أو اختيار الرقم التشغيلي.',
            ]);
        }

        if (! $settings->allow_free_format_reference && ! preg_match('/^[A-Za-z0-9\/\-]+$/', $referenceNumber)) {
            throw ValidationException::withMessages([
                'files' => 'صيغة الرقم الإشاري غير مسموحة حسب إعدادات النظام.',
            ]);
        }

        $month = isset($input['reference_month']) && $input['reference_month'] !== ''
            ? (int) $input['reference_month']
            : null;

        if (! $settings->month_optional && $month === null) {
            throw ValidationException::withMessages([
                'files' => 'الشهر مطلوب في الرقم الإشاري حسب إعدادات النظام.',
            ]);
        }

        $originalNumber = trim((string) ($input['original_document_number'] ?? ''));

        if (! $settings->original_document_number_optional && $originalNumber === '') {
            throw ValidationException::withMessages([
                'files' => 'رقم المستند الأصلي مطلوب حسب إعدادات النظام.',
            ]);
        }

        if ($settings->prevent_duplicate_numbers && ! $user->hasPermission('documents.reference-number.duplicate-override')) {
            $this->assertNotDuplicate($referenceNumber, $department->id, $year, $transactionType?->code);
        }

        return [
            'reference_number' => $referenceNumber,
            'reference_year' => $year,
            'reference_month' => $month,
            'original_document_number' => $originalNumber !== '' ? $originalNumber : null,
            'is_operational_number' => false,
        ];
    }

    public function assertNotDuplicate(
        string $referenceNumber,
        int $departmentId,
        int $year,
        ?string $documentTypeCode,
    ): void {
        if (! $this->settings()->prevent_duplicate_numbers) {
            return;
        }

        $attachmentExists = TransactionAttachment::query()
            ->where('reference_number', $referenceNumber)
            ->whereHas('transaction', fn ($query) => $query
                ->where('department_id', $departmentId)
                ->when($documentTypeCode, fn ($q) => $q->whereHas('transactionType', fn ($tq) => $tq->where('code', $documentTypeCode)))
            )
            ->exists();

        $documentExists = Document::query()
            ->where('reference_number', $referenceNumber)
            ->where('department_id', $departmentId)
            ->exists();

        if ($attachmentExists || $documentExists) {
            throw ValidationException::withMessages([
                'files' => "الرقم الإشاري «{$referenceNumber}» مستخدم مسبقاً ضمن نفس الوحدة والسنة.",
            ]);
        }
    }

    private function nextSequence(int $departmentId, int $year, string $documentTypeCode): int
    {
        $attachmentCount = TransactionAttachment::query()
            ->where('is_operational_number', true)
            ->where('reference_year', $year)
            ->whereHas('transaction', function ($query) use ($departmentId, $documentTypeCode) {
                $query->where('department_id', $departmentId)
                    ->whereHas('transactionType', fn ($typeQuery) => $typeQuery->where('code', $documentTypeCode));
            })
            ->count();

        return $attachmentCount + 1;
    }
}
