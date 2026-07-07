<?php

namespace App\Models;

use App\Services\BrandingStorage;
use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    private static ?self $resolved = null;

    /** @var array<string, string> */
    public const LOGIN_TEXT_KEYS = [
        'welcome_back' => 'login_welcome_back',
        'login_desc' => 'login_desc',
        'brand_subtitle' => 'login_brand_subtitle',
        'feature_1' => 'login_feature_1',
        'feature_2' => 'login_feature_2',
        'feature_3' => 'login_feature_3',
        'email' => 'login_email',
        'password' => 'login_password',
        'email_placeholder' => 'login_email_placeholder',
        'password_placeholder' => 'login_password_placeholder',
        'remember_me' => 'login_remember_me',
        'forgot_password' => 'login_forgot_password',
        'login' => 'login_button',
        'login_page_title' => 'login_page_title',
        'copyright' => 'login_copyright',
    ];

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
        'login_texts',
    ];

    protected function casts(): array
    {
        return [
            'login_texts' => 'array',
        ];
    }

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

    public function loginText(string $key, ?string $locale = null): string
    {
        $locale = $locale ?? app()->getLocale();
        $texts = $this->login_texts;

        if (is_array($texts) && filled($texts[$locale][$key] ?? null)) {
            return (string) $texts[$locale][$key];
        }

        $defaultLocale = Language::query()->where('is_default', true)->value('code');

        if ($defaultLocale && $defaultLocale !== $locale && is_array($texts) && filled($texts[$defaultLocale][$key] ?? null)) {
            return (string) $texts[$defaultLocale][$key];
        }

        $authKey = match ($key) {
            'copyright' => null,
            'email_placeholder' => 'email_placeholder',
            'password_placeholder' => 'password_placeholder',
            default => $key,
        };

        if ($authKey !== null) {
            $currentLocale = app()->getLocale();

            if ($locale !== $currentLocale) {
                app()->setLocale($locale);
            }

            $translated = __("auth.{$authKey}");

            if ($locale !== $currentLocale) {
                app()->setLocale($currentLocale);
            }

            if ($translated !== "auth.{$authKey}") {
                return $translated;
            }
        }

        return match ($key) {
            'copyright' => '© '.date('Y').' '.$this->appName(),
            'email_placeholder' => 'example@domain.com',
            'password_placeholder' => '••••••••',
            default => '',
        };
    }

    public function loginTextValue(string $locale, string $key): string
    {
        $stored = $this->login_texts[$locale][$key] ?? null;

        if (filled($stored)) {
            return (string) $stored;
        }

        return $this->loginText($key, $locale);
    }
}
