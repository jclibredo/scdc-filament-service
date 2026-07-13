<?php

namespace App\Filament\Resources\Employees;

use App\Filament\Resources\Earnings\EarningsResource;
// use App\Filament\Resources\Employees\Pages\CreateEmployee;
use App\Filament\Resources\Employees\Pages\ListEmployees;
use App\Filament\Resources\EmpSchedules\EmpScheduleResource;
// use App\Jobs\ProcessEmployeeCsv;
use App\Models\Category;
use App\Models\Employee;
use App\Models\EmployeeProjectHistory;
// use App\Models\EmpSchedule;
use App\Models\User;
use App\Services\TransactionCheckService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
// use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
// use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use UnitEnum;

class EmployeeResource extends Resource
{
    protected static ?string $model = Employee::class;
    protected  static string|UnitEnum|null $navigationGroup = 'Utility Management';
    protected static string|BackedEnum|null $navigationIcon = Heroicon::UserGroup;


    protected static ?string $recordTitleAttribute = 'Employee Details';
    protected static ?string $pluralModelLabel = 'Employee';
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
            ->whereIn('module', ['SUPERADMIN', 'HR'])
            ->exists();
    }
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Employee Information')
                    ->extraAttributes([
                        'style' => 'border: 2px solid #2d2380 !important; border-radius: 0.75rem;', // Deep Sapphire Blue
                    ])
                    ->description('Please fill out all the required fields to manage this employee record.')
                    ->columns(3) // Sets a 3-column grid layout for the entire section
                    ->columnSpanFull() // Makes the section span the full width of the form
                    ->schema([
                        TextInput::make('employeeid')
                            ->label('Employee ID')
                            ->unique(table: 'employees', column: 'employeeid', ignoreRecord: true)
                            ->extraInputAttributes([
                                // Added 0-9 to the regex character validation layout to permit numeric inputs safely
                                'oninput' => "this.value = this.value.replace(/[^A-Za-z0-9\\s]/g, '')
                            .toUpperCase().replace(/^\\s+/, '').slice(0, 8);",
                                'maxlength' => 8,
                            ])
                            ->required(),
                        TextInput::make('firstname')->required()->maxLength(255)
                            ->extraInputAttributes([
                                // Added 0-9 to the regex character validation layout to permit numeric inputs safely
                                'oninput' => "this.value = this.value.replace(/[^A-Za-z0-9\\s]/g, '')
                            .toUpperCase().replace(/^\\s+/, '').slice(0, 50);",
                                'maxlength' => 50,
                            ]),
                        TextInput::make('middlename')->maxLength(255)
                            ->extraInputAttributes([
                                // Added 0-9 to the regex character validation layout to permit numeric inputs safely
                                'oninput' => "this.value = this.value.replace(/[^A-Za-z0-9\\s]/g, '')
                            .toUpperCase().replace(/^\\s+/, '').slice(0, 50);",
                                'maxlength' => 50,
                            ]),
                        TextInput::make('lastname')->required()->maxLength(255)
                            ->extraInputAttributes([
                                // Added 0-9 to the regex character validation layout to permit numeric inputs safely
                                'oninput' => "this.value = this.value.replace(/[^A-Za-z0-9\\s]/g, '')
                            .toUpperCase().replace(/^\\s+/, '').slice(0, 50);",
                                'maxlength' => 50,
                            ]),
                        Toggle::make('status')->label('Active')->default(true),
                        TextInput::make('mobile')->maxLength(20)
                            ->rule([
                                'regex:/^[89]\d{9}$/', // Starts with 8 or 9, followed by 9 digits
                            ])
                            ->extraInputAttributes([
                                'oninput' => "
                                    this.value = this.value.replace(/\\D/g, '');
                                    if (this.value.startsWith('0')) {
                                        this.value = this.value.substring(1);
                                    }
                                    this.value = this.value.slice(0, 10);
                                ",
                            ]),
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
                            ->maxLength(100)
                            ->unique(ignoreRecord: true),
                        DatePicker::make('birthdate')->required(),
                        Select::make('sex')
                            ->options([
                                'Male' => 'Male',
                                'Female' => 'Female',
                                'Other' => 'Other',
                            ])
                            ->required(),
                        Textarea::make('address')
                            ->extraInputAttributes([
                                // Added 0-9 to the regex character validation layout to permit numeric inputs safely
                                'oninput' => "this.value = this.value.replace(/[^A-Za-z0-9\\s]/g, '')
                            .toUpperCase().replace(/^\\s+/, '').slice(0, 100);",
                                'maxlength' => 100,
                            ])->rows(3),
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
                            ->required()
                            ->live(),

                        // 2. Dependent Sub. Contractor Field
                        Select::make('partners')
                            ->label('Sub. Contractor')
                            ->options(function () {
                                return Category::query()
                                    ->where('cat', 'SUBCON')
                                    ->pluck('name', 'id');
                            })
                            ->searchable()
                            ->preload()
                            ->visible(function (Get $get) {
                                $selectedId = $get('employeetype');
                                if (! $selectedId) {
                                    return false;
                                }
                                $category = Category::find($selectedId);
                                return $category && strtoupper($category->name) === 'SUB-CON';
                            })
                            ->required(
                                fn(Get $get) => ($cat = Category::find($get('employeetype'))) && strtoupper($cat->name) === 'SUB-CON'
                            ),


                        Select::make('skill_id')
                            ->label('Skill')
                            ->relationship('skill', 'title')
                            ->searchable()
                            ->preload(),
                        Select::make('project_id')
                            ->label('Project')
                            ->required()
                            ->relationship('project', 'name')
                            ->searchable()
                            ->preload(),
                    ]),
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
                    return Employee::whereRaw('1 = 0');
                }
                return Employee::where('status', true); // Add this line
            })
            ->columns([
                TextColumn::make('employeeid')->sortable()->searchable(),
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
                // 1. FILTER: Employee Type (Filtered by Category: EMPLOYEE_TYPE)
                SelectFilter::make('employeetype_id')
                    ->label('Employee Type')
                    ->relationship(
                        name: 'empType',
                        titleAttribute: 'name',
                        // 💡 Scopes down the drop-down list to ONLY show items under this category
                        modifyQueryUsing: fn(Builder $query) => $query->where('cat', 'EMPLOYEE_TYPE')
                    )
                    ->preload()
                    ->placeholder('All Employee Types'),

                // 2. FILTER: Employment Status (Filtered by Category: EMPLOYEE_STATUS)
                SelectFilter::make('empstatus_id')
                    ->label('Employment Status')
                    ->relationship(
                        name: 'empStat',
                        titleAttribute: 'name',
                        // 💡 Scopes down the drop-down list to ONLY show items under this category
                        modifyQueryUsing: fn(Builder $query) => $query->where('cat', 'EMPLOYEE_STATUS')
                    )
                    ->preload()
                    ->placeholder('All Statuses'),

                // 3. FILTER: Project
                SelectFilter::make('project_id')
                    ->label('Project')
                    ->relationship('project', 'name')
                    ->preload()
                    ->placeholder('All Projects'),
            ], layout: FiltersLayout::AboveContent)
            ->filtersFormWidth('2xl')
            ->actions([
                ActionGroup::make([
                    Action::make('viewEarnings')
                        ->label('View Earnings')
                        ->icon('heroicon-o-banknotes')
                        ->action(function (Employee $record) {
                            session(['session_employee_id' => $record->employeeid]);
                            return redirect(EarningsResource::getUrl('index'));
                        }),

                    Action::make('transferAssignment')
                        ->label('Transfer Assignment')
                        ->icon('heroicon-o-arrow-path')
                        ->color('warning')
                        ->form([

                            // SECTION 1: Current Assignment & End Date
                            Section::make('Current Assignment Details')
                                ->extraAttributes([
                                    'style' => 'border: 2px solid #2d2380 !important; border-radius: 0.75rem;', // Deep Sapphire Blue
                                ])
                                ->description('Review the employee’s current status and set their assignment end date.')
                                ->icon('heroicon-o-user')
                                ->schema([
                                    Grid::make(2)
                                        ->schema([
                                            TextInput::make('employee_display_id')
                                                ->label('Employee ID')
                                                ->disabled(),
                                            TextInput::make('employee_display_name')
                                                ->label('Full Name')
                                                ->disabled(),
                                            TextInput::make('employee_type')
                                                ->label('Employee Type')
                                                ->disabled(),
                                            TextInput::make('employee_status')
                                                ->label('Emp. Status')
                                                ->disabled(),
                                        ]),
                                ]),

                            // SECTION 2: New Assignment Allocation
                            Section::make('New Assignment Allocation')
                                ->extraAttributes([
                                    'style' => 'border: 2px solid #2d2380 !important; border-radius: 0.75rem;', // Deep Sapphire Blue
                                ])
                                ->description('Select the target destination project and configuration rules.')
                                ->icon('heroicon-o-briefcase')
                                ->schema([
                                    Grid::make(2)
                                        ->schema([
                                            Select::make('new_project_code')
                                                ->label('Select New Project')
                                                ->relationship(
                                                    name: 'project',
                                                    titleAttribute: 'name',
                                                    modifyQueryUsing: fn($query, Employee $record) => $query->where('project_code', '!=', $record->project_code)
                                                )
                                                ->preload()
                                                ->live() // ⚡ Forces the form to evaluate visibility conditions instantly
                                                ->required()
                                                ->columnSpan(2),
                                            // 📅 Hidden by default, visible ONLY when a valid new project selection is made
                                            DatePicker::make('dateended')
                                                ->label('Current Project Date Ended')
                                                ->default(now())
                                                // ->required()
                                                ->visible(fn(Get $get) => filled($get('new_project_code'))),
                                            // 📅 Hidden by default, visible ONLY when a valid new project selection is made
                                            DatePicker::make('datestarted')
                                                ->label('New Project Date Started')
                                                ->default(now())
                                                // ->required()
                                                ->visible(fn(Get $get) => filled($get('new_project_code'))),
                                        ]),
                                ]),
                        ])
                        ->mountUsing(function (Schema $form, Employee $record) {
                            $form->fill([
                                'employee_display_id'   => $record->employeeid,
                                'employee_display_name' => "{$record->lastname}, {$record->firstname} {$record->middlename}",
                                'employee_type'         => $record->empType?->name,
                                'employee_status'       => $record->empStat?->name,
                            ]);
                        })
                        ->action(function (Employee $record, array $data): void {
                            $newProjectCode = $data['new_project_code'];
                            $dateEnded = $data['dateended'];     // 📅 From Section 1
                            $dateStarted = $data['datestarted']; // 📅 From Section 2
                            // 1. Process active assignment history logic
                            $currentHistory = EmployeeProjectHistory::where('employeeid', $record->employeeid)
                                ->where('projectid', $record->project_id)
                                ->where('status', true)
                                ->first();
                            if ($currentHistory) {
                                if ($currentHistory->projectid != $newProjectCode) {
                                    if ($dateEnded === null || $dateStarted === null) {
                                        Notification::make()
                                            ->title('Error: Both "Current Project Date Ended" and "New Project Date Started" must be provided.')
                                            ->danger()
                                            ->send();
                                        return;
                                    }
                                    // Terminate the active history using Section 1's "dateended"
                                    $currentHistory->update([
                                        'status'    => false,
                                        'dateended' => $dateEnded,
                                    ]);

                                    // Spin up new history using Section 2's "datestarted"
                                    EmployeeProjectHistory::create([
                                        'employeeid'      => $record->employeeid,
                                        'projectid'       => $newProjectCode,
                                        'employeetype'    => $record->employeetype_id,
                                        'employee_status' => $record->empstatus_id,
                                        'datestarted'     => $dateStarted,
                                        'status'          => true,
                                    ]);
                                }
                            } else {
                                if ($dateStarted === null) {
                                    Notification::make()
                                        ->title('Error: "New Project Date Started" must be provided.')
                                        ->danger()
                                        ->send();
                                    return;
                                }
                                // Backup initial creation if no active history tracking exists yet
                                EmployeeProjectHistory::create([
                                    'employeeid'      => $record->employeeid,
                                    'projectid'       => $newProjectCode,
                                    'employeetype'    => $record->employeetype_id,
                                    'employee_status' => $record->empstatus_id,
                                    'datestarted'     => $dateStarted,
                                    'status'          => true,
                                ]);
                            }

                            // 2. Sync to core Employee entity
                            $record->update([
                                'project_code' => $newProjectCode,
                            ]);

                            Notification::make()
                                ->title('Assignment updated and structural history logged.')
                                ->success()
                                ->send();
                        }),







                    Action::make('viewSched')
                        ->label('View Sched')
                        ->color('success')
                        ->icon('heroicon-o-calendar-days')
                        ->action(function (Employee $record) {
                            // Store the values in the session temporarily
                            session(['session_employee_id' => $record->employeeid]);
                            // Redirect to the create page WITHOUT query parameters
                            return redirect(EmpScheduleResource::getUrl('index'));
                        }),

                    EditAction::make()
                        ->after(function (Employee $record) {
                            // 1. Handle termination logic immediately if dateseperated is set
                            if ($record->dateseperated !== null) {
                                EmployeeProjectHistory::where('employeeid', $record->employeeid)
                                    ->where('status', true)
                                    ->where('projectid', $record->project_id) // Ensure we only update the current project history
                                    ->update([
                                        'status'    => false,
                                        'dateended' => $record->dateseperated,
                                    ]);

                                return; // Exit early since employment ended
                            }
                            if ($record->dateseperated === null) {
                                DB::transaction(function () use ($record) {
                                    if ($record->wasChanged(['employeeid'])) {
                                        // Find the history using the original ID before it was changed
                                        EmployeeProjectHistory::where('employeeid', $record->getOriginal('employeeid'))
                                            ->where('status', true)
                                            ->update([
                                                'employeeid'      => $record->employeeid, // Update to the new ID
                                                'employeetype'    => $record->employeetype,
                                                'employee_status' => $record->empstatus,
                                                'projectid'       => $record->project_id,
                                            ]);
                                    } else {
                                        // Standard update if employeeid didn't change
                                        EmployeeProjectHistory::where('employeeid', $record->employeeid)
                                            ->where('status', true)
                                            ->update([
                                                'employeetype'    => $record->employeetype,
                                                'employee_status' => $record->empstatus,
                                                'projectid'       => $record->project_id,
                                            ]);
                                    }
                                });
                            }
                        })
                        ->label('Update'),
                    // DeleteAction::make()
                    //     ->label('Remove'),
                    DeleteAction::make()
                        ->label('Remove')
                        ->visible(fn(Employee $record) => !TransactionCheckService::hasEmployeeTransactions($record))
                        ->requiresConfirmation() // ⚠️ Prompts the user with a confirmation modal
                        ->modalHeading('Remove Employee Record')
                        ->modalDescription('Are you sure you want to permanently remove this employee? This action will also wipe all linked employee project assignment histories.')
                        ->modalSubmitActionLabel('Yes, delete everything')
                        ->action(function (Employee $record) {
                            DB::transaction(function () use ($record) {
                                // 1. Purge all linked history records matching this employee configuration
                                EmployeeProjectHistory::where('employeeid', $record->employeeid)->delete();
                                // 2. Clear out the main employee record
                                $record->delete();
                            });
                        }),

                    Action::make('deactivate')
                        ->label('Deactivate')
                        ->icon('heroicon-o-x-circle')
                        ->color('warning')
                        ->requiresConfirmation() // ⚠️ Adds the confirmation step before running
                        ->modalHeading('Deactivate Record')
                        ->modalDescription('This record has active transactions and cannot be deleted. Deactivating it will turn its status to inactive. Proceed?')
                        ->modalSubmitActionLabel('Yes, deactivate')
                        ->action(function ($record) {
                            // Deactivate the record
                            $record->status = false;
                            $record->save();
                            Notification::make()
                                ->title('Record successfully deactivated.')
                                ->warning()
                                ->send();
                        })
                        // 👁️ Only visible if it has transactions AND is currently active
                        ->visible(fn($record) => TransactionCheckService::hasEmployeeTransactions($record) && ($record->status === true || $record->status == 1)),

                ])->label('Action')
                    ->icon('heroicon-m-chevron-down')
                    ->button()
                    ->size('xs')
                    ->outlined()
                    ->color('success'),
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
            'index' => ListEmployees::route('/'),
            // 'create' => CreateEmployee::route('/create'),
        ];
    }
}
