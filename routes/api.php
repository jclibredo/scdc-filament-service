<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
// use Exception;


// Helper function to validate the Bearer token
function verify_api_token(Request $request)
{
    $token = $request->bearerToken();
    $expectedToken = config('services.cloud.token', env('CLOUD_API_TOKEN'));

    return $token && hash_equals($expectedToken, $token);
}

// Safe helper function declaration
if (!function_exists('verify_api_token')) {
    function verify_api_token(Request $request)
    {
        $token = $request->bearerToken();
        $expectedToken = config('services.cloud.token', env('CLOUD_API_TOKEN'));

        return $token && hash_equals($expectedToken, $token);
    }
}
// --- MASTER FETCH ALL ENDPOINT ---
Route::get('/fetch-all-cloud-data', function (Request $request) {
    if (!verify_api_token($request)) {
        return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);
    }

    try {
        return response()->json([
            'success'  => true,
            'logs'     => DB::table('attendance_logs')->get(),
            'skills'   => DB::table('skills')->get(),
            'projects' => DB::table('projects')->get(),
            'categories' => DB::table('categories')->get(),
            'gov_deductions'   => DB::table('gov_deductions')->get(),
            'holidays'         => DB::table('holidays')->get(),
            'other_deductions' => DB::table('other_deductions')->get(),
        ], 200);
    } catch (\Exception $e) {
        return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
    }
});

