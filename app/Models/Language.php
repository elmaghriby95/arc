<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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

    public function directionLabel(): string
    {
        return $this->direction === 'rtl' ? 'من اليمين لليسار' : 'من اليسار لليمين';
    }
}
