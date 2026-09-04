<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\IncentiveBonus;
use App\Models\YearEndReport;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
// use Maatwebsite\Excel\Facades\Excel;

class IncentiveBonusCSVController extends Controller
{

    public function export(Request $request)
    {
        // 1. Authenticated User Check
        $user = Auth::user();
        if (! $user || ! $user->id) {
            Notification::make()
                ->title('Export Failed')
                ->body('Unauthorized user session. Please log in again.')
                ->danger()
                ->send();

            return redirect()->back();
        }

        // 2. Extract Required Sessions
        $yearendid      = session('session_yearendreportspid');
        $sessionType    = session('session_employeetypeid');
        $sessionStatus  = session('session_employeestatusid');
        $sessionReptype = session('session_reptype');

        // 3. Validate Required Sessions & Send Notification on Failure
        $missingSessions = [];

        if (! $yearendid) {
            $missingSessions[] = 'Year-End Report Period';
        }
        if (! $sessionType) {
            $missingSessions[] = 'Employee Type';
        }
        if (! $sessionStatus) {
            $missingSessions[] = 'Employee Status';
        }
        if (! $sessionReptype) {
            $missingSessions[] = 'Report Type';
        }

        if (! empty($missingSessions)) {
            Notification::make()
                ->title('Export Failed: Missing Required Filters')
                ->body('Please set the following required session filter(s) before exporting: ' . implode(', ', $missingSessions) . '.')
                ->danger()
                ->send();

            return redirect()->back();
        }

        // 4. Fetch Active Date Period
        $datePeriodData = YearEndReport::where('code', $yearendid)
            ->where('status', true)
            ->first();

        if (! $datePeriodData) {
            Notification::make()
                ->title('Export Failed')
                ->body('Active Year-End Report period record was not found.')
                ->danger()
                ->send();

            return redirect()->back();
        }

        // 5. Build Query directly from Employee Table
        $query = Employee::query()
            ->where('status', true)
            ->where('datehired', '<=', $datePeriodData->datefrom)
            ->where('empstatus', $sessionStatus)
            ->where('employeetype', $sessionType);

        $partnerSession = session('session_partnersid');
        if ($partnerSession && $partnerSession !== 'ALL') {
            $query->where('partners', $partnerSession);
        }

        $project = session('session_projectid');
        if ($project && $project !== 'ALL') {
            $query->where('project_id', $project);
        }

        // Eager load using empStat (matches Employee model relationship name)
        $employees = $query->with(['empType', 'empStat', 'project'])
            ->orderBy('lastname')
            ->get();

        if ($employees->isEmpty()) {
            Notification::make()
                ->title('Export Warning')
                ->body('No employee records found matching your current filter criteria.')
                ->warning()
                ->send();

            return redirect()->back();
        }

        // 6. Prepare Metadata Headers
        $reportsLabel = ($sessionReptype === '13THMONTH') ? '13th Month Reports' : 'Incentives Reports';

        $startdate = $datePeriodData->datefrom
            ? Carbon::parse($datePeriodData->datefrom)->format('M d, Y')
            : 'N/A';

        $enddate = $datePeriodData->dateto
            ? Carbon::parse($datePeriodData->dateto)->format('M d, Y')
            : 'N/A';

        $filename = 'Incentive_Bonus_' . $yearendid . '_' . date('Ymd_His') . '.csv';

        // 7. Stream CSV Response
        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        return response()->stream(function () use ($employees, $reportsLabel, $startdate, $enddate) {
            $handle = fopen('php://output', 'w');

            // Write UTF-8 BOM for Microsoft Excel compatibility
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // Custom Metadata Top Header Rows
            fputcsv($handle, ["Rep. Type: {$reportsLabel}", "Date Covered: {$startdate} - {$enddate}"]);
            fputcsv($handle, []); // Blank Row Spacer

            // CSV Column Headers
            fputcsv($handle, ['employeeid', 'fullname', 'employeetype', 'employeestatus', 'project', 'amount']);

            // Data Rows directly from Employee table
            foreach ($employees as $employee) {
                $fullName    = trim("{$employee->lastname}, {$employee->firstname} {$employee->middlename}");
                $empType     = $employee->empType?->name ?? 'N/A';
                $empStatus   = $employee->empStat?->name ?? 'N/A';
                $projectName = $employee->project?->name ?? 'N/A';

                fputcsv($handle, [
                    $employee->employeeid,
                    $fullName,
                    $empType,
                    $empStatus,
                    $projectName,
                    '0.00',
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }

    public function showBreakdown(Request $request)
    {
        // Receive yearendid and employee IDs
        $yearendid = $request->input('yearendid');
        $employeeIds = $request->input('ids', []);

        $period = YearEndReport::where('code', $yearendid)->firstOrFail();

        $employees = Employee::query()
            ->when(!empty($employeeIds), fn($q) => $q->whereIn('employeeid', (array) $employeeIds))
            ->with([
                'incentiveBonus' => fn($q) => $q->where('yearendrepid', $yearendid),
                'adjustmentData' => fn($q) => $q->where('date_period_id', $yearendid),
                'govdeductionData' => fn($q) => $q->where('date_period_id', $yearendid),
                'otherdeductionData' => fn($q) => $q->where('date_period_id', $yearendid),
                'project',
            ])
            ->get();

        return view('payroll.incentivebonus', compact('employees', 'period', 'yearendid'));
    }
}
