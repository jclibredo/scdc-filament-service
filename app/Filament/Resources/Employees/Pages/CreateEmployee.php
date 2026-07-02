<?php

namespace App\Filament\Resources\Employees\Pages;

use App\Filament\Resources\Employees\EmployeeResource;
use App\Models\EmployeeProjectHistory;
use Filament\Resources\Pages\CreateRecord;

class CreateEmployee extends CreateRecord
{
    protected static string $resource = EmployeeResource::class;

    // protected function afterCreate()
    // {
    //     EmployeeProjectHistory::create([
    //         'employeeid'      => $this->data['employeeid'],    // or another source
    //         'projectid'       => $this->data['project_id'],    // map category → projectid
    //         'employeetype'    => $this->data['employeetype'],
    //         'employee_status' => $this->data['empstatus'],                // your logic
    //         'datestarted'     => $this->data['datehired'],
    //         'dateended'       => null, // or another source if applicable
    //         'status'          => true,
    //     ]);
    // }
}
