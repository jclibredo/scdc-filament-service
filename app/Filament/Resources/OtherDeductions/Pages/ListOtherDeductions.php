<?php

namespace App\Filament\Resources\OtherDeductions\Pages;

use App\Filament\Resources\OtherDeductions\OtherDeductionResource;
use App\Models\ActivityLog;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;

class ListOtherDeductions extends ListRecords
{
    protected static string $resource = OtherDeductionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('New Deduction')
                ->button()
                ->color('success')
                ->size('xs')
                ->outlined()
                ->after(function ($record) {
                    ActivityLog::create([
                        'user_id'   => Auth::id() ?? 'System',
                        'activity'  => "Created a new other deduction category: {$record->title} (ID: {$record->id})",
                        'module'    => 'Other-Deduction Management',
                        'ipaddress' => request()->ip(),
                        'windows'   => request()->userAgent(),
                    ]);
                })
                ->icon('heroicon-m-plus-circle'),
        ];
    }
}
