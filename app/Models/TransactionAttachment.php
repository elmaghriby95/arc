<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class TransactionAttachment extends Model
{
    protected $fillable = [
        'transaction_id',
        'document_id',
        'title',
        'file_path',
        'file_name',
        'original_name',
        'file_size',
        'mime_type',
        'uploaded_by',
        'sort_order',
    ];

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function isFromArchive(): bool
    {
        return $this->document_id !== null;
    }

    public function displayName(): string
    {
        return $this->title
            ?: $this->original_name
            ?: $this->file_name
            ?: $this->document?->title
            ?: 'مرفق';
    }

    public function effectiveMimeType(): ?string
    {
        return $this->mime_type ?? $this->document?->mime_type;
    }

    public function effectiveFileSize(): ?int
    {
        return $this->file_size ?? $this->document?->file_size;
    }

    public function effectiveFilePath(): ?string
    {
        return $this->file_path ?? $this->document?->file_path;
    }

    public function effectiveFileName(): ?string
    {
        return $this->file_name ?? $this->document?->file_name;
    }

    public function isImage(): bool
    {
        $mime = $this->effectiveMimeType();

        return $mime !== null && str_starts_with($mime, 'image/');
    }

    public function fileKind(): string
    {
        $mime = $this->effectiveMimeType() ?? '';
        $name = strtolower($this->effectiveFileName() ?? $this->displayName());

        if ($this->isImage()) {
            return 'image';
        }

        if (str_contains($mime, 'pdf') || str_ends_with($name, '.pdf')) {
            return 'pdf';
        }

        if (str_contains($mime, 'word') || str_contains($mime, 'document') || preg_match('/\.docx?$/', $name)) {
            return 'word';
        }

        if (str_contains($mime, 'sheet') || str_contains($mime, 'excel') || preg_match('/\.xlsx?$/', $name)) {
            return 'excel';
        }

        return 'file';
    }

    public function formattedSize(): string
    {
        $bytes = $this->effectiveFileSize();

        if ($bytes === null) {
            return '—';
        }

        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 1).' MB';
        }

        return number_format($bytes / 1024, 1).' KB';
    }

    public function fileExists(): bool
    {
        $path = $this->effectiveFilePath();

        return $path !== null && Storage::disk('local')->exists($path);
    }
}
