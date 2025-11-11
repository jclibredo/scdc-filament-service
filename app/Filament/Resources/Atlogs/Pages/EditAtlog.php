<?php

namespace App\Filament\Resources\Atlogs\Pages;

use App\Filament\Resources\Atlogs\AtlogResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAtlog extends EditRecord
{
    protected static string $resource = AtlogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
