<?php

namespace App\Filament\Resources\AutomationLogs\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AutomationLogInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Automation event')->schema([
                    TextEntry::make('business.name')->label('Workspace'),
                    TextEntry::make('connectedAccount.platform')->label('Channel')->badge(),
                    TextEntry::make('event_type')->badge(),
                    TextEntry::make('status')->badge(),
                    TextEntry::make('created_at')->dateTime(),
                    TextEntry::make('message')->columnSpanFull(),
                    TextEntry::make('error_details')->label('Error details')->placeholder('No error recorded')->columnSpanFull(),
                    TextEntry::make('metadata')
                        ->formatStateUsing(fn (mixed $state): string => json_encode($state ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '{}')
                        ->columnSpanFull(),
                ])->columns(2),
            ]);
    }
}
