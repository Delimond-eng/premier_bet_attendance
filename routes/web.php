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
use App\Http\Controllers\HRController;
use App\Http\Controllers\ExportController;

/*
|--------------------------------------------------------------------------
| Web Routes - SALAMA ATTENDANCE (Global Management)
|--------------------------------------------------------------------------
*/

Auth::routes();

Route::get('/schedules.verify', function () {
    return response()->json(['status' => 'success']);
})->name('schedules.verify');

Route::middleware(['auth'])->group(function () {

    // Route générique de suppression (utilisée par les scripts Vue)
    Route::post('/table/delete', [AdminController::class, 'triggerDelete'])->name('table.delete');

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
            return view('agent_attendance', [
                'agent' => null,
                'sites' => \App\Models\Station::all(),
            ]);
        })->name('view.attendances');

        Route::get('/data', [AdminController::class, 'fetchAgents'])->name('data');
        Route::post('/store', [AdminController::class, 'createAgent'])->name('store');
        Route::get('/attendances/summary', [PresenceController::class, 'agentAttendanceSummary'])->name('attendances.summary');
        Route::get('/attendances/history', [PresenceController::class, 'agentHistory'])->name('attendances.history');
        Route::get('/export/pdf', [ExportController::class, 'agentsPdf'])->name('export.pdf');
        Route::get('/export/excel', [ExportController::class, 'agentsExcel'])->name('export.excel');
    });

    // 4️⃣ PLANIFICATION & ROTATIONS
    Route::prefix('rh')->name('rh.')->group(function () {
        Route::get("/horaires.view", fn() => view("horaires"))->name("horaires.view");
        Route::get("/groupes.view", fn() => view("groupes"))->name("groupes.view");
        Route::get("/plannings.view", fn() => view("plannings"))->name("plannings.view");
        Route::get('/horaires', [PresenceController::class, 'getAllHoraires'])->name('horaires.data');
        Route::get('/horaires/export/pdf', [ExportController::class, 'horairesPdf'])->name('horaires.export.pdf');
        Route::get('/horaires/export/excel', [ExportController::class, 'horairesExcel'])->name('horaires.export.excel');
        Route::post('/generate', [PlanningController::class, 'generateMonthlyPlanning'])->name('planning.generate');
        Route::post('/horaire/store', [PresenceController::class, 'createHoraire'])->name('horaire.store');
        Route::get('/groups', [PresenceController::class, 'getAllGroups'])->name('groups.data');
        Route::post('/group/store', [PresenceController::class, 'createGroup'])->name('group.store');
        Route::get('/planning/week', [PlanningController::class, 'getStationWeeklyPlanning'])->name('planning.week');
        Route::post('/planning/import-week', [PlanningController::class, 'importWeeklyPlanning'])->name('planning.import_week');

        // RH - Congés / Autorisations / Justifications
        Route::get('/timesheet.view', fn () => view('rh_timesheet'))->name('timesheet.view');
        Route::get('/conges.view', fn () => view('rh_conges'))->name('conges.view');
        Route::get('/authorizations.view', fn () => view('rh_authorizations'))->name('authorizations.view');
        Route::get('/justifications.retard.view', fn () => view('rh_justifications_retard'))->name('justifications.retard.view');
        Route::get('/justifications.absence.view', fn () => view('rh_justifications_absence'))->name('justifications.absence.view');
        Route::get('/attributions.view', fn () => view('rh_attributions'))->name('attributions.view');

        Route::get('/conges', [HRController::class, 'congesIndex'])->name('conges.index');
        Route::post('/conges/store', [HRController::class, 'congesStore'])->name('conges.store');
        Route::post('/conges/delete', [HRController::class, 'congesDelete'])->name('conges.delete');
        Route::get('/conges/reference', [HRController::class, 'referenceData'])->name('conges.reference');

        Route::get('/authorizations', [HRController::class, 'authorizationsIndex'])->name('authorizations.index');
        Route::post('/authorizations/store', [HRController::class, 'authorizationsStore'])->name('authorizations.store');
        Route::post('/authorizations/delete', [HRController::class, 'authorizationsDelete'])->name('authorizations.delete');

        Route::get('/justifications', [HRController::class, 'justificationsIndex'])->name('justifications.index');
        Route::post('/justifications/store', [HRController::class, 'justificationsStore'])->name('justifications.store');
        Route::post('/justifications/delete', [HRController::class, 'justificationsDelete'])->name('justifications.delete');

        Route::get('/timesheet/monthly', [HRController::class, 'monthlyTimesheet'])->name('timesheet.monthly');
        Route::get('/timesheet/export/pdf', [ExportController::class, 'timesheetMonthlyPdf'])->name('timesheet.export.pdf');
        Route::get('/timesheet/export/excel', [ExportController::class, 'timesheetMonthlyExcel'])->name('timesheet.export.excel');
        Route::get('/attributions', [HRController::class, 'attributionsIndex'])->name('attributions.index');
        Route::post('/attributions/store', [HRController::class, 'attributionsStore'])->name('attributions.store');
        Route::post('/attributions/delete', [HRController::class, 'attributionsDelete'])->name('attributions.delete');
    });

    // 5️⃣ POINTAGES & PRÉSENCES (OPÉRATIONNEL)
    Route::prefix('presences')->group(function () {
        Route::get('/live', function() { return view('attendances'); })->name('presences.live');
        Route::get('/data', [PresenceController::class, 'getPresencesBySiteAndDate'])->name('presences.data');
        Route::post('/store', [PresenceController::class, 'createPresenceAgent'])->name('presence.store');
        Route::get('/export/pdf', [ExportController::class, 'attendancesPdf'])->name('presences.export.pdf');
        Route::get('/export/excel', [ExportController::class, 'attendancesExcel'])->name('presences.export.excel');
    });

    // 6️⃣ RAPPORTS & ANALYSE
    Route::prefix('reports')->group(function () {
        Route::get('/daily', function() { return view('report_presences'); })->name('reports.presences');
        Route::get('/absences/daily', function() { return view('report_absences_daily'); })->name('reports.absences.daily.view');
        Route::get('/weekly', function() { return view('report_presences_weekly'); })->name('reports.weekly.view');
        Route::get('/monthly/view', function() { return view('report_presences_monthly'); })->name('reports.monthly.view');
        Route::get('/daily/data', [PresenceController::class, 'dailyReport'])->name('reports.daily.data');
        Route::get('/daily/export/pdf', [ExportController::class, 'dailyPresencesPdf'])->name('reports.daily.export.pdf');
        Route::get('/daily/export/excel', [ExportController::class, 'dailyPresencesExcel'])->name('reports.daily.export.excel');
        Route::get('/absences/daily/data', [PresenceController::class, 'dailyAbsenceReport'])->name('reports.absences.daily.data');
        Route::get('/weekly/data', [PresenceController::class, 'weeklyReport'])->name('reports.weekly.data');
        Route::get('/monthly', [PresenceController::class, 'monthlyReport'])->name('reports.monthly');
        Route::get('/export/pdf', [AdminController::class, 'exportPresenceReport'])->name('reports.export.presence');
        Route::get('/absences/daily/export/pdf', [ExportController::class, 'absencesDailyPdf'])->name('reports.absences.daily.export.pdf');
        Route::get('/absences/daily/export/excel', [ExportController::class, 'absencesDailyExcel'])->name('reports.absences.daily.export.excel');
        Route::get('/weekly/export/pdf', [ExportController::class, 'weeklyPresenceSummaryPdf'])->name('reports.weekly.export.pdf');
        Route::get('/weekly/export/excel', [ExportController::class, 'weeklyPresenceSummaryExcel'])->name('reports.weekly.export.excel');
        Route::get('/monthly/export/pdf', [ExportController::class, 'monthlyPresenceSummaryPdf'])->name('reports.monthly.export.pdf');
        Route::get('/monthly/export/excel', [ExportController::class, 'monthlyPresenceSummaryExcel'])->name('reports.monthly.export.excel');
    });

    // 7️⃣ ADMINISTRATION & SÉCURITÉ
    Route::prefix('admin')->name("admin.")->group(function () {
        Route::get('/users', function() { return view('users'); })->name('users');
        Route::get('/roles', function() { return view('roles'); })->name('roles');
        Route::get('/logs', function() { return view('logs'); })->name('logs');
    });


    //=============================User permissions manage route begin=====================================//
    Route::get("/actions", [UserController::class, "getActions"])->name("actions");
    Route::post("/role/create", [UserController::class, "createOrUpdateRole"])->name("role.create")->middleware("can:roles.create");
    Route::get("/roles/all", [UserController::class, "getAllRoles"])->name("roles.all")->middleware("can:roles.view");
    Route::post("/user/create", [UserController::class, "createOrUpdateUser"])->name("user.create")->middleware("can:users.create");
    Route::get("/users/all", [UserController::class, "getAllUsers"])->name("users.all")->middleware("can:users.view");
    Route::post("/user/access", [UserController::class, "attributeAccess"])->name("user.create")->middleware("can:users.update");

});
