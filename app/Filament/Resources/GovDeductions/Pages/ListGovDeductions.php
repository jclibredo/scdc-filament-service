<?php

namespace App\Filament\Resources\GovDeductions\Pages;

use App\Filament\Resources\GovDeductions\GovDeductionResource;
use App\Models\ActivityLog;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;

class ListGovDeductions extends ListRecords
{
    protected static string $resource = GovDeductionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Deductions')
                ->button()
                ->color('success')
                ->size('xs')
                ->outlined()
                ->after(function ($record) {
                    ActivityLog::create([
                        'user_id'   => Auth::id() ?? 'System',
                        'activity'  => "Created a new mandatory deduction category: {$record->title} (Amount: PHP {$record->amount})",
                        'module'    => 'Gov Deduction Management',
                        'ipaddress' => request()->ip(),
                        'windows'   => request()->userAgent(),
                    ]);
                })
                ->icon('heroicon-m-plus-circle'),
        ];
    }
}
