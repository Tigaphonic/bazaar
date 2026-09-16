<?php

// Story 1.2 RED-PHASE scaffold: Tigaphonic\Bazaar\Shell\Livewire\EmptyState does not
// exist yet. DESIGN.md §Components → Empty State: icon + heading naming what's missing
// + supporting caption + an optional single primary-action button when actionable.

use Livewire\Livewire;
use Tigaphonic\Bazaar\Shell\Livewire\EmptyState;

it('renders the headline and supporting caption for a genuinely empty collection', function () {
    Livewire::test(EmptyState::class, [
        'headline' => 'Belum ada order',
        'caption' => 'Order baru akan muncul di sini.',
    ])
        ->assertSee('Belum ada order')
        ->assertSee('Order baru akan muncul di sini.');
});

it('renders a single primary action button only when the empty state is actionable', function () {
    Livewire::test(EmptyState::class, [
        'headline' => 'Belum ada Unit',
        'actionLabel' => 'Tambah Unit',
    ])
        ->assertSee('Tambah Unit');

    Livewire::test(EmptyState::class, ['headline' => 'Belum ada order'])
        ->assertDontSee('Tambah Unit');
});
