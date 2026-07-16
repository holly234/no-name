<?php

namespace App\Filament\Resources\ConnectedAccounts\Pages;

use App\Filament\Resources\ConnectedAccounts\ConnectedAccountResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListConnectedAccounts extends ListRecords
{
    protected static string $resource = ConnectedAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
