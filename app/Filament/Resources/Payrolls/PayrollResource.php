<?php

namespace App\Filament\Resources\Payrolls;

use App\Filament\Resources\Atlogs\AtlogResource;
use App\Filament\Resources\Payrolls\Pages\CreatePayroll;
use App\Filament\Resources\Payrolls\Pages\EditPayroll;
use App\Filament\Resources\Payrolls\Pages\ListPayrolls;
use App\Models\Category;
use App\Models\Employee;
use BackedEnum;
use Filament\Actions\Action;
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

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

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

                // dd("STATUS ".$sessionType." TYPE:".$sessionStatus);
                // Fallback if someone hits this page without using your redirect action button
                // if (!$sessionStatus || !$sessionType) {
                //     return Employee::query()->where('status', true);
                // }
                return Employee::where('empstatus', $sessionStatus)
                    ->where('employeetype', $sessionType)
                    ->where('status', true);
            })
            ->columns([
                TextColumn::make('employeeid')->sortable()->searchable(),
                // TextColumn::make('firstname')->sortable()->searchable(),
                // TextColumn::make('middlename')->sortable(),
                // TextColumn::make('lastname')->sortable()->searchable(),

                // Combined Full Name Column
                TextColumn::make('full_name')
                    ->label('Full Name')
                    // This allows users to search by any of the name parts
                    ->searchable(query: function ($query, string $search) {
                        $query->where(function ($q) use ($search) {
                            $q->where('lastname', 'like', "%{$search}%")
                                ->orWhere('firstname', 'like', "%{$search}%")
                                ->orWhere('middlename', 'like', "%{$search}%");
                        });
                    })
                    // This allows sorting by Lastname
                    ->sortable(query: function ($query, string $direction) {
                        return $query->orderBy('lastname', $direction)
                            ->orderBy('firstname', $direction);
                    })
                    // This handles the string concatenation cleanly
                    ->formatStateUsing(function ($record) {
                        return "{$record->lastname}, {$record->firstname} {$record->middlename}";
                    }),

                TextColumn::make('empType.name')->sortable()->searchable(),
                TextColumn::make('empStat.name')
                    ->label('Emp. Status')
                    ->badge() // Optional: makes it look like a pill
                    ->color('info')
                    ->sortable(),
                TextColumn::make('skill.title')->label('Skill'),
                TextColumn::make('project.name')->label('Project'),
            ])
            ->filters([])
            ->actions([
                ActionGroup::make([
                    EditAction::make()
                        ->label('Update'),
                    DeleteAction::make()
                        ->label('Remove'),
                    Action::make('view_timesheet')
                        ->label('View Timesheet')
                        ->action(function (Employee $record) {
                            session(['session_employee_id' => $record->employeeid]); // If you need to filter by e
                            return redirect(AtlogResource::getUrl('index'));
                        }),



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
