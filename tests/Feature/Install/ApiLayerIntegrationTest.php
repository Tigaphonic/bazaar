<?php

// API Layer is opt-in (FR-35): with the default config no Bazaar API route
// exists. Webhook ingress is never gated by the flag (AD-11); Payment and
// Shipping own those routes, so here the guarantee is structural: the only
// route file behind the flag is routes/api.php and it carries no webhook.

$packageRoot = dirname(__DIR__, 3);

function apiRouteFileCode(string $packageRoot): string
{
    // Comments explain the AD-11 split and legitimately mention webhooks.
    return strtolower(preg_replace('/^\s*\/\/.*$/m', '', file_get_contents($packageRoot.'/routes/api.php')));
}

function bazaarRouteUris(): \Illuminate\Support\Collection
{
    return collect(app('router')->getRoutes()->getRoutes())
        ->map(fn ($route) => $route->uri())
        ->filter(fn (string $uri) => str_contains($uri, 'bazaar'))
        ->values();
}

it('[1.9-API-001][P0] config bazaar.api.enabled defaults to false', function () {
    expect(config('bazaar.api.enabled'))->toBeFalse();
});

it('[1.9-API-002][P0] no bazaar API routes registered when api.enabled is false', function () {
    expect(bazaarRouteUris()->reject(fn (string $uri) => str_contains($uri, 'webhook')))->toHaveCount(0);
});

it('[1.9-API-003][P0] absent config key behaves as disabled', function () use ($packageRoot) {
    config()->set('bazaar.api', null);

    expect(config('bazaar.api.enabled', false))->toBeFalse()
        ->and(bazaarRouteUris()->filter(fn (string $uri) => str_contains($uri, 'bazaar/api')))->toHaveCount(0)
        ->and(file_get_contents($packageRoot.'/src/BazaarServiceProvider.php'))
        ->toContain("config('bazaar.api.enabled', false) === true");
});

it('[1.9-API-009][P1] API routes stay absent while the flag is off', function () {
    expect(bazaarRouteUris()->filter(fn (string $uri) => str_contains($uri, 'bazaar/api')))->toHaveCount(0);
});

it('[1.9-API-010][P0] the flag-gated route file carries no Payment webhook (AD-11)', function () use ($packageRoot) {
    $routes = apiRouteFileCode($packageRoot);

    expect($routes)->not->toContain('webhook')
        ->not->toContain('callback')
        ->not->toContain('midtrans')
        ->not->toContain('payment');
});

it('[1.9-API-011][P0] the flag-gated route file carries no Shipping webhook (AD-11)', function () use ($packageRoot) {
    $routes = apiRouteFileCode($packageRoot);

    expect($routes)->not->toContain('rajaongkir')
        ->not->toContain('shipping');
});

it('[1.9-API-012][P0] routes/api.php is the only route file behind the flag', function () use ($packageRoot) {
    $provider = file_get_contents($packageRoot.'/src/BazaarServiceProvider.php');

    preg_match_all('/loadRoutesFrom\(([^)]*)\)/', $provider, $calls);

    expect($calls[1])->toHaveCount(1)
        ->and($calls[1][0])->toContain('routes/api.php');
});

it('[1.9-API-014][P2] README documents API Layer activation and Sanctum token setup', function () use ($packageRoot) {
    $content = file_get_contents($packageRoot.'/README.md');

    expect($content)->toContain('bazaar.api.enabled')
        ->toContain('createToken')
        ->toContain('HasApiTokens');
});
