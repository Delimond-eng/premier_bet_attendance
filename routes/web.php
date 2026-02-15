<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\HRController;
use App\Http\Controllers\PlanningController;
use App\Http\Controllers\PresenceController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes - SALAMA ATTENDANCE
|--------------------------------------------------------------------------
*/

Auth::routes();

Route::get('/schedules.verify', function () {
    return response()->json(['status' => 'success']);
})->name('schedules.verify');

Route::middleware(['auth'])->group(function () {

    // Generic delete endpoint used by some Vue scripts (kept, but protected).
    Route::post('/table/delete', [AdminController::class, 'triggerDelete'])
        ->name('table.delete')
        ->middleware('canany:stations.delete,agents.delete,horaires.delete,groupes.delete,plannings.delete,conges.delete,attributions.delete,authorizations.delete,justifications.delete,users.delete,roles.delete');

    // Dashboard
    Route::get('/', [HomeController::class, 'index'])
        ->name('dashboard')
        ->middleware('can:dashboard_admin.view');
    Route::get('/dashboard/stats', [AdminController::class, 'getDashboardData'])
        ->name('dashboard.stats')
        ->middleware('can:dashboard_admin.view');

    // Stations
    Route::prefix('stations')->name('stations.')->group(function () {
        Route::get('/view', fn () => view('stations'))
            ->name('view')
            ->middleware('can:stations.view');
        Route::get('/list', [AdminController::class, 'viewAllSites'])
            ->name('list')
            ->middleware('can:stations.view');
        Route::post('/store', [AdminController::class, 'createAgencieSite'])
            ->name('create')
            ->middleware('canany:stations.create,stations.update');
        Route::get('/qrcodes', [AdminController::class, 'generateSiteQrcodes'])
            ->name('qrcode')
            ->middleware('can:stations.export');
    });

    // Agents
    Route::prefix('agents')->name('agents.')->group(function () {
        Route::get('/view', fn () => view('agents', ['sites' => \App\Models\Station::all()]))
            ->name('view')
            ->middleware('can:agents.view');

        Route::get('/view/attendances', function () {
            return view('agent_attendance', [
                'agent' => null,
                'sites' => \App\Models\Station::all(),
            ]);
        })->name('view.attendances')->middleware('can:agents.view');

        Route::get('/data', [AdminController::class, 'fetchAgents'])
            ->name('data')
            ->middleware('can:agents.view');
        Route::post('/store', [AdminController::class, 'createAgent'])
            ->name('store')
            ->middleware('canany:agents.create,agents.update');
        Route::get('/attendances/summary', [PresenceController::class, 'agentAttendanceSummary'])
            ->name('attendances.summary')
            ->middleware('can:agents.view');
        Route::get('/attendances/history', [PresenceController::class, 'agentHistory'])
            ->name('attendances.history')
            ->middleware('can:agents.view');
        Route::get('/export/pdf', [ExportController::class, 'agentsPdf'])
            ->name('export.pdf')
            ->middleware('can:agents.export');
        Route::get('/export/excel', [ExportController::class, 'agentsExcel'])
            ->name('export.excel')
            ->middleware('can:agents.export');
    });

    // RH - Horaires / Groupes / Plannings
    Route::prefix('rh')->name('rh.')->group(function () {
        Route::get('/horaires.view', fn () => view('horaires'))
            ->name('horaires.view')
            ->middleware('can:horaires.view');
        Route::get('/groupes.view', fn () => view('groupes'))
            ->name('groupes.view')
            ->middleware('can:groupes.view');
        Route::get('/plannings.view', fn () => view('plannings'))
            ->name('plannings.view')
            ->middleware('can:plannings.view');

        Route::get('/horaires', [PresenceController::class, 'getAllHoraires'])
            ->name('horaires.data')
            ->middleware('can:horaires.view');
        Route::get('/horaires/export/pdf', [ExportController::class, 'horairesPdf'])
            ->name('horaires.export.pdf')
            ->middleware('can:horaires.export');
        Route::get('/horaires/export/excel', [ExportController::class, 'horairesExcel'])
            ->name('horaires.export.excel')
            ->middleware('can:horaires.export');
        Route::post('/horaire/store', [PresenceController::class, 'createHoraire'])
            ->name('horaire.store')
            ->middleware('canany:horaires.create,horaires.update');

        Route::get('/groups', [PresenceController::class, 'getAllGroups'])
            ->name('groups.data')
            ->middleware('can:groupes.view');
        Route::post('/group/store', [PresenceController::class, 'createGroup'])
            ->name('group.store')
            ->middleware('canany:groupes.create,groupes.update');

        Route::post('/generate', [PlanningController::class, 'generateMonthlyPlanning'])
            ->name('planning.generate')
            ->middleware('can:plannings.create');
        Route::get('/planning/week', [PlanningController::class, 'getStationWeeklyPlanning'])
            ->name('planning.week')
            ->middleware('can:plannings.view');
        Route::post('/planning/import-week', [PlanningController::class, 'importWeeklyPlanning'])
            ->name('planning.import_week')
            ->middleware('can:plannings.import');

        // RH - Timesheet / Conges / Authorizations / Justifications / Attributions
        Route::get('/timesheet.view', fn () => view('rh_timesheet'))
            ->name('timesheet.view')
            ->middleware('can:timesheet.view');
        Route::get('/conges.view', fn () => view('rh_conges'))
            ->name('conges.view')
            ->middleware('can:conges.view');
        Route::get('/attributions.view', fn () => view('rh_attributions'))
            ->name('attributions.view')
            ->middleware('can:attributions.view');
        Route::get('/authorizations.view', fn () => view('rh_authorizations'))
            ->name('authorizations.view')
            ->middleware('can:authorizations.view');
        Route::get('/justifications.retard.view', fn () => view('rh_justifications_retard'))
            ->name('justifications.retard.view')
            ->middleware('can:justifications.view');
        Route::get('/justifications.absence.view', fn () => view('rh_justifications_absence'))
            ->name('justifications.absence.view')
            ->middleware('can:justifications.view');

        Route::get('/conges', [HRController::class, 'congesIndex'])
            ->name('conges.index')
            ->middleware('can:conges.view');
        Route::post('/conges/store', [HRController::class, 'congesStore'])
            ->name('conges.store')
            ->middleware('canany:conges.create,conges.update');
        Route::post('/conges/delete', [HRController::class, 'congesDelete'])
            ->name('conges.delete')
            ->middleware('can:conges.delete');

        Route::get('/conges/reference', [HRController::class, 'referenceData'])
            ->name('conges.reference')
            ->middleware('canany:conges.view,attributions.view,authorizations.view,justifications.view');

        Route::get('/authorizations', [HRController::class, 'authorizationsIndex'])
            ->name('authorizations.index')
            ->middleware('can:authorizations.view');
        Route::post('/authorizations/store', [HRController::class, 'authorizationsStore'])
            ->name('authorizations.store')
            ->middleware('canany:authorizations.create,authorizations.update');
        Route::post('/authorizations/delete', [HRController::class, 'authorizationsDelete'])
            ->name('authorizations.delete')
            ->middleware('can:authorizations.delete');

        Route::get('/justifications', [HRController::class, 'justificationsIndex'])
            ->name('justifications.index')
            ->middleware('can:justifications.view');
        Route::post('/justifications/store', [HRController::class, 'justificationsStore'])
            ->name('justifications.store')
            ->middleware('canany:justifications.create,justifications.update');
        Route::post('/justifications/delete', [HRController::class, 'justificationsDelete'])
            ->name('justifications.delete')
            ->middleware('can:justifications.delete');

        Route::get('/attributions', [HRController::class, 'attributionsIndex'])
            ->name('attributions.index')
            ->middleware('can:attributions.view');
        Route::post('/attributions/store', [HRController::class, 'attributionsStore'])
            ->name('attributions.store')
            ->middleware('canany:attributions.create,attributions.update');
        Route::post('/attributions/delete', [HRController::class, 'attributionsDelete'])
            ->name('attributions.delete')
            ->middleware('can:attributions.delete');

        Route::get('/timesheet/monthly', [HRController::class, 'monthlyTimesheet'])
            ->name('timesheet.monthly')
            ->middleware('can:timesheet.view');
        Route::get('/timesheet/export/pdf', [ExportController::class, 'timesheetMonthlyPdf'])
            ->name('timesheet.export.pdf')
            ->middleware('can:timesheet.export');
        Route::get('/timesheet/export/excel', [ExportController::class, 'timesheetMonthlyExcel'])
            ->name('timesheet.export.excel')
            ->middleware('can:timesheet.export');
    });

    // Presences (web journal)
    Route::prefix('presences')->group(function () {
        Route::get('/live', fn () => view('attendances'))
            ->name('presences.live')
            ->middleware('can:presences.view');
        Route::get('/data', [PresenceController::class, 'getPresencesBySiteAndDate'])
            ->name('presences.data')
            ->middleware('can:presences.view');
        Route::post('/store', [PresenceController::class, 'createPresenceAgent'])
            ->name('presence.store')
            ->middleware('can:presences.create');
        Route::get('/export/pdf', [ExportController::class, 'attendancesPdf'])
            ->name('presences.export.pdf')
            ->middleware('can:presences.export');
        Route::get('/export/excel', [ExportController::class, 'attendancesExcel'])
            ->name('presences.export.excel')
            ->middleware('can:presences.export');
    });

    // Reports
    Route::prefix('reports')->group(function () {
        Route::get('/daily', fn () => view('report_presences'))
            ->name('reports.presences')
            ->middleware('can:rapport_presences.view');
        Route::get('/absences/daily', fn () => view('report_absences_daily'))
            ->name('reports.absences.daily.view')
            ->middleware('can:rapport_absences.view');
        Route::get('/weekly', fn () => view('report_presences_weekly'))
            ->name('reports.weekly.view')
            ->middleware('can:rapport_presences.view');
        Route::get('/monthly/view', fn () => view('report_presences_monthly'))
            ->name('reports.monthly.view')
            ->middleware('can:rapport_presences.view');

        Route::get('/daily/data', [PresenceController::class, 'dailyReport'])
            ->name('reports.daily.data')
            ->middleware('can:rapport_presences.view');
        Route::get('/absences/daily/data', [PresenceController::class, 'dailyAbsenceReport'])
            ->name('reports.absences.daily.data')
            ->middleware('can:rapport_absences.view');
        Route::get('/weekly/data', [PresenceController::class, 'weeklyReport'])
            ->name('reports.weekly.data')
            ->middleware('can:rapport_presences.view');
        Route::get('/monthly', [PresenceController::class, 'monthlyReport'])
            ->name('reports.monthly')
            ->middleware('can:rapport_presences.view');

        Route::get('/daily/export/pdf', [ExportController::class, 'dailyPresencesPdf'])
            ->name('reports.daily.export.pdf')
            ->middleware('can:rapport_presences.export');
        Route::get('/daily/export/excel', [ExportController::class, 'dailyPresencesExcel'])
            ->name('reports.daily.export.excel')
            ->middleware('can:rapport_presences.export');
        Route::get('/absences/daily/export/pdf', [ExportController::class, 'absencesDailyPdf'])
            ->name('reports.absences.daily.export.pdf')
            ->middleware('can:rapport_absences.export');
        Route::get('/absences/daily/export/excel', [ExportController::class, 'absencesDailyExcel'])
            ->name('reports.absences.daily.export.excel')
            ->middleware('can:rapport_absences.export');
        Route::get('/weekly/export/pdf', [ExportController::class, 'weeklyPresenceSummaryPdf'])
            ->name('reports.weekly.export.pdf')
            ->middleware('can:rapport_presences.export');
        Route::get('/weekly/export/excel', [ExportController::class, 'weeklyPresenceSummaryExcel'])
            ->name('reports.weekly.export.excel')
            ->middleware('can:rapport_presences.export');
        Route::get('/monthly/export/pdf', [ExportController::class, 'monthlyPresenceSummaryPdf'])
            ->name('reports.monthly.export.pdf')
            ->middleware('can:rapport_presences.export');
        Route::get('/monthly/export/excel', [ExportController::class, 'monthlyPresenceSummaryExcel'])
            ->name('reports.monthly.export.excel')
            ->middleware('can:rapport_presences.export');
    });

    // Admin pages
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('/users', fn () => view('users'))
            ->name('users')
            ->middleware('can:users.view');
        Route::get('/roles', fn () => view('roles'))
            ->name('roles')
            ->middleware('can:roles.view');
        Route::get('/logs', fn () => view('logs'))
            ->name('logs')
            ->middleware('can:logs.view');
    });

    // Users/Roles management APIs (Vue)
    Route::get('/actions', [UserController::class, 'getActions'])
        ->name('actions')
        ->middleware('can:roles.view');
    Route::post('/role/create', [UserController::class, 'createOrUpdateRole'])
        ->name('role.create')
        ->middleware('canany:roles.create,roles.update');
    Route::get('/roles/all', [UserController::class, 'getAllRoles'])
        ->name('roles.all')
        ->middleware('can:roles.view');
    Route::post('/user/create', [UserController::class, 'createOrUpdateUser'])
        ->name('user.create')
        ->middleware('canany:users.create,users.update');
    Route::get('/users/all', [UserController::class, 'getAllUsers'])
        ->name('users.all')
        ->middleware('can:users.view');
    Route::post('/user/access', [UserController::class, 'attributeAccess'])
        ->name('user.access')
        ->middleware('can:users.update');
});
