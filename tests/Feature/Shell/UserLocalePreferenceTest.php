<?php

// Story 1.2 RED-PHASE scaffold: same storage decision as UserThemePreferenceTest —
// bazaar_user_preferences (Bazaar-owned, ULID PK), never a column on the host users
// table. AC3: "seluruh label chrome berubah instan tanpa reload ... persisten per-User".
// This file covers persistence + app-locale application on the next authenticated
// request; the instant no-reload DOM swap itself is out of automated scope.

use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\App;
use Tigaphonic\Bazaar\Shell\Support\UserPreferences;

it('persists a Staff member\'s locale choice across requests', function () {
    $user = User::create([
        'name' => 'Nina Staff',
        'email' => 'nina-locale@example.com',
        'password' => bcrypt('password'),
    ]);

    app(UserPreferences::class)->setLocale($user, 'id');

    $reread = app(UserPreferences::class)->locale($user->fresh());

    expect($reread)->toBe('id');
})->skip('Story 1.2 not implemented — Shell\Support\UserPreferences does not exist yet');

it('applies the Staff member\'s persisted locale to the app on their next authenticated request', function () {
    $this->artisan('bazaar:install');

    $user = User::create([
        'name' => 'Nina Staff',
        'email' => 'nina-locale-2@example.com',
        'password' => bcrypt('password'),
    ]);

    app(UserPreferences::class)->setLocale($user, 'id');

    $this->actingAs($user)->get('/admin');

    expect(App::getLocale())->toBe('id');
})->skip('Story 1.2 not implemented — no middleware applies the persisted locale yet');
