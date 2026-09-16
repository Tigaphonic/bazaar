<?php

// Story 1.2 RED-PHASE scaffold: the shell (sidebar/topbar) render hooks/views do not
// exist yet. EXPERIENCE.md §Accessibility Floor:
// - "Tab order matches visual/reading order on every surface"
// - "Every icon-only control ... carries an accessible name — never icon-only with no
//   label for assistive tech."
//
// These are markup-contract checks (DOM order / aria-label presence), not a real
// browser/contrast audit — actual focus-ring AA-contrast verification stays a Manual
// QA Gate item (Step 3) since this project has no browser test tooling.

use Filament\Facades\Filament;

it('gives every icon-only topbar control an accessible name', function () {
    $this->artisan('bazaar:install');

    $html = (string) view('bazaar::shell.topbar')->render();

    // Topbar icon-only controls per DESIGN.md §Components → Topbar: search, the Staff
    // Notification bell, language/theme controls, user menu chevron.
    foreach (['search', 'notifications', 'theme-toggle', 'language-switcher'] as $control) {
        expect($html)->toContain("aria-label", "Expected an aria-label near the {$control} control");
    }
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
