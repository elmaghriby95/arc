<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class HanelStorageItem extends Model
{
    protected $fillable = [
        'title',
        'description',
        'reference_number',
        'article_number',
        'shelf_number',
        'compartment_number',
        'compartment_depth',
        'file_name',
        'original_name',
        'file_path',
        'file_size',
        'mime_type',
        'last_job_number',
        'hanel_sync_status',
        'hanel_sync_message',
        'hanel_synced_at',
        'registered_by',
    ];

    protected function casts(): array
    {
        return [
            'shelf_number' => 'integer',
            'compartment_number' => 'integer',
            'compartment_depth' => 'integer',
            'file_size' => 'integer',
            'hanel_synced_at' => 'datetime',
        ];
    }

    public function registrar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registered_by');
    }

    public function displayFileName(): string
    {
        return $this->original_name
            ?: $this->file_name
            ?: $this->title;
    }

    public function fileExists(): bool
    {
        return $this->file_path !== null && Storage::disk('local')->exists($this->file_path);
    }

    public function formattedSize(): string
    {
        if ($this->file_size === null) {
            return '—';
        }

        if ($this->file_size >= 1048576) {
            return number_format($this->file_size / 1048576, 1).' MB';
        }

        return number_format($this->file_size / 1024, 1).' KB';
    }

    public function locationLabel(): string
    {
        $parts = [__('settings.hanel_storage.shelf_number').': '.$this->shelf_number];

        if ($this->compartment_number !== null) {
            $parts[] = __('settings.hanel_storage.compartment_number').': '.$this->compartment_number;
        }

        if ($this->article_number) {
            $parts[] = __('settings.hanel_storage.article_number').': '.$this->article_number;
        }

        return implode(' · ', $parts);
    }
}
