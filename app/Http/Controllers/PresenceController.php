<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\AgentGroup;
use App\Models\AgentGroupAssignment;
use App\Models\AgentGroupPlanning;
use App\Models\AttendanceAuthorization;
use App\Models\AttendanceJustification;
use App\Models\Conge;
use App\Models\MaintenanceAgent;
use App\Models\PresenceAgents;
use App\Models\PresenceHoraire;
use App\Models\Station;
use App\Services\AttendanceReportService; 
use App\Services\AbsenceReportService; 
use Barryvdh\DomPDF\Facade\Pdf; 
use Carbon\Carbon; 
use Illuminate\Http\JsonResponse; 
use Illuminate\Http\Request; 
use Illuminate\Pagination\LengthAwarePaginator; 
use Illuminate\Support\Facades\DB; 
use Illuminate\Support\Facades\Log; 
use Illuminate\Validation\ValidationException; 
 
class PresenceController extends Controller 
{ 
    /**
     * Enregistre un pointage (check-in / check-out).
     *
     * Règles:
     * - Chaque check-in et check-out doit être lié à une station.
     * - La présence conserve la station d’affectation (site_id) au moment du pointage.
     * - Plus de logique photo (champ ignoré si envoyé).
     */
    public function createPresenceAgent(Request $request): JsonResponse 
    { 
        try { 
            $data = $request->validate([ 
                'matricule' => 'required|string|exists:agents,matricule', 
                'key' => 'required|string|in:check-in,check-out,confirmation,maintenance-in,maintenance-out', 
                'station_id' => 'nullable|integer|exists:sites,id', 
                'coordonnees' => 'nullable|string', // "lat,lng" (mobile) 
                'photo' => 'nullable',
                'photo_debut' => 'nullable',
                'photo_fin' => 'nullable',
            ]); 
        } catch (ValidationException $e) { 
            // Important: keep HTTP 200 to avoid client conflicts (Flutter), encode failures in payload only.
            return response()->json([ 
                'status' => 'error', 
                'errors' => $e->validator->errors()->all(), 
            ], 200); 
        } 
 
        $now = Carbon::now()->setTimezone('Africa/Kinshasa'); 
 
        $agent = Agent::with(['station', 'horaire', 'groupe'])->where('matricule', $data['matricule'])->firstOrFail(); 
        $assignedStationId = $agent->site_id; 

        $stationId = null;
        if ($data['key'] !== 'confirmation') {
            $stationId = $this->resolveStationId(
                stationId: $data['station_id'] ?? null,
                coordonnees: $data['coordonnees'] ?? null,
                fallbackAssignedStationId: $assignedStationId,
            );

            if (!$stationId && $data['key'] === 'maintenance-out') {
                $stationId = MaintenanceAgent::query()
                    ->where('agent_id', $agent->id)
                    ->whereNull('end_at')
                    ->orderByDesc('started_at')
                    ->value('station_id');
            }

            if (!$stationId) {
                return response()->json(['errors' => ['Station introuvable pour ce pointage.']]);
            }
        }
 
        // On ne requiert un horaire QUE pour le check-in (pour rÃ©soudre date_reference et le retard).
        // Le check-out s'appuie sur la prÃ©sence ouverte (started_at non null + ended_at null).
        $horaire = null;
        $dateReference = $now->copy()->startOfDay();
        if (in_array($data['key'], ['check-in', 'confirmation'], true)) {
            $horaire = $this->getHoraireForAgent($agent, $now);
            if ($data['key'] === 'check-in' && !$horaire) {
                return response()->json([
                    'status' => 'error',
                    'errors' => ['Horaire introuvable pour cet agent (planning/groupe/agent/station).'],
                ], 200);
            }
            if ($horaire) {
                $dateReference = $this->getDateReference($now, $horaire);
            }
        }
 
        // If the agent is OFF (rest day) for the reference day, they are not expected to work and should not punch in. 
        if ($data['key'] === 'check-in') { 
            $gid = AgentGroupAssignment::query() 
                ->where('agent_id', $agent->id)
                ->whereDate('start_date', '<=', $dateReference->toDateString())
                ->where(function ($q) use ($dateReference) {
                    $q->whereNull('end_date')->orWhereDate('end_date', '>=', $dateReference->toDateString());
                })
                ->orderByDesc('start_date')
                ->value('agent_group_id');
            $gid = $gid ? (int) $gid : ($agent->groupe_id ? (int) $agent->groupe_id : null);

            $isOffDay = AgentGroupPlanning::query()
                ->where('agent_id', $agent->id)
                ->when($gid !== null, fn ($q) => $q->where('agent_group_id', $gid))
                ->whereDate('date', $dateReference->toDateString())
                ->where('is_rest_day', true)
                ->exists();

            if ($isOffDay) {
                return response()->json(['errors' => ['Jour OFF: pointage non autorise.']]);
            }
        }

        $photoDebut = $this->normalizePunchPhoto($data['photo_debut'] ?? $data['photo'] ?? null);
        $photoFin = $this->normalizePunchPhoto($data['photo_fin'] ?? $data['photo'] ?? null);
        $coordonnees = $data['coordonnees'] ?? null;

        try {
            return DB::transaction(function () use ($data, $agent, $assignedStationId, $stationId, $horaire, $dateReference, $now, $photoDebut, $photoFin, $coordonnees) {
                if ($data['key'] === 'check-in') {
                    return $this->handleCheckIn($agent, $assignedStationId, $stationId, $horaire, $dateReference, $now, $photoDebut);
                }

                if ($data['key'] === 'check-out') {
                    return $this->handleCheckOut($agent, $stationId, $now, $photoFin);
                }

                if ($data['key'] === 'maintenance-in') {
                    return $this->handleMaintenanceIn($agent, $stationId, $now, $photoDebut, $coordonnees);
                }

                if ($data['key'] === 'maintenance-out') {
                    return $this->handleMaintenanceOut($agent, $stationId, $now, $photoFin, $coordonnees);
                }

                return $this->handleMidCheckConfirmation($agent, $dateReference, $now);
            });
        } catch (\Throwable $e) {
            Log::error('createPresenceAgent failed', [
                'error' => $e->getMessage(),
                'agent_id' => $agent->id ?? null,
            ]);

            return response()->json(['errors' => ['Erreur interne lors du pointage.']]);
        }
    }

