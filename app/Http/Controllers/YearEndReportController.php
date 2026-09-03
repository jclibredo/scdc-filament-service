<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\ThirteenthMonth;
use App\Models\YearEndReport;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;

class YearEndReportController extends Controller
{
    // public function printReport(Request $request)
    // {
    //     // 1. Retrieve selected employee primary keys or IDs passed from Filament
    //     $selectedEmployeeIds = explode(',', $request->query('ids', ''));

    //     // 2. Fetch the active date period
    //     $datePeriodData = YearEndReport::where('code', session('session_yearendreportspid'))
    //         ->where('status', true)
    //         ->first();

    //     if (! $datePeriodData) {
    //         $employees = collect();
    //     } else {
    //         // Build base query
    //         $query = Employee::query()
    //             ->whereIn('id', $selectedEmployeeIds) // Filter by Filament selection
    //             ->where('status', true)
    //             ->where('datehired', '<=', $datePeriodData->datefrom);

    //         if (session('session_employeestatusid') && session('session_employeetypeid')) {
    //             $query->where('empstatus', session('session_employeestatusid'))
    //                 ->where('employeetype', session('session_employeetypeid'));
    //         }

    //         $partnerSession = session('session_partnersid');
    //         if ($partnerSession && $partnerSession !== 'ALL') {
    //             $query->where('partners', $partnerSession);
    //         }

    //         $sessionProject = session('session_projectid');
    //         if ($sessionProject !== null) {
    //             $query->where('project_id', $sessionProject);
    //         }

    //         // Load employees alongside their 13th Month monthly breakdowns
    //         $employees = $query->with(['thirteenthMonths' => function ($q) {
    //             $q->where('yearendcode', session('session_yearendreportspid'));
    //         }])
    //             ->orderBy('lastname')
    //             ->get();
    //     }

    //     return view('payroll.process_yearend', compact('employees', 'datePeriodData'));
    // }
    public function printReport(Request $request)
    {
        $selectedIds = array_filter(explode(',', $request->query('ids', '')));

        // 1. Fetch active period meta
        $period = YearEndReport::where('code', session('session_yearendreportspid'))
            ->where('status', true)
            ->first();

        if (! $period) {
            return redirect()->back()->withErrors(['period' => 'Invalid or inactive Year-End Report Period.']);
        }

        $periodCode = $period->code;
        // 2. Query filtered employees based on session filters and selected IDs
        $query = Employee::query()
            ->whereIn('id', $selectedIds)
            ->where('status', true)
            ->where('datehired', '<=', $period->datefrom);

        if (session('session_employeestatusid') && session('session_employeetypeid')) {
            $query->where('empstatus', session('session_employeestatusid'))
                ->where('employeetype', session('session_employeetypeid'));
        }
        $partnerSession = session('session_partnersid');
        if ($partnerSession && $partnerSession !== 'ALL') {
            $query->where('partners', $partnerSession);
        }
        $sessionProject = session('session_projectid');
        if ($sessionProject !== null && $sessionProject !== 'ALL') {
            $query->where('project_id', $sessionProject);
        }
        // dd($query->get());
        $employees = $query->with([
            'thirteenthMonths' => function ($q) use ($periodCode) {
                $q->where('yearendrepid', $periodCode);
            },
            'adjustmentData' => function ($q) use ($periodCode) {
                $q->where('date_period_id', $periodCode)
                    ->with('adjustmentName');
            },
            'otherdeductionData' => function ($q) use ($periodCode) {
                $q->where('date_period_id', $periodCode)
                    ->with('otherDeduction');
            },
            'govdeductionData' => function ($q) use ($periodCode) {
                $q->where('date_period_id', $periodCode);
            },
        ])
            ->orderBy('lastname')
            ->get();
        // 4. Fetch all 13th month records for the period to build distinct sub-period columns
        $allThirteenthRecords = ThirteenthMonth::where('yearendrepid', $periodCode)->get();

        // 5. Structure Months and Cutoffs dynamically
        $monthsStructure = [];
        $startDate = Carbon::parse($period->datefrom)->startOfMonth();
        $endDate = Carbon::parse($period->dateto)->endOfMonth();

        $datePeriod = CarbonPeriod::create($startDate, '1 month', $endDate);

        foreach ($datePeriod as $monthDate) {
            $mKey   = $monthDate->format('Y-m');
            $mStart = $monthDate->copy()->startOfMonth()->format('Y-m-d');
            $mEnd   = $monthDate->copy()->endOfMonth()->format('Y-m-d');

            // Filter cutoffs for this month and format dates cleanly
            $cutoffs = $allThirteenthRecords->filter(function ($item) use ($mStart, $mEnd) {
                if (! $item->datestart) return false;
                $start = Carbon::parse($item->datestart)->format('Y-m-d');
                return ($start >= $mStart && $start <= $mEnd);
            })
                ->sortBy('datestart')
                ->unique(function ($item) {
                    $s = Carbon::parse($item->datestart)->format('Y-m-d');
                    $e = Carbon::parse($item->dateend)->format('Y-m-d');
                    return $s . '_' . $e;
                })
                ->values();

            $monthsStructure[$mKey] = [
                'name'    => $monthDate->format('M'),
                'cutoffs' => $cutoffs->map(function ($c) {
                    $startFormatted = Carbon::parse($c->datestart)->format('Y-m-d');
                    $endFormatted   = Carbon::parse($c->dateend)->format('Y-m-d');

                    $sDay = Carbon::parse($c->datestart)->format('j');
                    $eDay = Carbon::parse($c->dateend)->format('j');

                    return [
                        'label'     => "{$sDay} - {$eDay}",
                        'datestart' => $startFormatted,
                        'dateend'   => $endFormatted,
                    ];
                })->toArray()
            ];
        }

        // // 4. Dynamic Months Mapping using $period->datefrom and $period->dateto
        // $monthsList = [];
        // $startDate = Carbon::parse($period->datefrom)->startOfMonth();
        // $endDate = Carbon::parse($period->dateto)->startOfMonth();

        // $datePeriod = CarbonPeriod::create($startDate, '1 month', $endDate);

        // foreach ($datePeriod as $date) {
        //     // Example output: ['01' => 'Jan', '02' => 'Feb', ...] 
        //     // Or if it crosses fiscal years across multi-years: ['2025-01' => 'Jan 2025']
        //     $monthsList[$date->format('m')] = $date->format('M');
        // }
        return view('payroll.process_yearend', compact('employees', 'period', 'monthsStructure'));
    }
}
