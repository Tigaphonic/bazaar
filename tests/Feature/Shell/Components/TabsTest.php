<?php

// Story 1.2 RED-PHASE scaffold: Tigaphonic\Bazaar\Shell\Livewire\Tabs does not exist
// yet. DESIGN.md §Leaf Controls → Tabs: horizontal row, underline slides under the
// active tab, sits under a card's section-head (e.g. "Timeline / Shipments / CS
// History" on Order detail — a later epic, but the component itself ships here).

use Livewire\Livewire;
use Tigaphonic\Bazaar\Shell\Livewire\Tabs;

it('renders every configured tab and marks the first as active by default', function () {
    Livewire::test(Tabs::class, ['tabs' => ['timeline' => 'Timeline', 'shipments' => 'Shipments']])
        ->assertSet('activeTab', 'timeline')
        ->assertSee('Timeline')
        ->assertSee('Shipments');
});

it('switches the active tab when a tab is selected', function () {
    Livewire::test(Tabs::class, ['tabs' => ['timeline' => 'Timeline', 'shipments' => 'Shipments']])
        ->call('select', 'shipments')
        ->assertSet('activeTab', 'shipments');
});
