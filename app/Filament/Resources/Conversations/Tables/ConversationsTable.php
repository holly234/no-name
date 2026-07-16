<?php

namespace App\Filament\Resources\Conversations\Tables;

use App\Models\Conversation;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ConversationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('business.name')->label('Workspace')->searchable()->sortable(),
                TextColumn::make('customer_name')->label('Customer')->searchable(),
                TextColumn::make('channel')->badge()->searchable(),
                TextColumn::make('status')->badge()->color(fn (string $state): string => match ($state) {
                    'Needs Human' => 'danger',
                    'AI Handling' => 'info',
                    'Closed' => 'gray',
                    default => 'warning',
                }),
                TextColumn::make('ai_mode')->label('Handler')->badge(),
                TextColumn::make('messages_count')->label('Messages')->counts('messages'),
                TextColumn::make('last_message_at')->label('Last activity')->since()->sortable(),
            ])
            ->filters([
                SelectFilter::make('channel')->options(fn (): array => Conversation::query()->distinct()->pluck('channel', 'channel')->all()),
                SelectFilter::make('status')->options(array_combine(Conversation::STATES, Conversation::STATES)),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->defaultSort('last_message_at', 'desc');
    }
}
