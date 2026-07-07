<?php

namespace App\Models;

use App\Services\BrandingStorage;
use Illuminate\Database\Eloquent\Model;

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
        return BrandingStorage::exists($this->logo_path);
    }

    public function logoUrl(): ?string
    {
        return BrandingStorage::url($this->logo_path, $this->updated_at?->timestamp);
    }

    public function hasFavicon(): bool
    {
        return BrandingStorage::exists($this->favicon_path);
    }

    public function faviconUrl(): ?string
    {
        return BrandingStorage::url($this->favicon_path, $this->updated_at?->timestamp);
    }
}
