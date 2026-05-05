<?php

namespace App\Filament\Resources\Earnings\Pages;

use App\Filament\Resources\Earnings\EarningsResource;
use Filament\Resources\Pages\CreateRecord;

class CreateEarnings extends CreateRecord
{
    protected static string $resource = EarningsResource::class;

    // protected function mutateFormDataBeforeCreate(array $data): array
    // {
    //     dd($data);
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
}
