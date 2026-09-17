<?php

// spec-simplify-topbar-native-filament.md: UserResource gained
// `$recordTitleAttribute = 'name'` so Filament's native global search (active by
// default, HasGlobalSearch.php:31) can actually return User results -- that addition
// had zero test coverage. This locks it down so a typo'd or removed attribute fails a
// test, not just silently breaks search in the running app.

use Tigaphonic\Bazaar\User\Filament\Resources\UserResource;
use Workbench\App\Models\User;

it('returns a matching User in native global search results', function () {
    $this->artisan('bazaar:install');

    $user = User::create([
        'name' => 'Rara Global Search',
        'email' => 'rara-global-search@example.com',
        'password' => bcrypt('password'),
    ]);

    $results = UserResource::getGlobalSearchResults('Rara Global Search');

    expect($results->pluck('title'))->toContain('Rara Global Search');
});
