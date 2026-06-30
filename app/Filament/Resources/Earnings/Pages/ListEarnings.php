<?php

namespace App\Filament\Resources\Earnings\Pages;

use App\Filament\Resources\Earnings\EarningsResource;
use App\Filament\Resources\Employees\EmployeeResource;
use App\Models\Employee;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListEarnings extends ListRecords
{
    protected static string $resource = EarningsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back_to_items')
                ->label('Back')
                ->button()
                ->color('success')
                ->size('xs')
                ->outlined()
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
                ->label('New Earning')
                ->button()
                ->color('success')
                ->size('xs')
                ->outlined()
                ->icon('heroicon-m-plus-circle'),


        ];
    }
    public function mount(): void
    {
        // 1. Check if your required session variable is completely missing or empty
        if (
            !session()->has('session_employee_id')
            || empty(session('session_employee_id'))
        ) {
            // 2. Prevent disrupting background Livewire requests 
            if (request()->hasHeader('X-Livewire')) {
                abort(401, 'Session expired.');
            }
            // 3. Cleanly redirect standard requests straight to the dashboard
            redirect()->route('filament.admin.pages.dashboard');
            return; // Halt further page execution
        }
        parent::mount();
    }
}
