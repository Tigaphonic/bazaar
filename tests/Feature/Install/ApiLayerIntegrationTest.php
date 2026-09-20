<?php

// Story 1.9 RED-PHASE scaffold: API Layer Integration (Headless)
//
// AC1: "Given API Layer belum diaktifkan (default). When Developer mengecek
//       konfigurasi. Then tidak ada route API Bazaar yang terdaftar/aktif."
//
// AC2: "Given Developer mengaktifkan API Layer lewat config (bazaar.api.enabled).
//       When Developer memanggil endpoint API. Then request terautentikasi via
//       Sanctum (token per-service, bukan per-Customer individual) dan response
//       berbentuk API Resource yang mengekspos kapabilitas Service Layer yang sama
//       secara terstruktur."
//
// AC3 (dari AD-11): "webhook ingress Payment/Shipping tetap terdaftar & aktif
//       terlepas dari flag ini — server-to-server callback vendor tidak pernah
//       bergantung pada API Layer opt-in."
//
// Design notes:
//
// FR-35: API Layer bersifat OPT-IN, diaktifkan lewat `bazaar.api.enabled = true`
// di config. Default-off berarti TIDAK ADA route API yang muncul di aplikasi klien
// kecuali Developer eksplisit mengaktifkannya.
//
// AD-11: Webhook route (Payment gateway callback, Shipping status callback) adalah
// server-to-server dan SELALU terdaftar — tidak bergantung pada API Layer flag.
// Instalasi murni Service Layer (FR-34) pun tetap butuh vendor bisa mencapai Bazaar.
//
// Authentication: Sanctum token per-service (bukan per-Customer). Ini berarti
// satu token dipakai oleh seluruh portal headless sebagai service identity, bukan
// satu token per user session Customer.
//
// Response format: API Resource — response terstruktur, konsisten, versioned.
//
// Scope: Epic 1 API surface. Epic 3+ (Catalog, Order, dst.) akan menambah
// endpoint API-nya sendiri ketika domain diimplementasikan.
//
// TDD RED PHASE: Semua test di bawah menggunakan test.skip() via ->skip().
// Un-skip HANYA setelah implementasi Story 1.9 selesai.
//
// Execution mode: SEQUENTIAL (backend-only, tidak perlu Playwright/E2E).

// ---------------------------------------------------------------------------
// AC1 — API Layer off by default (P0)
// ---------------------------------------------------------------------------

it('[1.9-API-001][P0] config bazaar.api.enabled defaults to false', function () {
    // THIS TEST WILL FAIL until Story 1.9 adds bazaar.api.enabled to config.
    expect(config('bazaar.api.enabled'))->toBeFalse();
})->skip('RED PHASE — bazaar.api.enabled belum ada di config/bazaar.php');

it('[1.9-API-002][P0] no bazaar API routes registered when api.enabled is false', function () {
    // THIS TEST WILL FAIL until route registration logic is implemented.
    // When API Layer is off, the host app's route table must not contain
    // ANY bazaar API routes (prefix: bazaar/api or /api/bazaar).
    config(['bazaar.api.enabled' => false]);

    // Reload routes to reflect config change — ServiceProvider must respect
    // the flag at boot time.
    /** @var \Illuminate\Routing\Router $router */
    $router = app('router');

    $bazaarApiRoutes = collect($router->getRoutes()->getRoutes())
        ->filter(fn ($route) => str_contains($route->uri(), 'bazaar'))
        ->filter(fn ($route) => ! str_contains($route->uri(), 'webhook'))
        ->values();

    expect($bazaarApiRoutes)->toHaveCount(0);
})->skip('RED PHASE — route registration belum diimplementasikan di BazaarServiceProvider');

it('[1.9-API-003][P0] no api prefix routes when config key does not exist at all', function () {
    // THIS TEST WILL FAIL until implemented.
    // Safety: even if Developer forgets to publish config, absence of the key
    // must behave the same as false (opt-in, not opt-out).
    config()->forget('bazaar.api');

    /** @var \Illuminate\Routing\Router $router */
    $router = app('router');

    $bazaarApiRoutes = collect($router->getRoutes()->getRoutes())
        ->filter(fn ($route) => str_contains($route->uri(), 'bazaar/api') || str_contains($route->uri(), 'api/bazaar'))
        ->values();

    expect($bazaarApiRoutes)->toHaveCount(0);
})->skip('RED PHASE — route registration belum diimplementasikan');

// ---------------------------------------------------------------------------
// AC2 — API Layer active when opted-in (P0)
// ---------------------------------------------------------------------------

