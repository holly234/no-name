<?php

namespace App\Filament\Resources\ConnectedAccounts;

use App\Filament\Resources\ConnectedAccounts\Pages\ListConnectedAccounts;
use App\Filament\Resources\ConnectedAccounts\Schemas\ConnectedAccountForm;
use App\Filament\Resources\ConnectedAccounts\Tables\ConnectedAccountsTable;
use App\Models\ConnectedAccount;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ConnectedAccountResource extends Resource
{
    protected static ?string $model = ConnectedAccount::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLink;

    protected static ?string $navigationLabel = 'Connections';

    protected static string|\UnitEnum|null $navigationGroup = 'Platform operations';

    public static function form(Schema $schema): Schema
    {
        return ConnectedAccountForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ConnectedAccountsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListConnectedAccounts::route('/'),
        ];
    }
}
