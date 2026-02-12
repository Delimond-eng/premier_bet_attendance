<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\Station;
use App\Models\User;
use App\Models\PresenceAgents;
use App\Models\PresenceHoraire;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

/**
 * Class AdminController
 * Gère l'administration des stations, des agents et des utilisateurs.
 * Optimisé pour une gestion mono-agence (Time Attendance).
 */
class AdminController extends Controller
{
    /**
     * Crée ou met à jour une Station.
     */
    public function createAgencieSite(Request $request): JsonResponse
    {
        try {
            $data = $request->validate([
                "id" => "nullable|integer",
                "name" => "required|string",
                "code" => "required|string|unique:sites,code," . ($request->id ?? 'NULL'),
                "latlng" => "nullable|string",
                "adresse" => "required|string",
                "phone" => "nullable|string",
                "presence" => "nullable|integer",
            ]);

            $station = Station::updateOrCreate(['id' => $request->id], $data);

            if (!$request->id) {
                $this->createDefaultSchedules($station->id);
            }

            return response()->json(["status" => "success", "result" => $station]);
        } catch (\Exception $e) {
            return response()->json(['errors' => [$e->getMessage()]], 500);
        }
    }

    private function createDefaultSchedules($stationId)
    {
        $defaults = [
            ["libelle" => "Shift Jour", "started_at" => "07:00", "ended_at" => "18:00", "site_id" => $stationId],
            ["libelle" => "Shift Nuit", "started_at" => "19:00", "ended_at" => "06:00", "site_id" => $stationId],
        ];
        foreach ($defaults as $d) {
            PresenceHoraire::create($d);
        }
    }

    /**
     * Liste toutes les stations.
     */
    public function viewAllSites()
    {
        $stations = Station::orderBy('name')->get();
        return response()->json(["status" => "success", "sites" => $stations]);
    }

    /**
     * Gère l'enrôlement et la mise à jour des agents.
     */
    public function createAgent(Request $request): JsonResponse
    {
        try {
            $data = $request->validate([
                "id" => "nullable|integer",
                "matricule" => "required|string|unique:agents,matricule," . ($request->id ?? 'NULL'),
                "fullname" => "required|string",
                "site_id" => "required|integer|exists:sites,id",
                "status" => "nullable|string",
                "photo" => "nullable|image|max:2048"
            ]);

            if ($request->hasFile('photo')) {
                $file = $request->file('photo');
                $filename = time() . '_' . $file->getClientOriginalName();
                $file->move(public_path('uploads/agents'), $filename);
                $data['photo'] = url('uploads/agents/' . $filename);
            }

            $data["password"] = $data["password"] ?? bcrypt("salama123");

            $agent = Agent::updateOrCreate(['id' => $request->id], $data);

            return response()->json(["status" => "success", "result" => $agent]);
        } catch (\Exception $e) {
            return response()->json(['errors' => [$e->getMessage()]], 500);
        }
    }

    /**
     * Récupère les données pour le dashboard (Stats temps réel).
     */
    public function getDashboardData(Request $request): JsonResponse
    {
        $date = $request->query('date') ? Carbon::parse($request->query('date')) : Carbon::today();

        $totalStations = Station::count();
        $totalAgents = Agent::count();

        $presentAgents = PresenceAgents::whereDate('date_reference', $date)
            ->whereNull('ended_at')
            ->count();

        $absentAgents = max($totalAgents - $presentAgents, 0);

        return response()->json([
            "status" => "success",
            "count" => [
                "sites" => $totalStations,
                "agents" => $totalAgents,
                "presences" => $presentAgents,
                "absents" => $absentAgents
            ]
        ]);
    }

    /**
     * Génère les QR Codes pour toutes les stations.
     */
    public function generateSiteQrcodes()
    {
        $stations = Station::all();
        $data = [];

        foreach ($stations as $station) {
            $qrData = json_encode([
                "id" => $station->id,
                "name" => $station->name,
                "type" => "station_pointage"
            ]);

            $qrCode = QrCode::format('png')->size(200)->generate($qrData);
            $data[] = [
                'name' => $station->name,
                'qrcode' => 'data:image/png;base64,' . base64_encode($qrCode)
            ];
        }

        $pdf = Pdf::loadView('pdf.qrcodes', ['areas' => $data]);
        return $pdf->download('qrcodes_stations.pdf');
    }

    /**
     * Suppression générique.
     */
    public function triggerDelete(Request $request): JsonResponse
    {
        $result = DB::table($request->table)->where('id', $request->id)->delete();
        return response()->json(["status" => "success", "result" => $result]);
    }

    /**
     * Liste des agents pour AJAX.
     */
    public function fetchAgents(Request $request)
    {
        $agents = Agent::with('station')->orderByDesc('id')->paginate(10);
        return response()->json(['agents' => $agents]);
    }

    /**
     * Création d'utilisateurs admin.
     */
    public function createUser(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'role' => 'required'
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => bcrypt($data['password']),
        ]);

        if (isset($data['role'])) {
            $user->assignRole($data['role']);
        }

        return response()->json(['status' => 'success', 'result' => $user]);
    }
}
