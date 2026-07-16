<?php

namespace App\Filament\Resources\Customers\Tables;

use App\Models\Customer;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CustomersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('business.name')->label('Workspace')->searchable()->sortable(),
                TextColumn::make('channel')->badge(),
                TextColumn::make('external_id')->label('External ID')->searchable()->toggleable(),
                TextColumn::make('conversations_count')->label('Conversations')->counts('conversations'),
                TextColumn::make('tags')->badge()->separator(','),
                TextColumn::make('created_at')->since()->sortable(),
            ])
            ->filters([
                SelectFilter::make('channel')->options(fn (): array => Customer::query()->distinct()->pluck('channel', 'channel')->all()),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
