<?php

namespace App\Filament\Resources\Atlogs;

// use App\Filament\Resources\Atlogs\Pages\EditAtlog;
use App\Filament\Resources\Atlogs\Pages\ListAtlogs;
use App\Models\ActivityLog;
use App\Models\Atlog;
use App\Models\DatePeriod;
use App\Models\Employee;
use App\Models\Project;
use App\Models\User;
use BackedEnum;
use Carbon\Carbon;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
// use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use FontLib\TrueType\Collection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;
use Livewire\Component;
use UnitEnum;

class AtlogResource extends Resource
{
    protected static ?string $model = Atlog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::FingerPrint;
    protected  static string|UnitEnum|null $navigationGroup = 'Report Management';

    protected static ?string $recordTitleAttribute = 'Atlog';
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
            ->whereIn(
                'module',
                [
                    'SUPERADMIN',
                    'IMPORT',
                    'PAYROLLADMINWEEKLY',
                    'PAYROLLADMINMONTHLY'
                ]
            )
            ->exists();
    }
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Log Details')
                    ->columnSpanFull()
                    ->extraAttributes([
                        'style' => 'border: 2px solid #2d2380 !important; border-radius: 0.75rem;', // Deep Sapphire Blue
                    ])
                    ->schema([
                        Select::make('user_id')
                            ->label('Employee')
                            ->required()
                            ->searchable()
                            ->live()
                            ->default(fn() => session('session_employee_id'))
                            ->disabled(fn() => filled(session('session_employee_id')))
                            ->getSearchResultsUsing(
                                fn(string $search): array =>
                                Employee::where('firstname', 'like', "%{$search}%")
                                    ->orWhere('lastname', 'like', "%{$search}%")
                                    ->orWhere('employeeid', 'like', "%{$search}%")
                                    ->limit(50)
                                    ->get()
                                    ->mapWithKeys(fn($item) => [$item->employeeid => "{$item->full_name} ({$item->employeeid})"])
                                    ->toArray()
                            )
                            ->getOptionLabelUsing(
                                fn($value): ?string =>
                                Employee::where('employeeid', $value)->first()?->full_name
                            )
                            ->afterStateUpdated(function (?string $state, Set $set) {
                                if (empty($state)) return;

                                $employee = Employee::where('employeeid', $state)->first();
                                if ($employee && $employee->project_id) {
                                    $set('project_code', $employee->project_id);
                                }
                            })
                            ->columnSpan(2),

                        Select::make('project_code')
                            ->label('Assigned Project')
                            ->placeholder('Select a project assignment...')
                            ->options(
                                Project::query()
                                    ->where('status', true)
                                    ->get()
                                    ->mapWithKeys(fn($project) => [$project->project_code => "{$project->name} ({$project->project_code})"])
                                    ->toArray()
                            )
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->required()
                            // 💡 FIX: Hydrate the default project_code value directly inside this field
                            ->default(function () {
                                if (filled(session('session_employee_id'))) {
                                    return Employee::where('employeeid', session('session_employee_id'))->first()?->project_id;
                                }
                                return null;
                            })
                            ->disabled(fn() => filled(session('session_employee_id')))
                            ->dehydrated()
                            ->columnSpan(2),
                        DateTimePicker::make('recorded_at')
                            ->required()
                            ->label('Date & Time'),

                        // 💡 FIXED: Aligned with your Table Column 1 (Att State)
                        Select::make('status')
                            ->label('Att State (Verification Method)')
                            ->options([
                                1 => 'Finger',
                                2 => 'Card',
                                5 => 'Face',
                                0 => 'Code', // Using 0 as fallback/default value
                            ])
                            ->disabled(fn(string $operation): bool => $operation === 'edit')
                            ->required()
                            ->native(false),

                        // 💡 FIXED: Aligned with your Table Column 2 (Verify Type)
                        Select::make('verification_mode')
                            ->label('Verify Type (Attendance State)')
                            ->options([
                                0 => 'Check-In',
                                1 => 'Check-Out',
                                2 => 'Break Out',
                                3 => 'Break In',
                            ])
                            ->required()
                            ->native(false),

                        TextInput::make('work_code')
                            ->numeric()
                            ->default(0)
                            ->disabled(fn(string $operation): bool => $operation === 'edit')
                            ->dehydrated(),

                        TextInput::make('reserved')
                            ->label('Device ID')
                            ->numeric()
                            ->default(0)
                            ->disabled(fn(string $operation): bool => $operation === 'edit')
                            ->dehydrated(),
                    ])->columns(2)
            ]);
    }

    public static function table(Table $table): Table
    {
        $periodcode     = session('session_periodcode');
        $sessionStatus  = session('session_employeestatus');
        $sessionType    = session('session_employeetype');
        $employeeid     = session('session_employee_id');

        if ($periodcode || $sessionStatus || $sessionType) {
            $datePerioDetails = cache()->remember(
                "header_admission_full_{$periodcode}",
                3600,
                function () use ($periodcode) {
                    return DatePeriod::where('code', $periodcode)
                        ->where('status', true)
                        ->first();
                }
            );

            $patientDetails = Employee::where('employeeid', $employeeid)->where('status', true)->first();
            if ($patientDetails) {
                $nameParts = array_filter([
                    strtoupper($patientDetails->lastname) ?? null,
                    strtoupper($patientDetails->firstname) ?? null,
                    strtoupper($patientDetails->middlename) ?? null
                ]);
                $empFullname = implode(' ', $nameParts);
            } else {
                $empFullname = 'NO ACTIVE EMPLOYEE SELECTED';
            }
            $emtype = $datePerioDetails?->employeeTypeCategory?->name ?? 'N/A';
            $emstat = $datePerioDetails?->category?->name ?? 'N/A';
            $startdate = $datePerioDetails->datefrom
                ? Carbon::parse($datePerioDetails->datefrom)->format('M d, Y') : 'N/A';
            $enddate = $datePerioDetails->dateto
                ? Carbon::parse($datePerioDetails->dateto)->format('M d, Y') : 'N/A';
            $details = [
                "EMP. FULLNAME: {$empFullname}",
                "DATE START: {$startdate}",
                "DATE END: {$enddate}",
                "EMP TYPE: {$emtype}",
                "EMP STATUS: {$emstat}",
            ];
            $formattedBadges = collect($details)
                ->map(fn($detail) => "
                    <span style='
                        padding: 0.25rem 0.625rem; 
                        font-size: 0.75rem; 
                        font-weight: 600; 
                        background-color: #ffffff; 
                        color: #374151; 
                        border-radius: 0.375rem; 
                        border: 1px solid #e5e7eb; 
                        box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05); 
                        white-space: nowrap;
                        font-family: system-ui, sans-serif;
                    '>{$detail}</span>
                ")
                ->implode(' ');
        }
        return $table
            ->header(fn() => blank(session('session_employee_id')) ? null : new HtmlString("
                        <div style='
                            padding: 1rem; 
                            margin: 1rem 1rem 0 1rem; 
                            border-left: 4px solid #d97706; 
                            background-color: rgba(254, 243, 199, 0.4); 
                            border-top-right-radius: 0.75rem; 
                            border-bottom-right-radius: 0.75rem; 
                            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
                        '>
                            <div style='
                                display: flex; 
                                flex-direction: column; 
                                gap: 0.75rem;
                            '>
                                <div style='display: flex; align-items: center; gap: 0.5rem;'>
                                    <span style='
                                        width: 0.5rem; 
                                        height: 0.5rem; 
                                        background-color: #f59e0b; 
                                        border-radius: 9999px;
                                    '></span>
                                    <h3 style='
                                        font-size: 1rem; 
                                        font-weight: 700; 
                                        color: #111827; 
                                        margin: 0;
                                        font-family: system-ui, sans-serif;
                                    '>
                                        DATE PERIOD CODE : <span style='font-family: monospace; color: #b45309;'>{$periodcode}</span>
                                    </h3>
                                </div>
                                <div style='
                                    display: flex; 
                                    flex-wrap: wrap; 
                                    align-items: center; 
                                    gap: 0.5rem;
                                '>
                                    {$formattedBadges}
                                </div>
                            </div>
                        </div>
                    "))
            ->recordUrl(null)
            ->extraAttributes([
                'style' => 'border: 2px solid #2d2380 !important; border-radius: 0.75rem;', // Deep Sapphire Blue
            ])
            ->query(function () {
                $sessionEmployeeId = session('session_employee_id');
                $sessionPeriodCode = session('session_periodcode');
                $user = Auth::user();
                if (! $user || ! $user->id) {
                    return Atlog::whereRaw('1 = 0');
                }
                if ($sessionEmployeeId) {
                    $datePerioDetails = DatePeriod::where('code', $sessionPeriodCode)
                        ->where('status', true)
                        ->first();
                    if ($datePerioDetails) {
                        $patientDetails = Employee::where('employeeid', session('session_employee_id'))->where('status', true)->first();
                        if ($patientDetails) {
                            $startdate = $datePerioDetails->datefrom
                                ? Carbon::parse($datePerioDetails->datefrom)->startOfDay() : null;
                            $enddate = $datePerioDetails->dateto
                                ? Carbon::parse($datePerioDetails->dateto)->endOfDay() : null;
                            return Atlog::query()
                                ->when($startdate, fn($q) => $q->where('recorded_at', '>=', $startdate))
                                ->when($enddate, fn($q) => $q->where('recorded_at', '<=', $enddate))
                                ->with('employee')
                                ->with('project')
                                ->where('project_code', $patientDetails->project_id)
                                ->where('user_id', $sessionEmployeeId);
                        }
                    } else {
                        return Atlog::query()
                            ->with('employee')
                            ->with('project')
                            ->where('user_id', $sessionEmployeeId);
                    }
                }
                return Atlog::query()
                    ->with('employee')
                    ->with('project');
            })
            ->columns([
                TextColumn::make('project.name')
                    ->hidden(fn() => filled(session('session_employee_id')))
                    ->label('Project'),
                TextColumn::make('employee.full_name')
                    ->hidden(fn() => filled(session('session_employee_id')))
                    ->label('Employee'),
                TextColumn::make('user_id')
                    ->label('ID')
                    ->searchable()
                    ->sortable(),
                // 2. Date & Time
                TextColumn::make('recorded_at')
                    ->dateTime('M d, Y h:i A')
                    ->label('Date & Time')
                    ->sortable(),
                // 5. Verification Method (Column 5 in your raw logs)
                TextColumn::make('status')
                    ->label('Att State')
                    ->badge()
                    ->color('info') // Cool blue badge style for hardware properties
                    ->icon(fn(int $state): string => match ($state) {
                        1 => 'heroicon-m-identification',    // Changed to actual fingerprint icon
                        2 => 'heroicon-m-credit-card',    // Changed to credit card for RFID badge
                        5 => 'heroicon-m-user-circle',    // Face scan icon
                        default => 'heroicon-m-key',      // Password/Code pin entry
                    })
                    ->formatStateUsing(fn(int $state): string => match ($state) {
                        1 => 'Finger',
                        2 => 'Card',
                        5 => 'Face',
                        default => 'Code',
                    })
                    ->sortable(),
                TextColumn::make('verification_mode')
                    ->label('Verify Type')
                    ->badge()
                    // 🎨 Using neutral gray since this is a technical hardware status code
                    ->color('gray')
                    ->icon('heroicon-m-squares-plus')
                    ->formatStateUsing(fn(int $state): string => match ($state) {
                        0 => 'Check-In',
                        1 => 'Check-Out',
                        2 => 'Break Out',
                        3 => 'Break In',
                        default => "Code ({$state})",
                    })
                    ->sortable(),
                // 6. Machine Terminal ID / Reserved (Column 6 in your raw logs)
                TextColumn::make('reserved')
                    ->label('Device ID')
                    ->fontFamily('mono'),
            ])
            ->filters([
                // 1 & 2. FILTER: Date Range (Handles both datefrom and dateto together)
                Filter::make('date_range')
                    ->form([
                        DatePicker::make('datefrom')
                            ->label('Date From'),
                        DatePicker::make('dateto')
                            ->label('Date To'),
                    ])
                    ->columns(2)
                    ->columnSpan(2)
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['datefrom'],
                                fn(Builder $query, $date): Builder => $query->whereDate('recorded_at', '>=', $date),
                            )
                            ->when(
                                $data['dateto'],
                                fn(Builder $query, $date): Builder => $query->whereDate('recorded_at', '<=', $date),
                            );
                    })
                    ->hidden(fn() => filled(session('session_employee_id'))),

                // 3. FILTER: Project
                SelectFilter::make('project_id')
                    ->label('Project')
                    ->relationship('project', 'name')
                    ->preload()
                    ->columnSpan(1)
                    ->placeholder('All Projects')
                    ->hidden(fn() => filled(session('session_employee_id'))),
                SelectFilter::make('employee_id')
                    ->label('Employee')
                    ->columnSpan(1)
                    ->relationship(
                        name: 'employee',
                        titleAttribute: 'lastname', // Fallback identifier
                        modifyQueryUsing: fn(Builder $query) => $query->orderBy('lastname')
                    )
                    ->searchable(['lastname', 'firstname', 'middlename', 'employeeid'])
                    ->getOptionLabelFromRecordUsing(function ($record) {
                        return "{$record->lastname}, {$record->firstname} {$record->middlename} ({$record->employeeid})";
                    })
                    ->placeholder('All Employees')
                    ->hidden(fn() => filled(session('session_employee_id'))),
            ], layout: FiltersLayout::AboveContent)
            ->filtersFormColumns(4)
            ->filtersFormWidth('full')
            ->actions([
                ActionGroup::make([
                    // ViewAction::make()
                    //     ->label('Details'),
                    EditAction::make()
                        ->after(function (Atlog $record) {
                            // Construct a detailed log descriptive of the modification
                            $activityLogMessage = "Updated Attendance Log for Employee ID: {$record->user_id} on " . Carbon::parse($record->recorded_at)->format('M d, Y h:i A') . ". New Verify Type: " . match ($record->verification_mode) {
                                0 => 'Check-In',
                                1 => 'Check-Out',
                                2 => 'Break Out',
                                3 => 'Break In',
                                default => "Code ({$record->verification_mode})",
                            };

                            ActivityLog::create([
                                'user_id'   => Auth::id() ?? 'System',
                                'activity'  => $activityLogMessage,
                                'module'    => 'Timekeeping Management',
                                'ipaddress' => request()->ip(),
                                'windows'   => request()->userAgent(),
                            ]);
                        })
                        ->label('Update'),
                    DeleteAction::make()
                        ->after(function (Atlog $record) {
                            // Log what was removed before the object completely leaves the tracking scope
                            $activityLogMessage = "Removed Attendance Log entry for Employee ID: {$record->user_id} recorded at " . Carbon::parse($record->recorded_at)->format('M d, Y h:i A');
                            ActivityLog::create([
                                'user_id'   => Auth::id() ?? 'System',
                                'activity'  => $activityLogMessage,
                                'module'    => 'Timekeeping Management',
                                'ipaddress' => request()->ip(),
                                'windows'   => request()->userAgent(),
                            ]);
                        })
                        ->label('Remove'),
                ])
                    ->label('Action')
                    ->icon('heroicon-m-chevron-down')
                    ->button()
                    ->color('success')
                    ->size('xs')
                    ->outlined(),
            ])
            // ->bulkActions([
            // ])
            ->defaultSort('recorded_at', 'desc');
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
            'index' => ListAtlogs::route('/'),
            // 'edit' => EditAtlog::route('/{record}/edit'),
        ];
    }
}
