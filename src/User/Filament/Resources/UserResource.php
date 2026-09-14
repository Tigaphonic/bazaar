<?php

namespace Tigaphonic\Bazaar\User\Filament\Resources;

use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use RuntimeException;
use Tigaphonic\Bazaar\User\Filament\Resources\UserResource\Pages\ListUsers;
use UnitEnum;

/**
 * Minimal read-only listing over the host app's own authenticatable model
 * (never a Bazaar-owned table — see AD-18). Full CRUD is Story 1.4's scope.
 */
class UserResource extends Resource
{
    protected static string|UnitEnum|null $navigationGroup = 'User & Access';

    public static function getModel(): string
    {
        return config('bazaar.models.user')
            ?? config('auth.providers.users.model')
            ?? throw new RuntimeException("Bazaar could not resolve a User & Access model. Set config('bazaar.models.user') or config('auth.providers.users.model').");
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name'),
                TextColumn::make('email'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
        ];
    }
}
