<?php

use App\Http\Controllers\AttendanceLogController;
use App\Http\Controllers\DeductionController;
use App\Http\Controllers\PayrollSummaryController;
use App\Http\Controllers\YearEndReportController;
use App\Models\OtherDeductionLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('webpage');
});

Route::get('/login', function () {
    // Check if the user was trying to access an 'hci' path before hitting the login fallback
    if (request()->is('admin*') || str_contains(url()->previous(), '/admin')) {
        return redirect()->route('filament.admin.auth.login');
    }
    // Default fallback if context is unclear
    return redirect()->route('filament.admin.auth.login');
})->name('login');
Route::middleware(['auth'])->group(function () {
    Route::get('/reports/year-end/print', [YearEndReportController::class, 'printReport'])
        ->name('reports.year-end.print');
    Route::get('/payslips/view/{id}', function ($id) {
        $datePeriod = DB::table('date_periods')
            ->where('id', $id)
            ->first();
        $type = $datePeriod->employeetype;
        $category = DB::table('categories')
            ->where('id', $datePeriod->category_id) // replace $categoryId with your value
            ->first();
        $employees = DB::table('employees as e')
            ->join('projects as p', 'p.project_code', '=', 'e.project_id') // join with projects
            ->join('thirteenth_months as tm', function ($join) use ($id) {
                $join->on('tm.employeeid', '=', 'e.employeeid')
                    ->where('tm.periodid', '=', $id); // filter by period
            })
            ->select(
                'e.*',
                'p.name as project_name',
                'p.project_code',
                'tm.total_amount as thirteenth_total'
            )
            ->orderBy('e.lastname', 'asc')
            ->get();
        $employeeIds = $employees->pluck('employeeid');
        $deductions = OtherDeductionLog::where('date_period_id', $id)
            ->whereIn('employee_id', $employeeIds)   // <-- added filter
            ->with('otherDeduction')
            ->get()
            ->groupBy('employee_id');
        $SumDeduction = $deductions->collapse()->sum('amount');
        return view('payslips.view', compact('employees', 'datePeriod', 'deductions', 'type', 'category', 'SumDeduction'));
    })->name('payslips.view');
    Route::delete('/deduction/{id}', [DeductionController::class, 'destroy'])->name('deduction.destroy');
    Route::get('/payroll-summary/{employee_id}/{period_code}', [PayrollSummaryController::class, 'show'])
        ->name('payroll.summary');
    Route::post('/attendance-logs/store-double', [AttendanceLogController::class, 'storeDoubleRaw'])
        ->name('attendance-logs.store-double');
    Route::delete('/attendance-logs/destroy-day', [AttendanceLogController::class, 'destroyDayRaw'])
        ->name('attendance-logs.destroy-day');

    Route::get('/payroll/process-sheet/{periodcode}/{expartners}', [PayrollSummaryController::class, 'showSheet'])
        ->name('payroll.process-sheet');

    Route::put('/attendance-logs/update-batch', [AttendanceLogController::class, 'updateBatch'])
        ->name('attendance-logs.update-batch');
    Route::post('/payroll/update-batch', [PayrollSummaryController::class, 'updateBatch'])->name('payroll.update-batch');
    // Add this line inside your routes/web.php file
    Route::get('/payroll/bulk-payslip', [PayrollSummaryController::class, 'printBulkPayslips'])->name('payroll.bulk-payslip');

    Route::get('/skills/export-csv-template', function () {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="skills_template.csv"',
        ];
        $callback = function () {
            $file = fopen('php://output', 'w');
            // Add header row
            fputcsv($file, ['title', 'details']);
            // Add sample row (optional)
            fputcsv($file, ['Sample Skill Title', 'Sample skill details or description']);
            fclose($file);
        };
        return response()->stream($callback, 200, $headers);
    })->name('skills.export.csv');

    Route::get('/projects/export-csv-template', function () {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="projects_template.csv"',
        ];

        $callback = function () {
            $file = fopen('php://output', 'w');
            // Add header row
            fputcsv($file, ['project_code', 'name', 'datecovered', 'scope', 'address']);
            // Add sample row
            fputcsv($file, ['PRJ-001', 'BUILDING A PROJECT', 'JAN 2026 - DEC 2026', 'FULL CIVIL WORKS', '123 MAIN ST, QUEZON CITY']);
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    })->name('projects.export.csv');

    Route::get('/employees/export-csv-template', function () {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="employees_template.csv"',
        ];

        $callback = function () {
            $file = fopen('php://output', 'w');

            // CSV Header Columns
            fputcsv($file, [
                'employeeid',
                'firstname',
                'middlename',
                'lastname',
                'status',
                'mobile',
                'empstatus',
                'email',
                'birthdate',
                'sex',
                'address',
                'datehired',
                'dateseperated',
                'employeetype',
                'partners',
                'skill',
                'project',
            ]);

            // Sample Data Row
            fputcsv($file, [
                'EMP00001',
                'JUAN',
                'DELA',
                'CRUZ',
                '1',
                '9123456789',
                'REGULAR',
                'juan.cruz@example.com',
                '1990-01-15',
                'Male',
                '123 MAIN STREET, QUEZON CITY',
                '2024-01-01',
                '',
                'SUB-CON',
                'ABC CONTRACTING INC',
                'CARPENTRY',
                'PROJECT ALPHA',
            ]);

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    })->name('employees.export.csv');
});
