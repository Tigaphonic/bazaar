<?php

use Tigaphonic\Bazaar\User\Filament\Resources\UserResource;

it('resolves to auth.providers.users.model by default', function () {
    expect(UserResource::getModel())->toBe(config('auth.providers.users.model'));
});

it('resolves to bazaar.models.user when the client overrides it', function () {
    config()->set('bazaar.models.user', 'App\\Models\\Staff');

    expect(UserResource::getModel())->toBe('App\\Models\\Staff');

    config()->set('bazaar.models.user', null);
});
