<?php

namespace App\Filament\Resources\Employees\Pages;

use App\Filament\Resources\Employees\EmployeeResource;
use App\Models\EmployeeProjectHistory;
// use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\DB;

class EditEmployee extends EditRecord
{
    protected static string $resource = EmployeeResource::class;
    // protected function afterSave(): void
    // {
    //     $employee = $this->record;

    //     // 1. Check if the employee was just terminated/separated
    //     if ($employee->dateseperated !== null) {
    //         EmployeeProjectHistory::where('employeeid', $employee->employeeid)
    //             ->where('status', true)
    //             ->update([
    //                 'status'    => false,
    //                 'dateended' => $employee->dateseperated,
    //             ]);

    //         return; // Early exit since employment has ended
    //     }

    //     // 2. Process structural assignment updates only if tracking attributes changed
    //     if ($employee->wasChanged(['empstatus', 'employeetype', 'project_id'])) {
    //         DB::transaction(function () use ($employee) {
    //             // Deactivate the current active history record
    //             EmployeeProjectHistory::where('employeeid', $employee->employeeid)
    //                 ->where('status', true)
    //                 ->update([
    //                     'status'    => false,
    //                     'dateended' => now(),
    //                 ]);

    //             // Spin up the new history record matching the updated attributes
    //             EmployeeProjectHistory::create([
    //                 'employeeid'      => $employee->employeeid,
    //                 'projectid'       => $employee->project_id,
    //                 'employeetype'    => $employee->employeetype,
    //                 'employee_status' => $employee->empstatus,
    //                 'datestarted'     => now(),
    //                 'status'          => true,
    //             ]);
    //         });
    //     }
    // protected function afterSave(): void
    // {
    //     $employee = $this->record;


    //     dd($employee);
    //     // 1. Detect if tracked attributes changed during the save operation
    //     $historyTrackedChanged = $employee->wasChanged(['empstatus', 'employeetype', 'project_id']);
    //     // $dateSeparatedChanged = $employee->wasChanged('dateseperated');

    //     // 2. Handle standard structural modifications
    //     if ($historyTrackedChanged && $employee->dateseperated === null) {

    //         // Deactivate old active history matching the original state
    //         EmployeeProjectHistory::where('employeeid', $employee->employeeid)
    //             ->where('status', true)
    //             ->update([
    //                 'status' => false,
    //                 'dateended' => now(),
    //             ]);

    //         // Spin up a brand new structural allocation record
    //         EmployeeProjectHistory::create([
    //             'employeeid'      => $employee->employeeid,
    //             'projectid'       => $employee->project_id,
    //             'employeetype'    => $employee->employeetype,
    //             'employee_status' => $employee->empstatus,
    //             'datestarted'     => now(),
    //             'status'          => true,
    //         ]);
    //     }

    //     // 3. Handle termination logic when dateseperated is set
    //     if ($employee->dateseperated !== null) {
    //         EmployeeProjectHistory::where('employeeid', $employee->employeeid)
    //             ->where('status', true)
    //             ->update([
    //                 'status'    => false,
    //                 'dateended' => $employee->dateseperated,
    //             ]);
    //     }
    // }
}
