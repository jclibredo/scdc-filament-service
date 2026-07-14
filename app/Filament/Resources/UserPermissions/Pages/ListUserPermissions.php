<?php

namespace App\Filament\Resources\UserPermissions\Pages;

use App\Filament\Resources\UserPermissions\UserPermissionResource;
use App\Models\ActivityLog;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ListUserPermissions extends ListRecords
{
    protected static string $resource = UserPermissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->using(function (array $data): \Illuminate\Database\Eloquent\Model {
                    return DB::transaction(function () use ($data) {
                        $lastCreatedModel = null;
                        // Loop through the selected modules from the array selection
                        foreach ($data['module'] as $moduleName) {
                            $exists = static::getModel()::where('user_id', $data['user_id'])
                                ->where('module', $moduleName)
                                ->exists();

                            if (!$exists) {
                                $lastCreatedModel = static::getModel()::create([
                                    'user_id' => $data['user_id'],
                                    'module'  => $moduleName,
                                ]);
                            }
                        }

                        // Log the activity if roles were successfully assigned
                        if (!empty($createdModules)) {
                            $targetUser = \App\Models\User::find($data['user_id']);
                            $modulesString = implode(', ', $createdModules);

                            ActivityLog::create([
                                'user_id'   => Auth::id() ?? 'System',
                                'activity'  => "Assigned permissions [{$modulesString}] to user: {$targetUser?->name} (ID: {$data['user_id']})",
                                'module'    => 'User Management',
                                'ipaddress' => request()->ip(),
                                'windows'   => request()->userAgent(),
                            ]);
                        }

                        // Return model state instance fallback to avoid lifecycle crashes
                        return $lastCreatedModel ?? new (static::getModel());
                    });
                })
                ->label('New Role')
                ->button()
                ->color('success')
                ->size('xs')
                ->outlined()
                ->icon('heroicon-m-plus-circle'),
        ];
    }
}
