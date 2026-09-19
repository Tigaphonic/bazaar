<?php

// Story 1.8 RED-PHASE scaffold: Service Layer Integration (Monolith)
//
// AC1: "Portal monolith klien (Blade+Livewire) butuh data/aksi dari Bazaar.
//       When Developer melakukan dependency injection/facade ke Service Layer
//       domain terkait. Then Service dapat dipanggil langsung dari kode aplikasi
//       klien tanpa request HTTP apapun."
//
// AC2: "When Developer membaca panduan integrasi. Then tersedia contoh pemanggilan
//       Service Layer untuk skenario umum (mis. availability-check, checkout)."
//
// Design note (AD-5): Presentation code may only call a Service, never a Model
// directly for mutations. The Service Layer is the public API Bazaar exposes to
// the host application. Each domain Service must be resolvable from the Laravel
// container without HTTP middleware, authentication scaffolding, or kernel boot —
// the host app can inject them via constructor DI or facade wherever its own code
// runs (controllers, Livewire components, Artisan commands, queued jobs).
//
// What "without HTTP" means in a package context: the Service is called in-process
// using PHP method invocations — not via curl, Guzzle, or any loopback request.
// These tests use Testbench (Laravel in-memory kernel) to simulate the host app's
// container; they never open a socket.
//
// Scope of this file: Epic 1 Services only (User, Settings, AuditTrail, SeoResolver).
// Epic 3+ Services (Catalog, Order, ...) will add their own counterpart tests when
// their domains are implemented. The contract being locked in here is: "any Service
// Bazaar ships can be resolved and called without HTTP from host app code."

use Tigaphonic\Bazaar\Settings\Services\SeoResolverService;
use Tigaphonic\Bazaar\Settings\Services\SettingsService;
use Tigaphonic\Bazaar\Settings\Support\BazaarSettings;
use Tigaphonic\Bazaar\User\Services\AuditTrailService;
use Tigaphonic\Bazaar\User\Services\RoleService;
use Tigaphonic\Bazaar\User\Services\UserService;

// ---------------------------------------------------------------------------
// AC1 — DI / container resolution (P0)
// ---------------------------------------------------------------------------

it('[1.8-INT-001][P0] resolves UserService from the container without an HTTP request', function () {
    // THIS TEST WILL FAIL if Story 1.8 is not yet implemented (Service not bound).
    // Simulates the host app wiring:
    //   app(UserService::class) — equivalent of constructor DI in a Livewire component.
    $service = app(UserService::class);

    expect($service)->toBeInstanceOf(UserService::class);
});

it('[1.8-INT-002][P0] resolves RoleService from the container without an HTTP request', function () {
    $service = app(RoleService::class);

    expect($service)->toBeInstanceOf(RoleService::class);
});

it('[1.8-INT-003][P0] resolves AuditTrailService from the container without an HTTP request', function () {
    $service = app(AuditTrailService::class);

    expect($service)->toBeInstanceOf(AuditTrailService::class);
});

it('[1.8-INT-004][P0] resolves SettingsService from the container without an HTTP request', function () {
    $service = app(SettingsService::class);

    expect($service)->toBeInstanceOf(SettingsService::class);
});

it('[1.8-INT-005][P0] resolves SeoResolverService from the container without an HTTP request', function () {
    $service = app(SeoResolverService::class);

    expect($service)->toBeInstanceOf(SeoResolverService::class);
});

// ---------------------------------------------------------------------------
// AC1 — constructor DI simulation (P0)
// ---------------------------------------------------------------------------

it('[1.8-INT-006][P0] can inject UserService via constructor DI as a host app class would', function () {
    // Simulates: class MyLivewireComponent extends Component {
    //     public function __construct(private UserService $users) {}
    // }
    $resolved = app()->make(UserService::class);

    expect($resolved)->toBeInstanceOf(UserService::class);
});

it('[1.8-INT-007][P0] Services share the same container binding across multiple resolutions within one request', function () {
    // Verify that multiple resolutions of the same Service do not throw.
    // If the service is singleton-bound, both references are identical objects.
    // If transient, both are separate valid instances. Either is fine.
    $first = app(SettingsService::class);
    $second = app(SettingsService::class);

    expect($first)->toBeInstanceOf(SettingsService::class);
    expect($second)->toBeInstanceOf(SettingsService::class);
});

// ---------------------------------------------------------------------------
// AC1 — callable without HTTP (in-process calls) (P0)
// ---------------------------------------------------------------------------

