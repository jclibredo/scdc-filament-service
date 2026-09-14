<?php

namespace App\Filament\Resources\Employees\Pages;

use App\Filament\Resources\Employees\EmployeeResource;
use App\Models\ActivityLog;
use App\Models\Adjustment;
use App\Models\Atlog;
use App\Models\Category;
use App\Models\DatePeriod;
use App\Models\Earnings;
use App\Models\Employee;
use App\Models\EmployeeProjectHistory;
use App\Models\EmpSchedule;
use App\Models\FacialProfile;
use App\Models\GovDeduction;
use App\Models\GovDeductionLog;
use App\Models\Holiday;
use App\Models\IncentiveBonus;
use App\Models\OtherDeduction;
use App\Models\OtherDeductionLog;
use App\Models\PayrollReport;
use App\Models\PayrollSummaryReport;
use App\Models\Project;
use App\Models\Skill;
use App\Models\ThirteenthMonth;
use App\Models\UserPermission;
use App\Models\YearEndReport;
use Carbon\Carbon;
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
                                $localSkill = Skill::find($cloudSkill['id']);
                                if (!$localSkill) {
                                    $localSkill = new Skill();
                                    $localSkill->id = $cloudSkill['id'];
                                }
                                $localSkill->fill([
                                    'title'      => Str::upper(trim($cloudSkill['title'])),
                                    'details'    => $cloudSkill['details'] ?? null,
                                    'status'     => $cloudSkill['status'] ?? true,
                                    'created_at' => $cloudSkill['created_at'] ?? now(),
                                    'updated_at' => $cloudSkill['updated_at'] ?? now(),
                                ]);
                                $localSkill->save();
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

                        // 4. Sync Categories
                        $cloudCategories = $response->json('categories', []);
                        $catCount = 0;
                        foreach ($cloudCategories as $cloudCategory) {
                            if (class_exists(Category::class)) {
                                $localCategory = \App\Models\Category::find($cloudCategory['id']);
                                if (!$localCategory) {
                                    $localCategory = new \App\Models\Category();
                                    $localCategory->id = $cloudCategory['id'];
                                }
                                $localCategory->fill([
                                    'cat'         => $cloudCategory['cat'] ?? null,
                                    'name'        => Str::upper($cloudCategory['name'] ?? ''),
                                    'description' => $cloudCategory['description'] ?? null,
                                    'status'      => $cloudCategory['status'] ?? true,
                                    'created_at'  => $cloudCategory['created_at'] ?? now(),
                                    'updated_at'  => $cloudCategory['updated_at'] ?? now(),
                                ]);
                                $localCategory->save();
                                $catCount++;
                            }
                        }
                        if ($catCount > 0) $summary[] = "{$catCount} categories";

                        // 5. Sync Gov Deductions
                        $cloudGov = $response->json('gov_deductions', []);
                        $govCount = 0;
                        foreach ($cloudGov as $gov) {
                            if (class_exists(GovDeduction::class)) {
                                $localGov = GovDeduction::find($gov['id']);
                                if (!$localGov) {
                                    $localGov = new GovDeduction();
                                    $localGov->id = $gov['id'];
                                }
                                $localGov->fill([
                                    'title'        => Str::upper(trim($gov['title'])),
                                    'date_started' => !empty($gov['date_started']) ? Carbon::parse($gov['date_started'])->format('Y-m-d') : null,
                                    'date_ended'   => !empty($gov['date_ended']) ? Carbon::parse($gov['date_ended'])->format('Y-m-d') : null,
                                    'amount'       => $gov['amount'] ?? 0,
                                    'status'       => $gov['status'] ?? true,
                                    'created_at'   => $gov['created_at'] ?? now(),
                                    'updated_at'   => $gov['updated_at'] ?? now(),
                                ]);
                                $localGov->save();
                                $govCount++;
                            }
                        }
                        if ($govCount > 0) $summary[] = "{$govCount} gov deductions";

                        // 6. Sync Holidays
                        $cloudHolidays = $response->json('holidays', []);
                        $holidayCount = 0;
                        foreach ($cloudHolidays as $holiday) {
                            if (class_exists(Holiday::class)) {
                                $localHoliday = Holiday::find($holiday['id']);
                                if (!$localHoliday) {
                                    $localHoliday = new Holiday();
                                    $localHoliday->id = $holiday['id'];
                                }
                                $localHoliday->fill([
                                    'type'       => Str::upper(trim($holiday['type'])),
                                    'percentage' => $holiday['percentage'] ?? 0,
                                    'details'    => $holiday['details'] ?? null,
                                    'status'     => $holiday['status'] ?? true,
                                    'created_at' => $holiday['created_at'] ?? now(),
                                    'updated_at' => $holiday['updated_at'] ?? now(),
                                ]);
                                $localHoliday->save();
                                $holidayCount++;
                            }
                        }
                        if ($holidayCount > 0) $summary[] = "{$holidayCount} holidays";

                        // 7. Sync Other Deductions
                        $cloudOther = $response->json('other_deductions', []);
                        $otherCount = 0;
                        foreach ($cloudOther as $other) {
                            if (class_exists(OtherDeduction::class)) {
                                $localOther = OtherDeduction::find($other['id']);
                                if (!$localOther) {
                                    $localOther = new OtherDeduction();
                                    $localOther->id = $other['id'];
                                }
                                $localOther->fill([
                                    'title'       => Str::upper(trim($other['title'])),
                                    'description' => $other['description'] ?? null,
                                    'status'      => $other['status'] ?? true,
                                    'created_at'  => $other['created_at'] ?? now(),
                                    'updated_at'  => $other['updated_at'] ?? now(),
                                ]);
                                $localOther->save();
                                $otherCount++;
                            }
                        }
                        if ($otherCount > 0) $summary[] = "{$otherCount} other deductions";

                        // 8. Sync Employees
                        $cloudEmployees = $response->json('employees', []);
                        $empCount = 0;
                        foreach ($cloudEmployees as $emp) {
                            if (class_exists(Employee::class)) {
                                $localEmp = Employee::where('employeeid', $emp['employeeid'])->first();

                                $data = [
                                    'firstname'     => Str::upper($emp['firstname'] ?? ''),
                                    'middlename'    => Str::upper($emp['middlename'] ?? ''),
                                    'lastname'      => Str::upper($emp['lastname'] ?? ''),
                                    'status'        => $emp['status'] ?? true,
                                    'empstatus'     => $emp['empstatus'] ?? null,
                                    'mobile'        => $emp['mobile'] ?? null,
                                    'email'         => $emp['email'] ?? null,
                                    'birthdate'     => !empty($emp['birthdate']) ? Carbon::parse($emp['birthdate'])->format('Y-m-d') : null,
                                    'sex'           => $emp['sex'] ?? null,
                                    'address'       => $emp['address'] ?? null,
                                    'datehired'     => !empty($emp['datehired']) ? Carbon::parse($emp['datehired'])->format('Y-m-d') : null,
                                    'employeetype'  => $emp['employeetype'] ?? null,
                                    'dateseperated' => !empty($emp['dateseperated']) ? Carbon::parse($emp['dateseperated'])->format('Y-m-d') : null,
                                    'skill_id'      => $emp['skill_id'] ?? null,
                                    'project_id'    => $emp['project_id'] ?? null,
                                    'partners'      => $emp['partners'] ?? null,
                                ];

                                if (!$localEmp) {
                                    Employee::create(array_merge(['employeeid' => $emp['employeeid']], $data));
                                } else {
                                    $localEmp->update($data);
                                }
                                $empCount++;
                            }
                        }
                        if ($empCount > 0) $summary[] = "{$empCount} employees";

                        // 9. Sync Employee Project Histories
                        $cloudHistories = $response->json('employee_project_histories', []);
                        $histCount = 0;
                        foreach ($cloudHistories as $hist) {
                            if (class_exists(EmployeeProjectHistory::class)) {
                                $dateStarted = !empty($hist['datestarted']) ? Carbon::parse($hist['datestarted'])->format('Y-m-d') : null;
                                $localHist = EmployeeProjectHistory::where('employeeid', $hist['employeeid'])
                                    ->where('projectid', $hist['projectid'])
                                    ->where('datestarted', $dateStarted)
                                    ->first();

                                $data = [
                                    'employeetype'    => $hist['employeetype'] ?? null,
                                    'employee_status' => $hist['employee_status'] ?? null,
                                    'dateended'       => !empty($hist['dateended']) ? Carbon::parse($hist['dateended'])->format('Y-m-d') : null,
                                    'status'          => $hist['status'] ?? true,
                                ];

                                if (!$localHist) {
                                    EmployeeProjectHistory::create(array_merge([
                                        'employeeid'  => $hist['employeeid'],
                                        'projectid'   => $hist['projectid'],
                                        'datestarted' => $dateStarted,
                                    ], $data));
                                } else {
                                    $localHist->update($data);
                                }
                                $histCount++;
                            }
                        }
                        if ($histCount > 0) $summary[] = "{$histCount} histories";

                        // 10. Sync Earnings
                        $cloudEarnings = $response->json('earnings', []);
                        $earnCount = 0;
                        foreach ($cloudEarnings as $earn) {
                            if (class_exists(Earnings::class)) {
                                $localEarn = Earnings::where('employee_id', $earn['employee_id'])
                                    ->where('title', $earn['title'])
                                    ->first();

                                $data = [
                                    'amount'    => $earn['amount'] ?? 0,
                                    'status'    => $earn['status'] ?? true,
                                    'hierarchy' => $earn['hierarchy'] ?? null,
                                    'frequency' => $earn['frequency'] ?? null,
                                ];

                                if (!$localEarn) {
                                    Earnings::create(array_merge([
                                        'employee_id' => $earn['employee_id'],
                                        'title'       => $earn['title'],
                                    ], $data));
                                } else {
                                    $localEarn->update($data);
                                }
                                $earnCount++;
                            }
                        }
                        if ($earnCount > 0) $summary[] = "{$earnCount} earnings";

                        // 11. Sync Employee Schedules
                        $cloudSchedules = $response->json('emp_schedule', []);
                        $schedCount = 0;
                        foreach ($cloudSchedules as $sched) {
                            if (class_exists(EmpSchedule::class)) {
                                $localSched = isset($sched['id']) ? EmpSchedule::find($sched['id']) : null;

                                if (!$localSched) {
                                    $localSched = new EmpSchedule();
                                    if (isset($sched['id'])) {
                                        $localSched->id = $sched['id'];
                                    }
                                }

                                $localSched->fill([
                                    'employeeid'   => $sched['employeeid'] ?? null,
                                    'timein'       => $sched['timein'] ?? null,
                                    'timeout'      => $sched['timeout'] ?? null,
                                    'status'       => $sched['status'] ?? true,
                                    'workingHours' => $sched['workingHours'] ?? null,
                                ]);

                                $localSched->save();
                                $schedCount++;
                            }
                        }
                        if ($schedCount > 0) $summary[] = "{$schedCount} employee schedules";

                        // 12. Sync Facial Profiles
                        $cloudProfiles = $response->json('facial_profiles', []);
                        $profileCount = 0;
                        foreach ($cloudProfiles as $profile) {
                            if (class_exists(FacialProfile::class)) {
                                $localProfile = FacialProfile::where('employee_id', $profile['employee_id'])->first();
                                $descriptor = is_string($profile['face_descriptor']) ? json_decode($profile['face_descriptor'], true) : $profile['face_descriptor'];

                                if (!$localProfile) {
                                    FacialProfile::create([
                                        'employee_id'     => $profile['employee_id'],
                                        'face_descriptor' => $descriptor,
                                    ]);
                                } else {
                                    $localProfile->update([
                                        'face_descriptor' => $descriptor,
                                    ]);
                                }
                                $profileCount++;
                            }
                        }
                        if ($profileCount > 0) $summary[] = "{$profileCount} facial profiles";

                        // 13. Sync Date Periods
                        $cloudPeriods = $response->json('date_periods', []);
                        $periodCount = 0;
                        foreach ($cloudPeriods as $period) {
                            if (class_exists(DatePeriod::class)) {
                                $localPeriod = DatePeriod::find($period['id']);
                                if (!$localPeriod) {
                                    $localPeriod = new DatePeriod();
                                    $localPeriod->id = $period['id'];
                                }
                                $localPeriod->fill([
                                    'employeetype'  => $period['employeetype'] ?? null,
                                    'category_id'   => $period['category_id'] ?? null,
                                    'code'          => $period['code'] ?? null,
                                    'datefrom'      => !empty($period['datefrom']) ? Carbon::parse($period['datefrom'])->format('Y-m-d') : null,
                                    'dateto'        => !empty($period['dateto']) ? Carbon::parse($period['dateto'])->format('Y-m-d') : null,
                                    'status'        => $period['status'] ?? true,
                                    'overtime_rate' => $period['overtime_rate'] ?? 0,
                                    'partners'      => $period['partners'] ?? null,
                                    'projectid'     => $period['projectid'] ?? null,
                                ]);
                                $localPeriod->save();
                                $periodCount++;
                            }
                        }
                        if ($periodCount > 0) $summary[] = "{$periodCount} date periods";

                        // 14. Sync Year End Reports
                        $cloudReports = $response->json('year_end_reports', []);
                        $reportCount = 0;
                        foreach ($cloudReports as $report) {
                            if (class_exists(YearEndReport::class)) {
                                $localReport = YearEndReport::find($report['id']);
                                if (!$localReport) {
                                    $localReport = new YearEndReport();
                                    $localReport->id = $report['id'];
                                }
                                $localReport->fill([
                                    'code'       => $report['code'] ?? null,
                                    'emptype'    => $report['emptype'] ?? null,
                                    'empstatus'  => $report['empstatus'] ?? null,
                                    'partners'   => $report['partners'] ?? null,
                                    'projectid'  => $report['projectid'] ?? null,
                                    'status'     => $report['status'] ?? true,
                                    'datefrom'   => !empty($report['datefrom']) ? Carbon::parse($report['datefrom'])->format('Y-m-d') : null,
                                    'dateto'     => !empty($report['dateto']) ? Carbon::parse($report['dateto'])->format('Y-m-d') : null,
                                    'rep_type'   => $report['rep_type'] ?? null,
                                ]);
                                $localReport->save();
                                $reportCount++;
                            }
                        }
                        if ($reportCount > 0) $summary[] = "{$reportCount} year-end reports";

                        // 15. Sync Activity Logs
                        $cloudActivity = $response->json('activity_logs', []);
                        $actCount = 0;
                        foreach ($cloudActivity as $act) {
                            if (class_exists(ActivityLog::class)) {
                                ActivityLog::firstOrCreate(
                                    ['user_id' => $act['user_id'], 'activity' => $act['activity'], 'created_at' => $act['created_at'] ?? now()],
                                    [
                                        'module'    => $act['module'] ?? null,
                                        'ipaddress' => $act['ipaddress'] ?? null,
                                        'windows'   => $act['windows'] ?? null,
                                    ]
                                );
                                $actCount++;
                            }
                        }
                        if ($actCount > 0) $summary[] = "{$actCount} activity logs";

                        // 16. Sync Adjustments
                        $cloudAdj = $response->json('adjustments', []);
                        $adjCount = 0;
                        foreach ($cloudAdj as $adj) {
                            if (class_exists(Adjustment::class)) {
                                $localAdj = Adjustment::where('employee_id', $adj['employee_id'])
                                    ->where('date_period_id', $adj['date_period_id'])
                                    ->where('adjustment_id', $adj['adjustment_id'])
                                    ->first();

                                if (!$localAdj) {
                                    Adjustment::create([
                                        'employee_id'    => $adj['employee_id'],
                                        'date_period_id' => $adj['date_period_id'],
                                        'adjustment_id'  => $adj['adjustment_id'],
                                        'amount'         => $adj['amount'] ?? 0,
                                    ]);
                                } else {
                                    $localAdj->update(['amount' => $adj['amount'] ?? 0]);
                                }
                                $adjCount++;
                            }
                        }
                        if ($adjCount > 0) $summary[] = "{$adjCount} adjustments";

                        // 17. Sync Gov Deduction Logs
                        $cloudGovLogs = $response->json('gov_deduction_logs', []);
                        $govLogCount = 0;
                        foreach ($cloudGovLogs as $log) {
                            if (class_exists(GovDeductionLog::class)) {
                                $localLog = GovDeductionLog::where('gov_deduction_id', $log['gov_deduction_id'])
                                    ->where('employee_id', $log['employee_id'])
                                    ->where('date_period_id', $log['date_period_id'])
                                    ->first();

                                if (!$localLog) {
                                    GovDeductionLog::create([
                                        'gov_deduction_id' => $log['gov_deduction_id'],
                                        'employee_id'      => $log['employee_id'],
                                        'date_period_id'   => $log['date_period_id'],
                                        'amount'           => $log['amount'] ?? 0,
                                    ]);
                                } else {
                                    $localLog->update(['amount' => $log['amount'] ?? 0]);
                                }
                                $govLogCount++;
                            }
                        }
                        if ($govLogCount > 0) $summary[] = "{$govLogCount} gov deduction logs";

                        // 18. Sync Other Deduction Logs
                        $cloudOtherLogs = $response->json('other_deduction_logs', []);
                        $otherLogCount = 0;
                        foreach ($cloudOtherLogs as $log) {
                            if (class_exists(OtherDeductionLog::class)) {
                                $localLog = OtherDeductionLog::where('other_deduction_id', $log['other_deduction_id'])
                                    ->where('employee_id', $log['employee_id'])
                                    ->where('date_period_id', $log['date_period_id'])
                                    ->first();

                                if (!$localLog) {
                                    OtherDeductionLog::create([
                                        'other_deduction_id' => $log['other_deduction_id'],
                                        'employee_id'        => $log['employee_id'],
                                        'date_period_id'     => $log['date_period_id'],
                                        'amount'             => $log['amount'] ?? 0,
                                    ]);
                                } else {
                                    $localLog->update(['amount' => $log['amount'] ?? 0]);
                                }
                                $otherLogCount++;
                            }
                        }
                        if ($otherLogCount > 0) $summary[] = "{$otherLogCount} other deduction logs";

                        // 19. Sync Incentive Bonuses
                        $cloudBonuses = $response->json('incentive_bonuses', []);
                        $bonusCount = 0;
                        foreach ($cloudBonuses as $bonus) {
                            if (class_exists(IncentiveBonus::class)) {
                                $localBonus = IncentiveBonus::where('employeeid', $bonus['employeeid'])
                                    ->where('yearendrepid', $bonus['yearendrepid'])
                                    ->first();

                                $data = [
                                    'status'   => $bonus['status'] ?? true,
                                    'earnings' => $bonus['earnings'] ?? 0,
                                ];

                                if (!$localBonus) {
                                    IncentiveBonus::create(array_merge([
                                        'employeeid'   => $bonus['employeeid'],
                                        'yearendrepid' => $bonus['yearendrepid'],
                                    ], $data));
                                } else {
                                    $localBonus->update($data);
                                }
                                $bonusCount++;
                            }
                        }
                        if ($bonusCount > 0) $summary[] = "{$bonusCount} incentive bonuses";

                        // 20. Sync Thirteenth Months
                        $cloudThirteenth = $response->json('thirteenth_months', []);
                        $tmCount = 0;
                        foreach ($cloudThirteenth as $tm) {
                            if (class_exists(ThirteenthMonth::class)) {
                                $localTm = ThirteenthMonth::where('employeeid', $tm['employeeid'])
                                    ->where('periodid', $tm['periodid'])
                                    ->first();

                                $data = [
                                    'earnings'     => $tm['earnings'] ?? 0,
                                    'partners'     => $tm['partners'] ?? null,
                                    'yearendrepid' => $tm['yearendrepid'] ?? null,
                                    'project'      => $tm['project'] ?? null,
                                    'allowance'    => $tm['allowance'] ?? 0,
                                    'datestart'    => !empty($tm['datestart']) ? Carbon::parse($tm['datestart'])->format('Y-m-d') : null,
                                    'dateend'      => !empty($tm['dateend']) ? Carbon::parse($tm['dateend'])->format('Y-m-d') : null,
                                    'yearendcode'  => $tm['yearendcode'] ?? null,
                                    'status'       => $tm['status'] ?? true,
                                ];

                                if (!$localTm) {
                                    ThirteenthMonth::create(array_merge([
                                        'employeeid' => $tm['employeeid'],
                                        'periodid'   => $tm['periodid'],
                                    ], $data));
                                } else {
                                    $localTm->update($data);
                                }
                                $tmCount++;
                            }
                        }
                        if ($tmCount > 0) $summary[] = "{$tmCount} thirteenth month records";

                        // 21. Sync User Permissions
                        $cloudPermissions = $response->json('user_permissions', []);
                        $permCount = 0;
                        foreach ($cloudPermissions as $perm) {
                            if (class_exists(UserPermission::class)) {
                                UserPermission::firstOrCreate(
                                    ['user_id' => $perm['user_id'], 'module' => $perm['module']]
                                );
                                $permCount++;
                            }
                        }
                        if ($permCount > 0) $summary[] = "{$permCount} user permissions";

                        // 22. Sync Payroll Reports
                        $cloudPayrollReports = $response->json('payroll_reports', []);
                        $prCount = 0;
                        foreach ($cloudPayrollReports as $report) {
                            if (class_exists(PayrollReport::class)) {
                                $dateEntry = !empty($report['date_entry']) ? Carbon::parse($report['date_entry'])->format('Y-m-d') : null;

                                $localReport = PayrollReport::where('dateperiod_id', $report['dateperiod_id'])
                                    ->where('employee_id', $report['employee_id'])
                                    ->where('date_entry', $dateEntry)
                                    ->first();

                                $data = [
                                    'paytype'        => $report['paytype'] ?? null,
                                    'overtime'       => $report['overtime'] ?? 0,
                                    'acquired_hours' => $report['acquired_hours'] ?? 0,
                                    'late_undertime' => $report['late_undertime'] ?? 0,
                                    'cat_id'         => $report['cat_id'] ?? null,
                                    'status'         => $report['status'] ?? true,
                                    'sched_id'       => $report['sched_id'] ?? null,
                                ];

                                if (!$localReport) {
                                    PayrollReport::create(array_merge([
                                        'dateperiod_id' => $report['dateperiod_id'],
                                        'employee_id'   => $report['employee_id'],
                                        'date_entry'    => $dateEntry,
                                    ], $data));
                                } else {
                                    $localReport->update($data);
                                }
                                $prCount++;
                            }
                        }
                        if ($prCount > 0) $summary[] = "{$prCount} payroll reports";

                        // 23. Sync Payroll Summary Reports
                        $cloudSummaryReports = $response->json('payroll_summary_reports', []);
                        $psrCount = 0;
                        foreach ($cloudSummaryReports as $summaryReport) {
                            if (class_exists(PayrollSummaryReport::class)) {
                                $localSummary = PayrollSummaryReport::where('dateperiod_id', $summaryReport['dateperiod_id'])
                                    ->where('employee_id', $summaryReport['employee_id'])
                                    ->first();

                                $data = [
                                    'totalhours'      => $summaryReport['totalhours'] ?? 0,
                                    'totalovertime'   => $summaryReport['totalovertime'] ?? 0,
                                    'totalabsent'     => $summaryReport['totalabsent'] ?? 0,
                                    'lateundertime'   => $summaryReport['lateundertime'] ?? 0,
                                    'totaldeductionn' => $summaryReport['totaldeductionn'] ?? 0,
                                    'totalearnings'   => $summaryReport['totalearnings'] ?? 0,
                                    'totaladjustment' => $summaryReport['totaladjustment'] ?? 0,
                                    'totalnetpay'     => $summaryReport['totalnetpay'] ?? 0,
                                    'grosspay'        => $summaryReport['grosspay'] ?? 0,
                                    'status'          => $summaryReport['status'] ?? true,
                                    'required_hours'  => $summaryReport['required_hours'] ?? 0,
                                    'required_income' => $summaryReport['required_income'] ?? 0,
                                ];

                                if (!$localSummary) {
                                    PayrollSummaryReport::create(array_merge([
                                        'dateperiod_id' => $summaryReport['dateperiod_id'],
                                        'employee_id'   => $summaryReport['employee_id'],
                                    ], $data));
                                } else {
                                    $localSummary->update($data);
                                }
                                $psrCount++;
                            }
                        }
                        if ($psrCount > 0) $summary[] = "{$psrCount} payroll summary reports";

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
                ->visible(fn() => app()->environment('local') && $isOnline)
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
                            'employees'                  => class_exists(Employee::class) ? Employee::all()->toArray() : [],
                            'employee_project_histories' => class_exists(EmployeeProjectHistory::class) ? EmployeeProjectHistory::all()->toArray() : [],
                            'earnings'                   => class_exists(Earnings::class) ? Earnings::all()->toArray() : [],
                            'emp_schedule'               => class_exists(EmpSchedule::class) ? EmpSchedule::all()->toArray() : [],
                            'facial_profiles'            => class_exists(FacialProfile::class) ? FacialProfile::all()->toArray() : [],
                            'date_periods'               => class_exists(DatePeriod::class) ? DatePeriod::all()->toArray() : [],
                            'year_end_reports'           => class_exists(YearEndReport::class) ? YearEndReport::all()->toArray() : [],
                            'activity_logs'        => class_exists(ActivityLog::class) ? ActivityLog::all()->toArray() : [],
                            'adjustments'          => class_exists(Adjustment::class) ? Adjustment::all()->toArray() : [],
                            'gov_deduction_logs'   => class_exists(GovDeductionLog::class) ? GovDeductionLog::all()->toArray() : [],
                            'other_deduction_logs' => class_exists(OtherDeductionLog::class) ? OtherDeductionLog::all()->toArray() : [],
                            'incentive_bonuses'    => class_exists(IncentiveBonus::class) ? IncentiveBonus::all()->toArray() : [],
                            'thirteenth_months'    => class_exists(ThirteenthMonth::class) ? ThirteenthMonth::all()->toArray() : [],
                            'user_permissions'     => class_exists(UserPermission::class) ? UserPermission::all()->toArray() : [],
                            'payroll_reports'         => class_exists(PayrollReport::class) ? PayrollReport::all()->toArray() : [],
                            'payroll_summary_reports' => class_exists(PayrollSummaryReport::class) ? PayrollSummaryReport::all()->toArray() : [],
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
