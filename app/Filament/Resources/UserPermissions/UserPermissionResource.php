<?php

namespace App\Filament\Resources\UserPermissions;

use App\Filament\Resources\UserPermissions\Pages\ListUserPermissions;
use App\Models\ActivityLog;
use App\Models\User;
use App\Models\UserPermission;
use BackedEnum;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Unique;
use UnitEnum;

class UserPermissionResource extends Resource
{
    protected static ?string $model = UserPermission::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::ShieldCheck;
    protected  static string|UnitEnum|null $navigationGroup = 'User Management';
    protected static ?string $navigationLabel = 'Role';
    protected static ?string $pluralModelLabel = 'User Role Data';

    public static function shouldRegisterNavigation(): bool
    {
        $user = Auth::user();
        // If $user is an integer (ID), fetch the actual User model from the database
        if (is_int($user)) {
            $user = User::find($user);
        }
        // Check if we have a valid User model instance now
        if (! $user instanceof User) {
            return false;
        }
        return $user->userPermissions()
            ->where('module', 'SUPERADMIN')
            ->exists();
    }
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Access Control Settings')
                    ->description('Assign and manage application permissions for this user. Select the target user and choose the specific operational module they are authorized to access.')
                    ->extraAttributes([
                        'style' => 'border: 2px solid #2d2380 !important; border-radius: 0.75rem;', // Deep Sapphire Blue
                    ])
                    ->columnSpanFull()
                    ->schema([
                        // Select User via Relationship
                        Select::make('user_id')
                            // ->relationship('userDetails', 'name')
                            ->relationship(
                                name: 'userDetails',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn($query) => $query->where('id', '!=', Auth::user()->id)
                                    ->where('status', true)
                            )
                            ->searchable()
                            ->preload()
                            ->searchDebounce(500)
                            ->required()
                            ->native(false)
                            ->label('User'),

                        // Select Module with hardcoded options
                        Select::make('module')
                            ->label('Module Permission')
                            ->options([
                                'IMPORT' => 'Data Importing',
                                'EXPORT' => 'Data Exporting',
                                'OFFICEMANAGER' => 'Office Manager',
                                'PAYROLLADMINWEEKLY' => 'Payroll Admin Weekly',
                                'PAYROLLADMINMONTHLY' => 'Payroll Admin Monthly',
                                'PAYROLLSUBCONWEEKLY' => 'Payroll Subcon Weekly',
                                'PAYROLLSUBCONMONTHLY' => 'Payroll Subcon Monthly',
                                'HR' => 'Human Resources',
                                'SUPERADMIN' => 'System Administrator'
                            ])
                            ->required()
                            ->native(false)
                            // 1. Dynamic casting context for Multiple selection vs single row editing
                            ->multiple(fn(?UserPermission $record) => $record === null)
                            ->afterStateHydrated(function (Select $component, ?UserPermission $record) {
                                if ($record) {
                                    // Set string value if editing an existing table row
                                    $component->state($record->module);
                                }
                            }),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->extraAttributes([
                'style' => 'border: 2px solid #2d2380 !important; border-radius: 0.75rem;', // Deep Sapphire Blue
            ])
            ->recordUrl(null)
            ->query(function () {
                $user = Auth::user();
                if (!$user) {
                    return UserPermission::whereRaw('1 = 0');
                }
                // return UserPermission::whereNot('user_id', $user->id)
                //     ->orderBy('module', 'asc');
                // Join the user table to allow proper ordering by user name
                return UserPermission::query()
                    ->select('user_permissions.*') // Avoid column collision issues
                    ->join('users', 'user_permissions.user_id', '=', 'users.id')
                    ->whereNot('user_permissions.user_id', $user->id)
                    ->orderBy('users.name', 'asc');
            })
            ->columns([
                TextColumn::make('userDetails.name')
                    ->label('User')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('module')
                    ->badge() // Optional: Makes the module look nice as a badge
                    ->color(fn(string $state): string => match ($state) {
                        'IMPORT' => 'warning',
                        'EXPORT' => 'warning',
                        'OFFICEMANAGER' => 'success',
                        'PAYROLLADMINWEEKLY' => 'success',
                        'PAYROLLADMINMONTHLY' => 'success',
                        'PAYROLLSUBCONWEEKLY' => 'success',
                        'PAYROLLSUBCONMONTHLY' => 'success',
                        'HR' => 'success',
                        'SUPERADMIN' => 'success',
                        default => 'danger',
                    })
                    ->sortable()
                    ->searchable(),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                ActionGroup::make([
                    EditAction::make()
                        ->after(function ($record) {
                            // Logs the update details after the row updates
                            ActivityLog::create([
                                'user_id'   => Auth::id() ?? 'System',
                                'activity'  => "Updated permission to '{$record->module}' for user: {$record->userDetails?->name}",
                                'module'    => 'User Management',
                                'ipaddress' => request()->ip(),
                                'windows'   => request()->userAgent(),
                            ]);
                        })
                        ->label('Update'),
                    DeleteAction::make()
                        ->label('Remove')
                        ->after(function ($record) {
                            // Logs the deletion details before the record is completely purged
                            ActivityLog::create([
                                'user_id'   => Auth::id() ?? 'System',
                                'activity'  => "Revoked permission '{$record->module}' from user: {$record->userDetails?->name}",
                                'module'    => 'User Management',
                                'ipaddress' => request()->ip(),
                                'windows'   => request()->userAgent(),
                            ]);
                        }),
                ])
                    ->label('Action')
                    ->icon('heroicon-m-chevron-down')
                    ->color('success')
                    ->button()
                    ->size('xs')
                    ->outlined()
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->after(function (Collection $records) {
                            // Group bulk records to log them efficiently
                            $logDetails = $records->map(function ($record) {
                                return "'{$record->module}' from {$record->userDetails?->name}";
                            })->implode(', ');

                            ActivityLog::create([
                                'user_id'   => Auth::id() ?? 'System',
                                'activity'  => "Bulk revoked permissions: [{$logDetails}]",
                                'module'    => 'User Management',
                                'ipaddress' => request()->ip(),
                                'windows'   => request()->userAgent(),
                            ]);
                        })
                        ->label('Remove Permission'),
                ])
                    ->label('BulkAction')
                    ->icon('heroicon-m-chevron-down')
                    ->color('success')
                    ->button()
                    ->size('xs')
                    ->outlined(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUserPermissions::route('/'),
        ];
    }
}
