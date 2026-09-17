<?php

// spec-simplify-topbar-native-filament.md: RoleResource gained
// `$recordTitleAttribute = 'name'` so Filament's native global search (active by
// default, HasGlobalSearch.php:31) can actually return Role results -- that addition
// had zero test coverage. This locks it down so a typo'd or removed attribute fails a
// test, not just silently breaks search in the running app.

use Spatie\Permission\Models\Role;
use Tigaphonic\Bazaar\User\Filament\Resources\RoleResource;

it('returns a matching Role in native global search results', function () {
    $this->artisan('bazaar:install');

    Role::create(['name' => 'Supervisor Retur Global Search']);

    $results = RoleResource::getGlobalSearchResults('Supervisor Retur Global Search');

    expect($results->pluck('title'))->toContain('Supervisor Retur Global Search');
});
