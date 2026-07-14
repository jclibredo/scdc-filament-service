<?php

namespace App\Filament\Resources\Categories\Pages;

use App\Filament\Resources\Categories\CategoryResource;
use App\Models\ActivityLog;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;

class ListCategories extends ListRecords
{
    protected static string $resource = CategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('New Category')
                ->button()
                ->color('success')
                ->size('xs')
                ->outlined()
                ->after(function ($record) {
                    // 4. Log New Category Creation
                    ActivityLog::create([
                        'user_id'   => Auth::id() ?? 'System',
                        'activity'  => "Created a new System Category: [{$record->name}] | Type: {$record->cat} | Status: " . ($record->status ? 'ACTIVE' : 'INACTIVE'),
                        'module'    => 'System Category Management',
                        'ipaddress' => request()->ip(),
                        'windows'   => request()->userAgent(),
                    ]);
                })
                ->icon('heroicon-m-plus-circle'),
        ];
    }
}
