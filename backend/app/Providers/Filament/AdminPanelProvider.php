<?php

namespace App\Providers\Filament;

use App\Http\Middleware\AuthenticateAdmin;
use App\Http\Middleware\IssuePreviewAccess;
use App\Http\Middleware\SetAdminLocale;
use App\Support\DigestPeriod;
use Filament\Actions\Action;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentTimezone;
use Filament\Support\Icons\Heroicon;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function boot(): void
    {
        // Editors read and enter dates in Cyprus time; storage stays UTC.
        FilamentTimezone::set(DigestPeriod::timezone());
    }

    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            // Saves (record + industries pivot) are atomic, so after-commit listeners see the complete record.
            ->databaseTransactions()
            ->passwordReset()
            ->brandName('Disrupt Cyprus')
            ->colors([
                'primary' => Color::hex('#0077B6'),
                'danger' => Color::hex('#BA0035'),
            ])
            ->font('Inter')
            // Keyed: resources name their group by key ('content', …) and Filament matches groups by array key.
            ->navigationGroups([
                'content' => NavigationGroup::make()->label(fn () => __('admin.nav.content')),
                'engagement' => NavigationGroup::make()->label(fn () => __('admin.nav.engagement')),
                'taxonomy' => NavigationGroup::make()->label(fn () => __('admin.nav.taxonomy')),
                'administration' => NavigationGroup::make()->label(fn () => __('admin.nav.administration')),
            ])
            ->userMenuItems([
                // While the pre-launch gate is on, these open the real site/app (staff get the access cookie here).
                Action::make('view-site')
                    ->label(fn () => __('admin.nav.view_site'))
                    ->icon(Heroicon::OutlinedGlobeAlt)
                    ->url(fn () => url('/'), shouldOpenInNewTab: true),
                Action::make('open-app')
                    ->label(fn () => __('admin.nav.open_app'))
                    ->icon(Heroicon::OutlinedDevicePhoneMobile)
                    ->url(fn () => rtrim((string) config('app.frontend_url'), '/').'/', shouldOpenInNewTab: true),
                Action::make('language-el')
                    ->label('Ελληνικά')
                    ->icon(Heroicon::OutlinedLanguage)
                    ->url(fn () => route('admin.language', 'el'))
                    ->visible(fn () => app()->getLocale() !== 'el'),
                Action::make('language-en')
                    ->label('English')
                    ->icon(Heroicon::OutlinedLanguage)
                    ->url(fn () => route('admin.language', 'en'))
                    ->visible(fn () => app()->getLocale() !== 'en'),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                AuthenticateAdmin::class,
                SetAdminLocale::class,
                // Pre-launch: staff get the cookie that opens the public site and the app (no shared password).
                IssuePreviewAccess::class,
            ]);
    }
}
