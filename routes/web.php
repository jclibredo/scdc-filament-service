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
    $category = $datePeriod->category->name;
    $type = $datePeriod->employeetype;
    $projectId = $datePeriod->project_id ?? null; // if your DatePeriod has a project_id

    // Get deductions grouped by employee
    $deductions = OtherDeductionLog::where('date_period_id', $id)
        ->with('otherDeduction')
        ->get()
        ->groupBy('employee_id');

    // Fetch employees with direct columns
    $employeesQuery = Employee::query()
        ->where('status', 1)
        ->where('employeetype', $type);
    // Optional project filter if project_id is provided
    if ($projectId) {
        $employeesQuery->where('project_id', $projectId);
    }
    $employees = $employeesQuery
        ->with([
            'project', // eager load project
            'thirteenthMonth' => fn($q) => $q->where('periodid', $datePeriod->id),
        ])
        ->orderBy('lastname', 'asc')
        ->get();

    return view('payslips.view', compact('employees', 'datePeriod', 'deductions', 'type', 'category'));
})->name('payslips.view');




// routes/web.php
Route::delete('/deduction/{id}', [DeductionController::class, 'destroy'])->name('deduction.destroy');

// Route::get('/employee-deductions/{employee}/{datePeriod}', [DeductionController::class, 'getDeductions'])
//     ->name('employee.deductions');