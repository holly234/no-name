<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class Revenue extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static string|\UnitEnum|null $navigationGroup = 'Commercial';

    protected static ?string $navigationLabel = 'Revenue & credits';

    protected static ?string $title = 'Revenue & AI credits';

    protected string $view = 'filament.pages.revenue';
}
