<?php

namespace App\Filament\Resources\ConnectedAccounts\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ConnectedAccountsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('business.name')
                    ->label('Workspace')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('platform')
                    ->badge()
                    ->searchable(),
                TextColumn::make('account_name')
                    ->searchable(),
                TextColumn::make('external_account_id')
                    ->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->searchable(),
                TextColumn::make('connected_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('token_expires_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->defaultSort('created_at', 'desc');
    }
}
