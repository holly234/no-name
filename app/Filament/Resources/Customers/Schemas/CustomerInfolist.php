<?php

namespace App\Filament\Resources\Customers\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CustomerInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Customer profile')->schema([
                    TextEntry::make('name'),
                    TextEntry::make('business.name')->label('Workspace'),
                    TextEntry::make('channel')->badge(),
                    TextEntry::make('external_id')->label('External ID'),
                    TextEntry::make('tags')->badge(),
                    TextEntry::make('created_at')->dateTime(),
                    TextEntry::make('notes')->placeholder('No notes')->columnSpanFull(),
                ])->columns(2),
            ]);
    }
}
