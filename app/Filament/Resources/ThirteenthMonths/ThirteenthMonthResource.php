<?php

namespace App\Filament\Resources\ThirteenthMonths;

use App\Filament\Resources\ThirteenthMonths\Pages\CreateThirteenthMonth;
use App\Filament\Resources\ThirteenthMonths\Pages\EditThirteenthMonth;
use App\Filament\Resources\ThirteenthMonths\Pages\ListThirteenthMonths;
use App\Models\DatePeriod;
use App\Models\Employee;
use App\Models\OtherDeduction;
use App\Models\OtherDeductionLog;
use App\Models\ThirteenthMonth;
use BackedEnum;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class ThirteenthMonthResource extends Resource
{
    protected static ?string $model = ThirteenthMonth::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;
    protected  static string|UnitEnum|null $navigationGroup = 'Reports';
    protected static ?string $recordTitleAttribute = 'ThirteenthMonth';
    protected static ?string $navigationLabel = 'Year End Reports';
    protected static ?string $modelLabel = '13Month and Incentives Reports';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Select::make('employeeid') // still bind to the actual foreign key
                    ->label('Employee')
                    ->options(Employee::all()->pluck('fullname', 'employeeid')) // use the accessor
                    ->searchable()
                    ->disabled()
                    ->required(),


                TextInput::make('total_amount')
                    ->numeric()
                    ->label('Total Amount')
                    ->required()
                    ->default(0),
            ]);
    }


    public static function table(Table $table): Table
    {
        return $table
            ->extraAttributes([
                'style' => 'border: 2px solid #2d2380 !important; border-radius: 0.75rem;', // Deep Sapphire Blue
            ])
            ->columns([
                TextColumn::make('period.category.name')
                    ->label('Category')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('period.datefrom')
                    ->label('Date From')
                    ->date()
                    ->sortable()
                    ->searchable(),
                TextColumn::make('period.dateto')
                    ->label('Date To')
                    ->date()
                    ->sortable()
                    ->searchable(),
                TextColumn::make('employee.fullname')
                    ->label('Employee')
                    ->sortable('employees.lastname') // directly reference the database column
                    ->searchable(query: function ($query, $search) {
                        $query->whereHas('employee', function ($q) use ($search) {
                            $q->where('firstname', 'like', "%{$search}%")
                                ->orWhere('lastname', 'like', "%{$search}%");
                        });
                    }),
                TextColumn::make('total_amount')
                    ->label('Total Amount')
                    ->money('php')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('periodid')
                    ->label('Period')
                    ->options(
                        DatePeriod::all()
                            ->mapWithKeys(fn($period) => [
                                $period->id => ($period->code ?: 'OOOOOO') . ' | ' .
                                    Carbon::parse($period->datefrom)->format('M. d, Y') .
                                    ' - ' .
                                    Carbon::parse($period->dateto)->format('M. d, Y')
                            ])
                            ->toArray()
                    )
                    ->placeholder('Select Period'),
            ], layout: FiltersLayout::AboveContent)
            ->filtersFormWidth('2xl')
            ->modifyQueryUsing(function (Builder $query, Table $table): Builder {
                $periodId = $table->getFilter('periodid')?->getState();
                if (empty($periodId)) {
                    return $query->whereRaw('1 = 0');
                }
                return $query->where('periodid', $periodId);
            })
            ->defaultSort('period.datefrom')
            ->emptyStateActions([])
            ->actions([
                ActionGroup::make([
                    EditAction::make()
                        ->label('Update'),
                    DeleteAction::make()
                        ->label('Remove'),
                    Action::make('otherDeduction')
                        ->label('Manage Other Deductions')
                        ->icon('heroicon-o-plus-circle')
                        ->color('warning')
                        ->modalHeading('Other Deductions')
                        ->modalContent(fn($record) => view('livewire.other-deduction-modal', [
                            'employeeId' => $record->employeeid,
                            'datePeriodId' => $record->periodid,
                        ]))
                        ->modalContent(function ($record) {
                            $deductionLogs = OtherDeductionLog::where('employee_id', $record->employeeid)
                                ->where('date_period_id', $record->periodid)
                                ->with('otherDeduction')
                                ->get();

                            // Display current deductions with a remove button
                            return view('filament.partials.other-deduction-modal', [
                                'deductionLogs' => $deductionLogs,
                                'record' => $record,
                            ]);
                        })
                        ->modalFooterActions([
                            Action::make('addDeduction')
                                ->label('Add Deduction')
                                ->form([
                                    Select::make('other_deduction_id')
                                        ->label('Deduction Type')
                                        ->options(OtherDeduction::pluck('title', 'id'))
                                        ->searchable()
                                        ->required(),
                                    TextInput::make('amount')
                                        ->label('Amount')
                                        ->numeric()
                                        ->required(),
                                ])
                                ->action(function ($record, array $data) {
                                    OtherDeductionLog::create([
                                        'other_deduction_id' => $data['other_deduction_id'],
                                        'employee_id'        => $record->employeeid,
                                        'date_period_id'     => $record->periodid,
                                        'amount'             => $data['amount'],
                                    ]);
                                    Notification::make()
                                        ->title('Deduction Added')
                                        ->body('Deduction saved successfully.')
                                        ->success()
                                        ->send();
                                }),
                        ]),
                ])
                    ->label('Action')
                    ->icon('heroicon-m-chevron-down')
                    ->color('success')
                    ->size('xs')
                    ->outlined(),

            ])
            ->bulkActions([
                DeleteBulkAction::make(),
                BulkAction::make('printPayslip')
                    ->label('Print Payslip')
                    ->icon('heroicon-o-printer')
                    ->requiresConfirmation()
                    ->action(function ($records) {
                        foreach ($records as $record) {


                            $employeeId = $record->employee_id;
                            $periodId   = $record->period_id;

                            // You can now use:
                            // $record->employee_id
                            // $record->period_id
                            // $record->employee->fullname
                            // $record->period->datefrom
                            // etc.

                            // Example: send to worker that prints PDF
                            // PrintPayslipJob::dispatch($employeeId, $periodId);
                        }

                        Notification::make()
                            ->title('Payslips are being generated')
                            ->success()
                            ->send();
                    }),

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
            'index' => ListThirteenthMonths::route('/'),
            'create' => CreateThirteenthMonth::route('/create'),
            'edit' => EditThirteenthMonth::route('/{record}/edit'),
        ];
    }
}