// --- MASTER SYNC ALL ENDPOINT ---
Route::post('/sync-all', function (Request $request) {
    if (!verify_api_token($request)) {
        return response()->json(['success' => false, 'message' => 'Unauthorized. Invalid or missing token.'], 401);
    }

    try {
        $summary = [];

        // 1. Sync Attendance Logs
        $logs = $request->input('logs', []);
        if (!empty($logs)) {
            $logCount = 0;
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
                $logCount++;
            }
            $summary[] = "{$logCount} logs";
        }

        // 2. Sync Skills
        $skills = $request->input('skills', []);
        if (!empty($skills)) {
            $skillCount = 0;
            foreach ($skills as $skill) {
                $createdAt = isset($skill['created_at']) ? Carbon::parse($skill['created_at'])->format('Y-m-d H:i:s') : now();
                $updatedAt = isset($skill['updated_at']) ? Carbon::parse($skill['updated_at'])->format('Y-m-d H:i:s') : now();

                DB::table('skills')->updateOrInsert(
                    ['title' => $skill['title']],
                    [
                        'details'    => $skill['details'] ?? null,
                        'status'     => $skill['status'] ?? true,
                        'created_at' => $createdAt,
                        'updated_at' => $updatedAt,
                    ]
                );
                $skillCount++;
            }
            $summary[] = "{$skillCount} skills";
        }

        // 3. Sync Projects
        $projects = $request->input('projects', []);
        if (!empty($projects)) {
            $projectCount = 0;
            foreach ($projects as $project) {
                $createdAt = isset($project['created_at']) ? Carbon::parse($project['created_at'])->format('Y-m-d H:i:s') : now();
                $updatedAt = isset($project['updated_at']) ? Carbon::parse($project['updated_at'])->format('Y-m-d H:i:s') : now();

                DB::table('projects')->updateOrInsert(
                    ['project_code' => $project['project_code']],
                    [
                        'name'         => $project['name'] ?? null,
                        'datecovered'  => $project['datecovered'] ?? null,
                        'scope'        => $project['scope'] ?? null,
                        'address'      => $project['address'] ?? null,
                        'image'        => $project['image'] ?? null,
                        'status'       => $project['status'] ?? true,
                        'created_at'   => $createdAt,
                        'updated_at'   => $updatedAt,
                    ]
                );
                $projectCount++;
            }
            $summary[] = "{$projectCount} projects";
        }

        // 4. Sync Categories (🟢 Added)
        $categories = $request->input('categories', []);
        if (!empty($categories)) {
            $catCount = 0;
            foreach ($categories as $category) {
                $createdAt = isset($category['created_at']) ? Carbon::parse($category['created_at'])->format('Y-m-d H:i:s') : now();
                $updatedAt = isset($category['updated_at']) ? Carbon::parse($category['updated_at'])->format('Y-m-d H:i:s') : now();

                DB::table('categories')->updateOrInsert(
                    ['cat' => $category['cat'] ?? $category['name']],
                    [
                        'name'        => $category['name'] ?? null,
                        'description' => $category['description'] ?? null,
                        'status'      => $category['status'] ?? true,
                        'created_at'  => $createdAt,
                        'updated_at'  => $updatedAt,
                    ]
                );
                $catCount++;
            }
            $summary[] = "{$catCount} categories";
        }

        // 5. Sync Gov Deductions (🟢 Added)
        $govDeductions = $request->input('gov_deductions', []);
        if (!empty($govDeductions)) {
            $govCount = 0;
            foreach ($govDeductions as $gov) {
                $createdAt = isset($gov['created_at']) ? Carbon::parse($gov['created_at'])->format('Y-m-d H:i:s') : now();
                $updatedAt = isset($gov['updated_at']) ? Carbon::parse($gov['updated_at'])->format('Y-m-d H:i:s') : now();

                DB::table('gov_deductions')->updateOrInsert(
                    ['title' => $gov['title']],
                    [
                        'date_started' => $gov['date_started'] ?? null,
                        'date_ended'   => $gov['date_ended'] ?? null,
                        'amount'       => $gov['amount'] ?? 0,
                        'status'       => $gov['status'] ?? true,
                        'created_at'   => $createdAt,
                        'updated_at'   => $updatedAt,
                    ]
                );
                $govCount++;
            }
            $summary[] = "{$govCount} gov deductions";
        }

        // 6. Sync Holidays (🟢 Added)
        $holidays = $request->input('holidays', []);
        if (!empty($holidays)) {
            $holidayCount = 0;
            foreach ($holidays as $holiday) {
                $createdAt = isset($holiday['created_at']) ? Carbon::parse($holiday['created_at'])->format('Y-m-d H:i:s') : now();
                $updatedAt = isset($holiday['updated_at']) ? Carbon::parse($holiday['updated_at'])->format('Y-m-d H:i:s') : now();

                DB::table('holidays')->updateOrInsert(
                    ['type' => $holiday['type']],
                    [
                        'percentage' => $holiday['percentage'] ?? 0,
                        'details'    => $holiday['details'] ?? null,
                        'status'     => $holiday['status'] ?? true,
                        'created_at' => $createdAt,
                        'updated_at' => $updatedAt,
                    ]
                );
                $holidayCount++;
            }
            $summary[] = "{$holidayCount} holidays";
        }

        // 7. Sync Other Deductions (🟢 Added)
        $otherDeductions = $request->input('other_deductions', []);
        if (!empty($otherDeductions)) {
            $otherCount = 0;
            foreach ($otherDeductions as $other) {
                $createdAt = isset($other['created_at']) ? Carbon::parse($other['created_at'])->format('Y-m-d H:i:s') : now();
                $updatedAt = isset($other['updated_at']) ? Carbon::parse($other['updated_at'])->format('Y-m-d H:i:s') : now();

                DB::table('other_deductions')->updateOrInsert(
                    ['title' => $other['title']],
                    [
                        'description' => $other['description'] ?? null,
                        'status'      => $other['status'] ?? true,
                        'created_at'  => $createdAt,
                        'updated_at'  => $updatedAt,
                    ]
                );
                $otherCount++;
            }
            $summary[] = "{$otherCount} other deductions";
        }

        if (empty($summary)) {
            return response()->json(['success' => false, 'message' => 'No data provided for synchronization.'], 400);
        }

        $message = "Successfully synchronized: " . implode(', ', $summary) . ".";
        return response()->json(['success' => true, 'message' => $message], 200);
    } catch (\Exception $e) {
        // \Illuminate\Support\Facades\Log::error('Sync All Error: ' . $e->getMessage());
        return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
    }
});

// // --- ATTENDANCE LOGS ---
// Route::post('/sync-attendance', function (Request $request) {
//     if (!verify_api_token($request)) {
//         return response()->json(['success' => false, 'message' => 'Unauthorized. Invalid or missing token.'], 401);
//     }

//     try {
//         $logs = $request->input('logs', []);
//         if (empty($logs)) {
//             return response()->json(['success' => false, 'message' => 'No logs provided for synchronization.'], 400);
//         }

//         $syncedCount = 0;
//         foreach ($logs as $log) {
//             $recordedAt = isset($log['recorded_at']) ? Carbon::parse($log['recorded_at'])->format('Y-m-d H:i:s') : now();
//             $createdAt  = isset($log['created_at']) ? Carbon::parse($log['created_at'])->format('Y-m-d H:i:s') : now();

