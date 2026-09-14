<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Str;


// Helper function to validate the Bearer token
function verify_api_token(Request $request)
{
    $token = $request->bearerToken();
    $expectedToken = config('services.cloud.token', env('CLOUD_API_TOKEN'));

    return $token && hash_equals($expectedToken, $token);
}

// Safe helper function declaration
if (!function_exists('verify_api_token')) {
    function verify_api_token(Request $request)
    {
        $token = $request->bearerToken();
        $expectedToken = config('services.cloud.token', env('CLOUD_API_TOKEN'));

        return $token && hash_equals($expectedToken, $token);
    }
}
// --- MASTER FETCH ALL ENDPOINT ---
Route::get('/fetch-all-cloud-data', function (Request $request) {
    if (!verify_api_token($request)) {
        return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);
    }

    try {
        return response()->json([
            'success'  => true,
            'logs'     => DB::table('attendance_logs')->get(),
            'skills'   => DB::table('skills')->get(),
            'projects' => DB::table('projects')->get(),
            'categories' => DB::table('categories')->get(),
            'gov_deductions'   => DB::table('gov_deductions')->get(),
            'holidays'         => DB::table('holidays')->get(),
            'other_deductions' => DB::table('other_deductions')->get(),
            'employees'               => DB::table('employees')->get(),
            'employee_project_histories' => DB::table('employee_project_histories')->get(),
            'earnings'                => DB::table('earnings')->get(),
            'emp_schedule'            => DB::table('emp_schedule')->get(),
            'facial_profiles'         => DB::table('facial_profiles')->get(),
            'date_periods'            => DB::table('date_periods')->get(),
            'year_end_reports'        => DB::table('year_end_reports')->get(),
            'activity_logs'        => DB::table('activity_logs')->get(),
            'adjustments'          => DB::table('adjustments')->get(),
            'gov_deduction_logs'   => DB::table('gov_deduction_logs')->get(),
            'other_deduction_logs' => DB::table('other_deduction_logs')->get(),
            'incentive_bonuses'    => DB::table('incentive_bonuses')->get(),
            'thirteenth_months'    => DB::table('thirteenth_months')->get(),
            'user_permissions'     => DB::table('user_permissions')->get(),
            'payroll_reports'         => DB::table('payroll_reports')->get(),
            'payroll_summary_reports' => DB::table('payroll_summary_reports')->get(),
        ], 200);
    } catch (\Exception $e) {
        return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
    }
});

