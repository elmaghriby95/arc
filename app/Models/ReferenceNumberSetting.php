<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReferenceNumberSetting extends Model
{
    protected $fillable = [
        'auto_assign_department',
        'allow_previous_years',
        'month_optional',
        'original_document_number_optional',
        'operational_number_enabled',
        'prevent_duplicate_numbers',
        'audit_number_changes',
        'allow_free_format_reference',
        'support_multilingual_characters',
        'operational_number_separator',
        'operational_number_format',
        'operational_number_disclaimer',
    ];

    protected function casts(): array
    {
        return [
            'auto_assign_department' => 'boolean',
            'allow_previous_years' => 'boolean',
            'month_optional' => 'boolean',
            'original_document_number_optional' => 'boolean',
            'operational_number_enabled' => 'boolean',
            'prevent_duplicate_numbers' => 'boolean',
            'audit_number_changes' => 'boolean',
            'allow_free_format_reference' => 'boolean',
            'support_multilingual_characters' => 'boolean',
        ];
    }

    public static function instance(): self
    {
        return static::query()->firstOrCreate([], [
            'operational_number_separator' => '/',
            'operational_number_format' => '{department_code}{separator}{year}{separator}{document_type}{separator}{sequence}',
            'operational_number_disclaimer' => 'الرقم التشغيلي ليس رقماً إشارياً رسمياً — يُستخدم فقط للمستندات التي لا تحتوي على رقم إشاري.',
        ]);
    }

    public function formatPreview(): string
    {
        $separator = $this->operational_number_separator ?: '/';

        return str_replace(
            ['{department_code}', '{year}', '{document_type}', '{sequence}', '{separator}'],
            ['12', '2026', 'LTR', '001', $separator],
            $this->operational_number_format
        );
    }
}