    private function handleCheckIn(Agent $agent, ?int $assignedStationId, int $stationId, ?PresenceHoraire $horaire, Carbon $dateReference, Carbon $now, ?string $photoDebut = null): JsonResponse
    {
        $existing = PresenceAgents::query()
            ->where('agent_id', $agent->id)
            ->whereDate('date_reference', $dateReference->toDateString())
            ->first();

        if ($existing && $existing->started_at) {
            return response()->json(['errors' => ['Pointage d’entrée déjà effectué pour cette période.']]);
        }

        $retard = 'non';
        if ($horaire) {
            $heureRef = $dateReference->copy()->setTimeFromTimeString($horaire->started_at);
            $toleranceMinutes = (int) ($horaire->tolerence_minutes ?? 15);
            if ($now->gt($heureRef->copy()->addMinutes($toleranceMinutes))) {
                $retard = 'oui';
            }
        }

        $presence = PresenceAgents::create([
            'agent_id' => $agent->id,
            'site_id' => $assignedStationId, // station d'affectation (référence/contrôle)
            'gps_site_id' => $stationId, // legacy
            'station_check_in_id' => $stationId,
            'horaire_id' => $horaire?->id,
            'date_reference' => $dateReference->toDateString(),
            'started_at' => $now,
            'retard' => $retard,
            'photos_debut' => $photoDebut,
            'status' => 'arrive',
        ]);

        $presence->load(['agent.station', 'horaire', 'stationCheckIn', 'stationCheckOut', 'assignedStation']);

        return response()->json([
            'status' => 'success',
            'message' => 'Entrée enregistrée.',
            'result' => $presence,
        ]);
    }

    private function handleCheckOut(Agent $agent, int $stationId, Carbon $now, ?string $photoFin = null): JsonResponse
    {
        $presence = PresenceAgents::query()
            ->where('agent_id', $agent->id)
            ->whereNotNull('started_at')
            ->whereNull('ended_at')
            ->orderByDesc('started_at')
            ->first();

        if (!$presence) {
            return response()->json(['errors' => ['Aucun pointage d’entrée ouvert trouvé.']] );
        }

        $startedAt = Carbon::parse($presence->started_at);
        $dureeMinutes = $startedAt->diffInMinutes($now);
        $dureeFormat = $this->formatDuration($dureeMinutes);

        $presence->update([
            'ended_at' => $now,
            'duree' => $dureeFormat,
            'station_check_out_id' => $stationId,
            'photos_fin' => $photoFin,
            'status' => 'depart',
        ]);

        $presence->load(['agent.station', 'horaire', 'stationCheckIn', 'stationCheckOut', 'assignedStation']);

        return response()->json([
            'status' => 'success',
            'message' => 'Sortie enregistrée.',
            'result' => $presence,
        ]);
    }

    private function handleMidCheckConfirmation(Agent $agent, Carbon $dateReference, Carbon $now): JsonResponse
    {
        $presence = PresenceAgents::query()
            ->where('agent_id', $agent->id)
            ->whereDate('date_reference', $dateReference->toDateString())
            ->whereNotNull('started_at')
            ->whereNull('ended_at')
            ->orderByDesc('started_at')
            ->first();

        if (!$presence) {
            return response()->json(['errors' => ["Aucun pointage d'entree ouvert trouve pour la date de reference."]]);
        }

        if (!empty($presence->mid_check)) {
            return response()->json(['errors' => ['Confirmation deja effectuee.']]);
        }

        $presence->update([
            'mid_check' => $now,
        ]);

        $presence->load(['agent.station', 'horaire', 'stationCheckIn', 'stationCheckOut', 'assignedStation']);

        return response()->json([
            'status' => 'success',
            'message' => 'Controle intermediaire enregistre.',
            'result' => $presence,
        ]);
    }

    private function handleMaintenanceIn(Agent $agent, int $stationId, Carbon $now, ?string $photoDebut, ?string $coordonnees): JsonResponse
    {
        $openMaintenance = MaintenanceAgent::query()
            ->where('agent_id', $agent->id)
            ->whereNull('end_at')
            ->orderByDesc('started_at')
            ->first();

        if ($openMaintenance) {
            return response()->json(['errors' => ['Une maintenance est deja ouverte pour cet agent.']]);
        }

        $geo = $this->buildMaintenanceGeoContext($stationId, $coordonnees);

        $maintenance = MaintenanceAgent::create([
            'agent_id' => $agent->id,
            'station_id' => $stationId,
            'started_at' => $now,
            'date_maintenance' => $now->toDateString(),
            'photo_debut' => $photoDebut,
            'latlng' => $geo['station_latlng'],
            'commentaire' => $this->buildMaintenanceCommentLine('debut', $geo),
        ]);

        $maintenance->load(['agent.station', 'station']);
        $this->attachMaintenanceMeta($maintenance);

        return response()->json([
            'status' => 'success',
            'message' => 'Debut de maintenance enregistre.',
            'result' => $maintenance,
        ]);
    }

    private function handleMaintenanceOut(Agent $agent, int $stationId, Carbon $now, ?string $photoFin, ?string $coordonnees): JsonResponse
    {
        $maintenance = MaintenanceAgent::query()
            ->where('agent_id', $agent->id)
            ->whereNull('end_at')
            ->orderByDesc('started_at')
            ->first();

        if (!$maintenance) {
            return response()->json(['errors' => ['Aucune maintenance ouverte trouvee pour cet agent.']]);
        }

        $targetStationId = (int) ($maintenance->station_id ?: $stationId);
        $geo = $this->buildMaintenanceGeoContext($targetStationId, $coordonnees);
        $existingComment = trim((string) ($maintenance->commentaire ?? ''));
        $endComment = $this->buildMaintenanceCommentLine('fin', $geo);

        $maintenance->update([
            'end_at' => $now,
            'photo_fin' => $photoFin,
            'latlng' => $maintenance->latlng ?: $geo['station_latlng'],
            'commentaire' => $existingComment !== ''
                ? ($existingComment . "\n" . $endComment)
                : $endComment,
        ]);

        $maintenance->load(['agent.station', 'station']);
        $this->attachMaintenanceMeta($maintenance);

        return response()->json([
            'status' => 'success',
            'message' => 'Fin de maintenance enregistree.',
            'result' => $maintenance,
        ]);
    }

    private function buildMaintenanceGeoContext(int $stationId, ?string $coordonnees): array
    {
        $station = Station::query()
            ->withoutGlobalScopes()
            ->find($stationId, ['id', 'name', 'latlng']);

        $stationLatLng = $station?->latlng ? trim((string) $station->latlng) : null;
        $agentLatLng = $coordonnees ? trim((string) $coordonnees) : null;

        $stationPoint = $this->parseLatLng($stationLatLng);
        $agentPoint = $this->parseLatLng($agentLatLng);
        $errors = [];

        $distanceMeters = null;
        $isOnStation = null;

        if (!$stationLatLng) {
            $errors[] = 'latlng station non renseigne';
        } elseif (!$stationPoint) {
            $errors[] = 'latlng station invalide';
        }

        if (!$agentLatLng) {
            $errors[] = 'coordonnees agent non renseignees';
        } elseif (!$agentPoint) {
            $errors[] = 'coordonnees agent invalides';
        }

        if ($stationPoint && $agentPoint) {
            $distanceMeters = $this->calculateDistanceMeters(
                $agentPoint['lat'],
                $agentPoint['lng'],
                $stationPoint['lat'],
                $stationPoint['lng']
            );
            $isOnStation = $distanceMeters <= 500;
        }

        return [
            'station_name' => $station?->name,
            'station_latlng' => $stationLatLng,
            'agent_latlng' => $agentLatLng,
            'distance_meters' => $distanceMeters,
            'is_on_station' => $isOnStation,
            'station_latlng_valid' => $stationPoint !== null,
            'agent_latlng_valid' => $agentPoint !== null,
            'errors' => $errors,
        ];
    }

