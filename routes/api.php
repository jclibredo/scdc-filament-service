<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Exception;


Route::middleware('auth:sanctum')->group(function () {
    //ATTENDANCE LOGS
    Route::post('/sync-attendance', function (Request $request) {
        try {
            $logs = $request->input('logs', []);

            if (empty($logs)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No logs provided for synchronization.'
                ], 400);
            }

            $syncedCount = 0;

            foreach ($logs as $log) {
                // Safely parse dates to match MySQL datetime format
                $recordedAt = isset($log['recorded_at']) ? Carbon::parse($log['recorded_at'])->format('Y-m-d H:i:s') : now();
                $createdAt  = isset($log['created_at']) ? Carbon::parse($log['created_at'])->format('Y-m-d H:i:s') : now();

                // Insert or update using the correct 'attendance_logs' table name
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

            return response()->json([
                'success' => true,
                'message' => "Successfully synchronized {$syncedCount} records to the cloud database."
            ], 200);
        } catch (Exception $e) {
            // Log the error on the cloud server for tracking
            \Illuminate\Support\Facades\Log::error('Cloud API Sync Error: ' . $e->getMessage());

            // Return a clean error response to the local kiosk
            return response()->json([
                'success' => false,
                'message' => 'Server error during sync: ' . $e->getMessage()
            ], 500);
        }
    });
    Route::get('/fetch-cloud-attendance', function (Request $request) {
        try {
            // Fetch all cloud logs (or filter by ?since=timestamp if you want to optimize later)
            $cloudLogs = DB::table('attendance_logs')->get();
            return response()->json([
                'success' => true,
                'logs' => $cloudLogs
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch cloud logs: ' . $e->getMessage()
            ], 500);
        }
    });
    //SKILLS
    // 1. Push Route (Local Skills to Cloud)
    Route::post('/sync-skills', function (Request $request) {
        try {
            $skills = $request->input('skills', []);
            if (empty($skills)) {
                return response()->json(['success' => false, 'message' => 'No skills provided.'], 400);
            }
            $syncedCount = 0;
            foreach ($skills as $skill) {
                DB::table('skills')->updateOrInsert(
                    ['title' => $skill['title']], // Unique identifier
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
    // 2. Pull Route (Cloud Skills to Local)
    Route::get('/fetch-cloud-skills', function () {
        try {
            $skills = DB::table('skills')->get();
            return response()->json(['success' => true, 'skills' => $skills], 200);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    });
    //PROJECTS
    // 1. Push Route (Local Projects to Cloud)
    Route::post('/sync-projects', function (Request $request) {
        try {
            $projects = $request->input('projects', []);
            if (empty($projects)) {
                return response()->json(['success' => false, 'message' => 'No projects provided.'], 400);
            }
            $syncedCount = 0;
            foreach ($projects as $project) {
                DB::table('projects')->updateOrInsert(
                    ['project_code' => $project['project_code']], // Unique identifier
                    [
                        'name'         => $project['name'] ?? null,
                        'datecovered'  => $project['datecovered'] ?? null,
                        'scope'        => $project['scope'] ?? null,
                        'address'      => $project['address'] ?? null,
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
    // 2. Pull Route (Cloud Projects to Local)
    Route::get('/fetch-cloud-projects', function () {
        try {
            $projects = DB::table('projects')->get();
            return response()->json(['success' => true, 'projects' => $projects], 200);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    });
});
