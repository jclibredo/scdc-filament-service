<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class pdfController extends Controller
{
    public function generatePayslip($employeeId)
    {
        // Fetch employee data from DB
        $employee = Employee::findOrFail($employeeId);

        // Ensure all string fields are UTF-8
        $employee = collect($employee->toArray())->map(function ($value) {
            return is_string($value) ? utf8_encode($value) : $value;
        });

        // Month (optional)
        $month = now()->format('F Y');

        // Render Blade to HTML
        $html = view('payslips.view', [
            'employee' => $employee,
            'month' => $month
        ])->render();

        // Ensure UTF-8
        $html = mb_convert_encoding($html, 'UTF-8', 'UTF-8');

        // Generate PDF
        $pdf = Pdf::loadHTML($html)
            ->setPaper('a4', 'portrait');

        return $pdf->stream('payslip.pdf');
    }
}
