<?php

// Story 1.3 RED-PHASE scaffold: Tigaphonic\Bazaar\User\Filament\Resources\RoleResource
// does not exist yet. AC1's Given clause: "Staff berwenang membuka User & Access ->
// Roles" -- EXPERIENCE.md §Navigation: "Roles & Permissions | Sidebar -> User & Access
// -> Roles | Create Role, check granular permissions, no deploy needed (FR-19)".

use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tigaphonic\Bazaar\User\Filament\Resources\RoleResource;
use Tigaphonic\Bazaar\User\Filament\Resources\RoleResource\Pages\ListRoles;

it('lists every Role with its name column', function () {
    $role = Role::create(['name' => 'Supervisor Retur']);

    Livewire::test(ListRoles::class)
        ->assertCanSeeTableRecords([$role])
        ->assertTableColumnExists('name');
})->skip('Story 1.3 not implemented — RoleResource\Pages\ListRoles does not exist yet');

it('registers the Roles resource under the User & Access navigation group', function () {
    $this->artisan('bazaar:install');

    expect(RoleResource::getNavigationGroup())->toBe('User & Access');
})->skip('Story 1.3 not implemented — RoleResource does not exist yet');
