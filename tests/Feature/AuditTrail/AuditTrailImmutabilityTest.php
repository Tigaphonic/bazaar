<?php

// Story 1.5 AC2 (read-only mutlak, NFR4): no edit/delete control anywhere in the
// UI, and no mutation method on the domain boundary (AuditTrailService) either.

use Livewire\Livewire;
use Tigaphonic\Bazaar\User\Filament\Resources\AuditTrailResource;
use Tigaphonic\Bazaar\User\Filament\Resources\AuditTrailResource\Pages\ListAuditTrail;
use Tigaphonic\Bazaar\User\Models\AuditTrail;
use Tigaphonic\Bazaar\User\Services\AuditTrailService;

function seedAuditEntry(): AuditTrail
{
    return AuditTrail::query()->create([
        'log_name' => 'bazaar',
        'description' => 'created',
        'event' => 'created',
        'subject_type' => 'Some\\Entity',
        'subject_id' => '1',
    ]);
}

it('does not expose a DeleteAction in the AuditTrail table', function () {
    $entry = seedAuditEntry();

    Livewire::test(ListAuditTrail::class)
        ->assertTableActionDoesNotExist('delete', record: $entry);
});

it('does not expose a bulk DeleteAction in the AuditTrail table', function () {
    seedAuditEntry();

    Livewire::test(ListAuditTrail::class)
        ->assertTableBulkActionDoesNotExist('delete');
});

it('does not expose an EditAction in the AuditTrail table', function () {
    $entry = seedAuditEntry();

    Livewire::test(ListAuditTrail::class)
        ->assertTableActionDoesNotExist('edit', record: $entry);
});

it('does not have a record edit page registered for AuditTrailResource', function () {
    expect(AuditTrailResource::getPages())->not->toHaveKey('edit')
        ->and(AuditTrailResource::canEdit(seedAuditEntry()))->toBeFalse();
});

it('does not have a record create page registered for AuditTrailResource', function () {
    expect(AuditTrailResource::getPages())->not->toHaveKey('create')
        ->and(AuditTrailResource::canCreate())->toBeFalse();
});

it('does not allow deleting an entry through the AuditTrailResource', function () {
    expect(AuditTrailResource::canDelete(seedAuditEntry()))->toBeFalse()
        ->and(AuditTrailResource::canDeleteAny())->toBeFalse();
});

it('AuditTrailService does not expose a delete() or update() method, enforcing NFR4 append-only at the domain boundary', function () {
    $publicMethods = collect((new ReflectionClass(app(AuditTrailService::class)))->getMethods(ReflectionMethod::IS_PUBLIC))
        ->filter(fn (ReflectionMethod $method) => ! $method->isConstructor())
        ->map(fn (ReflectionMethod $method) => $method->getName())
        ->values()
        ->toArray();

    expect($publicMethods)
        ->not->toContain('delete')
        ->not->toContain('destroy')
        ->not->toContain('update')
        ->not->toContain('edit')
        ->not->toContain('truncate');
});
