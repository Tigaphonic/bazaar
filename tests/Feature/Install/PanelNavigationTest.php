<?php

use Filament\Facades\Filament;

it('shows the User & Access and Global Settings navigation groups after install', function () {
    $this->artisan('bazaar:install');

    $groups = collect(Filament::getNavigationGroups())
        ->map(fn ($group) => $group->getLabel());

    expect($groups)->toContain('User & Access')
        ->and($groups)->toContain('Global Settings');
});
