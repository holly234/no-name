<?php

namespace App\Filament\Resources\AiSettings;

use App\Filament\Resources\AiSettings\Pages\ListAiSettings;
use App\Filament\Resources\AiSettings\Pages\ViewAiSetting;
use App\Filament\Resources\AiSettings\Schemas\AiSettingInfolist;
use App\Filament\Resources\AiSettings\Tables\AiSettingsTable;
use App\Models\AiSetting;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class AiSettingResource extends Resource
{
    protected static ?string $model = AiSetting::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|\UnitEnum|null $navigationGroup = 'AI operations';

    protected static ?string $navigationLabel = 'AI agents';

    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    public static function infolist(Schema $schema): Schema
    {
        return AiSettingInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AiSettingsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAiSettings::route('/'),
            'view' => ViewAiSetting::route('/{record}'),
        ];
    }
}
