<?php

// Story 1.3 RED-PHASE scaffold: Tigaphonic\Bazaar\User\Filament\Resources\RoleResource
// does not exist yet. AC1's Given clause: "Staff berwenang membuka User & Access ->
// Roles" -- EXPERIENCE.md §Navigation: "Roles & Permissions | Sidebar -> User & Access
// -> Roles | Create Role, check granular permissions, no deploy needed (FR-19)".

use Filament\Facades\Filament;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tigaphonic\Bazaar\User\Filament\Resources\RoleResource;
use Tigaphonic\Bazaar\User\Filament\Resources\RoleResource\Pages\ListRoles;

it('lists every Role with its name column', function () {
    $role = Role::create(['name' => 'Supervisor Retur']);

    Livewire::test(ListRoles::class)
        ->assertCanSeeTableRecords([$role])
        ->assertTableColumnExists('name');
});

it('registers the Roles resource under the User & Access navigation group', function () {
    $this->artisan('bazaar:install');

    expect(RoleResource::getNavigationGroup())->toBe('User & Access');
});

it('registers RoleResource on the panel so the Roles admin screen cannot be silently dropped', function () {
    $this->artisan('bazaar:install');

    $panel = Filament::getDefaultPanel();

    expect($panel->getResources())->toContain(RoleResource::class);
});
