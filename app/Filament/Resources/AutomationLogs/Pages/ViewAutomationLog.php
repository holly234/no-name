<?php

namespace App\Filament\Resources\AutomationLogs\Pages;

use App\Filament\Resources\AutomationLogs\AutomationLogResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewAutomationLog extends ViewRecord
{
    protected static string $resource = AutomationLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
