<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WatermarkSetting extends Model
{
    protected $fillable = [
        'is_enabled',
        'opacity',
        'font_size',
        'angle',
        'show_center_text',
        'show_footer',
        'show_qr_code',
        'show_user_name',
        'show_user_id',
        'show_department',
        'show_datetime',
        'show_action_type',
        'show_transaction_id',
        'apply_on_view',
        'apply_on_download',
        'apply_on_print',
    ];

    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'opacity' => 'integer',
            'font_size' => 'integer',
            'angle' => 'integer',
            'show_center_text' => 'boolean',
            'show_footer' => 'boolean',
            'show_qr_code' => 'boolean',
            'show_user_name' => 'boolean',
            'show_user_id' => 'boolean',
            'show_department' => 'boolean',
            'show_datetime' => 'boolean',
            'show_action_type' => 'boolean',
            'show_transaction_id' => 'boolean',
            'apply_on_view' => 'boolean',
            'apply_on_download' => 'boolean',
            'apply_on_print' => 'boolean',
        ];
    }

    public static function instance(): self
    {
        return static::query()->firstOrCreate([], [
            'is_enabled' => true,
            'opacity' => 18,
            'font_size' => 28,
            'angle' => -45,
            'show_center_text' => true,
            'show_footer' => true,
            'show_qr_code' => true,
            'show_user_name' => true,
            'show_user_id' => true,
            'show_department' => true,
            'show_datetime' => true,
            'show_action_type' => true,
            'show_transaction_id' => true,
            'apply_on_view' => true,
            'apply_on_download' => true,
            'apply_on_print' => true,
        ]);
    }

    public function shouldApplyFor(string $action): bool
    {
        if (! $this->is_enabled) {
            return false;
        }

        return match ($action) {
            'view' => $this->apply_on_view,
            'download' => $this->apply_on_download,
            'print' => $this->apply_on_print,
            default => false,
        };
    }

    public function alpha(): float
    {
        return max(0.05, min(0.6, $this->opacity / 100));
    }
}
