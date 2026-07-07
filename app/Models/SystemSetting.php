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
        'logo_navbar_height',
        'logo_navbar_max_width',
        'logo_login_height',
        'logo_login_max_width',
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

    public function navbarLogoHeight(): int
    {
        return (int) ($this->logo_navbar_height ?: 28);
    }

    public function navbarLogoMaxWidth(): int
    {
        return (int) ($this->logo_navbar_max_width ?: 100);
    }

    public function loginLogoHeight(): int
    {
        return (int) ($this->logo_login_height ?: 40);
    }

    public function loginLogoMaxWidth(): int
    {
        return (int) ($this->logo_login_max_width ?: 120);
    }

    public function navbarLogoStyle(): string
    {
        return sprintf(
            'height:%dpx;max-height:%dpx;width:auto;max-width:%dpx;object-fit:contain;display:block;',
            $this->navbarLogoHeight(),
            $this->navbarLogoHeight(),
            $this->navbarLogoMaxWidth(),
        );
    }

    public function loginLogoStyle(): string
    {
        return sprintf(
            'height:%dpx;max-height:%dpx;width:auto;max-width:%dpx;object-fit:contain;display:block;',
            $this->loginLogoHeight(),
            $this->loginLogoHeight(),
            $this->loginLogoMaxWidth(),
        );
    }
}
