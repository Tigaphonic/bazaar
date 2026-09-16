<?php

// Story 1.2 RED-PHASE scaffold: Tigaphonic\Bazaar\Shell\Livewire\AlertBanner does not
// exist yet. DESIGN.md §Components → Alert Banner: exactly three semantic variants
// (danger/warn/info), each with its own DESIGN.md color trio (bg/ring/text). No fourth
// variant exists in the system.

use Livewire\Livewire;
use Tigaphonic\Bazaar\Shell\Livewire\AlertBanner;

it('renders each of the three DESIGN.md variants with its matching semantic class', function (string $variant, string $expectedClass) {
    Livewire::test(AlertBanner::class, ['variant' => $variant, 'message' => 'Test'])
        ->assertSee($expectedClass, escape: false);
})->with([
    ['danger', 'alert-banner--danger'],
    ['warn', 'alert-banner--warn'],
    ['info', 'alert-banner--info'],
]);

it('rejects a variant outside the three DESIGN.md semantic families', function () {
    Livewire::test(AlertBanner::class, ['variant' => 'success', 'message' => 'Test'])
        ->assertHasErrors('variant');
});
