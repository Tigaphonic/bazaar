<?php

// Story 1.2 RED-PHASE scaffold: Tigaphonic\Bazaar\Shell\Support\DesignTokens does not
// exist yet. Un-skip once it ships. Values below are transcribed verbatim from
// _artifacts/planning-artifacts/ux-designs/ux-Tigaphonic/bazaar-2026-09-11/DESIGN.md.

use Tigaphonic\Bazaar\Shell\Support\DesignTokens;

it('exposes the DESIGN.md primary color and its dark-mode pair', function () {
    $colors = DesignTokens::colors();

    expect($colors['primary'])->toBe('#00609e')
        ->and($colors['primary-dark'])->toBe('#3d94c9');
})->skip('Story 1.2 not implemented — DesignTokens::colors() does not exist yet');

it('does not derive dark tokens by literally inverting the light color', function () {
    // DESIGN.md §Colors: dark pairs are a net-new palette that preserves hue
    // family and inverts luminance — never a bitwise/literal color inversion.
    $colors = DesignTokens::colors();

    $literalInversion = '#'.implode('', array_map(
        fn (string $channel) => str_pad(dechex(255 - hexdec($channel)), 2, '0', STR_PAD_LEFT),
        str_split(ltrim($colors['primary'], '#'), 2)
    ));

    expect($colors['primary-dark'])->not->toBe($literalInversion);
})->skip('Story 1.2 not implemented — DesignTokens::colors() does not exist yet');

it('exposes the two-family DESIGN.md typography (Poppins headings, Mulish body)', function () {
    $typography = DesignTokens::typography();

    expect($typography['heading-family'])->toContain('Poppins')
        ->and($typography['body-family'])->toContain('Mulish');
})->skip('Story 1.2 not implemented — DesignTokens::typography() does not exist yet');

it('exposes the fixed shell dimensions from DESIGN.md Layout & Spacing', function () {
    $spacing = DesignTokens::spacing();

    expect($spacing['sidebar-width'])->toBe('250px')
        ->and($spacing['topbar-height'])->toBe('62px');
})->skip('Story 1.2 not implemented — DesignTokens::spacing() does not exist yet');
