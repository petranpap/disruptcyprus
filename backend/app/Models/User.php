<?php

namespace App\Models;

use App\Enums\UserRole;
use App\Models\Concerns\StoresUtcTimestamps;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

#[Fillable(['name', 'email', 'password', 'role', 'locale', 'content_locales', 'timezone', 'consent_at', 'consent_version', 'onboarded_at', 'email_verified_at'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser, HasLocalePreference, HasMedia, MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, InteractsWithMedia, Notifiable, SoftDeletes, StoresUtcTimestamps;

    public const AVATAR_COLLECTION = 'avatar';

    /** @var array<string, mixed> */
    protected $attributes = [
        'role' => 'reader',
        'locale' => 'el',
        'content_locales' => '["el","en"]',
        'timezone' => 'Asia/Nicosia',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'content_locales' => 'array',
            'consent_at' => 'datetime',
            'onboarded_at' => 'datetime',
        ];
    }

    /**
     * @return HasMany<SocialAccount, $this>
     */
    public function socialAccounts(): HasMany
    {
        return $this->hasMany(SocialAccount::class);
    }

    /**
     * @return BelongsToMany<Industry, $this>
     */
    public function industries(): BelongsToMany
    {
        return $this->belongsToMany(Industry::class)->withPivot('notify')->withTimestamps();
    }

    /**
     * @return HasMany<Bookmark, $this>
     */
    public function bookmarks(): HasMany
    {
        return $this->hasMany(Bookmark::class);
    }

    /**
     * @return HasOne<NotificationPreference, $this>
     */
    public function notificationPreference(): HasOne
    {
        return $this->hasOne(NotificationPreference::class);
    }

    /**
     * Preferences row, created with defaults on first access.
     */
    public function preferences(): NotificationPreference
    {
        $preferences = $this->notificationPreference()->firstOrCreate();

        // Materializing defaults is not a creation from the API caller's point of view (no 201).
        $preferences->wasRecentlyCreated = false;

        return $preferences;
    }

    /**
     * Notifications and mails are rendered in the user's UI language.
     */
    public function preferredLocale(): string
    {
        return $this->locale;
    }

    /**
     * Only editors and admins may use the Filament panel.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->role->canAccessAdmin();
    }

    public function hasPassword(): bool
    {
        return $this->password !== null;
    }

    public function isEditor(): bool
    {
        return $this->role === UserRole::Editor;
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::AVATAR_COLLECTION)
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->nonQueued()
            ->fit(Fit::Crop, 256, 256)
            ->format('webp');
    }
}
