<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class SystemSetting extends Model
{
    private static ?self $resolved = null;

    protected $fillable = [
        'app_name',
        'logo_path',
        'favicon_path',
        'support_email',
        'support_phone',
    ];

    public static function instance(): self
    {
        return self::$resolved ??= static::query()->firstOrCreate([]);
    }

    public static function clearCache(): void
    {
        self::$resolved = null;
    }

    public function appName(): string
    {
        return filled($this->app_name) ? $this->app_name : (string) config('app.name', 'Laravel');
    }

    public function hasLogo(): bool
    {
        return filled($this->logo_path) && Storage::disk('public')->exists($this->logo_path);
    }

    public function logoUrl(): ?string
    {
        return $this->hasLogo() ? Storage::disk('public')->url($this->logo_path) : null;
    }

    public function hasFavicon(): bool
    {
        return filled($this->favicon_path) && Storage::disk('public')->exists($this->favicon_path);
    }

    public function faviconUrl(): ?string
    {
        return $this->hasFavicon() ? Storage::disk('public')->url($this->favicon_path) : null;
    }
}