// --- MASTER SYNC ALL ENDPOINT ---
Route::post('/sync-all', function (Request $request) {
    if (!verify_api_token($request)) {
        return response()->json(['success' => false, 'message' => 'Unauthorized. Invalid or missing token.'], 401);
    }

    try {
        $summary = [];

        // 1. Sync Attendance Logs
        $logs = $request->input('logs', []);
        if (!empty($logs)) {
            $logCount = 0;
            foreach ($logs as $log) {
                $recordedAt = isset($log['recorded_at']) ? Carbon::parse($log['recorded_at'])->format('Y-m-d H:i:s') : now();
                $createdAt  = isset($log['created_at']) ? Carbon::parse($log['created_at'])->format('Y-m-d H:i:s') : now();

                DB::table('attendance_logs')->updateOrInsert(
                    [
                        'user_id'     => $log['user_id'],
                        'recorded_at' => $recordedAt,
                    ],
                    [
                        'project_code'      => $log['project_code'] ?? 0,
                        'status'            => $log['status'],
                        'verification_mode' => $log['verification_mode'],
                        'work_code'         => $log['work_code'] ?? 0,
                        'reserved'          => $log['reserved'] ?? 0,
                        'created_at'        => $createdAt,
                        'updated_at'        => now()->format('Y-m-d H:i:s'),
                    ]
                );
                $logCount++;
            }
            $summary[] = "{$logCount} logs";
        }

        // 2. Sync Skills
        $skills = $request->input('skills', []);
        if (!empty($skills)) {
            $skillCount = 0;
            foreach ($skills as $skill) {
                $createdAt = isset($skill['created_at']) ? Carbon::parse($skill['created_at'])->format('Y-m-d H:i:s') : now();
                $updatedAt = isset($skill['updated_at']) ? Carbon::parse($skill['updated_at'])->format('Y-m-d H:i:s') : now();

                DB::table('skills')->updateOrInsert(
                    ['id' => $skill['id']],
                    [
                        'title' => Str::upper($skill['title']),
                        'details' => $skill['details'] ?? null,
                        'status' => $skill['status'] ?? true,
                        'created_at' => $createdAt,
                        'updated_at' => $updatedAt
                    ]
                );
                $skillCount++;
            }
            $summary[] = "{$skillCount} skills";
        }

        // 3. Sync Projects
        $projects = $request->input('projects', []);
        if (!empty($projects)) {
            $projectCount = 0;
            foreach ($projects as $project) {
                $createdAt = isset($project['created_at']) ? Carbon::parse($project['created_at'])->format('Y-m-d H:i:s') : now();
                $updatedAt = isset($project['updated_at']) ? Carbon::parse($project['updated_at'])->format('Y-m-d H:i:s') : now();

                DB::table('projects')->updateOrInsert(
                    ['project_code' => $project['project_code']],
                    [
                        'name'         => $project['name'] ?? null,
                        'datecovered'  => $project['datecovered'] ?? null,
                        'scope'        => $project['scope'] ?? null,
                        'address'      => $project['address'] ?? null,
                        'image'        => $project['image'] ?? null,
                        'status'       => $project['status'] ?? true,
                        'created_at'   => $createdAt,
                        'updated_at'   => $updatedAt,
                    ]
                );
                $projectCount++;
            }
            $summary[] = "{$projectCount} projects";
        }

        // 4. Sync Categories (🟢 Added)
        $categories = $request->input('categories', []);
        if (!empty($categories)) {
            $catCount = 0;
            foreach ($categories as $category) {
                $createdAt = isset($category['created_at']) ? Carbon::parse($category['created_at'])->format('Y-m-d H:i:s') : now();
                $updatedAt = isset($category['updated_at']) ? Carbon::parse($category['updated_at'])->format('Y-m-d H:i:s') : now();
                DB::table('categories')->updateOrInsert(
                    ['id' => $category['id']],
                    [
                        'cat' => $category['cat'] ?? null,
                        'name' => Str::upper($category['name'] ?? ''),
                        'description' => $category['description'] ?? null,
                        'status' => $category['status'] ?? true,
                        'created_at' => $createdAt,
                        'updated_at' => $updatedAt
                    ]
                );
                $catCount++;
            }
            $summary[] = "{$catCount} categories";
        }

        // 5. Sync Gov Deductions
        $govDeductions = $request->input('gov_deductions', []);
        if (!empty($govDeductions)) {
            $govCount = 0;
            foreach ($govDeductions as $gov) {
                $createdAt = isset($gov['created_at']) ? Carbon::parse($gov['created_at'])->format('Y-m-d H:i:s') : now();
                $updatedAt = isset($gov['updated_at']) ? Carbon::parse($gov['updated_at'])->format('Y-m-d H:i:s') : now();

                // Format dates safely for MySQL DATE columns
                $dateStarted = !empty($gov['date_started']) ? Carbon::parse($gov['date_started'])->format('Y-m-d') : null;
                $dateEnded   = !empty($gov['date_ended']) ? Carbon::parse($gov['date_ended'])->format('Y-m-d') : null;

                DB::table('gov_deductions')->updateOrInsert(
                    ['id' => $gov['id']],
                    [
                        'title'        => Str::upper($gov['title']),
                        'date_started' => $dateStarted,
                        'date_ended'   => $dateEnded,
                        'amount'       => $gov['amount'] ?? 0,
                        'status'       => $gov['status'] ?? true,
                        'created_at'   => $createdAt,
                        'updated_at'   => $updatedAt,
                    ]
                );
                $govCount++;
            }
            $summary[] = "{$govCount} gov deductions";
        }
        // 6. Sync Holidays (🟢 Added)
        $holidays = $request->input('holidays', []);
        if (!empty($holidays)) {
            $holidayCount = 0;
            foreach ($holidays as $holiday) {
                $createdAt = isset($holiday['created_at']) ? Carbon::parse($holiday['created_at'])->format('Y-m-d H:i:s') : now();
                $updatedAt = isset($holiday['updated_at']) ? Carbon::parse($holiday['updated_at'])->format('Y-m-d H:i:s') : now();

                DB::table('holidays')->updateOrInsert(
                    ['id' => $holiday['id']],
                    [
                        'type'       => Str::upper($holiday['type']),
                        'percentage' => $holiday['percentage'] ?? 0,
                        'details'    => $holiday['details'] ?? null,
                        'status'     => $holiday['status'] ?? true,
                        'created_at' => $createdAt,
                        'updated_at' => $updatedAt,
                    ]
                );
                $holidayCount++;
            }
            $summary[] = "{$holidayCount} holidays";
        }

        // 7. Sync Other Deductions (🟢 Added)
        $otherDeductions = $request->input('other_deductions', []);
        if (!empty($otherDeductions)) {
            $otherCount = 0;
            foreach ($otherDeductions as $other) {
                $createdAt = isset($other['created_at']) ? Carbon::parse($other['created_at'])->format('Y-m-d H:i:s') : now();
                $updatedAt = isset($other['updated_at']) ? Carbon::parse($other['updated_at'])->format('Y-m-d H:i:s') : now();

                DB::table('other_deductions')->updateOrInsert(
                    ['id' => $other['id']],
                    [
                        'title'       => Str::upper($other['title']),
                        'description' => $other['description'] ?? null,
                        'status'      => $other['status'] ?? true,
                        'created_at'  => $createdAt,
                        'updated_at'  => $updatedAt,
                    ]
                );
                $otherCount++;
            }
            $summary[] = "{$otherCount} other deductions";
        }

        // 8. Sync Employees
        $employees = $request->input('employees', []);
        if (!empty($employees)) {
            $count = 0;
            foreach ($employees as $emp) {
                $createdAt = isset($emp['created_at']) ? Carbon::parse($emp['created_at'])->format('Y-m-d H:i:s') : now();
                $updatedAt = isset($emp['updated_at']) ? Carbon::parse($emp['updated_at'])->format('Y-m-d H:i:s') : now();

                DB::table('employees')->updateOrInsert(
                    ['employeeid' => $emp['employeeid']],
                    [
                        'firstname'     => $emp['firstname'] ?? '',
                        'middlename'    => $emp['middlename'] ?? '',
                        'lastname'      => $emp['lastname'] ?? '',
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
                        'created_at'  => $createdAt,
                        'updated_at'  => $updatedAt,
                    ]
                );
                $count++;
            }
            $summary[] = "{$count} employees";
        }

        // 9. Sync Employee Project Histories
        $histories = $request->input('employee_project_histories', []);
        if (!empty($histories)) {
            $count = 0;
            foreach ($histories as $hist) {
                $createdAt = isset($hist['created_at']) ? Carbon::parse($hist['created_at'])->format('Y-m-d H:i:s') : now();
                $updatedAt = isset($hist['updated_at']) ? Carbon::parse($hist['updated_at'])->format('Y-m-d H:i:s') : now();

                DB::table('employee_project_histories')->updateOrInsert(
                    ['employeeid' => $hist['employeeid'], 'projectid' => $hist['projectid'], 'datestarted' => !empty($hist['datestarted']) ? Carbon::parse($hist['datestarted'])->format('Y-m-d') : null],
                    [
                        'employeetype'    => $hist['employeetype'] ?? null,
                        'employee_status' => $hist['employee_status'] ?? null,
                        'dateended'       => !empty($hist['dateended']) ? Carbon::parse($hist['dateended'])->format('Y-m-d') : null,
                        'status'          => $hist['status'] ?? true,
                        'created_at'  => $createdAt,
                        'updated_at'  => $updatedAt,
                    ]
                );
                $count++;
            }
            $summary[] = "{$count} employee project histories";
        }

        // 10. Sync Earnings
        $earnings = $request->input('earnings', []);
        if (!empty($earnings)) {
            $count = 0;
            foreach ($earnings as $earn) {
                $createdAt = isset($earn['created_at']) ? Carbon::parse($earn['created_at'])->format('Y-m-d H:i:s') : now();
                $updatedAt = isset($earn['updated_at']) ? Carbon::parse($earn['updated_at'])->format('Y-m-d H:i:s') : now();
                DB::table('earnings')->updateOrInsert(
                    ['employee_id' => $earn['employee_id'], 'title' => $earn['title']],
                    [
                        'amount'    => $earn['amount'] ?? 0,
                        'status'    => $earn['status'] ?? true,
                        'hierarchy' => $earn['hierarchy'] ?? null,
                        'frequency' => $earn['frequency'] ?? null,
                        'created_at'  => $createdAt,
                        'updated_at'  => $updatedAt,
                    ]
                );
                $count++;
            }
            $summary[] = "{$count} earnings";
        }

        // 11. Sync Employee Schedules
        $schedules = $request->input('emp_schedule', []);
        if (!empty($schedules)) {
            $count = 0;
            foreach ($schedules as $sched) {
                $createdAt = isset($sched['created_at']) ? Carbon::parse($sched['created_at'])->format('Y-m-d H:i:s') : now();
                $updatedAt = isset($sched['updated_at']) ? Carbon::parse($sched['updated_at'])->format('Y-m-d H:i:s') : now();

                DB::table('emp_schedule')->updateOrInsert(
                    ['employeeid' => $sched['employeeid']],
                    [
                        'timein'       => $sched['timein'] ?? null,
                        'timeout'      => $sched['timeout'] ?? null,
                        'status'       => $sched['status'] ?? true,
                        'workingHours' => $sched['workingHours'] ?? null,
                        'created_at'  => $createdAt,
                        'updated_at'  => $updatedAt,
                    ]
                );
                $count++;
            }
            $summary[] = "{$count} employee schedules";
        }

        // 12. Sync Facial Profiles
        $profiles = $request->input('facial_profiles', []);
        if (!empty($profiles)) {
            $count = 0;
            foreach ($profiles as $profile) {
                $createdAt = isset($profile['created_at']) ? Carbon::parse($profile['created_at'])->format('Y-m-d H:i:s') : now();
                $updatedAt = isset($profile['updated_at']) ? Carbon::parse($profile['updated_at'])->format('Y-m-d H:i:s') : now();
                DB::table('facial_profiles')->updateOrInsert(
                    ['employee_id' => $profile['employee_id']],
                    [
                        'face_descriptor' => is_array($profile['face_descriptor']) ? json_encode($profile['face_descriptor']) : $profile['face_descriptor'],
                        'created_at'  => $createdAt,
                        'updated_at'  => $updatedAt,
                    ]
                );
                $count++;
            }
            $summary[] = "{$count} facial profiles";
        }

        // 13. Sync Date Periods
        $periods = $request->input('date_periods', []);
        if (!empty($periods)) {
            $count = 0;
            foreach ($periods as $period) {
                $createdAt = isset($period['created_at']) ? Carbon::parse($period['created_at'])->format('Y-m-d H:i:s') : now();
                $updatedAt = isset($period['updated_at']) ? Carbon::parse($period['updated_at'])->format('Y-m-d H:i:s') : now();
                DB::table('date_periods')->updateOrInsert(
                    ['id' => $period['id']],
                    [
                        'employeetype'  => $period['employeetype'] ?? null,
                        'category_id'   => $period['category_id'] ?? null,
                        'code'          => $period['code'] ?? null,
                        'datefrom'      => !empty($period['datefrom']) ? Carbon::parse($period['datefrom'])->format('Y-m-d') : null,
                        'dateto'        => !empty($period['dateto']) ? Carbon::parse($period['dateto'])->format('Y-m-d') : null,
                        'status'        => $period['status'] ?? true,
                        'overtime_rate' => $period['overtime_rate'] ?? 0,
                        'partners'      => $period['partners'] ?? null,
                        'projectid'     => $period['projectid'] ?? null,
                        'updated_at'    => now(),
                    ]
                );
                $count++;
            }
            $summary[] = "{$count} date periods";
        }

        // 14. Sync Year End Reports
        $reports = $request->input('year_end_reports', []);
        if (!empty($reports)) {
            $count = 0;
            foreach ($reports as $report) {
                $createdAt = isset($report['created_at']) ? Carbon::parse($report['created_at'])->format('Y-m-d H:i:s') : now();
                $updatedAt = isset($report['updated_at']) ? Carbon::parse($report['updated_at'])->format('Y-m-d H:i:s') : now();

                DB::table('year_end_reports')->updateOrInsert(
                    ['id' => $report['id']],
                    [
                        'code'       => $report['code'] ?? null,
                        'emptype'    => $report['emptype'] ?? null,
                        'empstatus'  => $report['empstatus'] ?? null,
                        'partners'   => $report['partners'] ?? null,
                        'projectid'  => $report['projectid'] ?? null,
                        'status'     => $report['status'] ?? true,
                        'datefrom'   => !empty($report['datefrom']) ? Carbon::parse($report['datefrom'])->format('Y-m-d') : null,
                        'dateto'     => !empty($report['dateto']) ? Carbon::parse($report['dateto'])->format('Y-m-d') : null,
                        'rep_type'   => $report['rep_type'] ?? null,
                        'updated_at' => now(),
                    ]
                );
                $count++;
            }
            $summary[] = "{$count} year-end reports";
        }

        // 15. Sync Activity Logs
        $activityLogs = $request->input('activity_logs', []);
        if (!empty($activityLogs)) {
            $count = 0;
            foreach ($activityLogs as $log) {
                $createdAt = isset($log['created_at']) ? Carbon::parse($log['created_at'])->format('Y-m-d H:i:s') : now();
                $updatedAt = isset($log['updated_at']) ? Carbon::parse($log['updated_at'])->format('Y-m-d H:i:s') : now();
                DB::table('activity_logs')->updateOrInsert(
                    ['user_id' => $log['user_id'], 'activity' => $log['activity'], 'created_at' => $createdAt],
                    [
                        'module'     => $log['module'] ?? null,
                        'ipaddress'  => $log['ipaddress'] ?? null,
                        'windows'    => $log['windows'] ?? null,
                        'created_at'  => $createdAt,
                        'updated_at'  => $updatedAt,
                    ]
                );
                $count++;
            }
            $summary[] = "{$count} activity logs";
        }

        // 16. Sync Adjustments
        $adjustments = $request->input('adjustments', []);
        if (!empty($adjustments)) {
            $count = 0;
            foreach ($adjustments as $adj) {
                $createdAt = isset($adj['created_at']) ? Carbon::parse($adj['created_at'])->format('Y-m-d H:i:s') : now();
                $updatedAt = isset($adj['updated_at']) ? Carbon::parse($adj['updated_at'])->format('Y-m-d H:i:s') : now();
                DB::table('adjustments')->updateOrInsert(
                    [
                        'employee_id' => $adj['employee_id'],
                        'date_period_id' => $adj['date_period_id'],
                        'adjustment_id' => $adj['adjustment_id']
                    ],
                    [
                        'amount'     => $adj['amount'] ?? 0,
                        'created_at'  => $createdAt,
                        'updated_at'  => $updatedAt,
                    ]
                );
                $count++;
            }
            $summary[] = "{$count} adjustments";
        }

        // 17. Sync Gov Deduction Logs
        $govLogs = $request->input('gov_deduction_logs', []);
        if (!empty($govLogs)) {
            $count = 0;
            foreach ($govLogs as $log) {
                $createdAt = isset($log['created_at']) ? Carbon::parse($log['created_at'])->format('Y-m-d H:i:s') : now();
                $updatedAt = isset($log['updated_at']) ? Carbon::parse($log['updated_at'])->format('Y-m-d H:i:s') : now();
                DB::table('gov_deduction_logs')->updateOrInsert(
                    [
                        'gov_deduction_id' => $log['gov_deduction_id'],
                        'employee_id' => $log['employee_id'],
                        'date_period_id' => $log['date_period_id']
                    ],
                    [
                        'amount'     => $log['amount'] ?? 0,
                        'created_at'  => $createdAt,
                        'updated_at'  => $updatedAt,
                    ]
                );
                $count++;
            }
            $summary[] = "{$count} gov deduction logs";
        }

        // 18. Sync Other Deduction Logs
        $otherLogs = $request->input('other_deduction_logs', []);
        if (!empty($otherLogs)) {
            $count = 0;
            foreach ($otherLogs as $log) {
                $createdAt = isset($log['created_at']) ? Carbon::parse($log['created_at'])->format('Y-m-d H:i:s') : now();
                $updatedAt = isset($log['updated_at']) ? Carbon::parse($log['updated_at'])->format('Y-m-d H:i:s') : now();
                DB::table('other_deduction_logs')->updateOrInsert(
                    ['other_deduction_id' => $log['other_deduction_id'], 'employee_id' => $log['employee_id'], 'date_period_id' => $log['date_period_id']],
                    [
                        'amount'     => $log['amount'] ?? 0,
                        'created_at'  => $createdAt,
                        'updated_at'  => $updatedAt,
                    ]
                );
                $count++;
            }
            $summary[] = "{$count} other deduction logs";
        }

        // 19. Sync Incentive Bonuses
        $bonuses = $request->input('incentive_bonuses', []);
        if (!empty($bonuses)) {
            $count = 0;
            foreach ($bonuses as $bonus) {
                $createdAt = isset($bonus['created_at']) ? Carbon::parse($bonus['created_at'])->format('Y-m-d H:i:s') : now();
                $updatedAt = isset($bonus['updated_at']) ? Carbon::parse($bonus['updated_at'])->format('Y-m-d H:i:s') : now();
                DB::table('incentive_bonuses')->updateOrInsert(
                    ['employeeid' => $bonus['employeeid'], 'yearendrepid' => $bonus['yearendrepid']],
                    [
                        'status'     => $bonus['status'] ?? true,
                        'earnings'   => $bonus['earnings'] ?? 0,
                        'created_at'  => $createdAt,
                        'updated_at'  => $updatedAt,
                    ]
                );
                $count++;
            }
            $summary[] = "{$count} incentive bonuses";
        }

        // 20. Sync Thirteenth Months
        $thirteenth = $request->input('thirteenth_months', []);
        if (!empty($thirteenth)) {
            $count = 0;
            foreach ($thirteenth as $tm) {
                $createdAt = isset($tm['created_at']) ? Carbon::parse($tm['created_at'])->format('Y-m-d H:i:s') : now();
                $updatedAt = isset($tm['updated_at']) ? Carbon::parse($tm['updated_at'])->format('Y-m-d H:i:s') : now();
                DB::table('thirteenth_months')->updateOrInsert(
                    ['employeeid' => $tm['employeeid'], 'periodid' => $tm['periodid']],
                    [
                        'earnings'     => $tm['earnings'] ?? 0,
                        'partners'     => $tm['partners'] ?? null,
                        'yearendrepid' => $tm['yearendrepid'] ?? null,
                        'project'      => $tm['project'] ?? null,
                        'allowance'    => $tm['allowance'] ?? 0,
                        'datestart'    => !empty($tm['datestart']) ? Carbon::parse($tm['datestart'])->format('Y-m-d') : null,
                        'dateend'      => !empty($tm['dateend']) ? Carbon::parse($tm['dateend'])->format('Y-m-d') : null,
                        'yearendcode'  => $tm['yearendcode'] ?? null,
                        'status'       => $tm['status'] ?? true,
                        'created_at'  => $createdAt,
                        'updated_at'  => $updatedAt,
                    ]
                );
                $count++;
            }
            $summary[] = "{$count} thirteenth month records";
        }

        // 21. Sync User Permissions
        $permissions = $request->input('user_permissions', []);
        if (!empty($permissions)) {
            $count = 0;
            foreach ($permissions as $perm) {
                $createdAt = isset($perm['created_at']) ? Carbon::parse($perm['created_at'])->format('Y-m-d H:i:s') : now();
                $updatedAt = isset($perm['updated_at']) ? Carbon::parse($perm['updated_at'])->format('Y-m-d H:i:s') : now();
                DB::table('user_permissions')->updateOrInsert(
                    [
                        'user_id' => $perm['user_id'],
                        'module' => $perm['module']
                    ],
                    [
                        'created_at'  => $createdAt,
                        'updated_at'  => $updatedAt,
                    ]
                );
                $count++;
            }
            $summary[] = "{$count} user permissions";
        }

        // 22. Sync Payroll Reports
        $payrollReports = $request->input('payroll_reports', []);
        if (!empty($payrollReports)) {
            $count = 0;
            foreach ($payrollReports as $report) {
                $createdAt = isset($report['created_at']) ? Carbon::parse($report['created_at'])->format('Y-m-d H:i:s') : now();
                $updatedAt = isset($report['updated_at']) ? Carbon::parse($report['updated_at'])->format('Y-m-d H:i:s') : now();
                DB::table('payroll_reports')->updateOrInsert(
                    [
                        'dateperiod_id' => $report['dateperiod_id'],
                        'employee_id' => $report['employee_id'],
                        'date_entry' => !empty($report['date_entry']) ? Carbon::parse($report['date_entry'])->format('Y-m-d') : null
                    ],
                    [
                        'paytype'        => $report['paytype'] ?? null,
                        'overtime'       => $report['overtime'] ?? 0,
                        'acquired_hours' => $report['acquired_hours'] ?? 0,
                        'late_undertime' => $report['late_undertime'] ?? 0,
                        'cat_id'         => $report['cat_id'] ?? null,
                        'status'         => $report['status'] ?? true,
                        'sched_id'       => $report['sched_id'] ?? null,
                        'created_at'  => $createdAt,
                        'updated_at'  => $updatedAt,
                    ]
                );
                $count++;
            }
            $summary[] = "{$count} payroll reports";
        }

        // 23. Sync Payroll Summary Reports
        $summaryReports = $request->input('payroll_summary_reports', []);
        if (!empty($summaryReports)) {
            $count = 0;
            foreach ($summaryReports as $summaryReport) {
                DB::table('payroll_summary_reports')->updateOrInsert(
                    ['dateperiod_id' => $summaryReport['dateperiod_id'], 'employee_id' => $summaryReport['employee_id']],
                    [
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
                        'updated_at'      => now(),
                    ]
                );
                $count++;
            }
            $summary[] = "{$count} payroll summary reports";
        }

        if (empty($summary)) {
            return response()->json(['success' => false, 'message' => 'No data provided for synchronization.'], 400);
        }

        $message = "Successfully synchronized: " . implode(', ', $summary) . ".";
        return response()->json(['success' => true, 'message' => $message], 200);
    } catch (\Exception $e) {
        // \Illuminate\Support\Facades\Log::error('Sync All Error: ' . $e->getMessage());
        return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
    }
});
