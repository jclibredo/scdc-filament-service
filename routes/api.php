<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Exception;




// Safe helper function declaration
if (!function_exists('verify_api_token')) {
    function verify_api_token(Request $request)
    {
        $token = $request->bearerToken();
        $expectedToken = config('services.cloud.token', env('CLOUD_API_TOKEN'));

        return $token && hash_equals($expectedToken, $token);
    }
}

// --- ATTENDANCE LOGS ---
Route::post('/sync-attendance', function (Request $request) {
    if (!verify_api_token($request)) {
        return response()->json(['success' => false, 'message' => 'Unauthorized. Invalid or missing token.'], 401);
    }

    try {
        $logs = $request->input('logs', []);
        if (empty($logs)) {
            return response()->json(['success' => false, 'message' => 'No logs provided for synchronization.'], 400);
        }

        $syncedCount = 0;
        foreach ($logs as $log) {
            $recordedAt = isset($log['recorded_at']) ? Carbon::parse($log['recorded_at'])->format('Y-m-d H:i:s') : now();
            $createdAt  = isset($log['created_at']) ? Carbon::parse($log['created_at'])->format('Y-m-d H:i:s') : now();

            DB::table('attendance_logs')->updateOrInsert(
                [
                    'user_id'     => $log['user_id'],
                    'recorded_at' => $recordedAt,
                ],
                [
                    'project_code'      => $log['project_code'] ?? 0,
                    'status'            => $log['status'],
                    'verification_mode' => $log['verification_mode'],
                    'work_code'         => $log['work_code'] ?? 0,
                    'reserved'          => $log['reserved'] ?? 0,
                    'created_at'        => $createdAt,
                    'updated_at'        => now()->format('Y-m-d H:i:s'),
                ]
            );
            $syncedCount++;
        }

        return response()->json(['success' => true, 'message' => "Successfully synchronized {$syncedCount} records."], 200);
    } catch (Exception $e) {
        \Illuminate\Support\Facades\Log::error('Sync Attendance Error: ' . $e->getMessage());
        return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
    }
});

Route::get('/fetch-cloud-attendance', function (Request $request) {
    if (!verify_api_token($request)) {
        return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);
    }

    try {
        $cloudLogs = DB::table('attendance_logs')->get();
        return response()->json(['success' => true, 'logs' => $cloudLogs], 200);
    } catch (Exception $e) {
        return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
    }
});

// --- SKILLS ---
Route::post('/sync-skills', function (Request $request) {
    if (!verify_api_token($request)) {
        return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);
    }

    try {
        $skills = $request->input('skills', []);
        if (empty($skills)) {
            return response()->json(['success' => false, 'message' => 'No skills provided.'], 400);
        }
        $syncedCount = 0;
        foreach ($skills as $skill) {
            DB::table('skills')->updateOrInsert(
                ['title' => $skill['title']],
                [
                    'details'    => $skill['details'] ?? null,
                    'status'     => $skill['status'] ?? true,
                    'created_at' => $skill['created_at'] ?? now(),
                    'updated_at' => now()->format('Y-m-d H:i:s'),
                ]
            );
            $syncedCount++;
        }
        return response()->json(['success' => true, 'message' => "Successfully synchronized {$syncedCount} skills."], 200);
    } catch (Exception $e) {
        return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
    }
});

Route::get('/fetch-cloud-skills', function (Request $request) {
    if (!verify_api_token($request)) {
        return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);
    }

    try {
        $skills = DB::table('skills')->get();
        return response()->json(['success' => true, 'skills' => $skills], 200);
    } catch (Exception $e) {
        return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
    }
});

// --- PROJECTS ---
Route::post('/sync-projects', function (Request $request) {
    if (!verify_api_token($request)) {
        return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);
    }

    try {
        $projects = $request->input('projects', []);
        if (empty($projects)) {
            return response()->json(['success' => false, 'message' => 'No projects provided.'], 400);
        }
        $syncedCount = 0;
        foreach ($projects as $project) {
            DB::table('projects')->updateOrInsert(
                ['project_code' => $project['project_code']],
                [
                    'name'         => $project['name'] ?? null,
                    'datecovered'  => $project['datecovered'] ?? null,
                    'scope'        => $project['scope'] ?? null,
                    'address'      => $project['address'] ?? null,
                    'image'        => $project['image'] ?? null, // Added missing image column
                    'status'       => $project['status'] ?? true,
                    'created_at'   => $project['created_at'] ?? now(),
                    'updated_at'   => now()->format('Y-m-d H:i:s'),
                ]
            );
            $syncedCount++;
        }
        return response()->json(['success' => true, 'message' => "Successfully synchronized {$syncedCount} projects."], 200);
    } catch (Exception $e) {
        return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
    }
});

Route::get('/fetch-cloud-projects', function (Request $request) {
    if (!verify_api_token($request)) {
        return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);
    }

    try {
        $projects = DB::table('projects')->get();
        return response()->json(['success' => true, 'projects' => $projects], 200);
    } catch (Exception $e) {
        return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
    }
});
