<?php

namespace App\Filament\Resources\AiSettings\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AiSettingInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Agent configuration')->schema([
                    TextEntry::make('business.name')->label('Workspace'),
                    TextEntry::make('assistant_name')->label('Assistant'),
                    TextEntry::make('tone')->badge(),
                    IconEntry::make('auto_reply_enabled')->label('Auto reply')->boolean(),
                    IconEntry::make('human_takeover_enabled')->label('Human takeover')->boolean(),
                    IconEntry::make('business_hours_enabled')->label('Business hours')->boolean(),
                    TextEntry::make('fallback_response')->placeholder('Not configured')->columnSpanFull(),
                    TextEntry::make('escalation_instructions')->placeholder('Not configured')->columnSpanFull(),
                    TextEntry::make('never_say')->placeholder('Not configured')->columnSpanFull(),
                    TextEntry::make('handover_rules')->placeholder('Not configured')->columnSpanFull(),
                ])->columns(3),
            ]);
    }
}
