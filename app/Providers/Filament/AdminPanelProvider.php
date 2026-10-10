<?php

namespace App\Providers\Filament;

use Filament\FontProviders\GoogleFontProvider;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Enums\Width;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->colors([
                'primary' => config('marca.paletas.azul'),
                'secondary' => config('marca.paletas.dorado'),
                'info' => config('marca.paletas.complementario'),
            ])
            ->font(config('marca.fuente'), provider: GoogleFontProvider::class)
            ->brandName(config('marca.nombre_corto').' '.config('marca.plataforma'))
            ->brandLogo(fn () => view('filament.marca', ['variante' => 'oscuro']))
            ->darkModeBrandLogo(fn () => view('filament.marca', ['variante' => 'claro']))
            ->brandLogoHeight('2.5rem')
            ->favicon(fn () => asset('favicon.png'))
            ->theme(asset('build-admin/theme.css'))
            // Después de crear o editar, el admin vuelve al listado del recurso.
            ->resourceCreatePageRedirect('index')
            ->resourceEditPageRedirect('index')
            ->maxContentWidth(Width::SevenExtraLarge)
            ->unsavedChangesAlerts()
            ->navigationGroups([
                NavigationGroup::make('Contenido')->collapsible()->collapsed(),
                NavigationGroup::make('Evaluación')->collapsible()->collapsed(),
                NavigationGroup::make('Personas')->collapsible()->collapsed(),
                NavigationGroup::make('Reportes')->collapsible()->collapsed(),
            ])
            ->renderHook(PanelsRenderHook::USER_MENU_BEFORE, fn () => view('filament.topbar-usuario'))
            ->renderHook(PanelsRenderHook::HEAD_END, fn () => view('filament.titulo'))
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
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
            ]);
    }
}
