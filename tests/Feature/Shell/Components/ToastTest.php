<?php

// Story 1.2 RED-PHASE scaffold: Tigaphonic\Bazaar\Shell\Support\Toast does not exist
// yet. DESIGN.md §Components → Toast: "Net-new, required on every user action per
// decision log." Filament already ships a native toast/notification system
// (Filament\Notifications\Notification + the assertNotified() test helper) — Bazaar's
// job is a thin, consistently-styled wrapper every later epic's actions call, not a
// bespoke component built from scratch.

use Filament\Notifications\Notification;
use Tigaphonic\Bazaar\Shell\Support\Toast;

use function Filament\Notifications\Testing\assertNotified;

it('fires a success toast that later epics can call after a state-changing action', function () {
    Toast::success('AWB berhasil dibuat');

    assertNotified();
})->skip('Story 1.2 not implemented — Shell\Support\Toast does not exist yet');

it('maps each Toast variant to the matching DESIGN.md semantic family', function () {
    Toast::danger('Gagal memproses');

    Notification::assertNotified(
        Notification::make()->danger()->title('Gagal memproses')
    );
})->skip('Story 1.2 not implemented — Shell\Support\Toast does not exist yet');
