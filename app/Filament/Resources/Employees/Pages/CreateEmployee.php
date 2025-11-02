<?php

namespace App\Filament\Resources\Employees\Pages;

use App\Filament\Resources\Employees\EmployeeResource;
use App\Models\EmployeeProjectHistory;
use Filament\Resources\Pages\CreateRecord;

class CreateEmployee extends CreateRecord
{
    protected static string $resource = EmployeeResource::class;

    // protected function mutateFormDataBeforeCreate(array $data): array
    // {
    //     // // ✅ contains the form data
    //     // EmployeeProjectHistory::create([
    //     //     'employeeid'      => $data['employeeid'],    // or another source
    //     //     'projectid'       => $data['project_id'],    // map category → projectid
    //     //     'employeetype'    => $data['employeetype'],
    //     //     'employee_status' => 'active',                // your logic
    //     //     'datestarted'     => $data['datehired'],
    //     //     'dateended'       => $data['dateseperated'],
    //     //     'status'          => 1,
    //     // ]);
    //     return $data;
    // }

    protected function afterCreate()
    {
        // dd($this->data);
        EmployeeProjectHistory::create([
            'employeeid'      => $this->data['employeeid'],    // or another source
            'projectid'       => $this->data['project_id'],    // map category → projectid
            'employeetype'    => $this->data['employeetype'],
            'employee_status' => 'active',                // your logic
            'datestarted'     => $this->data['datehired'],
            'dateended'       => $this->data['dateseperated'],
            'status'          => 1,
        ]);
    }
}
