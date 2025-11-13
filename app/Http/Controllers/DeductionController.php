<?php

namespace App\Http\Controllers;

use App\Models\OtherDeductionLog;

class DeductionController extends Controller
{
    public function destroy($id)
    {
        $deduction = OtherDeductionLog::findOrFail($id);
        $deduction->delete();

        return redirect()->back()->with('success', 'Deduction removed successfully.');
    }

    public function getDeductions($employeeId, $datePeriodId)
    {
        $deductions = OtherDeductionLog::where('employee_id', $employeeId)
            ->where('date_period_id', $datePeriodId)
            ->with('otherDeduction')
            ->get();

        return response()->json($deductions);
    }
}