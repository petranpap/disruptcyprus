<?php

namespace App\Providers\Filament;

use App\Http\Middleware\SetAdminLocale;
use App\Support\DigestPeriod;
use Filament\Actions\Action;
use Filament\Http\Middleware\Authenticate;
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
            ->navigationGroups([
                NavigationGroup::make('content')->label(fn () => __('admin.nav.content')),
                NavigationGroup::make('engagement')->label(fn () => __('admin.nav.engagement')),
                NavigationGroup::make('taxonomy')->label(fn () => __('admin.nav.taxonomy')),
                NavigationGroup::make('administration')->label(fn () => __('admin.nav.administration')),
            ])
            ->userMenuItems([
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
                Authenticate::class,
                SetAdminLocale::class,
            ]);
    }
}
