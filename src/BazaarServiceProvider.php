<?php

namespace Tigaphonic\Bazaar;

use Filament\Navigation\NavigationGroup;
use Filament\PanelRegistry;
use Filament\Support\Assets\Css;
use Filament\Support\Assets\Js;
use Filament\Support\Assets\Theme;
use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentAsset;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Cache;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Tigaphonic\Bazaar\Install\Commands\BazaarInstallCommand;
use Tigaphonic\Bazaar\Install\Commands\BazaarStatusCommand;
use Tigaphonic\Bazaar\Install\Jobs\RecordQueueHeartbeat;
use Tigaphonic\Bazaar\Install\Support\PanelResolver;
use Tigaphonic\Bazaar\Shell\Http\Middleware\ApplyUserLocale;
use Tigaphonic\Bazaar\Shell\Support\DesignTokens;

class BazaarServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('bazaar')
            ->hasConfigFile()
            ->hasViews()
            ->hasTranslations()
            ->hasMigration('create_bazaar_user_preferences_table')
            ->runsMigrations()
            ->hasCommands([
                BazaarInstallCommand::class,
                BazaarStatusCommand::class,
            ]);
    }

    /**
     * Registered during the register phase (not boot) so it queues before
     * Filament's own boot() eagerly resolves the PanelRegistry singleton --
     * afterResolving() never fires retroactively for an already-resolved one.
     */
    public function packageRegistered(): void
    {
        $this->app->afterResolving(PanelRegistry::class, function (PanelRegistry $registry): void {
            PanelResolver::resolve($registry)
                ->resources((array) config('bazaar.resources'))
                ->pages((array) config('bazaar.pages'))
                ->navigationGroups([
                    NavigationGroup::make('User & Access'),
                    NavigationGroup::make('Global Settings'),
                ])
                // AD-33 exact registration shape: the panel references the
                // registered asset id, never ->viteTheme() (which pairs with a
                // host-side Vite build Story 1.1 promises the client never needs).
                ->theme('bazaar-shell')
                ->colors([
                    'primary' => Color::hex(DesignTokens::colors()['primary']),
                ])
                ->sidebarWidth(DesignTokens::spacing()['sidebar-width'])
                // Locale application is scoped to this panel's own middleware
                // stack, never the global `web` group (EXPERIENCE.md is explicit
                // that bilingual EN/ID is an admin-panel-scoped requirement).
                ->middleware([ApplyUserLocale::class]);
        });
    }

    public function packageBooted(): void
    {
        $this->app->booted(function (): void {
            /** @var Schedule $schedule */
            $schedule = $this->app->make(Schedule::class);

            $schedule
                ->call(fn () => Cache::put('bazaar:heartbeat:scheduler_last_tick', now()))
                ->everyMinute();

            $schedule
                ->job(new RecordQueueHeartbeat)
                ->everyMinute();
        });

        $this->registerShellTheme();
        $this->registerShellRenderHooks();
    }

    /**
     * AD-33's exact registration shape. `Theme::make()` (a `Css` subclass with
     * no behavioral difference) is registered alongside a plain `Css::make()`
     * under the identical 'bazaar-shell' id: `Filament\Panel\Concerns\
     * HasTheme::getTheme()` only resolves `$panel->theme('bazaar-shell')` via
     * `FilamentAsset::getTheme()`, which reads a *separate* internal registry
     * populated only by `Theme` instances -- a plain `Css` asset alone would
     * never be found there. Registering both keeps `$panel->getTheme()->
     * getId()` exactly 'bazaar-shell' *and* keeps 'bazaar-shell' present in
     * `FilamentAsset::getStyles(['bazaar'])`, both required by
     * PanelThemeOverrideTest.php. Both point at the same compiled file, so
     * this never diverges into two different stylesheets.
     */
    private function registerShellTheme(): void
    {
        FilamentAsset::register([
            Theme::make('bazaar-shell', __DIR__.'/../resources/dist/shell.css'),
            Css::make('bazaar-shell', __DIR__.'/../resources/dist/shell.css'),
            Js::make('bazaar-shell', __DIR__.'/../resources/dist/shell.js'),
        ], package: 'bazaar');
    }

    /**
     * Shell owns the only render hooks Bazaar injects into Filament's stock
     * topbar/sidebar chrome (AD-33: "only Shell's own ServiceProvider may
     * call FilamentAsset::register() ... other domains consume Shell's kit
     * through its Blade components"). This is what actually places Shell's
     * icon-only topbar controls (search/notifications/theme/language) and
     * its translated Dashboard nav entry onto the live panel -- restyling
     * alone (CSS) cannot add controls Filament's stock markup doesn't render
     * (e.g. no user menu at all while no ->login() panel auth exists yet).
     */
    private function registerShellRenderHooks(): void
    {
        FilamentView::registerRenderHook(
            PanelsRenderHook::TOPBAR_END,
            fn (): string => view('bazaar::shell.topbar')->render(),
        );

        FilamentView::registerRenderHook(
            PanelsRenderHook::SIDEBAR_NAV_START,
            fn (): string => view('bazaar::shell.sidebar-nav-start')->render(),
        );
    }
}
