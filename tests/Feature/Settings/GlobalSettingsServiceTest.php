<?php

// Story 1.6 RED-PHASE scaffold. AC: "Staff dapat melihat & mengedit parameter
// konfigurasi sistem yang sifatnya tetap" (FR-28) — SettingsService sebagai
// satu-satunya gateway (AD-5). Parameter fixed dari awal; tidak bisa tambah/hapus
// lewat UI. Kredensial payment gateway tersimpan terenkripsi. Min < Max Refund %.
// Semua uang whole-Rupiah unsigned bigint (epic-1-context.md).
//
// Pact tidak relevan di sini: tidak ada dua independently-deployable service yang
// saling memanggil dalam repo ini. Tidak ada browser E2E khusus untuk Settings
// (PHP Pest Feature test sudah cukup untuk logika service + Filament page).

use Tigaphonic\Bazaar\Settings\Services\SettingsService;

// ---------------------------------------------------------------------------
// AC2: Staff dapat mengedit dan save parameter; perubahan persisten di database
// ---------------------------------------------------------------------------

it('SettingsService::get() returns current settings with all required keys present', function () {
    test()->skip('RED — SettingsService belum ada; dibuat Story 1.6');

    $settings = app(SettingsService::class)->get();

    expect($settings)->toBeObject()
        ->and($settings)->toHaveProperties([
            'timeout_otp_minutes',
            'timeout_payment_minutes',
            'timeout_review_hours',
            'timeout_tokenized_page_days',
            'timeout_on_process_days',
            'timeout_auto_confirm_days',
            'payment_gateways',
            'shipping_couriers',
            'default_warehouse_id',
            'store_name',
            'store_logo',
            'store_favicon',
            'store_social_media',
            'refund_min_percent',
            'refund_max_percent',
            'robots_txt_content',
            'analytics_gsc_code',
            'analytics_ga4_code',
            'analytics_fb_pixel_code',
            'seo_default_meta_title_template',
            'seo_default_meta_description',
            'seo_default_og_image',
        ]);
});

it('SettingsService::update() persists changed values so the next get() returns updated data', function () {
    test()->skip('RED — SettingsService::update() belum ada; dibuat Story 1.6');

    $service = app(SettingsService::class);

    $service->update(['store_name' => 'Toko Baru']);

    expect($service->get()->store_name)->toBe('Toko Baru');
});

it('SettingsService::update() only changes the keys provided and leaves untouched keys intact', function () {
    test()->skip('RED — SettingsService::update() belum ada; dibuat Story 1.6');

    $service = app(SettingsService::class);
    $original = $service->get()->timeout_otp_minutes;

    $service->update(['store_name' => 'X']);

    expect($service->get()->timeout_otp_minutes)->toBe($original);
});

// ---------------------------------------------------------------------------
// AC3: Tidak bisa tambah parameter baru lewat service (fixed schema)
// ---------------------------------------------------------------------------

it('SettingsService::update() silently ignores unknown keys and does not persist them', function () {
    test()->skip('RED — SettingsService::update() belum ada; dibuat Story 1.6');

    $service = app(SettingsService::class);

    $service->update(['made_up_key' => 'value']);

    expect(isset($service->get()->made_up_key))->toBeFalse();
});

// ---------------------------------------------------------------------------
// AC4: Payment gateway credentials tersimpan terenkripsi
// ---------------------------------------------------------------------------

it('payment gateway credentials are stored encrypted — raw database value is not the plaintext credential', function () {
    test()->skip('RED — SettingsService + encryption belum ada; dibuat Story 1.6');

    $service = app(SettingsService::class);

    $service->update([
        'payment_gateways' => [
            ['provider' => 'midtrans', 'active' => true, 'server_key' => 'SB-Mid-server-abc123'],
        ],
    ]);

    // Nilai tersimpan di DB tidak boleh sama dengan plaintext (harus encrypted)
    $raw = \Illuminate\Support\Facades\DB::table('settings')
        ->where('group', 'bazaar')
        ->where('name', 'payment_gateways')
        ->value('payload');

    expect($raw)->not->toContain('SB-Mid-server-abc123');
});

it('SettingsService::get() decrypts payment gateway credentials transparently', function () {
    test()->skip('RED — SettingsService + encryption belum ada; dibuat Story 1.6');

    $service = app(SettingsService::class);

    $service->update([
        'payment_gateways' => [
            ['provider' => 'midtrans', 'active' => true, 'server_key' => 'SB-Mid-server-abc123'],
        ],
    ]);

    $gateways = $service->get()->payment_gateways;

    expect($gateways[0]['server_key'])->toBe('SB-Mid-server-abc123');
});

