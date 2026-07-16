<?php

namespace App\Filament\Resources\AutomationLogs\Tables;

use App\Models\AutomationLog;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AutomationLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')->label('Time')->since()->sortable(),
                TextColumn::make('business.name')->label('Workspace')->searchable()->sortable(),
                TextColumn::make('event_type')->label('Event')->badge()->searchable(),
                TextColumn::make('status')->badge()->color(fn (string $state): string => match (strtolower($state)) {
                    'success', 'completed', 'sent' => 'success',
                    'failed', 'error' => 'danger',
                    default => 'warning',
                }),
                TextColumn::make('message')->limit(70)->wrap()->searchable(),
                TextColumn::make('connectedAccount.platform')->label('Channel')->badge(),
            ])
            ->filters([
                SelectFilter::make('status')->options(fn (): array => AutomationLog::query()->distinct()->pluck('status', 'status')->all()),
                SelectFilter::make('event_type')->options(fn (): array => AutomationLog::query()->distinct()->pluck('event_type', 'event_type')->all()),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
