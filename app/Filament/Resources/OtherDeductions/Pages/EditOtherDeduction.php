<?php

namespace App\Filament\Resources\OtherDeductions\Pages;

use App\Filament\Resources\OtherDeductions\OtherDeductionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditOtherDeduction extends EditRecord
{
    protected static string $resource = OtherDeductionResource::class;
}
