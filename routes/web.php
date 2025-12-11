<?php

use App\Http\Controllers\DeductionController;
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
    $deductions = OtherDeductionLog::where('date_period_id', $id)
        ->with('otherDeduction')
        ->get()
        ->groupBy('employee_id');
    $SumDeduction = $deductions->collapse()->sum('amount');
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

    return view('payslips.view', compact('employees', 'datePeriod', 'deductions', 'type', 'category', 'SumDeduction'));
})->name('payslips.view');




// routes/web.php
Route::delete('/deduction/{id}', [DeductionController::class, 'destroy'])->name('deduction.destroy');

// Route::get('/employee-deductions/{employee}/{datePeriod}', [DeductionController::class, 'getDeductions'])
//     ->name('employee.deductions');