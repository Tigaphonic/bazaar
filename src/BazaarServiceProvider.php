<?php

namespace Tigaphonic\Bazaar;

use Filament\Actions\Action;
use Filament\Navigation\NavigationGroup;
use Filament\PanelRegistry;
use Filament\Support\Assets\Css;
use Filament\Support\Assets\Js;
use Filament\Support\Assets\Theme;
use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentAsset;
use Filament\Support\Facades\FilamentView;
use Filament\Support\Icons\Heroicon;
use Filament\View\PanelsRenderHook;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Tigaphonic\Bazaar\Install\Commands\BazaarInstallCommand;
use Tigaphonic\Bazaar\Install\Commands\BazaarStatusCommand;
use Tigaphonic\Bazaar\Install\Jobs\RecordQueueHeartbeat;
use Tigaphonic\Bazaar\Install\Support\PanelResolver;
use Tigaphonic\Bazaar\Shell\Http\Middleware\ApplyUserLocale;
use Tigaphonic\Bazaar\Shell\Support\DesignTokens;
use Tigaphonic\Bazaar\User\Support\AuditTrailRecorder;

class BazaarServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('bazaar')
            ->hasConfigFile()
            ->hasViews()
            ->hasTranslations()
            ->hasMigrations([
                'create_bazaar_user_preferences_table',
                'create_bazaar_user_statuses_table',
                'create_bazaar_audit_trails_table',
            ])
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
                ->middleware([ApplyUserLocale::class])
                // Language switcher (topbar-simplification refactor): the only
                // Shell chrome control without a native Filament equivalent, so
                // it rides inside Filament's own native user-menu dropdown
                // rather than a second, separately-discoverable dropdown.
                // Deliberately no ->action() (that triggers a Livewire/PHP
                // round-trip) -- locale switching is purely client-side
                // (localStorage + DOM swap), so an Alpine x-on:click calling
                // the existing window.bazaarShell.setLocale() (resources/js/
                // shell.js, unchanged) keeps the exact same mechanism the old
                // custom dropdown used. Toggles to the other of the two
                // supported locales on each click.
                ->userMenuItems([
                    Action::make('language')
                        // Closure, not an eager __() call: this whole chain runs from
                        // afterResolving(PanelRegistry::class, ...) in packageRegistered(),
                        // which (per that method's own doc comment) fires during the
                        // register phase specifically so it queues ahead of Filament's
                        // boot()-time PanelRegistry resolution -- i.e. potentially before
                        // this package's own hasTranslations() wiring has booted. An eager
                        // __() call here silently returns the raw translation key; a
                        // Closure defers it to request-time label rendering instead.
                        ->label(fn (): string => __('bazaar::shell.language_switcher'))
                        ->icon(Heroicon::Language)
                        // ->alpineClickHandler() (not ->extraAttributes(['x-on:click' =>
                        // ...])) -- passing a non-blank handler also flips
                        // livewireClickHandlerEnabled(false) internally (Action.php:321),
                        // which is what actually suppresses the default wire:click=
                        // "mountAction('language')" Livewire round-trip an Action gets by
                        // default when it has no ->url()/->action(). extraAttributes()
                        // alone does NOT win here: Filament's own attribute bag already
                        // declares an 'x-on:click' key (null, from getAlpineClickHandler())
                        // before merging extraAttributes in, and ComponentAttributeBag::
                        // merge() keeps the pre-existing key over the merged-in one for any
                        // non-class/style attribute -- so an extraAttributes-only x-on:click
                        // is silently dropped.
                        ->alpineClickHandler(
                            "window.bazaarShell && window.bazaarShell.setLocale(document.documentElement.getAttribute('lang') === 'id' ? 'en' : 'id')",
                        ),
                ]);
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
        $this->registerAuditTrail();
    }

    /**
     * Audit Trail auto-capture (Story 1.5): one wildcard listener per Eloquent
     * lifecycle event covers every model in every domain, so no Service needs
     * a manual activity() call. Entries live in Bazaar's own ULID table via
     * the AuditTrail subclass rather than the vendor `activity_log` (AD-18).
     */
    private function registerAuditTrail(): void
    {
        config(['activitylog.activity_model' => config('bazaar.audit.model')]);

        foreach (['created', 'updated', 'deleted'] as $event) {
            Event::listen("eloquent.{$event}: *", [AuditTrailRecorder::class, 'handle']);
        }
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
     * Shell owns the only render hook Bazaar injects into Filament's stock
     * sidebar chrome (AD-33: "only Shell's own ServiceProvider may call
     * FilamentAsset::register() ... other domains consume Shell's kit
     * through its Blade components"). This is what actually places Shell's
     * translated Dashboard nav entry onto the live panel -- restyling alone
     * (CSS) cannot add a nav entry Filament's stock markup doesn't render.
     *
     * The topbar no longer gets a render hook here (topbar-simplification
     * refactor): since Story 1.3 added ->login(), Filament renders its own
     * native topbar controls (search, sidebar-toggle, avatar+user-menu)
     * already, so a second custom TOPBAR_END control cluster only produced
     * uncoordinated, visually-overlapping chrome. The one Shell-only control
     * (Language) without a native equivalent moved into ->userMenuItems()
     * in packageRegistered() instead of a render hook.
     */
    private function registerShellRenderHooks(): void
    {
        FilamentView::registerRenderHook(
            PanelsRenderHook::SIDEBAR_NAV_START,
            fn (): string => view('bazaar::shell.sidebar-nav-start')->render(),
        );

        FilamentView::registerRenderHook(
            PanelsRenderHook::SIDEBAR_LOGO_AFTER,
            fn (): string => view('bazaar::shell.sidebar-brand')->render(),
        );

        FilamentView::registerRenderHook(
            PanelsRenderHook::SIDEBAR_FOOTER,
            fn (): string => view('bazaar::shell.sidebar-footer')->render(),
        );
    }
}
