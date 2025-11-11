<?php

namespace App\Filament\Resources\ThirteenthMonths;

use App\Filament\Resources\ThirteenthMonths\Pages\CreateThirteenthMonth;
use App\Filament\Resources\ThirteenthMonths\Pages\EditThirteenthMonth;
use App\Filament\Resources\ThirteenthMonths\Pages\ListThirteenthMonths;
use App\Filament\Resources\ThirteenthMonths\Schemas\ThirteenthMonthForm;
use App\Filament\Resources\ThirteenthMonths\Tables\ThirteenthMonthsTable;
use App\Models\DatePeriod;
use App\Models\Employee;
use App\Models\OtherDeduction;
use App\Models\OtherDeductionLog;
use App\Models\ThirteenthMonth;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class ThirteenthMonthResource extends Resource
{
    protected static ?string $model = ThirteenthMonth::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;
    protected  static string|UnitEnum|null $navigationGroup = 'Reports';
    protected static ?string $recordTitleAttribute = 'ThirteenthMonth';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([

                Select::make('periodid')
                    ->label('Period')
                    ->options(DatePeriod::pluck('periodname', 'id'))
                    ->searchable()
                    ->required(),

                Select::make('employeeid')
                    ->label('Employee')
                    ->options(Employee::pluck('fullname', 'employeeid'))
                    ->searchable()
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
                    ->sortable()
                    ->searchable(),

                TextColumn::make('total_amount')
                    ->label('Total Amount')
                    ->money('php')
                    ->sortable(),


            ])
            ->filters([])
            ->actions([
                // EditAction::make(),
                Action::make('otherDeduction')
                    ->label('Manage Other Deductions')
                    ->icon('heroicon-o-plus-circle')
                    ->color('warning')
                    ->modalHeading('Other Deductions')
                    ->modalContent(fn($record) => view('livewire.other-deduction-modal', [
                        'employeeId' => $record->employeeid,
                        'datePeriodId' => $record->periodid,
                    ]))
                    // ->modalContent(function ($record) {
                    //     $deductionLogs = OtherDeductionLog::where('employee_id', $record->employeeid)
                    //         ->where('date_period_id', $record->periodid)
                    //         ->with('otherDeduction')
                    //         ->get();

                    //     // Display current deductions with a remove button
                    //     return view('filament.partials.other-deduction-modal', [
                    //         'deductionLogs' => $deductionLogs,
                    //         'record' => $record,
                    //     ]);
                    // })
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


                                // dd($record);
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
                // Action::make('otherDeduction')
                //     ->label('Add Other Deduction')
                //     ->icon('heroicon-o-plus-circle')
                //     ->color('warning')
                //     ->modalHeading('Add Other Deduction')
                //     ->modalSubmitActionLabel('Save Deduction')
                //     ->form([
                //         Select::make('deduction_id')
                //             ->label('Deduction Type')
                //             ->options(OtherDeduction::pluck('title', 'id'))
                //             ->searchable()
                //             ->required(),
                //         TextInput::make('amount')
                //             ->label('Amount')
                //             ->numeric()
                //             ->required(),
                //     ])
                //     ->action(function ($record, array $data) {
                //         OtherDeductionLog::create([
                //             'other_deduction_id' => $data['other_deduction_id'],
                //             'employee_id'        => $record->employeeid,
                //             'date_period_id'     => $record->periodid,
                //             'amount'             => $data['amount'],
                //         ]);

                //         Notification::make()
                //             ->title('Deduction Added')
                //             ->body("Deduction for {$record->employee->fullname} saved successfully.")
                //             ->success()
                //             ->send();
                //     }),
            ])
            ->bulkActions([
                DeleteBulkAction::make(),
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
