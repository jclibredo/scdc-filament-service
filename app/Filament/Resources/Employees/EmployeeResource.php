<?php

namespace App\Filament\Resources\Employees;

use App\Filament\Resources\Earnings\EarningsResource;
use App\Filament\Resources\Employees\Pages\CreateEmployee;
use App\Filament\Resources\Employees\Pages\ListEmployees;
use App\Jobs\ProcessEmployeeCsv;
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
                TextInput::make('employeeid')
                    ->label('Employee ID')
                    ->unique(ignoreRecord: true)
                    // Generates a number between 1000 and 9999
                    ->default(fn() => (string) random_int(1000, 9999))
                    ->required()
                    ->readOnly()
                    ->length(4) // Ensures it is exactly 4 characters
                    ->numeric(),


                TextInput::make('firstname')->required()->maxLength(255),
                TextInput::make('middlename')->maxLength(255),
                TextInput::make('lastname')->required()->maxLength(255),
                Toggle::make('status')->label('Active')->default(true),
                TextInput::make('mobile')->maxLength(20),
                // Add this Select component
                Select::make('empstatus')
                    ->label('Employment Status')
                    ->required()
                    ->options([
                        'admin' => 'SCDC Admin',
                        'subcon' => 'Subcontractor',
                    ]),
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
                    ->options([
                        'SM' => 'Semi-monthly',
                        'W' => 'Weekly',
                    ])
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
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordUrl(null)
            ->columns([
                TextColumn::make('employeeid')->sortable()->searchable(),
                TextColumn::make('firstname')->sortable()->searchable(),
                TextColumn::make('middlename')->sortable(),
                TextColumn::make('lastname')->sortable()->searchable(),
                TextColumn::make('employeetype')->sortable()->searchable(),
                IconColumn::make('status')->boolean()->label('Active'),
                // Add this column
                TextColumn::make('empstatus')
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