//             DB::table('attendance_logs')->updateOrInsert(
//                 [
//                     'user_id'     => $log['user_id'],
//                     'recorded_at' => $recordedAt,
//                 ],
//                 [
//                     'project_code'      => $log['project_code'] ?? 0,
//                     'status'            => $log['status'],
//                     'verification_mode' => $log['verification_mode'],
//                     'work_code'         => $log['work_code'] ?? 0,
//                     'reserved'          => $log['reserved'] ?? 0,
//                     'created_at'        => $createdAt,
//                     'updated_at'        => now()->format('Y-m-d H:i:s'),
//                 ]
//             );
//             $syncedCount++;
//         }

//         return response()->json(['success' => true, 'message' => "Successfully synchronized {$syncedCount} records."], 200);
//     } catch (Exception $e) {
//         \Illuminate\Support\Facades\Log::error('Sync Attendance Error: ' . $e->getMessage());
//         return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
//     }
// });

// Route::get('/fetch-cloud-attendance', function (Request $request) {
//     if (!verify_api_token($request)) {
//         return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);
//     }

//     try {
//         $cloudLogs = DB::table('attendance_logs')->get();
//         return response()->json(['success' => true, 'logs' => $cloudLogs], 200);
//     } catch (Exception $e) {
//         return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
//     }
// });

// // --- SKILLS ---
// Route::post('/sync-skills', function (Request $request) {
//     if (!verify_api_token($request)) {
//         return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);
//     }

//     try {
//         $skills = $request->input('skills', []);
//         if (empty($skills)) {
//             return response()->json(['success' => false, 'message' => 'No skills provided.'], 400);
//         }
//         $syncedCount = 0;
//         foreach ($skills as $skill) {
//             $createdAt = isset($skill['created_at']) ? Carbon::parse($skill['created_at'])->format('Y-m-d H:i:s') : now();
//             $updatedAt = isset($skill['updated_at']) ? Carbon::parse($skill['updated_at'])->format('Y-m-d H:i:s') : now();

//             DB::table('skills')->updateOrInsert(
//                 ['title' => $skill['title']],
//                 [
//                     'details'    => $skill['details'] ?? null,
//                     'status'     => $skill['status'] ?? true,
//                     'created_at' => $createdAt,
//                     'updated_at' => $updatedAt,
//                 ]
//             );
//             $syncedCount++;
//         }
//         return response()->json(['success' => true, 'message' => "Successfully synchronized {$syncedCount} skills."], 200);
//     } catch (\Exception $e) {
//         return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
//     }
// });

// Route::get('/fetch-cloud-skills', function (Request $request) {
//     if (!verify_api_token($request)) {
//         return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);
//     }

//     try {
//         $skills = DB::table('skills')->get();
//         return response()->json(['success' => true, 'skills' => $skills], 200);
//     } catch (Exception $e) {
//         return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
//     }
// });

// // --- PROJECTS ---
// Route::post('/sync-projects', function (Request $request) {
//     if (!verify_api_token($request)) {
//         return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);
//     }

//     try {
//         $projects = $request->input('projects', []);
//         if (empty($projects)) {
//             return response()->json(['success' => false, 'message' => 'No projects provided.'], 400);
//         }
//         $syncedCount = 0;
//         foreach ($projects as $project) {
//             $createdAt = isset($project['created_at']) ? Carbon::parse($project['created_at'])->format('Y-m-d H:i:s') : now();
//             $updatedAt = isset($project['updated_at']) ? Carbon::parse($project['updated_at'])->format('Y-m-d H:i:s') : now();

//             DB::table('projects')->updateOrInsert(
//                 ['project_code' => $project['project_code']],
//                 [
//                     'name'         => $project['name'] ?? null,
//                     'datecovered'  => $project['datecovered'] ?? null,
//                     'scope'        => $project['scope'] ?? null,
//                     'address'      => $project['address'] ?? null,
//                     'image'        => $project['image'] ?? null,
//                     'status'       => $project['status'] ?? true,
//                     'created_at'   => $createdAt,
//                     'updated_at'   => $updatedAt,
//                 ]
//             );
//             $syncedCount++;
//         }
//         return response()->json(['success' => true, 'message' => "Successfully synchronized {$syncedCount} projects."], 200);
//     } catch (\Exception $e) {
//         return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
//     }
// });

// Route::get('/fetch-cloud-projects', function (Request $request) {
//     if (!verify_api_token($request)) {
//         return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);
//     }

//     try {
//         $projects = DB::table('projects')->get();
//         return response()->json(['success' => true, 'projects' => $projects], 200);
//     } catch (Exception $e) {
//         return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
//     }
// });
