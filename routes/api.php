<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Exception;

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
        // Log the error on the cloud server for debugging
        \Illuminate\Support\Facades\Log::error('Cloud API Sync Error: ' . $e->getMessage());

        // Return a clean error message back to the local caller
        return response()->json([
            'success' => false,
            'message' => 'Server error during sync: ' . $e->getMessage()
        ], 500);
    }
});
