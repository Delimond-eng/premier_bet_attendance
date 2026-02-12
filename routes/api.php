<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AppManagerController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\EmailController;
use App\Http\Controllers\FCMController;
use App\Http\Controllers\LogController;
use App\Http\Controllers\PresenceController;
use App\Http\Controllers\TalkieWalkieController;
use App\Models\Agent;
use App\Models\AgentGroupPlanning;
use App\Models\PresenceHoraire;
use App\Models\Site;
use App\Models\AgentGroup;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
Route::middleware(["geo.restricted.api","check.api.key","cors"])->group(function(){
    
    Route::post('/agent.create', [AdminController::class, 'createAgent'])->name('agent.create');

    Route::get('/dashboard.counts', [PresenceController::class, 'countDashboard'])->name('dashboard.counts');

    Route::get('/agents.all', function(Request $request){
        $agents = Agent::with("horaire")->where("site_id", $request->query("site_id"))->get();
        return response()->json([
            "status"=>"success",
            "agents"=>$agents
        ]);
    })->name('agent.create');

    Route::post('/horaire.create', [PresenceController::class, 'createHoraire'])->name('horaire.create');
    
    Route::post('/group.create', [PresenceController::class, 'createGroup'])->name('group.create');

    Route::get('/horaires', function(Request $request){
        return response()->json(["horaires"=>PresenceHoraire::where("site_id", $request->query("site_id"))->get()]);
    })->name('horaires');
    
    //pour la creation de presence agent
    Route::post('/presence.create', action: [PresenceController::class, 'createPresenceAgent'])->name('presence.create');
    //Enregistre la visit d'un superviseur au site programmé
    Route::get('/presences', [PresenceController::class, 'getPresencesBySiteAndDate'])->name('presences');
    
    Route::post('/table.delete', [AdminController::class, 'triggerDelete'])->name('table.delete');

    Route::get('/groups', [PresenceController::class, 'getAllGroups'])->name('groups');
    //donnees presence
    Route::post('/supervisor.visit.create', [PresenceController::class, 'createSupervisorSiteVisit'])->name('supervisor.visit.create');

    //ALLOW TO CREATE AGENCY
    Route::post("/agency.create", [AdminController::class, "createAgencie"])->name("agency.create");

    //ALLOW TO COMPLETE AREA WITH GPS DATA LATLNG
    Route::post("area.complete", [AdminController::class, "completeArea"])->name("area.complete");

    //Insert site token
    Route::post("site.token", [AdminController::class, "completeToken"])->name("site.token");

    // AGENT ENROLL PHOTO
    Route::post("agent.enroll", [AdminController::class, "enrollAgent"])->name("area.complete");

    //ALLOW TO MAKE PATROL SCAN RECORD
    Route::post("patrol.scan", [AppManagerController::class, "startPatrol"])->name("patrol.scan");

    //ALLOW TO CLOSE PATROL SCAN
    Route::post("patrol.close", [AppManagerController::class, "closePatrolTag"])->name("patrol.close");

    //ALLOW TO CREATE ROND 011
    Route::post("ronde.scan", [AppManagerController::class, "confirmRonde011"])->name("ronde.scan");

    //ALLOW TO VIEW PENDING PATROLS
    Route::get("/patrols.pending", [AppManagerController::class, "viewPendingPatrols"])->name("patrols.pending");
    
    //VIEW PENDING PATROLS BY SITE
    Route::get("/site.patrol.pending", [AppManagerController::class, "getPendingPatrol"])->name("site.patrol.pending");

    //ALLOW TO LOAD ALL ANNOUNCES FROM MOBILE APP
    Route::get("/announces.load", [AppManagerController::class, "loadAnnouncesFromMobile"])->name("announces.load");

    //ALLOW TO AUTHENTICATE AGENT
    Route::post("/agent.login", [AppManagerController::class, "loginAgent"])->name("agent.login");

    //ALLOW TO CREATE SIGNALEMENT
    Route::post("/signalement.create", [AppManagerController::class, "createSignalement"])->name("signalement.create");

    //ALLOW TO CREATE REQUEST
    Route::post("/request.create", [AppManagerController::class, "createRequest"])->name("request.create");

    //ALLOW TO CREATE AGENT PHONE LOG
    Route::post("/log.create", [LogController::class, 'createPhoneLog'])->name('log.create');

    //ALLOW TO GET ALL SCHEDULES
    Route::get("/schedules.all", [AppManagerController::class, "viewAllSchedulesByApp"])->name("schedules.all");

    Route::post('/horaire.create', [PresenceController::class, 'createHoraire'])->name('horaire.create');
    
    //Emettre sur un canal de talkie walkie
    Route::post('/send.talk', [TalkieWalkieController::class, 'sendTalkAudio']);

    Route::get("/patrols.reports", [AppManagerController::class, "viewPatrolReports"])->name("patrols.reports");
    //horaires
    

    Route::get("/sites", function () {
        $agencyId = Auth::user()->agency_id ?? 1;
        $sites = Site::where("agency_id", $agencyId)
            ->with([
                "areas" => function ($query) {
                    return $query->where("status", "actif");
            }])
            ->get();
        return response()->json([
            "sites" => $sites
        ]);
    });

    Route::get("/patrols.pending", [AppManagerController::class, "viewPendingPatrols"])->name("patrols.pending");

    Route::post("/send.mail", [EmailController::class, "sendMail"])->name("send.mail");
    
    Route::post("/send.notication", [FCMController::class, "sendNotification"])->name("send.notification");

    // GET SUPERVISOR DATAS
    Route::get("/supervisor.datas", [AppManagerController::class, "getSupervisorDatas"])->name("supervisor.datas");

    //CLIENT LOGIN
    Route::post("/client.login", [ClientController::class, "loginClient"])->name("client.login");

    //CLIENT VERIFY OTP
    Route::post("/client.otp", [ClientController::class, "verifyOtp"])->name("client.opt");

    //VIEW CLIENT PENDING PATROL
    Route::get("/client.patrol.pending", [ClientController::class, "getPendingPatrol"])->name("client.patrol.pending");

    //VIEW PATROL HISTORIES
    Route::get("/client.patrol.histories", [ClientController::class, "getPatrolHistories"])->name("client.patrol.histories");

    //VIEW CLIENT AGENTS PRESENT
    Route::get("/client.agents.presence", [ClientController::class, "getAgentPresences"])->name("client.agents.presence");

    Route::get("/sup.reports", [AdminController::class, "getDashboardData"])->name("sup.reports");
    //UPDATE CLIENT TOKEN 
    Route::post("/client.token", [ClientController::class, "updateFcmToken"])->name("client.token");
});

Route::get("/check.update", function(){
    return response()->json([
        'version_code' => 3,
        'apk_url' => url('/apks/app.apk'),
        'changelog' => "- Ajout des nouvelles fonctionnalités \n- Correction des bugs \n- Interface améliorée"
    ]);
});

 Route::get('/monthly.report', [PresenceController::class, 'monthlyReport']);

