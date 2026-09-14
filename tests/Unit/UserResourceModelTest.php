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

it('throws RuntimeException when both config models are null', function () {
    config()->set('bazaar.models.user', null);
    config()->set('auth.providers.users.model', null);

    expect(fn () => UserResource::getModel())
        ->toThrow(RuntimeException::class, "Bazaar could not resolve a User & Access model.");
});
