<?php

namespace App\Http\Controllers;

use App\Models\Atlog;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AttendanceLogController extends Controller
{
    public function destroyDayRaw(Request $request)
    {
        $request->validate([
            'employee_id' => 'required',
            'log_date'    => 'required|date'
        ]);
        $userId = $request->input('employee_id');
        $targetDate = Carbon::parse($request->input('log_date'))->format('Y-m-d');

        // 💡 Deletes all raw log records registered on this single calendar date
        DB::transaction(function () use ($userId, $targetDate) {
            Atlog::where('user_id', $userId)
                ->whereRaw('DATE(recorded_at) = ?', [$targetDate])
                ->delete();
        });

        return back()->with('success', 'Attendance log records removed for the selected date.');
    }
    public function storeDoubleRaw(Request $request)
    {
        // 💡 Read the limits passed securely from your form
        $minDate = $request->input('period_start_raw');
        $maxDate = $request->input('period_end_raw');

        // 1. Enforce safety validation checks
        $request->validate([
            'employee_id'  => 'required',
            'log_date'     => [
                'required',
                'date',
                'after_or_equal:' . $minDate,
                'before_or_equal:' . $maxDate,
            ],
            'time_in'      => 'required',
            'time_out'     => 'required',
            'project_code' => 'nullable|string'
        ], [
            'log_date.after_or_equal'  => 'The log date cannot be earlier than the covered period start date.',
            'log_date.before_or_equal' => 'The log date cannot be later than the covered period cutoff date.',
        ]);

        // Note: The redundant duplicate $request->validate block has been removed from here ❌

        $dateStr = $request->input('log_date');
        $projectCode = $request->input('project_code', 'MAIN');
        $userId = $request->input('employee_id');

        // 2. Build explicit composite Carbon timestamp values
        $recordedAtTimeIn  = Carbon::parse($dateStr . ' ' . $request->input('time_in'));
        $recordedAtTimeOut = Carbon::parse($dateStr . ' ' . $request->input('time_out'));

        // 3. Database Transaction Injection (Creates 2 matching rows)
        DB::transaction(function () use ($userId, $projectCode, $recordedAtTimeIn, $recordedAtTimeOut) {

            // Entry Row 1: TIME IN Log
            Atlog::create([
                'user_id'           => $userId,
                'recorded_at'       => $recordedAtTimeIn,
                'status'            => 0,
                'verification_mode' => 0,
                'work_code'         => 0,
                'reserved'          => 0,
                'project_code'      => $projectCode,
            ]);

            // Entry Row 2: TIME OUT Log
            Atlog::create([
                'user_id'           => $userId,
                'recorded_at'       => $recordedAtTimeOut,
                'status'            => 0,
                'verification_mode' => 0,
                'work_code'         => 0,
                'reserved'          => 0,
                'project_code'      => $projectCode,
            ]);
        });

        // 4. Return user back with visual flash feedback confirmation message
        return back()->with('success', 'Time In and Time Out stamps logged successfully!');
    }
}
