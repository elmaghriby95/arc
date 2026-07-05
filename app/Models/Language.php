<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Language extends Model
{
    protected $fillable = [
        'name',
        'code',
        'native_name',
        'direction',
        'is_active',
        'is_default',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_default' => 'boolean',
        ];
    }

    public function translations(): HasMany
    {
        return $this->hasMany(Translation::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function directionLabel(): string
    {
        return $this->direction === 'rtl'
            ? __('common.direction_rtl_short')
            : __('common.direction_ltr_short');
    }
}
