<?php

use App\Models\DatePeriod;
use App\Models\Employee;
use App\Models\OtherDeductionLog;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});


Route::get('/payslips/view/{id}', function ($id) {
    $OtherDeductionLog = OtherDeductionLog::where('date_period_id', $id)->get();
    $datePeriod = DatePeriod::findOrFail($id);
    $type = $datePeriod->employeetype;
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
    return view('payslips.view', compact('employees', 'datePeriod', 'OtherDeductionLog'));
})->name('payslips.view');
