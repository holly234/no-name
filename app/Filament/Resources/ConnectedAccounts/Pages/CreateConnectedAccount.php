<?php

namespace App\Filament\Resources\ConnectedAccounts\Pages;

use App\Filament\Resources\ConnectedAccounts\ConnectedAccountResource;
use Filament\Resources\Pages\CreateRecord;

class CreateConnectedAccount extends CreateRecord
{
    protected static string $resource = ConnectedAccountResource::class;
}
