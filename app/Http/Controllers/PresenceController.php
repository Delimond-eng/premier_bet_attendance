<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\PresenceAgents;
use App\Models\PresenceHoraire;
use App\Models\Station;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\FcmService;

/**
 * Class PresenceController
 * Gère les pointages d'entrée/sortie et les rapports de présence.
 */
class PresenceController extends Controller
{
    /**
     * Enregistre un pointage (Check-in ou Check-out).
     */
    public function createPresenceAgent(Request $request): JsonResponse
    {
        try {
            $data = $request->validate([
                "matricule" => "required|string|exists:agents,matricule",
                "key" => "required|string|in:check-in,check-out",
                "coordonnees" => "required|string",
                "photo" => "nullable|file"
            ]);

            $now = Carbon::now()->setTimezone("Africa/Kinshasa");
            $agent = Agent::with("station")->where('matricule', $data['matricule'])->firstOrFail();
            $station = $agent->station;

            // Détection de la station la plus proche
            $stationProche = $this->findNearestStation($data['coordonnees']);
            $stationId = $stationProche ? $stationProche->id : $agent->site_id;

            // Récupération de l'horaire associé à la station de l'agent
            $horaire = PresenceHoraire::where("site_id", $agent->site_id)->first();
            $dateReference = $horaire ? $this->getDateReference($now, $horaire) : $now->startOfDay();

            $photoUrl = null;
            if ($request->hasFile('photo')) {
                $file = $request->file('photo');
                $filename = time() . '_' . $file->getClientOriginalName();
                $file->move(public_path('uploads/presence_photos'), $filename);
                $photoUrl = url('uploads/presence_photos/' . $filename);
            }

            if ($data['key'] === 'check-in') {
                return $this->handleCheckIn($agent, $stationId, $horaire, $dateReference, $now, $photoUrl);
            } else {
                return $this->handleCheckOut($agent, $now, $photoUrl);
            }

        } catch (\Exception $e) {
            return response()->json(['errors' => [$e->getMessage()]], 500);
        }
    }

    private function handleCheckIn($agent, $stationId, $horaire, $dateReference, $now, $photoUrl)
    {
        $existing = PresenceAgents::where('agent_id', $agent->id)
            ->whereDate('date_reference', $dateReference->toDateString())
            ->first();

        if ($existing && $existing->started_at) {
            return response()->json(['errors' => ['Pointage d\'entrée déjà effectué pour cette période.']]);
        }

        $retard = "non";
        if ($horaire) {
            $heureRef = $dateReference->copy()->setTimeFromTimeString($horaire->started_at);
            if ($now->gt($heureRef->addMinutes(30))) {
                $retard = "oui";
            }
        }

        $presence = PresenceAgents::create([
            'agent_id' => $agent->id,
            'site_id' => $agent->site_id, // Station d'affectation
            'gps_site_id' => $stationId, // Station effective
            'horaire_id' => $horaire ? $horaire->id : null,
            'date_reference' => $dateReference,
            'started_at' => $now,
            'photos_debut' => $photoUrl,
            'retard' => $retard,
            'status' => 'arrive',
        ]);

        return response()->json(["status" => "success", "message" => "Entrée enregistrée.", "result" => $presence]);
    }

    private function handleCheckOut($agent, $now, $photoUrl)
    {
        $presence = PresenceAgents::where('agent_id', $agent->id)
            ->whereNotNull('started_at')
            ->whereNull('ended_at')
            ->latest()
            ->first();

        if (!$presence) {
            return response()->json(['errors' => ['Aucun pointage d\'entrée ouvert trouvé.']]);
        }

        $startedAt = Carbon::parse($presence->started_at);
        $dureeMinutes = $startedAt->diffInMinutes($now);
        $dureeFormat = floor($dureeMinutes / 60) . "h " . ($dureeMinutes % 60) . "min";

        $presence->update([
            'ended_at' => $now,
            'duree' => $dureeFormat,
            'photos_fin' => $photoUrl,
            'status' => 'depart',
        ]);

        return response()->json(["status" => "success", "message" => "Sortie enregistrée.", "result" => $presence]);
    }

    private function findNearestStation($coords)
    {
        [$lat1, $lng1] = explode(',', $coords);
        return Station::all()->map(function ($station) use ($lat1, $lng1) {
            if (!$station->latlng) return null;
            [$lat2, $lng2] = explode(',', $station->latlng);
            $station->distance = (new AppManagerController())->calculateDistance($lat1, $lng1, $lat2, $lng2);
            return $station;
        })->filter(fn($s) => $s && $s->distance <= 500)->sortBy('distance')->first();
    }

    private function getDateReference(Carbon $now, $horaire): Carbon
    {
        $heureDebut = Carbon::createFromTimeString($horaire->started_at);
        $heureFin = Carbon::createFromTimeString($horaire->ended_at);
        $dateReference = $now->copy()->startOfDay();

        if ($heureFin->lt($heureDebut)) { // Shift de nuit
            $limiteFin = $now->copy()->startOfDay()->setTimeFromTimeString($horaire->ended_at);
            if ($now->lt($limiteFin)) {
                $dateReference = $now->copy()->subDay()->startOfDay();
            }
        }
        return $dateReference;
    }

    /**
     * Récupère la liste des pointages pour une station et une date.
     */
    public function getPresencesBySiteAndDate(Request $request): JsonResponse
    {
        $date = $request->query('date') ?? Carbon::today()->toDateString();
        $stationId = $request->query('station_id');

        $query = PresenceAgents::with(['agent.station', 'horaire'])
            ->whereDate('date_reference', $date);

        if ($stationId) {
            $query->where('site_id', $stationId);
        }

        return response()->json([
            "status" => "success",
            "presences" => $query->orderByDesc('created_at')->get()
        ]);
    }

    /**
     * Génère le rapport mensuel.
     */
    public function monthlyReport(Request $request)
    {
        $month = $request->query('month', Carbon::now()->month);
        $year = $request->query('year', Carbon::now()->year);

        // Logique simplifiée pour l'exemple
        $agents = Agent::with('station')->get();
        // ... Logique de génération PDF ici ...

        return response()->json(["message" => "Rapport en cours de développement"]);
    }

    public function getAllHoraires()
    {
        return response()->json(['horaires' => PresenceHoraire::all()]);
    }

    public function createHoraire(Request $request)
    {
        $data = $request->validate([
            "libelle" => "required|string",
            "started_at" => "required|string",
            "ended_at" => "required|string",
            "site_id" => "required|integer|exists:sites,id",
        ]);

        $horaire = PresenceHoraire::updateOrCreate(['id' => $request->id], $data);
        return response()->json(["status" => "success", "result" => $horaire]);
    }
}
