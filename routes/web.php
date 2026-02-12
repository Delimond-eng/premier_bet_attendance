<?php

use App\Http\Controllers\UserController;
use Illuminate\Routing\ViewController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PresenceController;
use App\Http\Controllers\PlanningController;
use App\Http\Controllers\ConfigController;

/*
|--------------------------------------------------------------------------
| Web Routes - SALAMA ATTENDANCE (Global Management)
|--------------------------------------------------------------------------
*/

Auth::routes();

Route::middleware(['auth'])->group(function () {

    // 1️⃣ TABLEAU DE BORD (DASHBOARD)
    Route::get('/', [HomeController::class, 'index'])->name('dashboard');
    Route::get("/dashboard/stats", [AdminController::class, "getDashboardData"])->name("dashboard.stats");

    // 2️⃣ GESTION DES STATIONS
    Route::prefix('stations')->name('stations.')->group(function () {
        Route::get('/view', function() { return view('stations'); })->name('view');
        Route::get('/list', [AdminController::class, 'viewAllSites'])->name('list');
        Route::post('/store', [AdminController::class, 'createAgencieSite'])->name('create');
        Route::get('/qrcodes', [AdminController::class, 'generateSiteQrcodes'])->name('qrcode');
    });

    // 3️⃣ GESTION DES AGENTS (RH)
    Route::prefix('agents')->name('agents.')->group(function () {
        Route::get('/view', function() {
            return view('agents', ['sites' => \App\Models\Station::all()]);
        })->name('view');

        Route::get('/view/attendances', function() {
            return view('agent_attendance', ['agent' => null]);
        })->name('view.attendances');

        Route::get('/data', [AdminController::class, 'fetchAgents'])->name('data');
        Route::post('/store', [AdminController::class, 'createAgent'])->name('store');
    });

    // 4️⃣ PLANIFICATION & ROTATIONS
    Route::prefix('rh')->name('rh.')->group(function () {
        Route::get("/horaires.view", fn() => view("horaires"))->name("horaires.view");
        Route::get("/groupes.view", fn() => view("groupes"))->name("groupes.view");
        Route::get("/plannings.view", fn() => view("plannings"))->name("plannings.view");
        Route::get('/horaires', [PresenceController::class, 'getAllHoraires'])->name('horaires.data');
        Route::post('/generate', [PlanningController::class, 'generateMonthlyPlanning'])->name('planning.generate');
        Route::post('/horaire/store', [PresenceController::class, 'createHoraire'])->name('horaire.store');
    });

    // 5️⃣ POINTAGES & PRÉSENCES (OPÉRATIONNEL)
    Route::prefix('presences')->group(function () {
        Route::get('/live', function() { return view('attendances'); })->name('presences.live');
        Route::get('/data', [PresenceController::class, 'getPresencesBySiteAndDate'])->name('presences.data');
        Route::post('/store', [PresenceController::class, 'createPresenceAgent'])->name('presence.store');
    });

    // 6️⃣ RAPPORTS & ANALYSE
    Route::prefix('reports')->group(function () {
        Route::get('/daily', function() { return view('report_presences'); })->name('reports.presences');
        Route::get('/monthly', [PresenceController::class, 'monthlyReport'])->name('reports.monthly');
        Route::get('/export/pdf', [AdminController::class, 'exportPresenceReport'])->name('reports.export.presence');
    });

    // 7️⃣ ADMINISTRATION & SÉCURITÉ
    Route::prefix('admin')->name("admin.")->group(function () {
        Route::get('/users', function() { return view('users'); })->name('users');
        Route::get('/roles', function() { return view('roles'); })->name('roles');
    });


    //=============================User permissions manage route begin=====================================//
    Route::get("/actions", [UserController::class, "getActions"])->name("actions");
    Route::post("/role/create", [UserController::class, "createOrUpdateRole"])->name("role.create")->middleware("can:roles.create");
    Route::get("/roles/all", [UserController::class, "getAllRoles"])->name("roles.all")->middleware("can:roles.view");
    Route::post("/user/create", [UserController::class, "createOrUpdateUser"])->name("user.create")->middleware("can:users.create");
    Route::get("/users/all", [UserController::class, "getAllUsers"])->name("users.all")->middleware("can:users.view");
    Route::post("/user/access", [UserController::class, "attributeAccess"])->name("user.create")->middleware("can:users.update");

});
