<?php

namespace App\Http\Controllers;

use App\Models\Atlog;
use App\Models\Employee;
use App\Models\FacialProfile;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class FaceRecognitionController extends Controller
{
    public function show()
    {
        $employees = Employee::doesntHave('facialProfile')
            ->orderBy('lastname')
            ->get();
        return view('face-registration', compact('employees'));
    }


    public function getVerify()
    {
        return view('face-verify');
    }

    // Register or update facial descriptor for an Employee
    public function store(Request $request)
    {
        try {
            $request->validate([
                'employee_id' => 'required',
                'face_descriptor' => 'required|array',
            ]);

            $newDescriptor = $request->face_descriptor;
            $employeeId = trim($request->employee_id);

            // 1. Verify target employee exists
            $employee = Employee::where('employeeid', $employeeId)->first();
            if (!$employee) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Employee not found.'
                ], 404);
            }

            // 2. Check if this employee ID already has a profile
            if (FacialProfile::where('employee_id', $employee->employeeid)->exists()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'This employee already has a registered face.'
                ], 422);
            }

            // 3. FACIAL MATCH CHECK: Compare captured face against ALL saved faces
            $existingProfiles = FacialProfile::with('employee')->get();
            $threshold = 0.6; // face-api.js standard distance threshold (lower = stricter)

            foreach ($existingProfiles as $profile) {
                $savedDescriptor = $profile->face_descriptor;

                if (is_array($savedDescriptor) && count($savedDescriptor) === count($newDescriptor)) {
                    $distance = $this->euclideanDistance($newDescriptor, $savedDescriptor);

                    if ($distance < $threshold) {
                        $matchedEmployee = $profile->employee;
                        $matchedName = $matchedEmployee
                            ? $matchedEmployee->firstname . ' ' . $matchedEmployee->lastname
                            : 'ID #' . $profile->employee_id;

                        return response()->json([
                            'status' => 'error',
                            'message' => 'Face rejected: This face is already registered to ' . $matchedName . ' (ID: ' . $profile->employee_id . ').'
                        ], 422);
                    }
                }
            }

            // 4. Save new registration if no face matches found
            FacialProfile::create([
                'employee_id' => $employee->employeeid,
                'face_descriptor' => $newDescriptor,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Face registered successfully for ' . $employee->firstname . ' ' . $employee->lastname
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
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
    // private function euclideanDistance(array $vectorA, array $vectorB): float
    // {
    //     $sum = 0.0;
    //     foreach ($vectorA as $i => $val) {
    //         if (isset($vectorB[$i])) {
    //             $sum += pow((float)$val - (float)$vectorB[$i], 2);
    //         }
    //     }
    //     return sqrt($sum);
    // }
    private function euclideanDistance(array $arr1, array $arr2): float
    {
        $sum = 0.0;
        for ($i = 0; $i < count($arr1); $i++) {
            $diff = $arr1[$i] - $arr2[$i];
            $sum += $diff * $diff;
        }
        return sqrt($sum);
    }

    // public function logAttendance(Request $request)
    // {

    //     // Log::info('Incoming Attendance Request:', $request->all());
    //     try {
    //         // 1. Explicitly target the 'employeeid' column in the validation check
    //         $request->validate([
    //             'employee_id' => 'required',
    //             'type'        => 'required',
    //             'logged_at'   => 'nullable|date_format:Y-m-d H:i:s',
    //         ]);

    //         // 2. Query by 'employeeid' column instead of primary key
    //         $employee = Employee::where('employeeid', $request->input('employee_id'))->first();

    //         if (!$employee) {
    //             return response()->json([
    //                 'status'  => 'error',
    //                 'message' => 'Employee not found.'
    //             ], 404);
    //         }

    //         $type = $request->input('type');

    //         // Map request type to numerical verification mode
    //         $verificationModeMap = [
    //             'time_in'   => 0,
    //             'check_in'  => 0,
    //             'time_out'  => 1,
    //             'check_out' => 1,
    //             'break_out' => 2,
    //             'break_in'  => 3,
    //         ];

    //         $verificationMode = $verificationModeMap[$type] ?? 0;

    //         $labels = [
    //             0 => 'CHECK-IN',
    //             1 => 'CHECK-OUT',
    //             2 => 'BREAK OUT',
    //             3 => 'BREAK IN',
    //         ];
    //         $readableType = $labels[$verificationMode];

    //         // Format timestamp safely
    //         $recordedAt = $request->filled('logged_at')
    //             ? Carbon::createFromFormat('Y-m-d H:i:s', $request->input('logged_at'), 'Asia/Manila')->format('Y-m-d H:i:s')
    //             : Carbon::now('Asia/Manila')->format('Y-m-d H:i:s');

    //         // Record attendance log
    //         Atlog::create([
    //             'user_id'           => $employee->employeeid,
    //             'project_code'      => $employee->project_id ?? 0,
    //             'recorded_at'       => $recordedAt,
    //             'status'            => 5, // 5 = Face recognition
    //             'verification_mode' => $verificationMode,
    //             'work_code'         => $request->input('work_code', 0),
    //             'reserved'          => 0,
    //         ]);

    //         return response()->json([
    //             'status'  => 'success',
    //             'type'    => $type,
    //             'message' => "Successfully registered {$readableType} for Employee ID: {$employee->employeeid}."
    //         ], 200);
    //     } catch (\Illuminate\Validation\ValidationException $e) {
    //         return response()->json([
    //             'status'  => 'error',
    //             'message' => collect($e->errors())->flatten()->first()
    //         ], 422);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'status'  => 'error',
    //             'message' => 'Database error: ' . $e->getMessage()
    //         ], 500);
    //     }
    // }
    public function logAttendance(Request $request)
    {
        try {
            $request->validate([
                'employee_id' => 'required',
                'type'        => 'required|in:time_in,check_in,time_out,check_out,break_out,break_in',
                'logged_at'   => 'nullable|date_format:Y-m-d H:i:s',
            ]);

            $employee = Employee::where('employeeid', $request->input('employee_id'))->first();

            if (!$employee) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Employee not found.'
                ], 404);
            }

            $type = $request->input('type');

            $verificationModeMap = [
                'time_in'   => 0,
                'check_in'  => 0,
                'time_out'  => 1,
                'check_out' => 1,
                'break_out' => 2,
                'break_in'  => 3,
            ];

            $verificationMode = $verificationModeMap[$type] ?? 0;

            $labels = [
                0 => 'CHECK-IN',
                1 => 'CHECK-OUT',
                2 => 'BREAK OUT',
                3 => 'BREAK IN',
            ];
            $readableType = $labels[$verificationMode];

            $recordedAt = $request->filled('logged_at')
                ? Carbon::createFromFormat('Y-m-d H:i:s', $request->input('logged_at'), 'Asia/Manila')->format('Y-m-d H:i:s')
                : Carbon::now('Asia/Manila')->format('Y-m-d H:i:s');

            // Prevent rapid duplicate submissions from face detection loops (5-second atomic lock per employee & type)
            $lockKey = "attendance_lock_{$employee->employeeid}_{$verificationMode}";
            $lock = Cache::lock($lockKey, 5);

            if (!$lock->get()) {
                return response()->json([
                    'status'  => 'ignored',
                    'message' => 'Duplicate request ignored (currently processing).'
                ], 200);
            }

            try {
                // Record attendance log
                $log = Atlog::create([
                    'user_id'           => $employee->employeeid,
                    'project_code'      => $employee->project_id ?? 0,
                    'recorded_at'       => $recordedAt,
                    'status'            => 5, // 5 = Face recognition
                    'verification_mode' => $verificationMode,
                    'work_code'         => $request->input('work_code', 0),
                    'reserved'          => 0,
                ]);

                return response()->json([
                    'status'  => 'success',
                    'type'    => $type,
                    'message' => "Successfully registered {$readableType} for Employee ID: {$employee->employeeid}."
                ], 200);
            } finally {
                // Optional: keep lock active or release safely
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => collect($e->errors())->flatten()->first()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Database error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Helper method to create Atlog entries cleanly.
     */

    public function checkRegistration(string $employeeid)
    {
        // Search explicitly by employeeid column
        $employee = Employee::where('employeeid', $employeeid)->first();

        if (!$employee) {
            return response()->json(['is_registered' => false, 'message' => 'Employee not found'], 404);
        }

        $exists = FacialProfile::where('employee_id', $employee->employeeid)->exists();

        return response()->json([
            'is_registered' => $exists
        ]);
    }
}