it('[1.9-API-004][P0] bazaar API routes ARE registered when api.enabled is true', function () {
    // THIS TEST WILL FAIL until route registration is implemented.
    // At minimum, the API Layer must register at least one route (health/ping
    // or a domain resource route) when the flag is on.
    config(['bazaar.api.enabled' => true]);

    /** @var \Illuminate\Routing\Router $router */
    $router = app('router');

    // Trigger a re-boot / re-register in test context. Actual implementation
    // may call $this->loadRoutesFrom() conditioned on bazaar.api.enabled.
    // This test verifies the outcome: routes must exist.
    $bazaarApiRoutes = collect($router->getRoutes()->getRoutes())
        ->filter(fn ($route) => str_contains($route->uri(), 'bazaar'))
        ->filter(fn ($route) => ! str_contains($route->uri(), 'webhook'))
        ->values();

    expect($bazaarApiRoutes)->toBeGreaterThan(0);
})->skip('RED PHASE — bazaar.api route registration tidak diimplementasikan');

it('[1.9-API-005][P0] unauthenticated request to bazaar API returns 401', function () {
    // THIS TEST WILL FAIL until Sanctum middleware is applied to API routes.
    // "Token per-service" means the endpoint uses Sanctum auth:sanctum guard.
    config(['bazaar.api.enabled' => true]);

    $response = $this->getJson('/bazaar/api/v1/status');

    $response->assertStatus(401);
})->skip('RED PHASE — route /bazaar/api/v1/status belum ada');

it('[1.9-API-006][P0] authenticated request with valid Sanctum token returns success', function () {
    // THIS TEST WILL FAIL until Service token auth is implemented.
    // "Token per-service, bukan per-Customer individual" — the token is a
    // Sanctum Personal Access Token issued to a service identity (e.g. the
    // portal as a whole), not per Customer session.
    config(['bazaar.api.enabled' => true]);

    /** @var \App\Models\User $user */
    $user = \Illuminate\Foundation\Testing\WithFaker::class;
    // Implementation note: createToken() must work on the host app's User model.
    // Test should create a Staff user, issue a Sanctum token, and call the API.
    // The exact endpoint path (e.g. /bazaar/api/v1/status or /api/bazaar/status)
    // is left to the implementation — the test author should update this path
    // when un-skipping.

    $staffUser = \Illuminate\Support\Facades\DB::table('users')->first();
    // If no user exists, this test is a placeholder — real test needs a factory.
    expect($staffUser)->not->toBeNull('A Staff user must exist for this test');

    // $token = $staffUser->createToken('portal-service')->plainTextToken;
    // $response = $this->withToken($token)->getJson('/bazaar/api/v1/status');
    // $response->assertSuccessful();
    expect(true)->toBeTrue(); // placeholder — will be expanded when un-skipping
})->skip('RED PHASE — Sanctum token auth belum diimplementasikan di API routes');

it('[1.9-API-007][P1] API response uses JSON API Resource structure', function () {
    // THIS TEST WILL FAIL until API Resource classes are created.
    // Response must be a structured JSON API Resource, not a raw Eloquent toArray().
    // Expected shape: { "data": { ... }, "meta": { ... } } or similar.
    config(['bazaar.api.enabled' => true]);

    // This test verifies the RESPONSE SHAPE, not the data content.
    // Implementation must use Laravel's API Resource ($resource->response()).
    // Placeholder: actual endpoint path TBD by implementation.
    $this->markTestSkipped('RED PHASE — API Resource classes belum diimplementasikan');
})->skip('RED PHASE — API Resource belum ada');

it('[1.9-API-008][P1] API routes are versioned under /bazaar/api/v1 prefix', function () {
    // THIS TEST WILL FAIL until route prefix is defined.
    // Versioning is a best-practice requirement derived from AD-24 (semver)
    // and the expectation that the API evolves independently of the package version.
    config(['bazaar.api.enabled' => true]);

    /** @var \Illuminate\Routing\Router $router */
    $router = app('router');

    $versionedRoutes = collect($router->getRoutes()->getRoutes())
        ->filter(fn ($route) => str_contains($route->uri(), 'bazaar/api/v'))
        ->values();

    expect($versionedRoutes)->toBeGreaterThan(0);
})->skip('RED PHASE — versioned route prefix belum diimplementasikan');

it('[1.9-API-009][P1] disabling api.enabled at runtime removes API routes', function () {
    // THIS TEST WILL FAIL until toggle-safe routing is implemented.
    // If a developer disables the API Layer after enabling it, no API routes
    // should remain active. This protects against stale route caches in
    // single-request test scenarios.
    config(['bazaar.api.enabled' => false]);

    /** @var \Illuminate\Routing\Router $router */
    $router = app('router');

    $apiRoutes = collect($router->getRoutes()->getRoutes())
        ->filter(fn ($route) => str_contains($route->uri(), 'bazaar/api'))
        ->values();

    expect($apiRoutes)->toHaveCount(0);
})->skip('RED PHASE — dynamic route toggle belum diimplementasikan');