    private function buildMaintenanceCommentLine(string $phase, array $geo): string
    {
        $label = $phase === 'fin' ? 'Fin' : 'Debut';

        if ($geo['distance_meters'] === null) {
            $line = $label . ' distance: inconnue';
            if (!empty($geo['errors'])) {
                $line .= ', cause: ' . implode('; ', $geo['errors']);
            }
        } else {
            $line = $label . ' distance: ' . $geo['distance_meters'] . ' m';
        }

        if ($geo['is_on_station'] !== null) {
            $line .= ', sur station: ' . ($geo['is_on_station'] ? 'oui' : 'non');
        }

        if (!empty($geo['agent_latlng'])) {
            $line .= ', position agent: ' . $geo['agent_latlng'];
        }

        if (!empty($geo['station_latlng'])) {
            $line .= ', position station: ' . $geo['station_latlng'];
        }

        return $line;
    }

    private function attachMaintenanceMeta(MaintenanceAgent $maintenance): void
    {
        $meta = $this->extractMaintenanceMeta($maintenance->commentaire);
        $maintenance->setAttribute('distance_meters', $meta['distance_meters']);
        $maintenance->setAttribute('is_on_station', $meta['is_on_station']);
        $maintenance->setAttribute('distance_label', $meta['distance_label']);
    }

    private function extractMaintenanceMeta(?string $commentaire): array
    {
        $text = (string) ($commentaire ?? '');

        $debutDistance = null;
        $finDistance = null;
        $debutOnStation = null;
        $finOnStation = null;

        if (preg_match('/Debut\\s+distance:\\s*(\\d+)\\s*m/i', $text, $m)) {
            $debutDistance = (int) $m[1];
        }

        if (preg_match('/Fin\\s+distance:\\s*(\\d+)\\s*m/i', $text, $m)) {
            $finDistance = (int) $m[1];
        }

        if (preg_match('/Debut\\s+distance:[^\\n]*sur\\s+station:\\s*(oui|non)/i', $text, $m)) {
            $debutOnStation = strtolower($m[1]) === 'oui';
        }

        if (preg_match('/Fin\\s+distance:[^\\n]*sur\\s+station:\\s*(oui|non)/i', $text, $m)) {
            $finOnStation = strtolower($m[1]) === 'oui';
        }

        $distance = $finDistance ?? $debutDistance;
        $onStation = $finOnStation ?? $debutOnStation;

        return [
            'distance_meters' => $distance,
            'is_on_station' => $onStation,
            'distance_label' => $distance !== null ? ($distance . ' m') : 'Distance indisponible',
        ];
    }

    private function parseLatLng(?string $value): ?array
    {
        if (!$value) {
            return null;
        }

        $parts = array_map('trim', explode(',', $value));
        if (count($parts) !== 2) {
            return null;
        }

        if (!is_numeric($parts[0]) || !is_numeric($parts[1])) {
            return null;
        }

        $lat = (float) $parts[0];
        $lng = (float) $parts[1];

        if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
            return null;
        }