// ---------------------------------------------------------------------------
// AC5: Timeout Timer family dapat diatur
// ---------------------------------------------------------------------------

it('SettingsService::update() accepts all Timeout Timer parameters and persists them', function () {
    test()->skip('RED — SettingsService belum ada; dibuat Story 1.6');

    $service = app(SettingsService::class);

    $service->update([
        'timeout_otp_minutes'         => 5,
        'timeout_payment_minutes'     => 60,
        'timeout_review_hours'        => 72,
        'timeout_tokenized_page_days' => 5,
        'timeout_on_process_days'     => 3,
        'timeout_auto_confirm_days'   => 7,
    ]);

    $settings = $service->get();

    expect($settings->timeout_otp_minutes)->toBe(5)
        ->and($settings->timeout_payment_minutes)->toBe(60)
        ->and($settings->timeout_review_hours)->toBe(72)
        ->and($settings->timeout_tokenized_page_days)->toBe(5)
        ->and($settings->timeout_on_process_days)->toBe(3)
        ->and($settings->timeout_auto_confirm_days)->toBe(7);
});

// ---------------------------------------------------------------------------
// AC6: Default Warehouse adalah pointer ke 1 Warehouse yang valid
// ---------------------------------------------------------------------------

it('SettingsService::update() rejects a default_warehouse_id that does not exist in the warehouses table', function () {
    test()->skip('RED — SettingsService + Warehouse domain belum ada; dibuat Story 1.6');

    $service = app(SettingsService::class);

    expect(fn () => $service->update(['default_warehouse_id' => 'non-existent-ulid']))
        ->toThrow(\Tigaphonic\Bazaar\Settings\Exceptions\InvalidSettingValueException::class);
});

it('SettingsService::update() accepts a default_warehouse_id that references an existing Warehouse', function () {
    test()->skip('RED — SettingsService + Warehouse domain belum ada; dibuat Story 1.6');

    $warehouse = \Tigaphonic\Bazaar\Catalog\Models\Warehouse::factory()->create();
    $service = app(SettingsService::class);

    $service->update(['default_warehouse_id' => $warehouse->getKey()]);

    expect($service->get()->default_warehouse_id)->toBe($warehouse->getKey());
});

// ---------------------------------------------------------------------------
// AC7: Robots.txt Content tersimpan sebagai teks bebas; Bazaar tidak serve-nya
// ---------------------------------------------------------------------------

it('SettingsService::update() stores robots_txt_content as free-form text without modification', function () {
    test()->skip('RED — SettingsService belum ada; dibuat Story 1.6');

    $content = "User-agent: *\nDisallow: /cart\nDisallow: /checkout\n";
    $service  = app(SettingsService::class);

    $service->update(['robots_txt_content' => $content]);

    expect($service->get()->robots_txt_content)->toBe($content);
});

it('Bazaar does not register a route for /robots.txt — portal is responsible for fetching and serving it', function () {
    test()->skip('RED — perlu konfirmasi tidak ada route robots.txt; dibuat Story 1.6');

    $routes = collect(\Illuminate\Support\Facades\Route::getRoutes()->getRoutes())
        ->map(fn ($route) => $route->uri());

    expect($routes)->not->toContain('robots.txt');
});

// ---------------------------------------------------------------------------
// AC8: Analytics Verification Codes tersimpan terpisah per kolom
// ---------------------------------------------------------------------------

it('SettingsService::update() stores Google Search Console, GA4, and Facebook Pixel codes as separate fields', function () {
    test()->skip('RED — SettingsService belum ada; dibuat Story 1.6');

    $service = app(SettingsService::class);

    $service->update([
        'analytics_gsc_code'      => 'google-site-verification=abc',
        'analytics_ga4_code'      => 'G-XXXXXXXXXX',
        'analytics_fb_pixel_code' => '123456789012345',
    ]);

    $settings = $service->get();

    expect($settings->analytics_gsc_code)->toBe('google-site-verification=abc')
        ->and($settings->analytics_ga4_code)->toBe('G-XXXXXXXXXX')
        ->and($settings->analytics_fb_pixel_code)->toBe('123456789012345');
});

// ---------------------------------------------------------------------------
// AC9: Min/Max Refund % — Min < Max; nilai whole-number (bukan decimal)
// ---------------------------------------------------------------------------

it('SettingsService::update() rejects refund_min_percent greater than or equal to refund_max_percent', function () {
    test()->skip('RED — SettingsService + validation belum ada; dibuat Story 1.6');

    $service = app(SettingsService::class);

    expect(fn () => $service->update(['refund_min_percent' => 80, 'refund_max_percent' => 20]))
        ->toThrow(\Tigaphonic\Bazaar\Settings\Exceptions\InvalidSettingValueException::class);
});

