<?php

use App\Http\Controllers\AttendanceLogController;
use App\Http\Controllers\DeductionController;
use App\Http\Controllers\PayrollSummaryController;
use App\Models\DatePeriod;
use App\Models\Employee;
use App\Models\OtherDeductionLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});


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




// routes/web.php
Route::delete('/deduction/{id}', [DeductionController::class, 'destroy'])->name('deduction.destroy');

// Route::get('/employee-deductions/{employee}/{datePeriod}', [DeductionController::class, 'getDeductions'])
//     ->name('employee.deductions');

Route::get('/payroll-summary/{employee_id}/{period_code}', [PayrollSummaryController::class, 'show'])
    ->name('payroll.summary');

Route::post('/attendance-logs/store-double', [AttendanceLogController::class, 'storeDoubleRaw'])
    ->name('attendance-logs.store-double');

Route::delete('/attendance-logs/destroy-day', [AttendanceLogController::class, 'destroyDayRaw'])
    ->name('attendance-logs.destroy-day');

// Route::middleware(['auth'])->group(function () {
// Route::get('/payroll/process-sheet/{payroll_processing_employees}/{periodcode}', [PayrollSummaryController::class, 'showSheet'])
//     ->name('payroll.process-sheet');

Route::get('/payroll/process-sheet/{periodcode}', [PayrollSummaryController::class, 'showSheet'])
    ->name('payroll.process-sheet');
// });


Route::put('/attendance-logs/update-batch', [AttendanceLogController::class, 'updateBatch'])
    ->name('attendance-logs.update-batch');
