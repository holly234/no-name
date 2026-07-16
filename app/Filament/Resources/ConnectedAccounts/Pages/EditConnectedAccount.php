<?php

namespace App\Filament\Resources\ConnectedAccounts\Pages;

use App\Filament\Resources\ConnectedAccounts\ConnectedAccountResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditConnectedAccount extends EditRecord
{
    protected static string $resource = ConnectedAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
