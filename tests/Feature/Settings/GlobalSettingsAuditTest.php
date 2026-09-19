<?php

use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Tigaphonic\Bazaar\Settings\Exceptions\InvalidSettingValueException;
use Tigaphonic\Bazaar\Settings\Services\SettingsService;
use Tigaphonic\Bazaar\User\Models\AuditTrail;

it('records only the changed keys in one Audit Trail entry', function () {
    app(SettingsService::class)->update(['store_name' => 'Toko A', 'timeout_otp_minutes' => 9]);

    $entry = AuditTrail::query()->where('log_name', 'bazaar')->sole();

    expect($entry->event)->toBe('updated')
        ->and($entry->attribute_changes['attributes'])->toBe(['timeout_otp_minutes' => 9, 'store_name' => 'Toko A'])
        ->and($entry->attribute_changes['old'])->toBe(['timeout_otp_minutes' => 5, 'store_name' => null]);
});

it('masks gateway credentials in the Audit Trail entry', function () {
    app(SettingsService::class)->update([
        'payment_gateways' => [
            ['provider' => 'midtrans', 'active' => true, 'server_key' => 'SB-Mid-server-secret', 'enabled_methods' => ['qris']],
        ],
    ]);

    $entry = AuditTrail::query()->where('log_name', 'bazaar')->sole();

    expect(json_encode($entry->attribute_changes))->not->toContain('SB-Mid-server-secret')
        ->and($entry->attribute_changes['attributes']['payment_gateways'])->toBe('***');
});

it('writes no Audit Trail entry when nothing changed', function () {
    app(SettingsService::class)->update(['timeout_otp_minutes' => 5]);

    expect(AuditTrail::query()->count())->toBe(0);
});

it('stores only explicitly enabled payment methods', function () {
    app(SettingsService::class)->update([
        'payment_gateways' => [
            ['provider' => 'midtrans', 'active' => true, 'server_key' => 'k', 'client_key' => 'c', 'enabled_methods' => ['qris', 'gopay']],
        ],
    ]);

    expect(app(SettingsService::class)->get()->payment_gateways[0]['enabled_methods'])->toBe(['qris', 'gopay']);
});

it('rejects a value of the wrong type', function () {
    expect(fn () => app(SettingsService::class)->update(['timeout_otp_minutes' => '5']))
        ->toThrow(InvalidSettingValueException::class);
});

it('creates the manage-settings permission when the permission tables exist', function () {
    expect(Schema::hasTable('permissions'))->toBeTrue();

    (include __DIR__.'/../../../database/data-migrations/seed_bazaar_manage_settings_permission.php')->up();

    expect(Permission::query()->where('name', 'manage-settings')->count())->toBe(1);

    (include __DIR__.'/../../../database/data-migrations/seed_bazaar_manage_settings_permission.php')->up();

    expect(Permission::query()->where('name', 'manage-settings')->count())->toBe(1);
});
