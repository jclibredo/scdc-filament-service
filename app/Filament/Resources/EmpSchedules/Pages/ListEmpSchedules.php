<?php

namespace App\Filament\Resources\EmpSchedules\Pages;

use App\Filament\Resources\Employees\EmployeeResource;
use App\Filament\Resources\EmpSchedules\EmpScheduleResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListEmpSchedules extends ListRecords
{
    protected static string $resource = EmpScheduleResource::class;

    protected function getHeaderActions(): array
    {
        return [

            Action::make('back_to_items')
                ->label('Back')
                ->color('success')
                ->icon('heroicon-m-arrow-left')
                ->action(function () {
                    // Forget the session data
                    // session()->forget(['pending_item_id']);
                    // // Redirect back to the Items Index
                    // return redirect(InventoryItemResource::getUrl('index'));

                    // $category = session('pending_item_category');

                    // 1. Clear the session data
                    session()->forget([
                        'earnings_employeeid',
                    ]);
                    // 2. Determine the dynamic redirect URL
                    // Replace 'InventoryMedicineResource' with your 
                    return redirect(EmployeeResource::getUrl('index'));
                }),

            CreateAction::make()
                ->label('New Sched')
                ->color('warning')
                ->icon('heroicon-m-plus-circle'),
        ];
    }
}
