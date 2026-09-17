<?php

// Topbar-simplification refactor (2026-09-17): Bazaar's own custom topbar controls
// (search/bell/mobile-menu icon buttons + a user-icon dropdown with its own
// theme-toggle+language items, injected at PanelsRenderHook::TOPBAR_END since Story
// 1.2) are gone. Since Story 1.3 wired panel auth (->login()), Filament renders its
// own native topbar chrome (search, sidebar-toggle, avatar+user-menu) already, so a
// second custom control cluster only produced uncoordinated, visually-overlapping
// chrome (search/bell/theme-toggle were redundant with native controls; the one
// control without a native equivalent -- Language -- now lives as a single Action
// inside Filament's own native user-menu dropdown, see BazaarServiceProvider::
// packageRegistered() -> ->userMenuItems()). This test's earlier version rendered
// resources/views/shell/topbar.blade.php directly, which no longer exists.
//
// EXPERIENCE.md §Accessibility Floor: "Every icon-only control ... carries an
// accessible name — never icon-only with no label for assistive tech." -- the native
// Filament user-menu trigger already satisfies this (aria-label on its avatar
// button); the Language action satisfies it too, via a visible text label rather
// than an icon-only control.
//
// This is a markup-contract check against a real authenticated panel request (the
// native user-menu only renders when filament()->auth()->check() is true — see
// vendor/filament/filament/resources/views/livewire/topbar.blade.php), not a real
// browser/contrast audit — actual focus-ring AA-contrast verification stays a Manual
// QA Gate item since this project has no browser test tooling.

use Tigaphonic\Bazaar\User\Filament\Resources\UserResource;
use Workbench\App\Models\User;

it('renders only native Filament topbar controls, with the Language switcher inside the native user-menu', function () {
    $this->artisan('bazaar:install');

    $user = User::create([
        'name' => 'Nina Staff',
        'email' => 'nina-topbar@example.com',
        'password' => bcrypt('password'),
    ]);

    // The panel root ('/admin') redirects to the first registered resource's index
    // page -- request that page's own URL directly (rather than hardcoding a route
    // that assumes UserResource stays first) so the response body is the actual
    // rendered panel page (with the topbar), not a redirect stub.
    $html = (string) $this->actingAs($user)->get(UserResource::getUrl('index'))->getContent();

    // Root fix for the overlap this refactor exists to remove: none of Shell's own
    // custom topbar/user-menu control classes survive in the rendered page.
    foreach ([
        'bazaar-topbar-controls',
        'bazaar-icon-btn',
        'bazaar-icon--search',
        'bazaar-icon--bell',
        'bazaar-icon--menu',
        'bazaar-icon--user',
        'bazaar-mobile-menu-btn',
        'bazaar-user-menu',
    ] as $deadClass) {
        expect($html)->not->toContain($deadClass, "Expected no leftover custom Bazaar topbar control [{$deadClass}] in the rendered page");
    }

    // Filament's own native topbar + user-menu trigger markup is present instead.
    expect($html)->toContain('fi-topbar')
        ->toContain('fi-user-menu-trigger');

    // EXPERIENCE.md §Accessibility Floor: "Every icon-only control ... carries an
    // accessible name — never icon-only with no label for assistive tech." Assert the
    // actual accessible-name text Filament renders for the two icon-only native
    // controls this refactor now relies on (search field, user-menu avatar trigger) --
    // not just the generic presence of the word "aria-label" anywhere in the page.
    expect($html)->toContain('aria-label="'.__('filament-panels::layout.actions.open_user_menu.label').'"')
        ->toContain(__('filament-panels::global-search.field.label'));

    // The Language action rides inside that native user-menu dropdown with a visible,
    // non-empty accessible label (text content, not an icon-only control), and reuses
    // the exact same client-side locale mechanism (window.bazaarShell.setLocale,
    // resources/js/shell.js, unchanged) as before.
    $languageLabel = __('bazaar::shell.language_switcher');
    expect($languageLabel)->not->toBeEmpty();
    expect($html)->toContain($languageLabel)
        ->toContain('window.bazaarShell')
        ->toContain('setLocale');
});

it('renders shell nav items in the same order they appear visually (tab order = reading order)', function () {
    $this->artisan('bazaar:install');

    $html = (string) view('bazaar::shell.sidebar')->render();

    $userAccessPosition = strpos($html, 'User & Access');
    $globalSettingsPosition = strpos($html, 'Global Settings');

    expect($userAccessPosition)->not->toBeFalse()
        ->and($globalSettingsPosition)->not->toBeFalse()
        ->and($userAccessPosition)->toBeLessThan($globalSettingsPosition);
});
