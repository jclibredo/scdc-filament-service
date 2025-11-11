<?php

use App\Models\DatePeriod;
use App\Models\Employee;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});


Route::get('/payslips/view/{id}', function ($id) {
    $datePeriod = DatePeriod::findOrFail($id);
    $type = $datePeriod->employeetype;
    $employees = Employee::whereHas('projectHistories', function ($q) use ($type) {
        $q->where('employeetype', $type)
            ->where('status', 1); // <-- only active project histories
    })
        ->with(['projectHistories' => function ($q) {
            $q->where('status', 1); // <-- only active project histories
            $q->with('project');    // eager load project
        }])
        ->get();
    return view('payslips.view', compact('employees', 'datePeriod'));
})->name('payslips.view');
