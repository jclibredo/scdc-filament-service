<?php

namespace App\Filament\Resources\UserPermissions;

use App\Filament\Resources\UserPermissions\Pages\ListUserPermissions;
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
use Illuminate\Validation\Rules\Unique;
use UnitEnum;

class UserPermissionResource extends Resource
{
    protected static ?string $model = UserPermission::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::ShieldCheck;
    protected  static string|UnitEnum|null $navigationGroup = 'User Management';
    protected static ?string $navigationLabel = 'Role';
    protected static ?string $pluralModelLabel = 'User Role Data';

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
                            ->relationship('userDetails', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->label('User'),

                        // Select Module with hardcoded options
                        Select::make('module')
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
                            ->label('Module Permission')
                            // Ensures the user_id + module combination is unique
                            ->unique(
                                table: 'user_permissions',
                                column: 'module',
                                modifyRuleUsing: function (Unique $rule, Get $get) {
                                    return $rule->where('user_id', $get('user_id'));
                                },
                                ignoreRecord: true // Allows editing the record without failing validation
                            )
                            ->label('Module Permission'),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->extraAttributes([
                'style' => 'border: 2px solid #2d2380 !important; border-radius: 0.75rem;', // Deep Sapphire Blue
            ])
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
                        // ->disabled(fn()=> {


                        // })
                        ->label('Update'),
                    DeleteAction::make()
                        ->label('Remove'),
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
