<?php

namespace Tigaphonic\Bazaar\User\Filament\Resources\UserResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Tigaphonic\Bazaar\User\Filament\Resources\UserResource;
use Tigaphonic\Bazaar\User\Services\UserService;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    /**
     * Presentation code may only call a Service (AD-5) — never
     * User::create() directly.
     *
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        return app(UserService::class)->create($data);
    }
}
