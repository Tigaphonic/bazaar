<?php

// Story 1.6 RED-PHASE scaffold — Filament Page: GlobalSettings.
// AC1: Staff dapat melihat halaman Global Settings di panel Filament.
// AC2: Staff dapat mengedit dan save; Filament form wire ke SettingsService.
// AC3: Tidak ada tombol Create / Delete — hanya Edit form.
// AC13: Hanya Staff dengan permission 'manage-settings' bisa akses.
//
// Namespace final GlobalSettings Page akan mengextend HasSettingsForms
// (spatie/laravel-settings + filament plugin) bukan Filament\\Pages\\Page polos.
// Placeholder yang ada sekarang belum punya form — semua test ini merah.

use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tigaphonic\Bazaar\Settings\Filament\Pages\GlobalSettings;
use Workbench\App\Models\User;

// ---------------------------------------------------------------------------
// AC1: Halaman dapat dirender oleh Staff berwenang
// ---------------------------------------------------------------------------

it('renders the GlobalSettings page without error for a Staff with manage-settings permission', function () {

    Permission::create(['name' => 'manage-settings']);
    $role = Role::create(['name' => 'Admin']);
    $role->givePermissionTo('manage-settings');

    $staff = User::create(['name' => 'Admin', 'email' => 'admin@example.com', 'password' => bcrypt('password')]);
    $staff->assignRole($role);

    $this->actingAs($staff);

    Livewire::test(GlobalSettings::class)
        ->assertStatus(200);
});

it('returns 403 for a Staff without the manage-settings permission', function () {

    $staff = User::create(['name' => 'Noob', 'email' => 'noob@example.com', 'password' => bcrypt('password')]);
    $this->actingAs($staff);

    Livewire::test(GlobalSettings::class)
        ->assertForbidden();
});

// ---------------------------------------------------------------------------
// AC3: Tidak ada action Create atau Delete — hanya Edit form
// ---------------------------------------------------------------------------

it('GlobalSettings page has no Create action', function () {

    Permission::create(['name' => 'manage-settings']);
    $role = Role::create(['name' => 'Admin']);
    $role->givePermissionTo('manage-settings');

    $staff = User::create(['name' => 'Admin', 'email' => 'admin2@example.com', 'password' => bcrypt('password')]);
    $staff->assignRole($role);
    $this->actingAs($staff);

    $component = Livewire::test(GlobalSettings::class);

    // Halaman settings adalah single-record edit — tidak boleh punya HeaderAction create
    expect(method_exists($component->instance(), 'getHeaderActions'))->toBeTrue();
    $actions = $component->instance()->getHeaderActions();
    $actionNames = collect($actions)->map(fn ($a) => $a->getName())->toArray();

    expect($actionNames)->not->toContain('create');
});

// ---------------------------------------------------------------------------
// AC2: Form submit memanggil SettingsService::update() dan persists data
// ---------------------------------------------------------------------------

it('submitting the GlobalSettings form with a new store_name persists via SettingsService', function () {

    Permission::create(['name' => 'manage-settings']);
    $role = Role::create(['name' => 'Admin']);
    $role->givePermissionTo('manage-settings');

    $staff = User::create(['name' => 'Admin', 'email' => 'admin3@example.com', 'password' => bcrypt('password')]);
    $staff->assignRole($role);
    $this->actingAs($staff);

    Livewire::test(GlobalSettings::class)
        ->fillForm(['store_name' => 'Toko Baru Sekali'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(app(\Tigaphonic\Bazaar\Settings\Services\SettingsService::class)->get()->store_name)
        ->toBe('Toko Baru Sekali');
});

it('the GlobalSettings form shows a success toast notification after a valid save', function () {

    Permission::create(['name' => 'manage-settings']);
    $role = Role::create(['name' => 'Admin']);
    $role->givePermissionTo('manage-settings');

    $staff = User::create(['name' => 'Admin', 'email' => 'admin4@example.com', 'password' => bcrypt('password')]);
    $staff->assignRole($role);
    $this->actingAs($staff);

    Livewire::test(GlobalSettings::class)
        ->fillForm(['store_name' => 'Test'])
        ->call('save')
        ->assertNotified();
});

// ---------------------------------------------------------------------------
// AC9: Form validates Min < Max Refund % before calling SettingsService
// ---------------------------------------------------------------------------

it('the GlobalSettings form shows a validation error when refund_min_percent >= refund_max_percent', function () {

    Permission::create(['name' => 'manage-settings']);
    $role = Role::create(['name' => 'Admin']);
    $role->givePermissionTo('manage-settings');

    $staff = User::create(['name' => 'Admin', 'email' => 'admin5@example.com', 'password' => bcrypt('password')]);
    $staff->assignRole($role);
    $this->actingAs($staff);

    Livewire::test(GlobalSettings::class)
        ->fillForm(['refund_min_percent' => 80, 'refund_max_percent' => 20])
        ->call('save')
        ->assertHasFormErrors(['refund_min_percent']);
});

// ---------------------------------------------------------------------------
// AC1: Navigasi — page terdaftar di sidebar dengan group "Global Settings"
// ---------------------------------------------------------------------------

it('GlobalSettings page is registered under the Global Settings navigation group', function () {

    expect(GlobalSettings::getNavigationGroup())->toBe('Global Settings');
});

// ---------------------------------------------------------------------------
// AC12: Widget bazaar:status menampilkan heartbeat queue worker & scheduler
// (wiring ke GlobalSettings widget yang sudah ada dari Story 1.1/bazaar:status)
// ---------------------------------------------------------------------------

it('GlobalSettings page contains the bazaar status widget showing queue and scheduler heartbeat', function () {

    Permission::create(['name' => 'manage-settings']);
    $role = Role::create(['name' => 'Admin']);
    $role->givePermissionTo('manage-settings');

    $staff = User::create(['name' => 'Admin', 'email' => 'admin6@example.com', 'password' => bcrypt('password')]);
    $staff->assignRole($role);
    $this->actingAs($staff);

    $component = Livewire::test(GlobalSettings::class);

    $widgetClasses = collect($component->instance()->getWidgets())
        ->map(fn ($w) => is_string($w) ? $w : get_class($w))
        ->toArray();

    expect($widgetClasses)->toContain(\Tigaphonic\Bazaar\Install\Filament\Widgets\BazaarStatusWidget::class);
});

it('keeps the provider identity of gateway and courier rows after a form save', function () {
    Permission::create(['name' => 'manage-settings']);
    $role = Role::create(['name' => 'Admin']);
    $role->givePermissionTo('manage-settings');

    $staff = User::create(['name' => 'Admin', 'email' => 'admin7@example.com', 'password' => bcrypt('password')]);
    $staff->assignRole($role);
    $this->actingAs($staff);

    Livewire::test(GlobalSettings::class)
        ->fillForm(['store_name' => 'Provider Check'])
        ->call('save')
        ->assertHasNoFormErrors();

    $settings = app(\Tigaphonic\Bazaar\Settings\Services\SettingsService::class)->get();

    expect($settings->payment_gateways[0]['provider'])->toBe('midtrans')
        ->and($settings->shipping_couriers[0]['provider'])->toBe('rajaongkir');
});
