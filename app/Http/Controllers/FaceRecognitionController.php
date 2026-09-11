<?php

namespace App\Http\Controllers;

use App\Models\Atlog;
use App\Models\Employee;
use App\Models\FacialProfile;
use Carbon\Carbon;
use Illuminate\Http\Request;

class FaceRecognitionController extends Controller
{
    public function show(string $employeeId)
    {
        // Find employee or 404
        $employee = Employee::where('employeeid', $employeeId)
            ->orWhere('id', $employeeId)
            ->firstOrFail();

        // Get profile if exists (don't use firstOrFail here so unregistered employees can load the registration page!)
        $profile = FacialProfile::where('employee_id', $employee->id)->first();
        return view('face-recognition', [
            'employee' => $employee,
            'profile'  => $profile,
        ]);
    }

    // Register or update facial descriptor for an Employee
    public function register(Request $request)
    {
        $request->validate([
            'employee_id'     => 'required|exists:employees,id',
            'face_descriptor' => 'required|array',
        ]);

        $profile = FacialProfile::updateOrCreate(
            ['employee_id' => $request->employee_id],
            ['face_descriptor' => $request->face_descriptor]
        );

        return response()->json([
            'status'  => 'success',
            'message' => 'Facial profile registered for employee successfully.',
            'data'    => $profile->load('employee'),
        ]);
    }

    // Identify face against stored profiles
    public function identify(Request $request)
    {
        $request->validate([
            'face_descriptor' => 'required|array',
        ]);
        $incomingVector = $request->face_descriptor;
        $profiles = FacialProfile::with('employee')->get();
        $bestMatch = null;
        // Lower threshold from 0.6 to 0.45 for accurate matching
        $lowestDistance = 0.45;
        foreach ($profiles as $profile) {
            $storedDescriptor = is_string($profile->face_descriptor)
                ? json_decode($profile->face_descriptor, true)
                : $profile->face_descriptor;
            if (!$storedDescriptor || !is_array($storedDescriptor) || count($storedDescriptor) !== 128) {
                continue;
            }
            $distance = $this->euclideanDistance($incomingVector, $storedDescriptor);

            if ($distance < $lowestDistance) {
                $lowestDistance = $distance;
                $bestMatch = $profile;
            }
        }
        if ($bestMatch) {
            return response()->json([
                'status'      => 'match',
                'person_name' => $bestMatch->employee->firstname . ' ' . $bestMatch->employee->lastname,
                'employee'    => $bestMatch->employee,
                'distance'    => round($lowestDistance, 4),
            ]);
        }

        return response()->json([
            'status'  => 'unknown',
            'message' => 'No matching employee found.',
        ], 200);
    }

    private function euclideanDistance(array $vectorA, array $vectorB): float
    {
        $sum = 0.0;
        foreach ($vectorA as $i => $val) {
            if (isset($vectorB[$i])) {
                $sum += pow((float)$val - (float)$vectorB[$i], 2);
            }
        }
        return sqrt($sum);
    }

    public function logAttendance(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'type'        => 'required|in:time_in,check_in,break_out,break_in,time_out,check_out',
            'logged_at'   => 'nullable|date_format:Y-m-d H:i:s',
        ]);

        $employee = Employee::findOrFail($request->input('employee_id'));
        $type = $request->input('type');

        // Map requested string type to numerical verification_mode
        $verificationModeMap = [
            'time_in'   => 0,
            'check_in'  => 0,
            'time_out'  => 1,
            'check_out' => 1,
            'break_out' => 2,
            'break_in'  => 3,
        ];

        $verificationMode = $verificationModeMap[$type] ?? 0;

        // Readable label for UI feedback response
        $labels = [
            0 => 'CHECK-IN',
            1 => 'CHECK-OUT',
            2 => 'BREAK OUT',
            3 => 'BREAK IN',
        ];
        $readableType = $labels[$verificationMode];

        // Determine timestamp in Asia/Manila timezone
        $recordedAt = $request->filled('logged_at')
            ? Carbon::createFromFormat('Y-m-d H:i:s', $request->input('logged_at'), 'Asia/Manila')
            : Carbon::now('Asia/Manila');

        // Save attendance entry to Atlog database table
        Atlog::create([
            'user_id'           => $employee->employeeid,
            'project_code'      => $employee->project_id ?? $employee->project_code ?? 0,
            'recorded_at'       => $recordedAt,
            'status'            => 5, // 5 => Face recognition
            'verification_mode' => $verificationMode, // 0 => Check-In, 1 => Check-Out, 2 => Break Out, 3 => Break In
            'work_code'         => $request->input('work_code', 0),
            'reserved'          => 0,
        ]);

        return response()->json([
            'status'  => 'success',
            'type'    => $type,
            'message' => "Successfully registered {$readableType} for Employee ID: {$employee->employeeid}."
        ], 200);
    }
}