it('SettingsService::update() rejects equal refund_min_percent and refund_max_percent', function () {
    test()->skip('RED — SettingsService + validation belum ada; dibuat Story 1.6');

    $service = app(SettingsService::class);

    expect(fn () => $service->update(['refund_min_percent' => 50, 'refund_max_percent' => 50]))
        ->toThrow(\Tigaphonic\Bazaar\Settings\Exceptions\InvalidSettingValueException::class);
});

it('SettingsService::update() accepts valid refund percent range where min is strictly less than max', function () {
    test()->skip('RED — SettingsService belum ada; dibuat Story 1.6');

    $service = app(SettingsService::class);

    $service->update(['refund_min_percent' => 20, 'refund_max_percent' => 80]);

    expect($service->get()->refund_min_percent)->toBe(20)
        ->and($service->get()->refund_max_percent)->toBe(80);
});

it('SettingsService::update() rejects decimal refund percent values — must be whole numbers', function () {
    test()->skip('RED — SettingsService + validation belum ada; dibuat Story 1.6');

    $service = app(SettingsService::class);

    expect(fn () => $service->update(['refund_min_percent' => 20.5, 'refund_max_percent' => 80]))
        ->toThrow(\Tigaphonic\Bazaar\Settings\Exceptions\InvalidSettingValueException::class);
});

// ---------------------------------------------------------------------------
// AC10: Global SEO Defaults (FR-29) — meta title template, description, OG image
// ---------------------------------------------------------------------------

it('SettingsService::update() persists Global SEO Defaults and get() returns them as fallback values', function () {
    test()->skip('RED — SettingsService belum ada; dibuat Story 1.6');

    $service = app(SettingsService::class);

    $service->update([
        'seo_default_meta_title_template' => '{nama entity} — {nama toko}',
        'seo_default_meta_description'    => 'Toko brand terpercaya.',
        'seo_default_og_image'            => '/images/og-default.jpg',
    ]);

    $settings = $service->get();

    expect($settings->seo_default_meta_title_template)->toBe('{nama entity} — {nama toko}')
        ->and($settings->seo_default_meta_description)->toBe('Toko brand terpercaya.')
        ->and($settings->seo_default_og_image)->toBe('/images/og-default.jpg');
});

// ---------------------------------------------------------------------------
// AC11: Mutasi settings tercatat di Audit Trail otomatis (tanpa instrumentasi manual)
// ---------------------------------------------------------------------------

it('updating settings via SettingsService creates an AuditTrail entry automatically without any manual activity() call', function () {
    test()->skip('RED — SettingsService belum ada; perlu konfirmasi Audit Trail auto-capture Settings; dibuat Story 1.6');

    $service = app(SettingsService::class);

    $service->update(['store_name' => 'Toko X']);

    $entry = \Tigaphonic\Bazaar\User\Models\AuditTrail::query()
        ->where('log_name', 'bazaar')
        ->latest()
        ->first();

    expect($entry)->not->toBeNull()
        ->and($entry->event)->toBe('updated');
});

it('does not require any direct activity() call inside SettingsService to capture audit events', function () {
    test()->skip('RED — SettingsService belum ada; dibuat Story 1.6');

    $source = file_get_contents(__DIR__.'/../../../src/Settings/Services/SettingsService.php');

    expect($source)
        ->not->toContain('activity()')
        ->not->toContain('LogActivity');
});

// ---------------------------------------------------------------------------
// AC13: Hanya Staff dengan permission yang benar bisa mengakses settings
// ---------------------------------------------------------------------------

it('SettingsService::get() throws AuthorizationException when called by a Staff without the manage-settings permission', function () {
    test()->skip('RED — SettingsService + Policy belum ada; dibuat Story 1.6');

    $staff = \Workbench\App\Models\User::create([
        'name'     => 'Unauthorized',
        'email'    => 'unauth@example.com',
        'password' => bcrypt('password'),
    ]);

    $this->actingAs($staff);

    expect(fn () => app(SettingsService::class)->get())
        ->toThrow(\Illuminate\Auth\Access\AuthorizationException::class);
});

it('SettingsService::update() throws AuthorizationException when called by a Staff without the manage-settings permission', function () {
    test()->skip('RED — SettingsService + Policy belum ada; dibuat Story 1.6');

    $staff = \Workbench\App\Models\User::create([
        'name'     => 'Unauthorized',
        'email'    => 'unauth2@example.com',
        'password' => bcrypt('password'),
    ]);

    $this->actingAs($staff);

    expect(fn () => app(SettingsService::class)->update(['store_name' => 'X']))
        ->toThrow(\Illuminate\Auth\Access\AuthorizationException::class);
});
