<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;


Route::post('/sync-attendance', function (Request $request) {
    $logs = $request->input('logs', []);
    $syncedCount = 0;

    foreach ($logs as $log) {
        DB::table('atlogs')->updateOrInsert(
            [
                'user_id'     => $log['user_id'],
                'recorded_at' => $log['recorded_at'],
            ],
            [
                'project_code'      => $log['project_code'] ?? 0,
                'status'            => $log['status'],
                'verification_mode' => $log['verification_mode'],
                'work_code'         => $log['work_code'] ?? 0,
                'reserved'          => $log['reserved'] ?? 0,
                'created_at'        => $log['created_at'] ?? now(),
                'updated_at'        => now(),
            ]
        );
        $syncedCount++;
    }

    return response()->json([
        'success' => true,
        'message' => "Successfully synced {$syncedCount} records."
    ]);
});
