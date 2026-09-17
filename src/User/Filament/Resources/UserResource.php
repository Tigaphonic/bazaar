<?php

namespace Tigaphonic\Bazaar\User\Filament\Resources;

use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;
use Spatie\Permission\Models\Role;
use Tigaphonic\Bazaar\User\Filament\Resources\UserResource\Pages\CreateUser;
use Tigaphonic\Bazaar\User\Filament\Resources\UserResource\Pages\EditUser;
use Tigaphonic\Bazaar\User\Filament\Resources\UserResource\Pages\ListUsers;
use Tigaphonic\Bazaar\User\Services\UserService;
use UnitEnum;

/**
 * Lets Staff create/edit/deactivate internal User accounts and assign
 * Role(s), built over the host app's own authenticatable model (never a
 * Bazaar-owned table — see AD-18). Presentation code here only ever calls
 * UserService (AD-5) — never User::create()/$record->update() directly.
 */
class UserResource extends Resource
{
    protected static string|UnitEnum|null $navigationGroup = 'User & Access';

    /**
     * Lets Filament's native global search (active by default,
     * HasGlobalSearch.php:31) actually return results for User records --
     * without this, the search provider has no title field to match against
     * or display.
     */
    protected static ?string $recordTitleAttribute = 'name';

    public static function getModel(): string
    {
        return config('bazaar.models.user')
            ?? config('auth.providers.users.model')
            ?? throw new RuntimeException("Bazaar could not resolve a User & Access model. Set config('bazaar.models.user') or config('auth.providers.users.model').");
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->required(),
            TextInput::make('email')
                ->required()
                ->email()
                ->unique(table: fn () => (new (static::getModel()))->getTable(), ignoreRecord: true),
            TextInput::make('password')
                ->password()
                ->required(fn (string $operation) => $operation === 'create')
                ->dehydrated(fn ($state) => filled($state)),
            CheckboxList::make('roles')
                ->options(fn () => Role::pluck('name', 'name'))
                ->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name'),
                TextColumn::make('email'),
                IconColumn::make('is_active')
                    ->boolean()
                    ->getStateUsing(fn (Model $record) => app(UserService::class)->isActive($record)),
            ])
            ->recordActions([
                Action::make('deactivate')
                    ->requiresConfirmation()
                    ->visible(fn (Model $record) => app(UserService::class)->isActive($record) && (string)$record->getKey() !== (string)auth()->id())
                    ->action(fn (Model $record) => app(UserService::class)->deactivate($record)),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }
}
