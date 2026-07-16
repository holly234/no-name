<?php

namespace App\Filament\Resources\Conversations\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ConversationInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Conversation')->schema([
                    TextEntry::make('business.name')->label('Workspace'),
                    TextEntry::make('customer_name')->label('Customer'),
                    TextEntry::make('customer_external_id')->label('External customer ID'),
                    TextEntry::make('channel')->badge(),
                    TextEntry::make('status')->badge(),
                    TextEntry::make('ai_mode')->label('Handling mode')->badge(),
                    TextEntry::make('last_message_at')->dateTime(),
                ])->columns(2),
                Section::make('Latest message')->schema([
                    TextEntry::make('latestMessage.body')->placeholder('No messages yet')->columnSpanFull(),
                    TextEntry::make('latestMessage.direction')->badge(),
                    TextEntry::make('latestMessage.sender_type')->label('Sender')->badge(),
                    TextEntry::make('latestMessage.created_at')->dateTime(),
                ])->columns(3),
            ]);
    }
}
