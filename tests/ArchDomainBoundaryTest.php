<?php

// AC4 / AD-5 / AD-6: cross-domain access to a domain's Models/Actions is only
// allowed through that domain's own Services. This rule is discovered dynamically
// against whatever src/{Domain} directories exist, so it starts enforcing itself
// the moment Epic 1's domains (Install, User, Settings) land — no edit needed here.
$domainDirectories = collect(glob(__DIR__.'/../src/*', GLOB_ONLYDIR))
    ->map(fn (string $path) => basename($path))
    ->reject(fn (string $domain) => in_array($domain, ['Facades', 'Commands'], true))
    ->values();

it('has at least one domain-grouped src/{Domain} directory to enforce AD-5/AD-6 boundaries against', function () use ($domainDirectories) {
    expect($domainDirectories)->not->toBeEmpty();
});

foreach ($domainDirectories as $domain) {
    $modelsNamespace = "Tigaphonic\\Bazaar\\{$domain}\\Models";
    $actionsNamespace = "Tigaphonic\\Bazaar\\{$domain}\\Actions";
    $servicesNamespace = "Tigaphonic\\Bazaar\\{$domain}\\Services";

    arch("{$domain}: Models are only used from within {$domain}'s own Services/Actions (AD-5)")
        ->expect($modelsNamespace)
        ->toOnlyBeUsedIn([$modelsNamespace, $actionsNamespace, $servicesNamespace]);

    arch("{$domain}: Actions are only used from within {$domain}'s own Services (AD-5)")
        ->expect($actionsNamespace)
        ->toOnlyBeUsedIn([$actionsNamespace, $servicesNamespace]);
}
