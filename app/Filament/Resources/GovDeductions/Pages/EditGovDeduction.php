<?php

namespace App\Filament\Resources\GovDeductions\Pages;

use App\Filament\Resources\GovDeductions\GovDeductionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditGovDeduction extends EditRecord
{
    protected static string $resource = GovDeductionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