        return [
            'lat' => $lat,
            'lng' => $lng,
        ];
    }

    private function normalizePunchPhoto(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_string($value)) {
            $trimmed = trim($value);
            return $trimmed !== '' ? $trimmed : null;
        }

        if ($value instanceof \Illuminate\Http\UploadedFile) {
            $directory = public_path('uploads/punches');
            if (!is_dir($directory)) {
                @mkdir($directory, 0777, true);
            }

            $filename = 'punch_' . time() . '_' . uniqid() . '.' . $value->getClientOriginalExtension();
            $value->move($directory, $filename);

            return url('uploads/punches/' . $filename);
        }

        return null;
    }

    private function calculateDistanceMeters(float $lat1, float $lng1, float $lat2, float $lng2): int
    {
        $earthRadius = 6371000;
        $lat1Rad = deg2rad($lat1);
        $lng1Rad = deg2rad($lng1);
        $lat2Rad = deg2rad($lat2);
        $lng2Rad = deg2rad($lng2);

        $latDiff = $lat2Rad - $lat1Rad;
        $lngDiff = $lng2Rad - $lng1Rad;

        $a = sin($latDiff / 2) * sin($latDiff / 2)
            + cos($lat1Rad) * cos($lat2Rad) * sin($lngDiff / 2) * sin($lngDiff / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return (int) round($earthRadius * $c);
    }

    private function resolveStationId(?int $stationId, ?string $coordonnees, ?int $fallbackAssignedStationId): ?int
    {
        if ($stationId) {
            return $stationId;
        }

        if ($coordonnees) {
            $nearest = $this->findNearestStation($coordonnees);
            if ($nearest) {
                return $nearest->id;
            }
        }

        return $fallbackAssignedStationId;
    }

    private function findNearestStation(string $coords): ?Station
    {
        $parts = array_map('trim', explode(',', $coords));
        if (count($parts) !== 2) {
            return null;
        }
        [$lat1, $lng1] = $parts;
        if (!is_numeric($lat1) || !is_numeric($lng1)) {
            return null;
        }

        $stations = Station::query()->whereNotNull('latlng')->get();
        $appManager = new AppManagerController();

        return $stations
            ->map(function (Station $station) use ($lat1, $lng1, $appManager) {
                $coords = array_map('trim', explode(',', (string) $station->latlng));
                if (count($coords) !== 2) {
                    return null;
                }
                [$lat2, $lng2] = $coords;
                if (!is_numeric($lat2) || !is_numeric($lng2)) {
                    return null;
                }
                $station->distance = $appManager->calculateDistance($lat1, $lng1, $lat2, $lng2);
                return $station;
            })
            ->filter(fn ($s) => $s && isset($s->distance) && $s->distance <= 500)
            ->sortBy('distance')
            ->first();
    }

    private function getHoraireForAgent(Agent $agent, Carbon $now): ?PresenceHoraire 
    { 
        $date = $now->toDateString(); 
        $yesterday = $now->copy()->subDay()->toDateString(); 

        // Resolve active group assignment for a given date (prefer assignments; fallback to agent.groupe_id).
        $groupIdFor = function (string $d) use ($agent): ?int {
            $a = AgentGroupAssignment::query()
                ->where('agent_id', $agent->id)
                ->whereDate('start_date', '<=', $d)
                ->where(function ($q) use ($d) {
                    $q->whereNull('end_date')->orWhereDate('end_date', '>=', $d);
                })
                ->orderByDesc('start_date')
                ->first(['agent_group_id']);

            if ($a?->agent_group_id) {
                return (int) $a->agent_group_id;
            }

            return $agent->groupe_id ? (int) $agent->groupe_id : null; 
        }; 
 
        $groupById = function (?int $gid): ?AgentGroup { 
            if (!$gid) { 
                return null; 
            } 
            return AgentGroup::query()->find($gid, ['id', 'horaire_id']); 
        }; 
 
        $gidToday = $groupIdFor($date); 
        $groupToday = $groupById($gidToday); 
        $isFlexibleToday = $groupToday && empty($groupToday->horaire_id); 
 
        $gidYesterday = $groupIdFor($yesterday); 
        $groupYesterday = $groupById($gidYesterday); 
        $isFlexibleYesterday = $groupYesterday && empty($groupYesterday->horaire_id); 
 
        $planningFor = function (string $d) use ($agent, $groupIdFor) { 
            $gid = $groupIdFor($d); 
            return AgentGroupPlanning::query() 
                ->where('agent_id', $agent->id) 
                ->when($gid !== null, fn ($q) => $q->where('agent_group_id', $gid)) 
                ->whereDate('date', $d) 
                ->where('is_rest_day', false) 
                ->first(); 
        }; 
 
        // Night shifts: between 00:00 and the shift end time, the reference schedule can be yesterday's planning. 
        $planningYesterday = $planningFor($yesterday); 
        if ($planningYesterday?->horaire_id) { 
            $h = PresenceHoraire::find($planningYesterday->horaire_id); 
            if ($h) { 
                try { 
                    $heureDebut = Carbon::createFromTimeString($h->started_at); 
                    $heureFin = Carbon::createFromTimeString($h->ended_at); 
                    if ($heureFin->lt($heureDebut)) { 
                        $limiteFin = $now->copy()->startOfDay()->setTimeFromTimeString($h->ended_at); 
                        if ($now->lt($limiteFin)) { 
                            return $h; 
                        } 
                    } 
                } catch (\Throwable $_) { 
                } 
            } 
        } 
 
        // For flexible groups, do not fallback to agent/group/station defaults: the planning row is the source of truth.
        if ($isFlexibleYesterday && $planningYesterday && empty($planningYesterday->horaire_id)) { 
            return null; 
        } 
 
        $planning = $planningFor($date); 
        if ($planning?->horaire_id) { 
            return PresenceHoraire::find($planning->horaire_id); 
        } 
 
        if ($isFlexibleToday) { 
            return null; 
        } 
 
        // Default schedule from the active group (if any)
        if ($gidToday) { 
            $group = AgentGroup::query()->with('horaire')->find($gidToday); 
            if ($group?->horaire_id) { 
                return $group->horaire ?? PresenceHoraire::find($group->horaire_id); 
            } 
        } 
 
        if ($agent->horaire_id) { 
            return PresenceHoraire::find($agent->horaire_id); 
        } 

        if ($agent->site_id) {
            return PresenceHoraire::query()->where('site_id', $agent->site_id)->orderBy('started_at')->first();
        }

        return null;
    }

    private function getDateReference(Carbon $now, PresenceHoraire $horaire): Carbon
    {
        $heureDebut = Carbon::createFromTimeString($horaire->started_at);
        $heureFin = Carbon::createFromTimeString($horaire->ended_at);
        $dateReference = $now->copy()->startOfDay();

        if ($heureFin->lt($heureDebut)) {
            $limiteFin = $now->copy()->startOfDay()->setTimeFromTimeString($horaire->ended_at);
            if ($now->lt($limiteFin)) {
                $dateReference = $now->copy()->subDay()->startOfDay();
            }
        }

        return $dateReference;
    }

    private function formatDuration(int $minutes): string
    {
        $hours = intdiv($minutes, 60);
        $mins = $minutes % 60;
        return $hours . 'h ' . $mins . 'min';
    }

    /**
     * Récupère la liste des pointages pour une station et une date.
     * Compatible avec le flow existant (station_id filtre sur la station d'affectation).
     */
    public function getPresencesBySiteAndDate(Request $request): JsonResponse
    {
        $data = $request->validate([
            'date' => 'nullable|date',
            'station_id' => 'nullable|integer|exists:sites,id',
        ]);

        $date = $data['date'] ?? Carbon::today()->toDateString();
        $stationId = $data['station_id'] ?? null;

        $query = PresenceAgents::query()
            ->with(['agent.station', 'horaire', 'stationCheckIn', 'stationCheckOut', 'assignedStation'])
            ->whereDate('date_reference', $date);

        if ($stationId !== null) {
            $query->where('site_id', (int) $stationId);
        }

        return response()->json([
            'status' => 'success',
            'presences' => $query
                ->orderByDesc('date_reference')
                ->orderByDesc('started_at')
                ->get(),
        ]);
    }

    public function getAllHoraires(Request $request): JsonResponse
    {
        $data = $request->validate([
            'site_id' => 'nullable|integer|exists:sites,id',
        ]);

        $siteId = $data['site_id'] ?? null;
        $horaires = PresenceHoraire::query()
            ->when($siteId !== null, fn ($q) => $q->where('site_id', (int) $siteId))
            ->orderBy('site_id')
            ->orderBy('started_at')
            ->get()
            ->map(function (PresenceHoraire $horaire) {
                $horaire->started_at = $horaire->getRawOriginal('started_at') ?? $horaire->started_at;
                $horaire->mid_check = $horaire->getRawOriginal('mid_check') ?? $horaire->mid_check;
                $horaire->ended_at = $horaire->getRawOriginal('ended_at') ?? $horaire->ended_at;
                return $horaire;
            });

        return response()->json(['status' => 'success', 'horaires' => $horaires]);
    }

    public function createHoraire(Request $request): JsonResponse
    {
        $data = $request->validate([
            'id' => 'nullable|integer',
            'libelle' => 'required|string',
            'started_at' => 'required|string',
            'mid_check' => 'nullable|string',
            'ended_at' => 'required|string',
            'tolerence_minutes' => 'nullable|integer|min:0',
            'site_id' => 'required|integer|exists:sites,id',
        ]);

        $data['mid_check'] = !empty($data['mid_check']) ? $data['mid_check'] : null;
        $horaire = PresenceHoraire::updateOrCreate(['id' => $data['id'] ?? null], $data);

        return response()->json(['status' => 'success', 'result' => $horaire]);
    }

    public function createGroup(Request $request): JsonResponse
    {
        $data = $request->validate([
            'id' => 'nullable|integer|exists:agent_groups,id',
            'libelle' => 'required|string',
            'horaire_id' => 'nullable|integer|exists:presence_horaires,id',
            'cycle_days' => 'nullable|integer|min:1',
            'status' => 'nullable|string|in:actif,inactif',
        ]);

        $group = AgentGroup::updateOrCreate(['id' => $data['id'] ?? null], $data);

        return response()->json(['status' => 'success', 'result' => $group]);
    }

    public function getAllGroups(Request $request): JsonResponse
    {
        $groups = AgentGroup::query()
            ->with('horaire')
            ->withCount('plannings')
            ->orderBy('libelle')
            ->get();

        return response()->json(['status' => 'success', 'groups' => $groups]);
    }

    public function countDashboard(Request $request, AttendanceReportService $service): JsonResponse
    {
        $date = $request->query('date') ? Carbon::parse($request->query('date')) : Carbon::today();
        $stationId = $request->query('station_id');

        $filters = [
            'station_id' => $stationId ? (int) $stationId : null,
        ];

        $matrix = $service->buildDailyMatrix($date, $filters);
        $key = $date->toDateString();

        $totalAgents = $matrix['agents']->count();
        $expectedAgents = 0;
        $presentAgents = 0;
        $lateAgents = 0;
        $absentAgents = 0;

        foreach (($matrix['data'] ?? []) as $row) {
            $cell = $row[$key] ?? null;
            $status = is_array($cell) ? ($cell['status'] ?? null) : null;

            if ($status === 'off' || $status === 'future') {
                continue;
            }

            $expectedAgents += 1;
            if (in_array($status, ['present', 'retard', 'retard_justifie'], true)) {
                $presentAgents += 1;
            }
            if (in_array($status, ['retard', 'retard_justifie'], true)) {
                $lateAgents += 1;
            }
            if ($status === 'absent') {
                $absentAgents += 1;
            }
        }

        return response()->json([
            'status' => 'success',
            'count' => [
                'agents' => $totalAgents,
                'agents_expected' => $expectedAgents,
                'presences' => $presentAgents,
                'retards' => $lateAgents,
                'absents' => $absentAgents,
            ],
        ]);
    }

    /**
     * Rapport mensuel (JSON + PDF si export=pdf).
     */
    public function monthlyReport(Request $request, AttendanceReportService $service): \Symfony\Component\HttpFoundation\Response
    {
        $data = $request->validate([
            'month' => 'nullable|integer|min:1|max:12',
            'year' => 'nullable|integer|min:2000|max:2100',
            'station_id' => 'nullable|integer|exists:sites,id',
            'agent_id' => 'nullable|integer|exists:agents,id',
            'group_id' => 'nullable|integer|exists:agent_groups,id',
        ]);

        $month = (int) ($data['month'] ?? Carbon::now()->month);
        $year = (int) ($data['year'] ?? Carbon::now()->year);

        $filters = [
            'station_id' => $data['station_id'] ?? null,
            'agent_id' => $data['agent_id'] ?? null,
            'group_id' => $data['group_id'] ?? null,
        ];

        $matrix = $service->buildMonthlyMatrix($month, $year, $filters);

        if ($request->query('export') === 'pdf') {
            $pdf = Pdf::loadView('pdf.reports.monthly_report', [
                'data' => $matrix['data'],
                'mois' => $month,
                'annee' => $year,
            ])->setPaper('a4', 'portrait');

            return $pdf->download("rapport_mensuel_{$month}_{$year}.pdf");
        }

        return response()->json([
            'status' => 'success',
            'month' => $month,
            'year' => $year,
            'data' => $matrix['data'],
            'agents' => $matrix['agents']
                ->mapWithKeys(function (Agent $a) {
                    $key = $a->fullname . ' (' . $a->matricule . ')';
                    return [
                        $key => [
                            'id' => $a->id,
                            'fullname' => $a->fullname,
                            'matricule' => $a->matricule,
                            'photo' => $a->photo,
                            'station_id' => $a->site_id,
                            'station_name' => $a->station?->name,
                        ],
                    ];
                }),
        ]);
    }

    /**
     * Endpoint mobile legacy (superviseur). Non couvert dans le périmètre "attendance agents".
     */
    public function createSupervisorSiteVisit(Request $request): JsonResponse
    {
        return response()->json([
            'status' => 'error',
            'errors' => ['Fonctionnalité superviseur non disponible sur ce backend.'],
        ], 501);
    }

    public function dailyReport(Request $request, AttendanceReportService $service): JsonResponse
    {
        $data = $request->validate([
            'date' => 'nullable|date',
            'station_id' => 'nullable|integer|exists:sites,id',
            'agent_id' => 'nullable|integer|exists:agents,id',
            'group_id' => 'nullable|integer|exists:agent_groups,id',
            'per_page' => 'nullable|integer|min:1|max:200',
        ]);

        $date = Carbon::parse($data['date'] ?? Carbon::today()->toDateString())->toDateString();

        $agentsQuery = Agent::query()
            ->when(!empty($data['station_id']), fn ($q) => $q->where('site_id', $data['station_id']))
            ->when(!empty($data['agent_id']), fn ($q) => $q->where('id', $data['agent_id']))
            ->when(!empty($data['group_id']), fn ($q) => $q->where('groupe_id', $data['group_id']));

        $totalAgents = $agentsQuery->count();
        $agentIds = $agentsQuery->pluck('id')->all();

        $filters = [
            'station_id' => $data['station_id'] ?? null,
            'agent_id' => $data['agent_id'] ?? null,
            'group_id' => $data['group_id'] ?? null,
        ];
        $dailyMatrix = $service->buildDailyMatrix(Carbon::parse($date), $filters);

        $expectedAgents = 0;
        $present = 0;
        $late = 0;
        $absent = 0;
        $conges = 0;
        $authorizations = 0;
        $absenceJustifiee = 0;
        $off = 0;
        $countByStation = [];
        $agentsByMatrixKey = collect($dailyMatrix['agents'] ?? [])
            ->mapWithKeys(function (Agent $a) {
                $key = $a->fullname . ' (' . $a->matricule . ')';
                return [$key => $a];
            });

        foreach (($dailyMatrix['data'] ?? []) as $agentKey => $row) {
            $cell = $row[$date] ?? null;
            $status = is_array($cell) ? ($cell['status'] ?? null) : null;
            /** @var Agent|null $agent */
            $agent = $agentsByMatrixKey->get($agentKey);

            $stationId = $agent && $agent->site_id ? (int) $agent->site_id : null;
            $stationName = $agent?->station?->name ?? 'Sans station';
            $stationKey = $stationId !== null ? (string) $stationId : 'none';

            if (!array_key_exists($stationKey, $countByStation)) {
                $countByStation[$stationKey] = [
                    'station_id' => $stationId,
                    'station_name' => $stationName,
                    'agents' => 0,
                    'agents_expected' => 0,
                    'presences' => 0,
                    'retards' => 0,
                    'absents' => 0,
                    'off' => 0,
                    'conges' => 0,
                    'authorizations' => 0,
                    'absence_justifiee' => 0,
                ];
            }
            $countByStation[$stationKey]['agents'] += 1;

            if ($status === 'off' || $status === 'future') {
                $off += 1;
                $countByStation[$stationKey]['off'] += 1;
                continue;
            }

            $expectedAgents += 1;
            $countByStation[$stationKey]['agents_expected'] += 1;

            if (in_array($status, ['present', 'retard', 'retard_justifie'], true)) {
                $present += 1;
                $countByStation[$stationKey]['presences'] += 1;
            }
            if (in_array($status, ['retard', 'retard_justifie'], true)) {
                $late += 1;
                $countByStation[$stationKey]['retards'] += 1;
            }
            if ($status === 'absent') {
                $absent += 1;
                $countByStation[$stationKey]['absents'] += 1;
            }
            if ($status === 'conge') {
                $conges += 1;
                $countByStation[$stationKey]['conges'] += 1;
            }
            if ($status === 'autorisation') {
                $authorizations += 1;
                $countByStation[$stationKey]['authorizations'] += 1;
            }
            if ($status === 'absence_justifiee') {
                $absenceJustifiee += 1;
                $countByStation[$stationKey]['absence_justifiee'] += 1;
            }
        }
        $countByStation = array_values($countByStation);
        usort($countByStation, fn ($a, $b) => strcmp((string) ($a['station_name'] ?? ''), (string) ($b['station_name'] ?? '')));

        $presencesQuery = PresenceAgents::query()
            ->with(['agent.station', 'horaire', 'stationCheckIn', 'stationCheckOut', 'assignedStation'])
            ->whereDate('date_reference', $date)
            ->whereIn('agent_id', $agentIds);

        $perPage = (int) ($data['per_page'] ?? 25);
        $presences = $presencesQuery
            ->orderByDesc('date_reference')
            ->orderByDesc('started_at')
            ->paginate($perPage);

        $this->attachPresenceMotifs($presences->getCollection(), $date);

        return response()->json([
            'status' => 'success',
            'date' => $date,
            'count' => [
                'agents' => $totalAgents,
                'agents_expected' => $expectedAgents,
                'presences' => $present,
                'retards' => $late,
                'absents' => $absent,
                'off' => $off,
                'conges' => $conges,
                'authorizations' => $authorizations,
                'absence_justifiee' => $absenceJustifiee,
            ],
            'count_by_station' => $countByStation,
            'presences' => $presences,
        ]);
    }

    public function maintenanceAgents(Request $request): JsonResponse
    {
        $data = $request->validate([
            'station_id' => 'nullable|integer|exists:sites,id',
            'search' => 'nullable|string',
        ]);

        $agents = Agent::query()
            ->select(['id', 'fullname', 'matricule', 'photo', 'site_id'])
            ->with('station:id,name')
            ->when(!empty($data['station_id']), fn ($q) => $q->where('site_id', (int) $data['station_id']))
            ->when(!empty($data['search']), function ($q) use ($data) {
                $search = trim((string) $data['search']);
                $q->where(function ($sub) use ($search) {
                    $sub->where('fullname', 'like', '%' . $search . '%')
                        ->orWhere('matricule', 'like', '%' . $search . '%');
                });
            })
            ->orderBy('fullname')
            ->get();

        return response()->json([
            'status' => 'success',
            'agents' => $agents,
        ]);
    }

    public function maintenanceReport(Request $request): JsonResponse
    {
        $data = $request->validate([
            'date' => 'nullable|date',
            'from' => 'nullable|date',
            'to' => 'nullable|date',
            'station_id' => 'nullable|integer|exists:sites,id',
            'agent_id' => 'nullable|integer|exists:agents,id',
            'per_page' => 'nullable|integer|min:1|max:500',
        ]);

        $baseDate = Carbon::parse($data['date'] ?? Carbon::today()->toDateString());
        $start = !empty($data['from']) ? Carbon::parse($data['from'])->startOfDay() : $baseDate->copy()->startOfDay();
        $end = !empty($data['to']) ? Carbon::parse($data['to'])->endOfDay() : $baseDate->copy()->endOfDay();

        if ($start->gt($end)) {
            [$start, $end] = [$end, $start];
        }

        $baseQuery = MaintenanceAgent::query()
            ->whereDate('date_maintenance', '>=', $start->toDateString())
            ->whereDate('date_maintenance', '<=', $end->toDateString())
            ->when(!empty($data['station_id']), fn ($q) => $q->where('station_id', (int) $data['station_id']))
            ->when(!empty($data['agent_id']), fn ($q) => $q->where('agent_id', (int) $data['agent_id']));

        $total = (clone $baseQuery)->count();
        $completed = (clone $baseQuery)->whereNotNull('end_at')->count();
        $ongoing = max($total - $completed, 0);

        $onStation = 0;
        $offStation = 0;
        foreach ((clone $baseQuery)->get(['commentaire']) as $row) {
            $meta = $this->extractMaintenanceMeta((string) $row->commentaire);
            if ($meta['is_on_station'] === true) {
                $onStation += 1;
            } elseif ($meta['is_on_station'] === false) {
                $offStation += 1;
            }
        }

        $perPage = (int) ($data['per_page'] ?? 200);
        $maintenances = (clone $baseQuery)
            ->with(['agent.station', 'station'])
            ->orderByDesc('date_maintenance')
            ->orderByDesc('started_at')
            ->paginate($perPage);

        $maintenances->getCollection()->transform(function (MaintenanceAgent $maintenance) {
            $maintenance->date_maintenance_iso = $maintenance->getRawOriginal('date_maintenance');
            $maintenance->started_at_raw = $maintenance->getRawOriginal('started_at');
            $maintenance->end_at_raw = $maintenance->getRawOriginal('end_at');
            $this->attachMaintenanceMeta($maintenance);
            return $maintenance;
        });

        return response()->json([
            'status' => 'success',
            'from' => $start->toDateString(),
            'to' => $end->toDateString(),
            'summary' => [
                'total' => $total,
                'completed' => $completed,
                'ongoing' => $ongoing,
                'on_station' => $onStation,
                'off_station' => $offStation,
            ],
            'maintenances' => $maintenances,
        ]);
    }

    private function attachPresenceMotifs(\Illuminate\Support\Collection $rows, string $date): void
    {
        $agentIds = $rows->pluck('agent_id')->filter()->unique()->values()->all();
        if (empty($agentIds)) {
            return;
        }

        $authorizations = AttendanceAuthorization::query()
            ->whereIn('agent_id', $agentIds)
            ->where('status', 'approved')
            ->whereDate('date_reference', $date)
            ->get()
            ->groupBy('agent_id');

        $justifications = AttendanceJustification::query()
            ->whereIn('agent_id', $agentIds)
            ->where('status', 'approved')
            ->whereDate('date_reference', $date)
            ->get()
            ->groupBy('agent_id');

        $conges = Conge::query()
            ->whereIn('agent_id', $agentIds)
            ->where('status', 'approved')
            ->whereDate('date_debut', '<=', $date)
            ->whereDate('date_fin', '>=', $date)
            ->get()
            ->groupBy('agent_id');

        foreach ($rows as $p) {
            $motifs = [];
            $auth = optional($authorizations->get($p->agent_id ?? null))->first();
            if ($auth) {
                $label = 'Autorisation';
                if (!empty($auth->reason)) {
                    $label .= ': ' . $auth->reason;
                } elseif (!empty($auth->type)) {
                    $label .= ': ' . strtoupper((string) $auth->type);
                }
                $motifs[] = $label;
            }

            $justif = optional($justifications->get($p->agent_id ?? null))->first();
            if ($justif) {
                $label = $justif->kind === 'retard' ? 'Retard justifie' : 'Absence justifiee';
                if (!empty($justif->justification)) {
                    $label .= ': ' . $justif->justification;
                }
                $motifs[] = $label;
            }

            $conge = optional($conges->get($p->agent_id ?? null))->first();
            if ($conge) {
                $label = 'Conge';
                if (!empty($conge->motif)) {
                    $label .= ': ' . $conge->motif;
                }
                $motifs[] = $label;
            }

            if (($p->retard ?? '') === 'oui' && !$justif) {
                $motifs[] = 'Retard';
            }

            $p->setAttribute('motif', implode(' | ', $motifs));
        }
    }

    /**
     * Rapport des absences (journalier / période) avec justificatifs (congé/autorisation/justification).
     *
     * L'agent est considéré "absent" s'il n'a pas de pointage started_at sur la date de référence.
     * Les justificatifs n'annulent pas l'absence, ils sont affichés en colonne.
     */
    public function dailyAbsenceReport(Request $request, AbsenceReportService $service): JsonResponse
    {
        $data = $request->validate([
            'date' => 'nullable|date',
            'from' => 'nullable|date',
            'to' => 'nullable|date',
            'station_id' => 'nullable|integer|exists:sites,id',
            'per_page' => 'nullable|integer|min:1|max:2000',
            'page' => 'nullable|integer|min:1',
        ]);

        $base = Carbon::parse($data['date'] ?? Carbon::today()->toDateString());
        $start = !empty($data['from']) ? Carbon::parse($data['from'])->startOfDay() : $base->copy()->startOfDay();
        $end = !empty($data['to']) ? Carbon::parse($data['to'])->startOfDay() : $base->copy()->startOfDay();
        if ($start->gt($end)) {
            [$start, $end] = [$end, $start];
        }

        $stationId = $data['station_id'] ?? null;
        $rows = $service->buildAbsenceRows($start, $end, $stationId ? (int) $stationId : null);

        $perPage = (int) ($data['per_page'] ?? 500);
        $page = (int) ($data['page'] ?? 1);
        $total = count($rows);
        $slice = array_slice($rows, max(($page - 1) * $perPage, 0), $perPage);
        $paginator = new LengthAwarePaginator(
            $slice,
            $total,
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

        return response()->json([
            'status' => 'success',
            'from' => $start->toDateString(),
            'to' => $end->toDateString(),
            'absences' => $paginator,
        ]);
    }

    public function weeklyReport(Request $request, AttendanceReportService $service): JsonResponse
    {
        $data = $request->validate([
            'date' => 'nullable|date',
            'station_id' => 'nullable|integer|exists:sites,id',
        ]);

        $baseDate = Carbon::parse($data['date'] ?? Carbon::today()->toDateString());
        $start = $baseDate->copy()->startOfWeek(Carbon::MONDAY);
        $end = $start->copy()->addDays(6);

        $filters = [
            'station_id' => isset($data['station_id']) ? (int) $data['station_id'] : null,
        ];

        $matrix = $service->buildWeeklyMatrix($baseDate, $filters);

        return response()->json([
            'status' => 'success',
            'from' => $start->toDateString(),
            'to' => $end->toDateString(),
            'data' => $matrix['data'],
            'agents' => $matrix['agents']
                ->mapWithKeys(function (Agent $a) {
                    $key = $a->fullname . ' (' . $a->matricule . ')';
                    return [
                        $key => [
                            'id' => $a->id,
                            'fullname' => $a->fullname,
                            'matricule' => $a->matricule,
                            'photo' => $a->photo,
                            'station_id' => $a->site_id,
                            'station_name' => $a->station?->name,
                        ],
                    ];
                }),
        ]);
    }

    public function agentMaintenanceHistory(Request $request): JsonResponse
    {
        $data = $request->validate([
            'agent_id' => 'required|integer|exists:agents,id',
            'from' => 'nullable|date',
            'to' => 'nullable|date',
            'per_page' => 'nullable|integer|min:1|max:500',
            'station_id' => 'nullable|integer|exists:sites,id',
        ]);

        $query = MaintenanceAgent::query()
            ->with(['agent.station', 'station'])
            ->where('agent_id', (int) $data['agent_id'])
            ->when(!empty($data['from']), fn ($q) => $q->whereDate('date_maintenance', '>=', $data['from']))
            ->when(!empty($data['to']), fn ($q) => $q->whereDate('date_maintenance', '<=', $data['to']))
            ->when(!empty($data['station_id']), fn ($q) => $q->where('station_id', (int) $data['station_id']))
            ->orderByDesc('date_maintenance')
            ->orderByDesc('started_at');

        $perPage = (int) ($data['per_page'] ?? 15);
        $page = $query->paginate($perPage);
        $page->getCollection()->transform(function (MaintenanceAgent $maintenance) {
            $maintenance->date_maintenance_iso = $maintenance->getRawOriginal('date_maintenance');
            $maintenance->started_at_raw = $maintenance->getRawOriginal('started_at');
            $maintenance->end_at_raw = $maintenance->getRawOriginal('end_at');
            $this->attachMaintenanceMeta($maintenance);
            return $maintenance;
        });

        return response()->json([
            'status' => 'success',
            'history' => $page,
        ]);
    }

    /**
     * Historique détaillé des pointages d'un agent (stations check-in/out + affectation).
     */
    public function agentHistory(Request $request): JsonResponse
    {
        $data = $request->validate([
            'agent_id' => 'required|integer|exists:agents,id',
            'from' => 'nullable|date',
            'to' => 'nullable|date',
            'per_page' => 'nullable|integer|min:1|max:500',
            'station_id' => 'nullable|integer|exists:sites,id',
        ]);

        $query = PresenceAgents::query()
            ->with(['agent.station', 'horaire', 'stationCheckIn', 'stationCheckOut', 'assignedStation'])
            ->where('agent_id', $data['agent_id'])
            ->when(!empty($data['from']), fn ($q) => $q->whereDate('date_reference', '>=', $data['from']))
            ->when(!empty($data['to']), fn ($q) => $q->whereDate('date_reference', '<=', $data['to']))
            ->when(!empty($data['station_id']), function ($q) use ($data) {
                $stationId = (int) $data['station_id'];
                $q->where(function ($qq) use ($stationId) {
                    $qq->where('site_id', $stationId)
                        ->orWhere('station_check_in_id', $stationId)
                        ->orWhere('station_check_out_id', $stationId);
                });
            })
            ->orderByDesc('date_reference')
            ->orderByDesc('started_at');

        $perPage = (int) ($data['per_page'] ?? 15);

        $page = $query->paginate($perPage);
        $page->getCollection()->transform(function (PresenceAgents $p) {
            // Keep original fields (incl. formatted casts) but add ISO values for front-end logic.
            $p->date_reference_iso = $p->getRawOriginal('date_reference');
            $p->started_at_raw = $p->getRawOriginal('started_at');
            $p->mid_check_raw = $p->getRawOriginal('mid_check');
            $p->ended_at_raw = $p->getRawOriginal('ended_at');
            return $p;
        });

        return response()->json([
            'status' => 'success',
            'history' => $page,
        ]);
    }

    /**
     * RÃ©sumÃ© "agent_attendance": profil, station, horaire du jour + stats (journalier/mensuel).
     */
    public function agentAttendanceSummary(Request $request): JsonResponse
    {
        $data = $request->validate([
            'agent_id' => 'required|integer|exists:agents,id',
            'hours_date' => 'nullable|date',
            'month' => 'nullable|date_format:Y-m',
        ]);

        $now = Carbon::now()->setTimezone('Africa/Kinshasa');

        $agent = Agent::with(['station', 'horaire', 'groupe.horaire'])->findOrFail((int) $data['agent_id']);

        $horaire = $this->getHoraireForAgent($agent, $now);
        $dateForHours = !empty($data['hours_date']) ? Carbon::parse($data['hours_date']) : $now->copy();
        $dateForHours = $dateForHours->setTimezone('Africa/Kinshasa');
        $dateForHoursWithTime = $dateForHours->copy()->setTime((int) $now->hour, (int) $now->minute, (int) $now->second);
        $dateReference = $horaire ? $this->getDateReference($dateForHoursWithTime, $horaire) : $dateForHours->copy()->startOfDay();
        $dateReferenceString = $dateReference->toDateString();

        $monthBase = !empty($data['month'])
            ? Carbon::createFromFormat('Y-m', $data['month'])->startOfMonth()
            : $now->copy()->startOfMonth();
        $monthStart = $monthBase->copy()->startOfMonth()->toDateString();
        $monthEnd = $monthBase->copy()->endOfMonth()->toDateString();

        $dailyRows = PresenceAgents::query()
            ->where('agent_id', $agent->id)
            ->whereDate('date_reference', $dateReferenceString)
            ->get();

        $totalMinutes = 0;
        foreach ($dailyRows as $row) {
            $rawStart = $row->getRawOriginal('started_at');
            if (!$rawStart) {
                continue;
            }
            $start = Carbon::parse($rawStart);
            $rawEnd = $row->getRawOriginal('ended_at');
            $end = $rawEnd ? Carbon::parse($rawEnd) : $now;
            $diff = $start->diffInMinutes($end, false);
            if ($diff > 0) {
                $totalMinutes += $diff;
            }
        }

        $presenceDaysMonthly = PresenceAgents::query()
            ->where('agent_id', $agent->id)
            ->whereBetween('date_reference', [$monthStart, $monthEnd])
            ->whereNotNull('started_at')
            ->count();

        $lateDaysMonthly = PresenceAgents::query()
            ->where('agent_id', $agent->id)
            ->whereBetween('date_reference', [$monthStart, $monthEnd])
            ->where('retard', 'oui')
            ->count();

        $isOnLeave = Conge::query()
            ->where('agent_id', $agent->id)
            ->where('status', 'approved')
            ->whereDate('date_debut', '<=', $now->toDateString())
            ->whereDate('date_fin', '>=', $now->toDateString())
            ->exists();

        $hasPresenceToday = PresenceAgents::query()
            ->where('agent_id', $agent->id)
            ->whereDate('date_reference', $dateReferenceString)
            ->whereNotNull('started_at')
            ->exists();

        $isOffDay = AgentGroupPlanning::query()
            ->where('agent_id', $agent->id)
            ->whereDate('date', $dateReferenceString)
            ->where('is_rest_day', true)
            ->exists();

        $todayStatus = $isOffDay ? 'off' : ($isOnLeave ? 'conge' : ($hasPresenceToday ? 'present' : 'absent'));

        $expectedStart = $horaire ? (string) $horaire->getRawOriginal('started_at') : null;
        $expectedMidCheck = $horaire ? (string) $horaire->getRawOriginal('mid_check') : null;
        $expectedEnd = $horaire ? (string) $horaire->getRawOriginal('ended_at') : null;
        $expectedStart = $expectedStart ? substr($expectedStart, 0, 5) : null;
        $expectedMidCheck = $expectedMidCheck ? substr($expectedMidCheck, 0, 5) : null;
        $expectedEnd = $expectedEnd ? substr($expectedEnd, 0, 5) : null;

        return response()->json([
            'status' => 'success',
            'agent' => [
                'id' => $agent->id,
                'fullname' => $agent->fullname,
                'matricule' => $agent->matricule,
                'photo' => $agent->photo,
                'station' => $agent->station ? ['id' => $agent->station->id, 'name' => $agent->station->name] : null,
            ],
            'schedule' => $horaire ? [
                'id' => $horaire->id,
                'name' => $horaire->libelle,
                'expected_start' => $expectedStart,
                'expected_mid_check' => $expectedMidCheck,
                'expected_end' => $expectedEnd,
                'tolerance_minutes' => $horaire->tolerence_minutes,
            ] : null,
            'today_status' => $todayStatus,
            'periods' => [
                'daily_date_reference' => $dateReferenceString,
                'monthly_from' => $monthStart,
                'monthly_to' => $monthEnd,
            ],
            'stats' => [
                'total_hours_daily' => round($totalMinutes / 60, 1),
                'presences_monthly' => (int) $presenceDaysMonthly,
                'retards_monthly' => (int) $lateDaysMonthly,
            ],
        ]);
    }

    /**
     * Scan d'un QR code station et retour des donnÃ©es station.
     */
    public function scanStation(Request $request): JsonResponse
    {
        $data = $request->validate([
            'station_id' => 'required|integer|exists:sites,id',
        ]);

        $station = Station::query()->find((int) $data['station_id']);
        if (!$station) {
            return response()->json(['errors' => ['Station introuvable.']]);
        }

        return response()->json([
            'status' => 'success',
            'station' => [
                'id' => $station->id,
                'name' => $station->name,
                'code' => $station->code,
                'latlng' => $station->latlng,
                'adresse' => $station->adresse,
                'presence' => $station->presence,
                'status' => $station->status,
            ],
        ]);
    }

    /**
     * API pointage agent.
     * Le matricule est considÃ©rÃ© comme identifiant unique (voir createPresenceAgent).
     */
    public function punchAgent(Request $request): JsonResponse
    {
        return $this->createPresenceAgent($request);
    }
}
