<?php

namespace App\Filament\Resources\Payrolls\Pages;

use App\Filament\Resources\DatePeriods\DatePeriodResource;
use App\Filament\Resources\Payrolls\PayrollResource;
use Filament\Actions\Action;
// use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPayrolls extends ListRecords
{
    protected static string $resource = PayrollResource::class;
    public function getTitle(): string
    {
        return "Payroll Records";
    }
    protected function getHeaderActions(): array
    {
        return [

            Action::make('back_to_billing')
                ->label('Back')
                ->color('success')
                ->icon('heroicon-m-arrow-left')
                ->action(function () {
                    $status = session('session_employeestatus');
                    $type = session('session_employeetype');
                    $code = session('session_periodcode');
                    session()->forget([
                        'session_employeetype',
                        'session_employeestatus',
                        'session_periodcode'
                    ]);
                    session([
                        'session_employeestatus' => $status,
                        'session_employeetype' => $type,
                        'session_periodcode' => $code,
                    ]);
                    return redirect()->to(DatePeriodResource::getUrl('index'));
                }),
        ];
    }

    public function mount(): void
    {
        // 1. Check if your required session variable is completely missing or empty
        if (
            !session()->has('session_periodcode')
            || empty(session('session_periodcode'))
            || !session()->has('session_employeestatus')
            || empty(session('session_employeestatus'))
            || !session()->has('session_employeetype')
            || empty(session('session_employeetype'))
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
