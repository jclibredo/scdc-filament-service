<?php

namespace App\Filament\Resources\IncentiveBonuses\Pages;

use App\Filament\Resources\IncentiveBonuses\IncentiveBonusResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditIncentiveBonus extends EditRecord
{
    protected static string $resource = IncentiveBonusResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
