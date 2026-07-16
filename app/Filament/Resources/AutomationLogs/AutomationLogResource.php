<?php

namespace App\Filament\Resources\AutomationLogs;

use App\Filament\Resources\AutomationLogs\Pages\ListAutomationLogs;
use App\Filament\Resources\AutomationLogs\Pages\ViewAutomationLog;
use App\Filament\Resources\AutomationLogs\Schemas\AutomationLogInfolist;
use App\Filament\Resources\AutomationLogs\Tables\AutomationLogsTable;
use App\Models\AutomationLog;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class AutomationLogResource extends Resource
{
    protected static ?string $model = AutomationLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|\UnitEnum|null $navigationGroup = 'Platform operations';

    protected static ?string $navigationLabel = 'Activity & errors';

    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    public static function infolist(Schema $schema): Schema
    {
        return AutomationLogInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AutomationLogsTable::configure($table);
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
            'index' => ListAutomationLogs::route('/'),
            'view' => ViewAutomationLog::route('/{record}'),
        ];
    }
}
