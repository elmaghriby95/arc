<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TranslationKey extends Model
{
    protected $fillable = [
        'group',
        'key',
        'description',
    ];

    public function translations(): HasMany
    {
        return $this->hasMany(Translation::class);
    }

    public function fullKey(): string
    {
        return "{$this->group}.{$this->key}";
    }
}
