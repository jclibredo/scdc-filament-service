<?php

namespace App\Filament\Resources\Employees\Pages;

use App\Filament\Resources\Employees\EmployeeResource;
// use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditEmployee extends EditRecord
{
    protected static string $resource = EmployeeResource::class;

    // protected function afterSave(): void
    // {
        // Access the updated model
        // $employee = $this->record;

        // // Example: generate a full name field after save
        // $employee->full_name = trim($employee->firstname . ' ' . $employee->middlename . ' ' . $employee->lastname);

        // // Example: update some other calculated field
        // $employee->last_updated_by = auth()->id();

        // // Save changes
        // $employee->save();
    // }
}
