<?php

namespace App\Filament\Resources\Expertises\Pages;

use App\Filament\Resources\Expertises\ExpertiseResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditExpertise extends EditRecord
{
    protected static string $resource = ExpertiseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
