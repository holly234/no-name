<?php

namespace App\Filament\Resources\ConnectedAccounts\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ConnectedAccountForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('business_id')
                    ->required()
                    ->numeric(),
                TextInput::make('platform')
                    ->required(),
                TextInput::make('account_name')
                    ->required(),
                TextInput::make('external_account_id')
                    ->required(),
                TextInput::make('page_id'),
                TextInput::make('phone_number_id')
                    ->tel(),
                TextInput::make('status')
                    ->required()
                    ->default('not_connected'),
                DateTimePicker::make('connected_at'),
                DateTimePicker::make('token_expires_at'),
                Textarea::make('provider_meta')
                    ->columnSpanFull(),
            ]);
    }
}
