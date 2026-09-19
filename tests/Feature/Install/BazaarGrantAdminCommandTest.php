<?php

use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tigaphonic\Bazaar\Settings\Filament\Pages\GlobalSettings;
use Workbench\App\Models\User;

it('gives an existing user the Admin Role with every shipped permission, even when none existed yet', function () {
    $user = User::create(['name' => 'First', 'email' => 'first@example.com', 'password' => bcrypt('password')]);

    expect(Permission::query()->where('name', 'manage-settings')->exists())->toBeFalse();

    $this->artisan('bazaar:grant-admin', ['email' => 'first@example.com'])->assertSuccessful();

    $this->actingAs($user->refresh());

    expect($user->hasRole('Admin'))->toBeTrue()
        ->and(Role::findByName('Admin')->hasPermissionTo('manage-settings'))->toBeTrue()
        ->and(GlobalSettings::canAccess())->toBeTrue();
});

it('fails for an unknown email', function () {
    $this->artisan('bazaar:grant-admin', ['email' => 'nobody@example.com'])->assertFailed();
});

it('is idempotent', function () {
    User::create(['name' => 'First', 'email' => 'first@example.com', 'password' => bcrypt('password')]);

    $this->artisan('bazaar:grant-admin', ['email' => 'first@example.com'])->assertSuccessful();
    $this->artisan('bazaar:grant-admin', ['email' => 'first@example.com'])->assertSuccessful();

    expect(Role::query()->where('name', 'Admin')->count())->toBe(1);
});

it('offers manage-settings in the Role form checklist even if it was never seeded', function () {
    $options = app(\Tigaphonic\Bazaar\User\Services\RoleService::class)->permissionOptions();

    expect($options->all())->toHaveKey('manage-settings');
});
