<?php

// Story 1.2 RED-PHASE scaffold: Tigaphonic\Bazaar\Shell\Livewire\Modal does not exist
// yet. DESIGN.md §Components → Modal/Dialog: "One modal open at a time; nested modals
// are not supported — a second action opens a new view instead."

use Livewire\Livewire;
use Tigaphonic\Bazaar\Shell\Livewire\Modal;

it('opens with a title, body, and footer action pair', function () {
    Livewire::test(Modal::class, ['title' => 'Konfirmasi', 'isOpen' => true])
        ->assertSee('Konfirmasi')
        ->assertSet('isOpen', true);
});

it('refuses to open a second modal while one is already open', function () {
    Livewire::test(Modal::class, ['isOpen' => true])
        ->call('open', ['title' => 'Second modal'])
        ->assertSet('isOpen', true)
        // The component must not silently stack a second modal state; opening
        // a second one while the first is open is a no-op at this level (the
        // real "opens a new view instead" behavior lives one level up, at the
        // page that hosts this component).
        ->assertDispatched('modal-open-refused');
});
