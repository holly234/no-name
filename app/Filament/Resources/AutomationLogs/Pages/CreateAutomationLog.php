<?php

namespace App\Filament\Resources\AutomationLogs\Pages;

use App\Filament\Resources\AutomationLogs\AutomationLogResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAutomationLog extends CreateRecord
{
    protected static string $resource = AutomationLogResource::class;
}
