<?php

use App\Http\Controllers\DeductionController;
use App\Models\DatePeriod;
use App\Models\Employee;
use App\Models\OtherDeductionLog;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});


Route::get('/payslips/view/{id}', function ($id) {
    $datePeriod = DatePeriod::findOrFail($id);
    $catgegory = $datePeriod->category->name;
    $type = $datePeriod->employeetype;
    // Group all deductions by employee_id
    $deductions = OtherDeductionLog::where('date_period_id', $id)
        ->with('otherDeduction') // ensure we can access the deduction title
        ->get()
        ->groupBy('employee_id');
    $employees = Employee::whereHas('projectHistories', function ($q) use ($type) {
        $q->where('employeetype', $type)
            ->where('status', 1);
    })
        ->with([
            'projectHistories' => function ($q) {
                $q->where('status', 1)->with('project');
            },
            'thirteenthMonth' => function ($q) use ($datePeriod) {
                $q->where('periodid', $datePeriod->id);
            }
        ])
        ->get();
    return view('payslips.view', compact('employees', 'datePeriod', 'deductions', 'type', 'catgegory'));
})->name('payslips.view');


// routes/web.php
Route::delete('/deduction/{id}', [DeductionController::class, 'destroy'])->name('deduction.destroy');

// Route::get('/employee-deductions/{employee}/{datePeriod}', [DeductionController::class, 'getDeductions'])
//     ->name('employee.deductions');