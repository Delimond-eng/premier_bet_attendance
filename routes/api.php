<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\PresenceController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Minimal API surface for mobile:
| - Scan station QR -> station data
| - Agent punch (check-in / check-out) using matricule as unique id
|
*/

Route::middleware(["cors"])->group(function () {
    Route::post('/station.scan', [PresenceController::class, 'scanStation'])->name('station.scan');
    Route::post('/agent.punch', [PresenceController::class, 'punchAgent'])->name('agent.punch');
    Route::post('/agent.enroll', [AdminController::class, 'enrollAgent'])->name('agent.enroll');
});

