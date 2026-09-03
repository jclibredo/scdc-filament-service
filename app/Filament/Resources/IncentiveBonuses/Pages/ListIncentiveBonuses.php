<?php

namespace App\Filament\Resources\IncentiveBonuses\Pages;

use App\Filament\Resources\IncentiveBonuses\IncentiveBonusResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListIncentiveBonuses extends ListRecords
{
    protected static string $resource = IncentiveBonusResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
