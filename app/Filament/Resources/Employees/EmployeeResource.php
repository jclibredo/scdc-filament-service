<?php

namespace App\Filament\Resources\Employees;

use App\Filament\Resources\Earnings\EarningsResource;
use App\Filament\Resources\Employees\Pages\CreateEmployee;
use App\Filament\Resources\Employees\Pages\ListEmployees;
use App\Jobs\ProcessEmployeeCsv;
use App\Models\Category;
use App\Models\Employee;
use App\Models\Project;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class EmployeeResource extends Resource
{
    protected static ?string $model = Employee::class;
    protected  static string|UnitEnum|null $navigationGroup = 'User Management';
    protected static string|BackedEnum|null $navigationIcon = Heroicon::FingerPrint;

    protected static ?string $recordTitleAttribute = 'Employee';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Employee Information')
                    ->description('Please fill out all the required fields to manage this employee record.')
                    ->columns(3) // Sets a 3-column grid layout for the entire section
                    ->columnSpanFull() // Makes the section span the full width of the form
                    ->schema([
                        TextInput::make('employeeid')
                            ->label('Employee ID')
                            ->unique(table: 'employees', column: 'employeeid', ignoreRecord: true)
                            ->required(),
                        TextInput::make('firstname')->required()->maxLength(255),
                        TextInput::make('middlename')->maxLength(255),
                        TextInput::make('lastname')->required()->maxLength(255),
                        Toggle::make('status')->label('Active')->default(true),
                        TextInput::make('mobile')->maxLength(20),
                        Select::make('empstatus')
                            ->label('Employee Status')
                            ->options(function () {
                                // Dynamically filters categories matching the 'EMPLOYEE_TYPE' handle
                                return Category::query()
                                    ->where('cat', 'EMPLOYEE_STATUS')
                                    ->pluck('name', 'id');
                            })
                            ->searchable()
                            ->preload()
                            ->required(),

                        TextInput::make('email')->label('Email Address')
                            ->email()
                            ->unique(ignoreRecord: true),
                        DatePicker::make('birthdate')->required(),
                        Select::make('sex')
                            ->options([
                                'Male' => 'Male',
                                'Female' => 'Female',
                                'Other' => 'Other',
                            ])
                            ->required(),
                        Textarea::make('address')->rows(3),
                        DatePicker::make('datehired')->required(),
                        DatePicker::make('dateseperated'),
                        Select::make('employeetype')
                            ->label('Employee Type')
                            ->options(function () {
                                // Dynamically filters categories matching the 'EMPLOYEE_TYPE' handle
                                return Category::query()
                                    ->where('cat', 'EMPLOYEE_TYPE')
                                    ->pluck('name', 'id');
                            })
                            ->searchable()
                            ->preload()
                            ->required(),


                        Select::make('skill_id')
                            ->label('Skill')
                            ->relationship('skill', 'title')
                            ->searchable()
                            ->preload(),
                        Select::make('project_id')
                            ->label('Project')
                            ->relationship('project', 'name')
                            ->searchable()
                            ->preload(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordUrl(null)
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
                IconColumn::make('status')->boolean()->label('Active'),
                // Add this column
                TextColumn::make('empStat.name')
                    ->label('Emp. Status')
                    ->badge() // Optional: makes it look like a pill
                    ->color('info')
                    ->sortable(),
                TextColumn::make('skill.title')->label('Skill'),
                TextColumn::make('project.name')->label('Project'),
            ])
            ->filters([
                // Filter by Employee Type
                SelectFilter::make('employeetype')
                    ->label('Employee Type')
                    ->options([
                        'SM' => 'Semi Monthly',
                        'W'  => 'Weekly',
                    ])
                    ->placeholder('Select Employee Type'),

                // NEW: Employment Status Filter
                SelectFilter::make('empstatus')
                    ->label('Employment Status')
                    ->options([
                        'admin' => 'SCDC Admin',
                        'subcon' => 'Subcontractor',
                    ])
                    ->placeholder('Select Status'),
                // FILTER: Project
                SelectFilter::make('project_id')
                    ->label('Project')
                    ->options(
                        Project::orderBy('name', 'asc')->pluck('name', 'id')
                    )
                    ->placeholder('Select Project'),
            ], layout: FiltersLayout::AboveContent)
            ->filtersFormWidth('2xl')
            ->actions([
                ActionGroup::make([
                    Action::make('viewEarnings')
                        ->label('View Earnings')
                        ->icon('heroicon-o-banknotes')
                        ->action(function (Employee $record) {
                            // Store the values in the session temporarily
                            session(['earnings_employeeid' => $record->employeeid]);
                            // Redirect to the create page WITHOUT query parameters
                            return redirect(EarningsResource::getUrl('index'));
                        }),
                    EditAction::make()
                        ->label('Update'),
                    DeleteAction::make()
                        ->label('Remove'),

                ])->label('Action')
                    ->icon('heroicon-m-chevron-down')
                    ->button()
                    ->outlined()
                    ->color('warning'),
            ])
            ->headerActions([
                Action::make('upload_employee')
                    ->label('Upload Employees')
                    ->button()
                    ->form([
                        FileUpload::make('uploadfile')
                            ->label('Employee CSV File')
                            ->required()
                            ->acceptedFileTypes(['text/csv'])
                            ->disk('public')
                            ->directory('employees'), // Stores in storage/app/public/employees
                    ])
                    ->action(function (array $data) {
                        // Handle uploaded CSV import logic here
                        $file = $data['uploadfile'];
                        ProcessEmployeeCsv::dispatch($file);
                        Notification::make()
                            ->title('CSV Queued for Processing')
                            ->body('The CSV file will be processed shortly.')
                            ->success()
                            ->send();
                    }),
            ])
        ;
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
            'index' => ListEmployees::route('/'),
            'create' => CreateEmployee::route('/create'),
        ];
    }
}
