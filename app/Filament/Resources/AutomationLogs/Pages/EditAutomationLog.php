<?php

namespace App\Filament\Resources\AutomationLogs\Pages;

use App\Filament\Resources\AutomationLogs\AutomationLogResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditAutomationLog extends EditRecord
{
    protected static string $resource = AutomationLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
