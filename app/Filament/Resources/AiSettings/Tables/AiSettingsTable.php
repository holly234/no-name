<?php

namespace App\Filament\Resources\AiSettings\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AiSettingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('business.name')->label('Workspace')->searchable()->sortable(),
                TextColumn::make('assistant_name')->label('Agent')->searchable(),
                TextColumn::make('tone')->badge(),
                IconColumn::make('auto_reply_enabled')->label('Auto reply')->boolean(),
                IconColumn::make('human_takeover_enabled')->label('Handover')->boolean(),
                IconColumn::make('business_hours_enabled')->label('Business hours')->boolean(),
                TextColumn::make('updated_at')->since()->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->defaultSort('updated_at', 'desc');
    }
}
