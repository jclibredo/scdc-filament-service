<?php

namespace App\Filament\Resources\Employees\Pages;

use App\Filament\Resources\Employees\EmployeeResource;
use App\Models\EmployeeProjectHistory;
use Filament\Resources\Pages\CreateRecord;

class CreateEmployee extends CreateRecord
{
    // protected static string $resource = EmployeeResource::class;

    // protected function afterCreate(): void
    // {
    //     $employee = $this->record;
    //     EmployeeProjectHistory::create([
    //         'employeeid' => $employee->employeeid,
    //         'projectid' => $employee->project_id,
    //         'employeetype' => $employee->employeetype,
    //         'employee_status' => $employee->status,
    //         'datestarted' => $employee->datehired,
    //         'dateended' => $employee->dateseperated,
    //         'status' => $employee->status,
    //     ]);
    // }
    protected static string $resource = EmployeeResource::class;

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        // First, create the Employee record
        $employee = parent::handleRecordCreation($data);

        // Then create EmployeeProjectHistory
        EmployeeProjectHistory::create([
            'employeeid' => $employee->employeeid,
            'projectid' => $employee->project_id,
            'employeetype' => $employee->employeetype,
            'employee_status' => $employee->status,
            'datestarted' => $employee->datehired,
            'dateended' => $employee->dateseperated,
            'status' => $employee->status,
        ]);

        return $employee;
    }
}
