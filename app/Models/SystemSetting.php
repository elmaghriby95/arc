<?php

namespace App\Models;

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
        return filled($this->logo_path);
    }

    public function logoUrl(): ?string
    {
        if (! $this->hasLogo()) {
            return null;
        }

        $url = asset('storage/'.$this->logo_path);

        if ($this->updated_at) {
            $url .= '?v='.$this->updated_at->timestamp;
        }

        return $url;
    }

    public function hasFavicon(): bool
    {
        return filled($this->favicon_path);
    }

    public function faviconUrl(): ?string
    {
        if (! $this->hasFavicon()) {
            return null;
        }

        return asset('storage/'.$this->favicon_path);
    }
}
