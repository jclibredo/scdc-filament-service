<?php

namespace App\Filament\Resources\DatePeriods;

use App\Filament\Resources\DatePeriods\Pages\ListDatePeriods;
use App\Models\Category;
use App\Models\DatePeriod;
use App\Models\Employee;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class DatePeriodResource extends Resource
{
    protected static ?string $model = DatePeriod::class;
    protected  static string|UnitEnum|null $navigationGroup = 'Reports';
    protected static string|BackedEnum|null $navigationIcon = Heroicon::Printer;

    protected static ?string $recordTitleAttribute = 'DatePeriod';

    public static function form(Schema $schema): Schema
    {
        // return DatePeriodForm::configure($schema);
        return $schema
            ->schema([
                Select::make('employeetype')
                    ->label('Employee Type')
                    ->options([
                        'semi-monthly' => 'Semi-monthly',
                        'weekly' => 'Weekly',
                    ])
                    ->required(),

                Select::make('category_id')
                    ->label('Category')
                    ->options(Category::pluck('name', 'id'))
                    ->searchable()
                    ->required(),

                DatePicker::make('datefrom')->label('Date From')->required(),
                DatePicker::make('dateto')->label('Date To')->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        // return DatePeriodsTable::configure($table);
        return $table
            ->columns([
                TextColumn::make('employeetype')->sortable(),
                TextColumn::make('category.name')->label('Category')->sortable(),
                TextColumn::make('datefrom')->date()->label('Date From'),
                TextColumn::make('dateto')->date()->label('Date To'),
                TextColumn::make('created_at')->dateTime()->label('Created'),
            ])
            ->filters([])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
                Action::make('view_payslip')
                    ->label('View Payslip')
                    ->color('primary')
                    ->button()
                    ->action(function ($record) {
                        Notification::make()
                            ->title('Payslip Viewer Coming Soon!')
                            ->body("This will open the payslip for: {$record->employee_type}")
                            ->success()
                            ->send();
                    }),

                Action::make('upload_data')
                    ->label('Upload Data')
                    ->button()
                    ->form([
                        FileUpload::make('uploadfile')
                            ->label('Upload CSV File')
                            ->required()
                            ->acceptedFileTypes(['text/csv']),
                    ])
                    ->action(function (array $data, $record) {
                        $file = $data['uploadfile'];
                        $path = $file->store('uploads/dateperiods');

                        // TODO: parse and import CSV data for this specific $record
                        Notification::make()
                            ->title('CSV Uploaded Successfully')
                            ->body("File for DatePeriod #{$record->id} stored at: {$path}")
                            ->success()
                            ->send();
                    }),

                Action::make('export_csv')
                    ->label('Export Employees')
                    ->color('success')
                    ->button()
                    ->action(function () {
                        $employees = Employee::with('project')->get();

                        // 🧩 Check if there are any employees
                        if ($employees->isEmpty()) {
                            Notification::make()
                                ->title('No Data Found')
                                ->body('There are no employees to export.')
                                ->warning()
                                ->send();

                            return;
                        }

                        $filename = 'employees_export_' . now()->format('Y_m_d_His') . '.csv';
                        $path = storage_path('app/' . $filename);

                        $handle = fopen($path, 'w');
                        fputcsv($handle, ['Employee ID', 'First Name', 'Last Name', 'Project']); // headers

                        foreach ($employees as $employee) {
                            fputcsv($handle, [
                                $employee->employeeid,
                                $employee->firstname,
                                $employee->lastname,
                                optional($employee->project)->name ?? 'No Project',
                            ]);
                        }

                        fclose($handle);

                        // ✅ Success notification
                        Notification::make()
                            ->title('Export Successful')
                            ->body('The employee data has been exported successfully.')
                            ->success()
                            ->send();

                        // ✅ Return the CSV file for download
                        return response()->download($path)->deleteFileAfterSend(true);
                    }),
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
            'index' => ListDatePeriods::route('/'),
            // 'create' => CreateDatePeriod::route('/create'),
            // 'edit' => EditDatePeriod::route('/{record}/edit'),
        ];
    }
}