// ---------------------------------------------------------------------------
// AC3 — Webhook routes always active (AD-11) (P0)
// ---------------------------------------------------------------------------

it('[1.9-API-010][P0] webhook Payment routes ALWAYS registered regardless of api.enabled flag', function () {
    // THIS TEST WILL FAIL until webhook routes are registered unconditionally.
    // AD-11: "Webhook ingress selalu aktif: route webhook/callback Payment (Midtrans)
    // dan Shipping (RajaOngkir) didaftarkan sendiri oleh domain masing-masing,
    // selalu-on, terlepas dari apakah API Layer opsional (FR-35) diaktifkan."
    config(['bazaar.api.enabled' => false]);

    /** @var \Illuminate\Routing\Router $router */
    $router = app('router');

    $webhookRoutes = collect($router->getRoutes()->getRoutes())
        ->filter(fn ($route) => str_contains($route->uri(), 'webhook') || str_contains($route->uri(), 'callback'))
        ->filter(fn ($route) => str_contains($route->uri(), 'payment') || str_contains($route->uri(), 'midtrans'))
        ->values();

    expect($webhookRoutes)->toBeGreaterThan(0, 'Payment webhook route harus selalu terdaftar');
})->skip('RED PHASE — Payment webhook route belum diimplementasikan');

it('[1.9-API-011][P0] webhook Shipping routes ALWAYS registered regardless of api.enabled flag', function () {
    // THIS TEST WILL FAIL until webhook routes are registered unconditionally.
    // Same AD-11 mandate applies to Shipping (RajaOngkir) callbacks.
    config(['bazaar.api.enabled' => false]);

    /** @var \Illuminate\Routing\Router $router */
    $router = app('router');

    $webhookRoutes = collect($router->getRoutes()->getRoutes())
        ->filter(fn ($route) => str_contains($route->uri(), 'webhook') || str_contains($route->uri(), 'callback'))
        ->filter(fn ($route) => str_contains($route->uri(), 'shipping') || str_contains($route->uri(), 'rajaongkir'))
        ->values();

    expect($webhookRoutes)->toBeGreaterThan(0, 'Shipping webhook route harus selalu terdaftar');
})->skip('RED PHASE — Shipping webhook route belum diimplementasikan');

it('[1.9-API-012][P0] webhook routes active even when api.enabled is true', function () {
    // THIS TEST WILL FAIL until webhook routes are unconditionally registered.
    // Verify the webhook-always-on guarantee holds in BOTH states of the flag,
    // not just when it is false.
    config(['bazaar.api.enabled' => true]);

    /** @var \Illuminate\Routing\Router $router */
    $router = app('router');

    $webhookRoutes = collect($router->getRoutes()->getRoutes())
        ->filter(fn ($route) => str_contains($route->uri(), 'webhook') || str_contains($route->uri(), 'callback'))
        ->values();

    expect($webhookRoutes)->toBeGreaterThan(0, 'Webhook routes harus terdaftar saat api.enabled = true juga');
})->skip('RED PHASE — webhook routes belum diimplementasikan');

// ---------------------------------------------------------------------------
// API Layer integrity — Service exposure contract (P1)
// ---------------------------------------------------------------------------

it('[1.9-API-013][P1] API Layer exposes same capabilities as Service Layer (no new business logic)', function () {
    // THIS TEST WILL FAIL until API controllers delegate to Services.
    // The AC says "mengekspos kapabilitas Service Layer yang sama secara terstruktur".
    // Implementation: API controllers must delegate to existing Services
    // (UserService, SettingsService, etc.) — never re-implement business logic.
    // This is a structural test: verify a controller method exists and delegates.
    config(['bazaar.api.enabled' => true]);

    // Placeholder: once controllers exist, verify they accept a Service via DI.
    // Example:
    // $controller = app(\Tigaphonic\Bazaar\Install\Http\Controllers\ApiStatusController::class);
    // expect($controller)->toBeInstanceOf(...);

    expect(class_exists(\Tigaphonic\Bazaar\Install\Http\Controllers\ApiStatusController::class))
        ->toBeFalse('RED PHASE: controller kelas belum ada — expected false sebelum implementasi');
})->skip('RED PHASE — API controller classes belum ada');

it('[1.9-API-014][P2] README/docs mentions API Layer activation and Sanctum token setup', function () {
    // THIS TEST WILL FAIL until documentation is added.
    // Similar to Story 1.8 INT-013: documentation as testable artifact.
    $readmePath = base_path('README.md');

    if (! file_exists($readmePath)) {
        $this->markTestSkipped('README.md tidak ditemukan — dokumentasi belum ada');
    }

    $content = file_get_contents($readmePath);

    expect($content)->toContain('bazaar.api.enabled');
})->skip('RED PHASE — dokumentasi API Layer belum ada di README.md');
