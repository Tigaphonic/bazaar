<?php

it('shows the User & Access and Global Settings navigation groups after install', function () {
    $this->artisan('bazaar:install');

    $groups = collect(\Filament\Facades\Filament::getNavigationGroups())
        ->map(fn ($group) => $group->getLabel());

    expect($groups)->toContain('User & Access')
        ->and($groups)->toContain('Global Settings');
})->skip('AC4 / UX-DR13 — panel navigation groups are not registered yet (Story 1.1)');
