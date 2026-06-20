<?php

use App\Http\Controllers\AttendanceLogController;
use App\Http\Controllers\DeductionController;
use App\Http\Controllers\PayrollSummaryController;
// use App\Models\DatePeriod;
// use App\Models\Employee;
use App\Models\OtherDeductionLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
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
});