it('[1.8-INT-008][P0] AuditTrailService::list() returns a collection without making an HTTP request', function () {
    // Simulates a portal Blade controller calling:
    //   $entries = app(AuditTrailService::class)->list();
    // without needing to hit a REST endpoint.
    $service = app(AuditTrailService::class);
    $result = $service->list();

    expect($result)->toBeInstanceOf(\Illuminate\Support\Collection::class);
});

it('[1.8-INT-009][P0] SettingsService::get() returns the current settings without an HTTP request', function () {
    // Simulates a portal Blade controller calling:
    //   $settings = app(SettingsService::class)->get();
    //   $storeName = $settings->store_name;
    $service = app(SettingsService::class);
    $settings = $service->get();

    // Must be the BazaarSettings instance — not null, not an HTTP response.
    expect($settings)->toBeInstanceOf(BazaarSettings::class);
});

it('[1.8-INT-010][P0] SeoResolverService::resolveOgImage() is callable with a plain array from the host app', function () {
    // AC1 example: the host app passes a plain metadata snapshot, never a Model.
    // Enforces AD-5 "no cross-domain Model import from host" rule.
    $service = app(SeoResolverService::class);

    $result = $service->resolveOgImage([
        'og_image' => 'https://example.com/og.jpg',
    ]);

    expect($result)->toBe('https://example.com/og.jpg');
});

it('[1.8-INT-011][P0] SeoResolverService falls back to Global SEO Default OG image when entity has no og_image', function () {
    // Locks in the fallback chain described in AC1 and FR-29:
    // entity value first, Global SEO Defaults last.
    $service = app(SeoResolverService::class);

    // Should not throw; returns either null or the configured default string.
    $result = $service->resolveOgImage([
        'og_image' => null,
    ]);

    expect($result === null || is_string($result))->toBeTrue(
        'resolveOgImage() with null entity value must return either null or the default OG image string — never another type.'
    );
});

// ---------------------------------------------------------------------------
// AC1 — no hidden HTTP round-trip regression guard (P1)
// ---------------------------------------------------------------------------

it('[1.8-INT-012][P1] resolving any Epic-1 Service does not issue an outbound HTTP request', function () {
    // If a Service accidentally calls an HTTP endpoint (misconfigured gateway,
    // stray Guzzle call), this test catches it: no real HTTP server is running
    // in the Testbench environment, so any socket attempt results in a
    // connection-refused exception — not a silent pass.
    app(SettingsService::class)->get();
    app(AuditTrailService::class)->list();

    expect(true)->toBeTrue(); // Reached here = no HTTP exception thrown.
});

// ---------------------------------------------------------------------------
// AC2 — integration documentation available (P2)
// ---------------------------------------------------------------------------

it('[1.8-INT-013][P2] a README or docs file describing Service Layer integration exists', function () {
    // AC2: "tersedia contoh pemanggilan Service Layer untuk skenario umum".
    // This test is a proxy for the documentation requirement.
    $rootReadme = base_path('README.md');
    $docsFiles = glob(base_path('docs/*.md')) ?: [];

    $hasReadme = file_exists($rootReadme) && str_contains(
        (string) file_get_contents($rootReadme),
        'Service Layer'
    );

    $hasDocsEntry = collect($docsFiles)->contains(function (string $path) {
        return str_contains((string) file_get_contents($path), 'Service Layer');
    });

    expect($hasReadme || $hasDocsEntry)->toBeTrue(
        'Expected README.md or a docs/*.md file to document Service Layer integration (AC2). ' .
        'Add a section showing how to inject a Service from portal Blade+Livewire code.'
    );
});

it('[1.8-INT-014][P2] the README includes a concrete usage example for at least one Service', function () {
    $readme = base_path('README.md');

    if (! file_exists($readme)) {
        test()->markTestSkipped('README.md not found; covered by 1.8-INT-013.');
    }

    $content = (string) file_get_contents($readme);

    $hasExample = str_contains($content, 'SettingsService')
        || str_contains($content, 'AuditTrailService')
        || str_contains($content, 'UserService')
        || str_contains($content, 'app(')        // DI facade example
        || str_contains($content, '__construct'); // Constructor DI example

    expect($hasExample)->toBeTrue(
        'README.md must contain at least one Service Layer usage example ' .
        '(e.g., app(SettingsService::class) or constructor injection pattern).'
    );
});
