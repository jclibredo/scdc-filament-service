<?php

namespace App\Filament\Resources\Payrolls;

use App\Filament\Resources\Payrolls\Pages\CreatePayroll;
use App\Filament\Resources\Payrolls\Pages\EditPayroll;
use App\Filament\Resources\Payrolls\Pages\ListPayrolls;
use App\Models\Category;
use App\Models\Employee;
use BackedEnum;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class PayrollResource extends Resource
{
    protected static ?string $model = Employee::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'Payroll';

    public static function table(Table $table): Table
    {
        // return PayrollsTable::configure($table);
        return $table
            ->recordUrl(null)
            ->query(function () {
                $user = Auth::user();
                if (! $user || ! $user->id) {
                    return Employee::whereRaw('1 = 0');
                }
                $sessionStatus = session('session_employeestatus');
                $sessionType = session('session_employeetype');

                // Fallback if someone hits this page without using your redirect action button
                if (!$sessionStatus || !$sessionType) {
                    return Employee::query()->where('status', true);
                }
                // 🌟 THE FIX: Convert Category ID numbers back into string values ('Semi-monthly', etc.)
                // If your session already stores strings like 'admin', this fallback keeps it intact.
                $empStatus = is_numeric($sessionStatus)
                    ? Category::find($sessionStatus)?->name
                    : $sessionStatus;

                $empType = is_numeric($sessionType)
                    ? Category::find($sessionType)?->name
                    : $sessionType;

                // Return the query builder instance. DO NOT USE ->get() or ->orderBy() here.
                return Employee::query()
                    ->where('empstatus', $empStatus)
                    ->where('employeetype', $empType)
                    ->where('status', true);
            })
            ->columns([
                TextColumn::make('employeeid')->sortable()->searchable(),
                TextColumn::make('firstname')->sortable()->searchable(),
                TextColumn::make('middlename')->sortable(),
                TextColumn::make('lastname')->sortable()->searchable(),
            ])
            ->filters([])
            ->actions([
                ActionGroup::make([
                    EditAction::make()
                        ->label('Update'),
                    DeleteAction::make()
                        ->label('Remove'),
                ])
                    ->label('Action')
                    ->icon('heroicon-m-chevron-down')
                    ->button()
                    ->outlined()
                    ->color('warning'),
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
            'index' => ListPayrolls::route('/'),
            'create' => CreatePayroll::route('/create'),
            'edit' => EditPayroll::route('/{record}/edit'),
        ];
    }
}
