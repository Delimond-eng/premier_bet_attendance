<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\PresenceController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\DeviceManagementController;
use App\Http\Controllers\Api\BiometricApiController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Minimal API surface for mobile:
| - Scan station QR -> station data
| - Agent punch (check-in / check-out / confirmation) using matricule as unique id
|
*/

Route::middleware(["cors"])->group(function () {
    Route::post('/station.scan', [PresenceController::class, 'scanStation'])->name('station.scan');
    Route::post('/agent.punch', [PresenceController::class, 'punchAgent'])->name('agent.punch');
    Route::post('/agent.enroll', [AdminController::class, 'enrollAgent'])->name('agent.enroll');

    // Terminal Tasks API
    Route::prefix('terminal')->group(function () {
        Route::get('/tasks', [TaskController::class, 'getTerminalTasks']);
        Route::post('/tasks/completion', [TaskController::class, 'completeTask']);
    });

    // Device & MDM Management
    Route::get('/devices/send-update', [DeviceManagementController::class, 'sendFcmUpdate']);
    Route::post('/devices/register', [BiometricApiController::class, 'registerDevice']);

    // Biometric Synchronization
    Route::post('/biometrics/by-matricules', [BiometricApiController::class, 'getEmbeddingsByMatricules']);
});
