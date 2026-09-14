<?php

// Story 1.2 RED-PHASE scaffold: Tigaphonic\Bazaar\Shell\Support\UserPreferences and
// its backing bazaar_user_preferences table do not exist yet.
//
// Storage decision (confirmed with the user during ATDD, since AD-18's amendment scopes
// Bazaar away from owning the host's users table): theme/locale preference lives in a
// Bazaar-owned table (bazaar_user_preferences, ULID PK per AD-18 default), never as a
// column bolted onto the host's own users table.
//
// AC2: "tema berganti terang↔gelap instan tanpa reload, persisten per-User". The instant
// no-reload DOM swap is a JS/Livewire runtime behavior out of this project's automated
// scope (see Step 3 Manual QA Gate); this file covers the persistence half only.

use Illuminate\Foundation\Auth\User;
use Tigaphonic\Bazaar\Shell\Support\UserPreferences;

it('persists a Staff member\'s theme choice across requests', function () {
    $user = User::create([
        'name' => 'Nina Staff',
        'email' => 'nina@example.com',
        'password' => bcrypt('password'),
    ]);

    app(UserPreferences::class)->setTheme($user, 'dark');

    // Fresh instance of the service to prove this reads from persisted storage,
    // not an in-memory/request-scoped cache.
    $reread = app(UserPreferences::class)->theme($user->fresh());

    expect($reread)->toBe('dark');
})->skip('Story 1.2 not implemented — Shell\Support\UserPreferences does not exist yet');

it('scopes theme preference per-User, never leaking between Staff accounts', function () {
    $nina = User::create(['name' => 'Nina', 'email' => 'nina2@example.com', 'password' => bcrypt('password')]);
    $budi = User::create(['name' => 'Budi', 'email' => 'budi@example.com', 'password' => bcrypt('password')]);

    $preferences = app(UserPreferences::class);
    $preferences->setTheme($nina, 'dark');
    $preferences->setTheme($budi, 'light');

    expect($preferences->theme($nina->fresh()))->toBe('dark')
        ->and($preferences->theme($budi->fresh()))->toBe('light');
})->skip('Story 1.2 not implemented — Shell\Support\UserPreferences does not exist yet');
