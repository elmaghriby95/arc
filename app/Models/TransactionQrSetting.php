<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransactionQrSetting extends Model
{
    protected $fillable = [
        'display_size',
        'print_size',
    ];

    protected function casts(): array
    {
        return [
            'display_size' => 'integer',
            'print_size' => 'integer',
        ];
    }

    public static function instance(): self
    {
        return static::query()->firstOrCreate([], [
            'display_size' => 160,
            'print_size' => 280,
        ]);
    }
}
