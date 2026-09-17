<?php

// Story 1.5 RED-PHASE scaffold — AC2 (immutability / read-only mutlak):
//
// "Given Staff membuka Audit Trail. When Staff mencoba mengedit atau menghapus
// sebuah entri. Then tidak ada kontrol edit/delete tersedia di UI manapun —
// read-only mutlak."
//
// NFR4 (Architecture Spine): Audit Trail bersifat append-only — tidak bisa
// diedit atau dihapus lewat UI manapun oleh User mana pun, termasuk yang
// memiliki permission administratif.
//
// Two layers of enforcement tested here:
//   1. AuditTrailResource exposes no DeleteAction and no EditAction in the
//      Filament table — the resource is list-only, no record pages for edit.
//   2. AuditTrailService has no delete() or update() method at all —
//      the Service contract itself is read-only, enforcing NFR4 at the
//      domain boundary (AD-5), not only at the UI layer.

use Livewire\Livewire;
use Tigaphonic\Bazaar\User\Filament\Resources\AuditTrailResource;
use Tigaphonic\Bazaar\User\Filament\Resources\AuditTrailResource\Pages\ListAuditTrail;
use Tigaphonic\Bazaar\User\Services\AuditTrailService;

// ---------------------------------------------------------------------------
// AC2 — No edit or delete action in the Filament table
// ---------------------------------------------------------------------------

it('does not expose a DeleteAction in the AuditTrail table')
    ->skip('Story 1.5 not implemented yet — RED PHASE');

it('does not expose a bulk DeleteAction in the AuditTrail table')
    ->skip('Story 1.5 not implemented yet — RED PHASE');

it('does not expose an EditAction in the AuditTrail table')
    ->skip('Story 1.5 not implemented yet — RED PHASE');

it('does not have a record edit page registered for AuditTrailResource')
    ->skip('Story 1.5 not implemented yet — RED PHASE');

it('does not have a record create page registered for AuditTrailResource')
    ->skip('Story 1.5 not implemented yet — RED PHASE');

// ---------------------------------------------------------------------------
// AC2 — AuditTrailService has no mutation methods (NFR4 at domain boundary)
// ---------------------------------------------------------------------------

it('AuditTrailService does not expose a delete() or update() method, enforcing NFR4 append-only at the domain boundary', function () {
    // Reflection check on the Service class contract — this test goes red if
    // a developer adds a mutation method to AuditTrailService.
    // The class itself does not exist yet (story is in backlog), so this will
    // correctly fail at class resolution until Story 1.5 is implemented.
    $service = app(AuditTrailService::class);
    $reflection = new ReflectionClass($service);

    $publicMethods = collect($reflection->getMethods(ReflectionMethod::IS_PUBLIC))
        ->filter(fn ($m) => ! $m->isConstructor())
        ->map(fn ($m) => $m->getName())
        ->values()
        ->toArray();

    expect($publicMethods)
        ->not->toContain('delete')
        ->not->toContain('destroy')
        ->not->toContain('update')
        ->not->toContain('edit')
        ->not->toContain('truncate');
})->skip('Story 1.5 not implemented yet — RED PHASE');
