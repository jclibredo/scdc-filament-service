<?php

namespace App\Filament\Resources\Employees\Pages;

use App\Filament\Resources\Employees\EmployeeResource;
use App\Models\ActivityLog;
use App\Models\Atlog;
use App\Models\Category;
use App\Models\Employee;
use App\Models\EmployeeProjectHistory;
use App\Models\FacialProfile;
use App\Models\GovDeduction;
use App\Models\Holiday;
use App\Models\OtherDeduction;
use App\Models\Project;
use App\Models\Skill;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Filament\Support\Enums\Size;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class ListEmployees extends ListRecords
{
    protected static string $resource = EmployeeResource::class;

    protected function getHeaderActions(): array
    {
        // Simple connectivity check for the upload button state
        $hasInternet = @fsockopen('scdc-web-app.com', 443, $errno, $errstr, 1);
        if ($hasInternet) {
            fclose($hasInternet);
            $isOnline = true;
        } else {
            $isOnline = false;
        }

        return [
            Action::make('pullAllFromCloud')
                ->label('Download All from Cloud')
                ->icon('heroicon-o-cloud-arrow-down')
                ->color('info')
                ->size('xs')
                ->outlined()
                ->requiresConfirmation()
                ->modalHeading('Download All Data from Cloud')
                ->modalDescription('This will download and merge all cloud data into your local database in one go. Proceed?')
                ->modalSubmitActionLabel('Yes, download all')
                ->visible(fn() => app()->environment('local') && $isOnline)
                ->action(function () {
                    try {
                        $token = env('CLOUD_API_TOKEN');
                        $pullUrl = 'https://scdc-web-app.com/api/fetch-all-cloud-data';
                        $response = Http::withToken($token)->timeout(60)->get($pullUrl);
                        if (!$response->successful()) {
                            $errorMsg = $response->json('message') ?? ('Cloud server error: ' . $response->status());
                            throw new \Exception($errorMsg);
                        }
                        $summary = [];
                        // 1. Sync Attendance Logs
                        $cloudLogs = $response->json('logs', []);
                        $logCount = 0;
                        foreach ($cloudLogs as $log) {
                            if (class_exists(Atlog::class)) {
                                Atlog::updateOrCreate(
                                    ['user_id' => $log['user_id'], 'recorded_at' => $log['recorded_at']],
                                    [
                                        'project_code'      => $log['project_code'] ?? 0,
                                        'status'            => $log['status'],
                                        'verification_mode' => $log['verification_mode'],
                                        'work_code'         => $log['work_code'] ?? 0,
                                        'reserved'          => $log['reserved'] ?? 0,
                                        'created_at'        => $log['created_at'] ?? now(),
                                        'updated_at'        => $log['updated_at'] ?? now(),
                                    ]
                                );
                                $logCount++;
                            }
                        }
                        if ($logCount > 0) $summary[] = "{$logCount} logs";

                        // 2. Sync Skills
                        $cloudSkills = $response->json('skills', []);
                        $skillCount = 0;
                        foreach ($cloudSkills as $cloudSkill) {
                            if (class_exists(Skill::class)) {
                                $title = trim($cloudSkill['title']);
                                $normalizedTitle = Str::upper(preg_replace('/\s+/', '', $title));

                                $localSkill = Skill::whereRaw("UPPER(REPLACE(title, ' ', '')) = ?", [$normalizedTitle])->first();

                                if (!$localSkill) {
                                    Skill::create([
                                        'title'      => Str::upper($title),
                                        'details'    => $cloudSkill['details'] ?? null,
                                        'status'     => $cloudSkill['status'] ?? true,
                                        'created_at' => $cloudSkill['created_at'] ?? now(),
                                        'updated_at' => $cloudSkill['updated_at'] ?? now(),
                                    ]);
                                } else {
                                    $localSkill->update([
                                        'details'    => $cloudSkill['details'] ?? null,
                                        'status'     => $cloudSkill['status'] ?? true,
                                        'updated_at' => $cloudSkill['updated_at'] ?? now(),
                                    ]);
                                }
                                $skillCount++;
                            }
                        }
                        if ($skillCount > 0) $summary[] = "{$skillCount} skills";

                        // 3. Sync Projects
                        $cloudProjects = $response->json('projects', []);
                        $projectCount = 0;
                        foreach ($cloudProjects as $cloudProject) {
                            if (class_exists(Project::class)) {
                                $code = trim($cloudProject['project_code']);
                                $normalizedCode = Str::upper(preg_replace('/\s+/', '', $code));

                                $localProject = Project::whereRaw("UPPER(REPLACE(project_code, ' ', '')) = ?", [$normalizedCode])->first();

                                if (!$localProject) {
                                    Project::create([
                                        'project_code' => Str::upper($code),
                                        'name'         => Str::upper($cloudProject['name'] ?? ''),
                                        'datecovered'  => Str::upper($cloudProject['datecovered'] ?? ''),
                                        'scope'        => Str::upper($cloudProject['scope'] ?? ''),
                                        'address'      => Str::upper($cloudProject['address'] ?? ''),
                                        'image'        => $cloudProject['image'] ?? null,
                                        'status'       => $cloudProject['status'] ?? true,
                                        'created_at'   => $cloudProject['created_at'] ?? now(),
                                        'updated_at'   => $cloudProject['updated_at'] ?? now(),
                                    ]);
                                } else {
                                    $localProject->update([
                                        'name'         => Str::upper($cloudProject['name'] ?? ''),
                                        'datecovered'  => Str::upper($cloudProject['datecovered'] ?? ''),
                                        'scope'        => Str::upper($cloudProject['scope'] ?? ''),
                                        'address'      => Str::upper($cloudProject['address'] ?? ''),
                                        'image'        => $cloudProject['image'] ?? null,
                                        'status'       => $cloudProject['status'] ?? true,
                                        'updated_at'   => $cloudProject['updated_at'] ?? now(),
                                    ]);
                                }
                                $projectCount++;
                            }
                        }
                        if ($projectCount > 0) $summary[] = "{$projectCount} projects";

                        // 4. Sync Categories (🟢 Added)
                        $cloudCategories = $response->json('categories', []);
                        $catCount = 0;
                        foreach ($cloudCategories as $cloudCategory) {
                            if (class_exists(Category::class)) {
                                $catKey = trim($cloudCategory['cat'] ?? $cloudCategory['name']);
                                $normalizedCat = Str::upper(preg_replace('/\s+/', '', $catKey));

                                $localCategory = Category::whereRaw("UPPER(REPLACE(COALESCE(cat, name), ' ', '')) = ?", [$normalizedCat])->first();

                                if (!$localCategory) {
                                    Category::create([
                                        'cat'         => $cloudCategory['cat'] ?? null,
                                        'name'        => Str::upper($cloudCategory['name'] ?? ''),
                                        'description' => $cloudCategory['description'] ?? null,
                                        'status'      => $cloudCategory['status'] ?? true,
                                        'created_at'  => $cloudCategory['created_at'] ?? now(),
                                        'updated_at'  => $cloudCategory['updated_at'] ?? now(),
                                    ]);
                                } else {
                                    $localCategory->update([
                                        'name'        => Str::upper($cloudCategory['name'] ?? ''),
                                        'description' => $cloudCategory['description'] ?? null,
                                        'status'      => $cloudCategory['status'] ?? true,
                                        'updated_at'  => $cloudCategory['updated_at'] ?? now(),
                                    ]);
                                }
                                $catCount++;
                            }
                        }
                        if ($catCount > 0) $summary[] = "{$catCount} categories";

                        // 5. Sync Gov Deductions (🟢 Added)
                        $cloudGov = $response->json('gov_deductions', []);
                        $govCount = 0;
                        foreach ($cloudGov as $gov) {
                            if (class_exists(GovDeduction::class)) {
                                $title = trim($gov['title']);
                                $normalizedTitle = Str::upper(preg_replace('/\s+/', '', $title));

                                $localGov = GovDeduction::whereRaw("UPPER(REPLACE(title, ' ', '')) = ?", [$normalizedTitle])->first();

                                if (!$localGov) {
                                    GovDeduction::create([
                                        'title'        => Str::upper($title),
                                        'date_started' => $gov['date_started'] ?? null,
                                        'date_ended'   => $gov['date_ended'] ?? null,
                                        'amount'       => $gov['amount'] ?? 0,
                                        'status'       => $gov['status'] ?? true,
                                        'created_at'   => $gov['created_at'] ?? now(),
                                        'updated_at'   => $gov['updated_at'] ?? now(),
                                    ]);
                                } else {
                                    $localGov->update([
                                        'date_started' => $gov['date_started'] ?? null,
                                        'date_ended'   => $gov['date_ended'] ?? null,
                                        'amount'       => $gov['amount'] ?? 0,
                                        'status'       => $gov['status'] ?? true,
                                        'updated_at'   => $gov['updated_at'] ?? now(),
                                    ]);
                                }
                                $govCount++;
                            }
                        }
                        if ($govCount > 0) $summary[] = "{$govCount} gov deductions";

                        // 6. Sync Holidays (🟢 Added)
                        $cloudHolidays = $response->json('holidays', []);
                        $holidayCount = 0;
                        foreach ($cloudHolidays as $holiday) {
                            if (class_exists(Holiday::class)) {
                                $type = trim($holiday['type']);
                                $normalizedType = Str::upper(preg_replace('/\s+/', '', $type));

                                $localHoliday = Holiday::whereRaw("UPPER(REPLACE(type, ' ', '')) = ?", [$normalizedType])->first();

                                if (!$localHoliday) {
                                    Holiday::create([
                                        'type'       => Str::upper($type),
                                        'percentage' => $holiday['percentage'] ?? 0,
                                        'details'    => $holiday['details'] ?? null,
                                        'status'     => $holiday['status'] ?? true,
                                        'created_at' => $holiday['created_at'] ?? now(),
                                        'updated_at' => $holiday['updated_at'] ?? now(),
                                    ]);
                                } else {
                                    $localHoliday->update([
                                        'percentage' => $holiday['percentage'] ?? 0,
                                        'details'    => $holiday['details'] ?? null,
                                        'status'     => $holiday['status'] ?? true,
                                        'updated_at' => $holiday['updated_at'] ?? now(),
                                    ]);
                                }
                                $holidayCount++;
                            }
                        }
                        if ($holidayCount > 0) $summary[] = "{$holidayCount} holidays";

                        // 7. Sync Other Deductions (🟢 Added)
                        $cloudOther = $response->json('other_deductions', []);
                        $otherCount = 0;
                        foreach ($cloudOther as $other) {
                            if (class_exists(OtherDeduction::class)) {
                                $title = trim($other['title']);
                                $normalizedTitle = Str::upper(preg_replace('/\s+/', '', $title));

                                $localOther = OtherDeduction::whereRaw("UPPER(REPLACE(title, ' ', '')) = ?", [$normalizedTitle])->first();

                                if (!$localOther) {
                                    OtherDeduction::create([
                                        'title'       => Str::upper($title),
                                        'description' => $other['description'] ?? null,
                                        'status'      => $other['status'] ?? true,
                                        'created_at'  => $other['created_at'] ?? now(),
                                        'updated_at'  => $other['updated_at'] ?? now(),
                                    ]);
                                } else {
                                    $localOther->update([
                                        'description' => $other['description'] ?? null,
                                        'status'      => $other['status'] ?? true,
                                        'updated_at'  => $other['updated_at'] ?? now(),
                                    ]);
                                }
                                $otherCount++;
                            }
                        }
                        if ($otherCount > 0) $summary[] = "{$otherCount} other deductions";

                        $bodyMessage = empty($summary) ? 'No data found to download.' : 'Successfully downloaded: ' . implode(', ', $summary) . '.';

                        Notification::make()
                            ->title('Download successful!')
                            ->body($bodyMessage)
                            ->success()
                            ->send();
                    } catch (\Exception $e) {
                        Notification::make()->title('Download failed: ' . $e->getMessage())->danger()->send();
                    }
                }),

            Action::make('syncAllToCloud')
                ->label('Sync All to Cloud')
                ->icon($isOnline ? 'heroicon-o-cloud-arrow-up' : 'heroicon-o-x-mark')
                ->color($isOnline ? 'success' : 'danger')
                ->size('xs')
                ->outlined()
                ->requiresConfirmation()
                ->modalHeading('Synchronize All Data to Cloud')
                ->modalDescription('This will push all local Attendance Logs, Skills, and Projects to the cloud database in one go. Proceed?')
                ->modalSubmitActionLabel('Yes, sync all')
                ->visible(fn() => app()->environment('local'))
                ->action(function () {
                    try {
                        // Gather data from all three models safely
                        $payload = [
                            'logs'     => class_exists(Atlog::class) ? Atlog::all()->toArray() : [],
                            'skills'   => class_exists(Skill::class) ? Skill::all()->toArray() : [],
                            'projects' => class_exists(Project::class) ? Project::all()->toArray() : [],
                            'categories' => class_exists(Category::class) ? Category::all()->toArray() : [],
                            'gov_deductions'   => class_exists(GovDeduction::class) ? GovDeduction::all()->toArray() : [], // 🟢 Added
                            'holidays'         => class_exists(Holiday::class) ? Holiday::all()->toArray() : [], // 🟢 Added
                            'other_deductions' => class_exists(OtherDeduction::class) ? OtherDeduction::all()->toArray() : [], // 🟢 Added
                        ];

                        $pushUrl = str_replace(['sync-attendance', 'sync-projects', 'sync-skills'], 'sync-all', env('CLOUD_API_URL', 'https://scdc-web-app.com/api/sync-all'));

                        $response = Http::withToken(env('CLOUD_API_TOKEN'))
                            ->timeout(60) // Slightly longer timeout since it's sending everything
                            ->post($pushUrl, $payload);

                        if ($response->successful()) {
                            $successMessage = $response->json('message') ?? 'All data synchronized successfully!';
                            Notification::make()->title($successMessage)->success()->send();
                        } else {
                            $errorMsg = $response->json('message') ?? ('Cloud server error: ' . $response->status());
                            throw new \Exception($errorMsg);
                        }
                    } catch (\Exception $e) {
                        Notification::make()->title('Sync failed: ' . $e->getMessage())->danger()->send();
                    }
                }),
            // 🗑️ NEW: Clear All Facial Profiles Table Action
            Action::make('clearAllFacialProfiles')
                ->label('Clear All Facial Profiles')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Clear All Facial Profiles')
                ->modalDescription('Are you sure you want to delete ALL facial profiles from the database? This action is permanent and will require all employees to re-register their faces.')
                ->modalSubmitActionLabel('Yes, delete all profiles')
                ->action(function () {
                    // Truncate or delete all entries in the FacialProfile table
                    $count = FacialProfile::count();
                    FacialProfile::truncate(); // Or FacialProfile::query()->delete(); if foreign key constraints require delete()

                    ActivityLog::create([
                        'user_id'   => Auth::id() ?? 'System',
                        'activity'  => "Cleared all facial profiles ({$count} records removed) from the database",
                        'module'    => 'Employee Management',
                        'ipaddress' => request()->ip(),
                        'windows'   => request()->userAgent(),
                    ]);

                    Notification::make()
                        ->title('All facial profiles have been successfully cleared.')
                        ->success()
                        ->send();
                }),
            Action::make('openVoiceToText')
                ->label('Voice to Text')
                ->icon('heroicon-o-microphone')
                ->color('warning')
                ->url(route('voice.to.text.show'))
                ->openUrlInNewTab(),

            Action::make('openRegisterFace')
                ->label('Register Employee Face')
                ->icon('heroicon-o-user-plus')
                ->color('info')
                ->url(route('face.register.show'))
                ->openUrlInNewTab(),

            Action::make('openKiosk')
                ->label('Open Face Recognition')
                ->icon('heroicon-o-camera')
                ->color('success')
                ->url(route('face.verify'))  // Use the named route for face recognition
                ->openUrlInNewTab(),

            Action::make('importEmployeesCsvFormat')
                ->label('Import .CSV File')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('success')
                ->size(Size::ExtraSmall)
                ->outlined()
                ->form([
                    FileUpload::make('dat_file')
                        ->label('Select Employee CSV File')
                        ->required()
                        ->disk('public')
                        ->directory('imports')
                        ->storeFiles(false)
                        ->acceptedFileTypes([
                            'text/csv',
                            'text/plain',
                            'application/csv',
                            'text/comma-separated-values',
                        ])
                        ->maxSize(10240) // 10 MB
                        ->helperText('Upload a CSV file matching the structure from the Export Template action.'),
                ])
                ->action(function (array $data) {
                    $fileInput = $data['dat_file'];

                    if (is_array($fileInput)) {
                        $fileInput = reset($fileInput);
                    }

                    $filePath = null;

                    if ($fileInput instanceof TemporaryUploadedFile) {
                        $filePath = $fileInput->getRealPath();
                    } elseif (is_string($fileInput)) {
                        $possiblePaths = [
                            $fileInput,
                            storage_path('app/livewire-tmp/' . basename($fileInput)),
                            storage_path('app/public/' . $fileInput),
                            storage_path('app/' . $fileInput),
                            Storage::disk('public')->path($fileInput),
                        ];

                        foreach ($possiblePaths as $path) {
                            if (file_exists($path)) {
                                $filePath = $path;
                                break;
                            }
                        }
                    }

                    if (! $filePath || ! file_exists($filePath)) {
                        Notification::make()
                            ->title('File not found!')
                            ->body('Unable to locate or open the uploaded file.')
                            ->danger()
                            ->send();

                        return;
                    }

                    $fileStream = fopen($filePath, 'r');

                    if (! $fileStream) {
                        Notification::make()
                            ->title('Unable to read file')
                            ->body('Failed to open file stream.')
                            ->danger()
                            ->send();

                        return;
                    }

                    $rawHeader = fgetcsv($fileStream);

                    if (! $rawHeader) {
                        fclose($fileStream);
                        Notification::make()
                            ->title('Invalid CSV File')
                            ->body('The uploaded CSV file is empty or corrupt.')
                            ->danger()
                            ->send();

                        return;
                    }

                    // Sanitize header keys
                    $header = array_map(function ($h) {
                        $h = preg_replace('/[\x00-\x1F\x7F\xEF\xBB\xBF]/s', '', $h);

                        return strtolower(trim($h));
                    }, $rawHeader);

                    $importedCount = 0;
                    $skippedCount  = 0;
                    $failedRows    = [];

                    while (($row = fgetcsv($fileStream)) !== false) {
                        if (empty(array_filter($row))) {
                            continue;
                        }

                        // Ensure row column count matches header column count
                        if (count($row) < count($header)) {
                            $row = array_pad($row, count($header), '');
                        }

                        $rowData = array_combine($header, array_slice($row, 0, count($header)));

                        // Raw inputs
                        $employeeId = trim($rowData['employeeid'] ?? '');
                        $firstName  = trim($rowData['firstname'] ?? '');
                        $lastName   = trim($rowData['lastname'] ?? '');

                        // === PRE-CHECK: Check if Record Already Exists ===
                        // Normalize CSV values (UPPERCASE & REMOVE ALL SPACES)
                        $normalizedCsvId        = Str::upper(preg_replace('/\s+/', '', $employeeId));
                        $normalizedCsvFirstName = Str::upper(preg_replace('/\s+/', '', $firstName));
                        $normalizedCsvLastName  = Str::upper(preg_replace('/\s+/', '', $lastName));

                        $isDuplicate = false;

                        if (!empty($normalizedCsvId) && ! empty($normalizedCsvFirstName) && ! empty($normalizedCsvLastName)) {
                            $isDuplicate = Employee::whereRaw("UPPER(REPLACE(employeeid, ' ', '')) = ?", [$normalizedCsvId])
                                ->whereRaw("UPPER(REPLACE(firstname, ' ', '')) = ?", [$normalizedCsvFirstName])
                                ->whereRaw("UPPER(REPLACE(lastname, ' ', '')) = ?", [$normalizedCsvLastName])
                                ->exists();
                        }

                        // If it already exists in DB, simply skip without adding to the error download list
                        if ($isDuplicate) {
                            $skippedCount++;
                            continue;
                        }

                        // Collect validation errors for current row
                        $rowErrors = [];

                        // Other fields
                        $email     = strtolower(trim($rowData['email'] ?? ''));
                        $mobile    = trim($rowData['mobile'] ?? '');
                        $birthdate = trim($rowData['birthdate'] ?? '');
                        $sex       = trim($rowData['sex'] ?? '');
                        $address   = Str::upper(trim($rowData['address'] ?? ''));
                        $datehired = trim($rowData['datehired'] ?? '');
                        $empStatus = trim($rowData['empstatus'] ?? '');
                        $empType   = trim($rowData['employeetype'] ?? '');
                        $skill     = trim($rowData['skill'] ?? '');
                        $project   = trim($rowData['project'] ?? '');

                        // Convert and validate date strings
                        $parsedBirthdate = ! empty($birthdate) ? date('Y-m-d', strtotime($birthdate)) : null;
                        $parsedDatehired = ! empty($datehired) ? date('Y-m-d', strtotime($datehired)) : null;

                        // Perform regular validations
                        $validator = Validator::make([
                            'employeeid'   => Str::upper($employeeId),
                            'firstname'    => Str::upper($firstName),
                            'lastname'     => Str::upper($lastName),
                            'email'        => $email,
                            'mobile'       => $mobile,
                            'birthdate'    => $parsedBirthdate,
                            'sex'          => $sex,
                            'address'      => Str::upper($address),
                            'datehired'    => $parsedDatehired,
                            'empstatus'    => $empStatus,
                            'employeetype' => $empType,
                            'skill'        => $skill,
                            'project'      => $project,
                        ], [
                            'employeeid'   => ['required', 'unique:employees,employeeid'],
                            'firstname'    => ['required'],
                            'lastname'     => ['required'],
                            'email'        => ['required', 'email', 'unique:employees,email'],
                            'mobile'       => ['required'],
                            'birthdate'    => ['required', 'date'],
                            'sex'          => ['required'],
                            'address'      => ['required'],
                            'datehired'    => ['required', 'date'],
                            'empstatus'    => ['required'],
                            'employeetype' => ['required'],
                            'skill'        => ['required'],
                            'project'      => ['required'],
                        ]);

                        if ($validator->fails()) {
                            foreach ($validator->errors()->all() as $err) {
                                $rowErrors[] = $err;
                            }
                        }

                        // Resolve Foreign Keys
                        $empStatusId = null;
                        if (! empty($empStatus)) {
                            $empStatusId = is_numeric($empStatus) ? $empStatus : Category::where('cat', 'EMPLOYEE_STATUS')
                                ->where('name', 'LIKE', $empStatus)
                                ->value('id');

                            if (! $empStatusId) {
                                $rowErrors[] = "Invalid Employee Status: '{$empStatus}'";
                            }
                        }

                        $empTypeId = null;
                        if (! empty($empType)) {
                            $empTypeId = is_numeric($empType) ? $empType : Category::where('cat', 'EMPLOYEE_TYPE')
                                ->where('name', 'LIKE', $empType)
                                ->value('id');

                            if (! $empTypeId) {
                                $rowErrors[] = "Invalid Employee Type: '{$empType}'";
                            }
                        }

                        $skillId = null;
                        if (! empty($skill)) {
                            $skillId = is_numeric($skill) ? $skill : Skill::where('title', 'LIKE', $skill)->value('id');

                            if (! $skillId) {
                                $rowErrors[] = "Invalid Skill: '{$skill}'";
                            }
                        }

                        $projectId = null;
                        if (! empty($project)) {
                            $projectId = is_numeric($project) ? $project : Project::where('name', 'LIKE', $project)->value('project_code');

                            if (! $projectId) {
                                $rowErrors[] = "Invalid Project: '{$project}'";
                            }
                        }

                        $partnerVal = trim($rowData['partners'] ?? '');
                        $partnerId  = is_numeric($partnerVal) ? $partnerVal : Category::where('cat', 'SUBCON')
                            ->where('name', 'LIKE', $partnerVal)
                            ->value('id');

                        $dateSeperated = ! empty(trim($rowData['dateseperated'] ?? '')) ? date('Y-m-d', strtotime(trim($rowData['dateseperated']))) : null;

                        // If there are real validation or FK errors, add row to download file
                        if (! empty($rowErrors)) {
                            $failedRow   = $row;
                            $failedRow[] = implode(' | ', $rowErrors);
                            $failedRows[] = $failedRow;

                            continue;
                        }

                        // Create Employee Record
                        Employee::create([
                            'employeeid'    => Str::upper($employeeId),
                            'firstname'     => Str::upper($firstName),
                            'middlename'    => Str::upper(trim($rowData['middlename'] ?? '')),
                            'lastname'      => Str::upper($lastName),
                            'status'        => filter_var($rowData['status'] ?? true, FILTER_VALIDATE_BOOLEAN),
                            'mobile'        => $mobile,
                            'empstatus'     => $empStatusId,
                            'email'         => $email,
                            'birthdate'     => $parsedBirthdate,
                            'sex'           => ucfirst(strtolower($sex)),
                            'address'       => $address,
                            'datehired'     => $parsedDatehired,
                            'dateseperated' => $dateSeperated,
                            'employeetype'  => $empTypeId,
                            'partners'      => $partnerId,
                            'skill_id'      => $skillId,
                            'project_id'    => $projectId,
                        ]);

                        // Create Employee Project History
                        if ($projectId) {
                            EmployeeProjectHistory::create([
                                'employeeid'      => Str::upper($employeeId),
                                'projectid'       => $projectId,
                                'employeetype'    => $empTypeId,
                                'employee_status' => $empStatusId,
                                'datestarted'     => $parsedDatehired,
                                'dateended'       => $dateSeperated,
                                'status'          => is_null($dateSeperated),
                            ]);
                        }

                        $importedCount++;
                    }

                    fclose($fileStream);

                    // Cleanup temporary stored file
                    if (is_string($fileInput) && Storage::disk('public')->exists($fileInput)) {
                        Storage::disk('public')->delete($fileInput);
                    }

                    // Log User Activity
                    ActivityLog::create([
                        'user_id'   => Auth::id() ?? 'System',
                        'activity'  => "Imported {$importedCount} employees, skipped {$skippedCount} existing, " . count($failedRows) . " failed validation",
                        'module'    => 'Employee Management',
                        'ipaddress' => request()->ip(),
                        'windows'   => request()->userAgent(),
                    ]);

                    // Trigger download ONLY for rows that had validation/FK errors
                    if (count($failedRows) > 0) {
                        $failedHeader = array_merge($rawHeader, ['Errors to fix']);

                        $callback = function () use ($failedHeader, $failedRows) {
                            $output = fopen('php://output', 'w');
                            fputcsv($output, $failedHeader);

                            foreach ($failedRows as $failedRow) {
                                fputcsv($output, $failedRow);
                            }

                            fclose($output);
                        };

                        Notification::make()
                            ->title('Import Completed with Validation Errors')
                            ->body("Imported {$importedCount} records. Skipped {$skippedCount} existing records. " . count($failedRows) . " rows failed validation and will download automatically.")
                            ->warning()
                            ->send();

                        return response()->streamDownload(
                            $callback,
                            'employee_import_errors_' . now()->format('Y_m_d_His') . '.csv',
                            ['Content-Type' => 'text/csv']
                        );
                    }

                    Notification::make()
                        ->title('CSV Import Complete')
                        ->body("Successfully imported {$importedCount} new records. Skipped {$skippedCount} existing records.")
                        ->success()
                        ->send();
                }),

            // Action::make('importEmployeesCsvFormat')
            //     ->label('Import .CSV File')
            //     ->icon('heroicon-o-arrow-up-tray')
            //     ->color('success')
            //     ->size(Size::ExtraSmall)
            //     ->outlined()
            //     ->form([
            //         FileUpload::make('dat_file')
            //             ->label('Select Employee CSV File')
            //             ->required()
            //             ->disk('public')
            //             ->directory('imports')
            //             ->storeFiles(false) // Prevents automatic premature moving before reading
            //             ->acceptedFileTypes([
            //                 'text/csv',
            //                 'text/plain',
            //                 'application/csv',
            //                 'text/comma-separated-values',
            //             ])
            //             ->maxSize(10240) // 10 MB
            //             ->helperText('Upload a CSV file matching the structure from the Export Template action.'),
            //     ])
            //     ->action(function (array $data) {
            //         $fileInput = $data['dat_file'];

            //         // Extract single item if array
            //         if (is_array($fileInput)) {
            //             $fileInput = reset($fileInput);
            //         }

            //         $filePath = null;

            //         // 1. If it's a Livewire TemporaryUploadedFile instance
            //         if ($fileInput instanceof TemporaryUploadedFile) {
            //             $filePath = $fileInput->getRealPath();
            //         }
            //         // 2. If it's a string path
            //         elseif (is_string($fileInput)) {
            //             $possiblePaths = [
            //                 $fileInput, // Direct path
            //                 storage_path('app/livewire-tmp/' . basename($fileInput)),
            //                 storage_path('app/public/' . $fileInput),
            //                 storage_path('app/' . $fileInput),
            //                 Storage::disk('public')->path($fileInput),
            //             ];

            //             foreach ($possiblePaths as $path) {
            //                 if (file_exists($path)) {
            //                     $filePath = $path;
            //                     break;
            //                 }
            //             }
            //         }

            //         if (! $filePath || ! file_exists($filePath)) {
            //             Notification::make()
            //                 ->title('File not found!')
            //                 ->body('Unable to locate or open the uploaded file.')
            //                 ->danger()
            //                 ->send();

            //             return;
            //         }

            //         // Open stream directly from physical path
            //         $fileStream = fopen($filePath, 'r');

            //         if (! $fileStream) {
            //             Notification::make()
            //                 ->title('Unable to read file')
            //                 ->body('Failed to open file stream.')
            //                 ->danger()
            //                 ->send();

            //             return;
            //         }

            //         // Parse CSV Header
            //         $header = fgetcsv($fileStream);

            //         if (! $header) {
            //             fclose($fileStream);
            //             Notification::make()
            //                 ->title('Invalid CSV File')
            //                 ->body('The uploaded CSV file is empty or corrupt.')
            //                 ->danger()
            //                 ->send();

            //             return;
            //         }

            //         // Sanitize header row (strip UTF-8 BOM, spaces, lowercase)
            //         $header = array_map(function ($h) {
            //             $h = preg_replace('/[\x00-\x1F\x7F\xEF\xBB\xBF]/s', '', $h);

            //             return strtolower(trim($h));
            //         }, $header);

            //         $importedCount = 0;
            //         $skippedCount = 0;

            //         while (($row = fgetcsv($fileStream)) !== false) {
            //             if (empty(array_filter($row))) {
            //                 continue;
            //             }

            //             $rowData = array_combine($header, $row);

            //             $employeeId = Str::upper(trim($rowData['employeeid'] ?? ''));
            //             $firstName  = Str::upper(trim($rowData['firstname'] ?? ''));
            //             $lastName   = Str::upper(trim($rowData['lastname'] ?? ''));

            //             if (empty($employeeId) || empty($firstName) || empty($lastName)) {
            //                 continue;
            //             }

            //             // Check existing record
            //             $exists = Employee::where('employeeid', $employeeId)
            //                 ->where('lastname', $lastName)
            //                 ->where('firstname', $firstName)
            //                 ->exists();

            //             if ($exists) {
            //                 $skippedCount++;
            //                 continue;
            //             }

            //             // Resolve Category IDs
            //             $empStatusVal = trim($rowData['empstatus'] ?? '');
            //             $empStatusId  = is_numeric($empStatusVal) ? $empStatusVal : Category::where('cat', 'EMPLOYEE_STATUS')
            //                 ->where('name', 'LIKE', $empStatusVal)
            //                 ->value('id');

            //             $empTypeVal = trim($rowData['employeetype'] ?? '');
            //             $empTypeId  = is_numeric($empTypeVal) ? $empTypeVal : Category::where('cat', 'EMPLOYEE_TYPE')
            //                 ->where('name', 'LIKE', $empTypeVal)
            //                 ->value('id');

            //             $partnerVal = trim($rowData['partners'] ?? '');
            //             $partnerId  = is_numeric($partnerVal) ? $partnerVal : Category::where('cat', 'SUBCON')
            //                 ->where('name', 'LIKE', $partnerVal)
            //                 ->value('id');

            //             // Resolve Skill ID
            //             $skillVal = trim($rowData['skill'] ?? '');
            //             $skillId  = is_numeric($skillVal) ? $skillVal : Skill::where('title', 'LIKE', $skillVal)->value('id');

            //             // Resolve Project ID
            //             $projectVal = trim($rowData['project'] ?? '');
            //             $projectId  = is_numeric($projectVal) ? $projectVal : Project::where('name', 'LIKE', $projectVal)->value('project_code');

            //             // Format Dates
            //             $birthDate     = ! empty(trim($rowData['birthdate'] ?? '')) ? date('Y-m-d', strtotime(trim($rowData['birthdate']))) : null;
            //             $dateHired     = ! empty(trim($rowData['datehired'] ?? '')) ? date('Y-m-d', strtotime(trim($rowData['datehired']))) : null;
            //             $dateSeperated = ! empty(trim($rowData['dateseperated'] ?? '')) ? date('Y-m-d', strtotime(trim($rowData['dateseperated']))) : null;

            //             // Create Employee Record
            //             $record = Employee::create([
            //                 'employeeid'    => $employeeId,
            //                 'firstname'     => $firstName,
            //                 'middlename'    => Str::upper(trim($rowData['middlename'] ?? '')),
            //                 'lastname'      => $lastName,
            //                 'status'        => filter_var($rowData['status'] ?? true, FILTER_VALIDATE_BOOLEAN),
            //                 'mobile'        => trim($rowData['mobile'] ?? ''),
            //                 'empstatus'     => $empStatusId,
            //                 'email'         => strtolower(trim($rowData['email'] ?? '')),
            //                 'birthdate'     => $birthDate,
            //                 'sex'           => ucfirst(strtolower(trim($rowData['sex'] ?? 'Male'))),
            //                 'address'       => Str::upper(trim($rowData['address'] ?? '')),
            //                 'datehired'     => $dateHired,
            //                 'dateseperated' => $dateSeperated,
            //                 'employeetype'  => $empTypeId,
            //                 'partners'      => $partnerId,
            //                 'skill_id'      => $skillId,
            //                 'project_id'    => $projectId,
            //             ]);

            //             // Create Employee Project History
            //             if ($projectId) {
            //                 EmployeeProjectHistory::create([
            //                     'employeeid'      => $employeeId,
            //                     'projectid'       => $projectId,
            //                     'employeetype'    => $empTypeId,
            //                     'employee_status' => $empStatusId,
            //                     'datestarted'     => $dateHired,
            //                     'dateended'       => $dateSeperated,
            //                     'status'          => is_null($dateSeperated),
            //                 ]);
            //             }

            //             $importedCount++;
            //         }

            //         fclose($fileStream);

            //         ActivityLog::create([
            //             'user_id'   => Auth::id() ?? 'System',
            //             'activity'  => "Imported {$importedCount} employees, skipped {$skippedCount} existing records from CSV",
            //             'module'    => 'Employee Management',
            //             'ipaddress' => request()->ip(),
            //             'windows'   => request()->userAgent(),
            //         ]);

            //         Notification::make()
            //             ->title('CSV Import Complete')
            //             ->body("Imported {$importedCount} new records. Skipped {$skippedCount} existing records.")
            //             ->success()
            //             ->send();
            //     }),
            Action::make('exportEmployeesCsvFormat')
                ->label('Export CSV Template')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('primary')
                ->size('xs')
                ->outlined()
                ->url(route('employees.export.csv'))
                ->openUrlInNewTab(),

            CreateAction::make()
                ->label('New Employee')
                ->color('success')
                ->size('xs')
                ->outlined()
                ->after(function (Employee $record) {
                    EmployeeProjectHistory::create([
                        'employeeid'      => $record->employeeid,
                        'projectid'       => $record->project_id,
                        'employeetype'    => $record->employeetype,
                        'employee_status' => $record->empstatus,
                        'datestarted'     => $record->datehired,
                        'dateended'       => $record->dateseperated,
                        'status'          => $record->dateseperated === null ? true : false,
                    ]);


                    ActivityLog::create([
                        'user_id'   => Auth::id() ?? 'System',
                        'activity'  => "Registered new employee profile: {$record->lastname}, {$record->firstname} {$record->middlename} (Assigned ID: {$record->employeeid})",
                        'module'    => 'Employee Management',
                        'ipaddress' => request()->ip(),
                        'windows'   => request()->userAgent(),
                    ]);
                })
                ->icon('heroicon-m-plus-circle'),
        ];
    }
}
