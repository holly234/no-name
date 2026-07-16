<?php

namespace App\Filament\Resources\Businesses\Tables;

use Filament\Actions\Action;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BusinessesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('owner.email')
                    ->label('Owner')
                    ->searchable(),
                TextColumn::make('category')
                    ->searchable(),
                TextColumn::make('email')
                    ->label('Email address')
                    ->searchable(),
                TextColumn::make('users_count')
                    ->label('Team')
                    ->counts('users'),
                TextColumn::make('conversations_count')
                    ->label('Conversations')
                    ->counts('conversations'),
                TextColumn::make('connected_accounts_count')
                    ->label('Connections')
                    ->counts('connectedAccounts'),
                IconColumn::make('is_suspended')
                    ->label('Suspended')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                Action::make('suspend')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn ($record): bool => ! $record->is_suspended)
                    ->action(fn ($record) => $record->update([
                        'is_suspended' => true,
                        'suspended_at' => now(),
                        'suspension_reason' => 'Suspended by platform owner',
                    ])),
                Action::make('reactivate')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn ($record): bool => $record->is_suspended)
                    ->action(fn ($record) => $record->update([
                        'is_suspended' => false,
                        'suspended_at' => null,
                        'suspension_reason' => null,
                    ])),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
