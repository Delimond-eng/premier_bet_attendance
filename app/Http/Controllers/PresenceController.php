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
use App\Services\CumulativeAlertService;
use App\Services\LateReportService;
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
    public function __construct(
        private readonly AttendanceReportService $attendanceService
    ) {
    }

    /**
     * Enregistre un pointage (check-in / check-out / maintenance / confirmation).
     * Utilisé par l'application mobile et le web admin.
     */
    public function createPresenceAgent(Request $request): JsonResponse
    {
        Log::info("Tentative de pointage reçue", $request->all());

        try {
            $data = $request->validate([
                'matricule' => 'required|string|exists:agents,matricule',
                'key' => 'required|string|in:check-in,check-out,confirmation,maintenance-in,maintenance-out',
                'station_id' => 'nullable|integer',
                'coordonnees' => 'nullable|string', // "lat,lng" (mobile)
                'photo' => 'nullable',
                'photo_debut' => 'nullable',
                'photo_fin' => 'nullable',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'errors' => $e->validator->errors()->all(),
            ], 200);
        }

        $now = Carbon::now()->setTimezone('Africa/Kinshasa');

        // Mobile Fix: Use withoutGlobalScopes to find agent by matricule
        $agent = Agent::withoutGlobalScopes()->with(['station', 'horaire', 'groupe'])->where('matricule', $data['matricule'])->firstOrFail();
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
                return response()->json(['status' => 'error', 'errors' => ['Station introuvable pour ce pointage.']], 200);
            }
        }

        $horaire = null;
        $dateReference = $now->copy()->startOfDay();
        if (in_array($data['key'], ['check-in', 'confirmation'], true)) {
            $horaire = $this->getHoraireForAgent($agent, $now, $stationId);
            if ($data['key'] === 'check-in' && !$horaire) {
                return response()->json([
                    'status' => 'error',
                    'errors' => ['Horaire introuvable pour cet agent sur cette station.'],
                ], 200);
            }
            if ($horaire) {
                $dateReference = $this->getDateReference($now, $horaire);
            }
        }

        // Check for OFF Day
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

            $isOffDay = AgentGroupPlanning::withoutGlobalScopes()
                ->where('agent_id', $agent->id)
                ->when($gid !== null, fn ($q) => $q->where('agent_group_id', $gid))
                ->when($stationId !== null, fn ($q) => $q->where('site_id', $stationId))
                ->whereDate('date', $dateReference->toDateString())
                ->where('is_rest_day', true)
                ->exists();

            if ($isOffDay) {
                return response()->json(['status' => 'error', 'errors' => ['Jour OFF: pointage non autorise.']], 200);
            }
        }

        $isMaintenanceKey = str_starts_with((string) $data['key'], 'maintenance-');
        $photoDirectory = $isMaintenanceKey ? 'maintenances' : 'presences';

        $photoDebut = null;
        $photoFin = null;

        if (in_array($data['key'], ['check-in', 'maintenance-in'], true)) {
            $photoDebut = $this->normalizePunchPhoto($data['photo_debut'] ?? $data['photo'] ?? null, $photoDirectory);
        }

        if (in_array($data['key'], ['check-out', 'maintenance-out'], true)) {
            $photoFin = $this->normalizePunchPhoto($data['photo_fin'] ?? $data['photo'] ?? null, $photoDirectory);
        }
        $coordonnees = $data['coordonnees'] ?? null;

        try {
            return DB::transaction(function () use ($data, $agent, $assignedStationId, $stationId, $horaire, $dateReference, $now, $photoDebut, $photoFin, $coordonnees) {
                if ($data['key'] === 'check-in') {
                    return $this->handleCheckIn($agent, $assignedStationId, $stationId, $horaire, $dateReference, $now, $photoDebut, $coordonnees);
                }

                if ($data['key'] === 'check-out') {
                    return $this->handleCheckOut($agent, $stationId, $now, $photoFin, $coordonnees);
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

            return response()->json(['status' => 'error', 'errors' => ['Erreur interne: ' . $e->getMessage()]], 200);
        }
    }

    private function handleCheckIn(Agent $agent, ?int $assignedStationId, int $stationId, ?PresenceHoraire $horaire, Carbon $dateReference, Carbon $now, ?string $photoDebut = null, ?string $coordonnees = null): JsonResponse
    {
        $existing = PresenceAgents::query()
            ->where('agent_id', $agent->id)
            ->whereDate('date_reference', $dateReference->toDateString())
            ->first();

        if ($existing && $existing->started_at) {
            return response()->json(['status' => 'error', 'errors' => ['Pointage d’entrée déjà effectué pour cette période.']], 200);
        }

        $retard = 'non';
        if ($horaire) {
            $heureRef = $dateReference->copy()->setTimeFromTimeString($horaire->started_at);
            $toleranceMinutes = (int) ($horaire->tolerence_minutes ?? 15);
            if ($now->gt($heureRef->copy()->addMinutes($toleranceMinutes))) {
                $retard = 'oui';
            }
        }

        $commentLine = "";
        if ($coordonnees) {
            $geo = $this->buildGenericGeoContext($stationId, $coordonnees);
            $commentLine = $this->buildGenericCommentLine('Check-in', $geo);
        }

        $presence = PresenceAgents::create([
            'agent_id' => $agent->id,
            'site_id' => $stationId,
            'gps_site_id' => $stationId,
            'station_check_in_id' => $stationId,
            'horaire_id' => $horaire?->id,
            'date_reference' => $dateReference->toDateString(),
            'started_at' => $now,
            'retard' => $retard,
            'photos_debut' => $photoDebut,
            'status' => 'arrive',
            'commentaires' => $commentLine,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Entrée enregistrée.',
            'result' => $presence,
        ]);
    }

    private function handleCheckOut(Agent $agent, int $stationId, Carbon $now, ?string $photoFin = null, ?string $coordonnees = null): JsonResponse
    {
        $presence = PresenceAgents::query()
            ->where('agent_id', $agent->id)
            ->whereNotNull('started_at')
            ->whereNull('ended_at')
            ->orderByDesc('started_at')
            ->first();

        if (!$presence) {
            return response()->json(['status' => 'error', 'errors' => ['Aucun pointage d’entrée ouvert trouvé.']], 200);
        }

        $startedAt = Carbon::parse($presence->started_at);
        $dureeMinutes = $startedAt->diffInMinutes($now);
        $dureeFormat = $this->formatDuration($dureeMinutes);

        $existingComment = trim((string) ($presence->commentaires ?? ''));
        $commentLine = "";
        if ($coordonnees) {
            $geo = $this->buildGenericGeoContext($stationId, $coordonnees);
            $commentLine = $this->buildGenericCommentLine('Check-out', $geo);
        }

        $presence->update([
            'ended_at' => $now,
            'duree' => $dureeFormat,
            'station_check_out_id' => $stationId,
            'photos_fin' => $photoFin,
            'status' => 'depart',
            'commentaires' => $existingComment !== '' && $commentLine !== ''
                ? ($existingComment . "\n" . $commentLine)
                : ($commentLine !== '' ? $commentLine : $existingComment),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Sortie enregistrée.',
            'result' => $presence,
        ]);
    }

    private function buildGenericGeoContext(int $stationId, ?string $coordonnees): array
    {
        $station = Station::query()
            ->withoutGlobalScopes()
            ->find($stationId, ['id', 'name', 'latlng']);

        $stationLatLng = $station?->latlng ? trim((string) $station->latlng) : null;
        $agentLatLng = $coordonnees ? trim((string) $coordonnees) : null;

        $stationPoint = $this->parseLatLng($stationLatLng);
        $agentPoint = $this->parseLatLng($agentLatLng);

        $distanceMeters = null;
        $isOnStation = null;

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
            'station_latlng' => $stationLatLng,
            'agent_latlng' => $agentLatLng,
            'distance_meters' => $distanceMeters,
            'is_on_station' => $isOnStation,
        ];
    }

    private function buildGenericCommentLine(string $phase, array $geo): string
    {
        if ($geo['distance_meters'] === null) {
            return $phase . " distance: inconnue";
        }

        $line = $phase . ' distance: ' . $geo['distance_meters'] . ' m';
        if ($geo['is_on_station'] !== null) {
            $line .= ', sur station: ' . ($geo['is_on_station'] ? 'oui' : 'non');
        }
        return $line;
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
            return response()->json(['status' => 'error', 'errors' => ["Aucun pointage d'entree ouvert trouve."]], 200);
        }

        if (!empty($presence->mid_check)) {
            return response()->json(['status' => 'error', 'errors' => ['Confirmation deja effectuee.']], 200);
        }

        $presence->update([
            'mid_check' => $now,
        ]);

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
            return response()->json(['status' => 'error', 'errors' => ['Une maintenance est deja ouverte.']], 200);
        }

        $geo = $this->buildMaintenanceGeoContext($stationId, $coordonnees);

        $maintenance = MaintenanceAgent::create([
            'agent_id' => $agent->id,
            'station_id' => $stationId,
            'started_at' => $now,
            'date_maintenance' => $now->toDateString(),
            'photo_debut' => $photoDebut,
            'latlng' => $geo['agent_latlng'],
            'commentaire' => $this->buildMaintenanceCommentLine('debut', $geo),
        ]);

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
            return response()->json(['status' => 'error', 'errors' => ['Aucune maintenance ouverte.']], 200);
        }

        $targetStationId = (int) ($maintenance->station_id ?: $stationId);
        $geo = $this->buildMaintenanceGeoContext($targetStationId, $coordonnees);
        $existingComment = trim((string) ($maintenance->commentaire ?? ''));
        $endComment = $this->buildMaintenanceCommentLine('fin', $geo);

        $maintenance->update([
            'end_at' => $now,
            'photo_fin' => $photoFin,
            'commentaire' => $existingComment !== ''
                ? ($existingComment . "\n" . $endComment)
                : $endComment,
        ]);

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
        } else {
            $line = $label . ' distance: ' . $geo['distance_meters'] . ' m';
        }

        if ($geo['is_on_station'] !== null) {
            $line .= ', sur station: ' . ($geo['is_on_station'] ? 'oui' : 'non');
        }

        return $line;
    }

    private function attachPresenceDistanceMeta(PresenceAgents $presence): void
    {
        $meta = $this->extractMaintenanceMeta($presence->commentaires);
        $presence->setAttribute('distance_label', $meta['distance_label']);
        $presence->setAttribute('is_on_station', $meta['is_on_station']);
    }

    private function extractMaintenanceMeta(?string $commentaire): array
    {
        $text = (string) ($commentaire ?? '');

        $dist = null;
        $onStation = null;

        if (preg_match('/(?:Debut|Check-in|Fin|Check-out)\\s+distance:\\s*(\\d+)\\s*m/i', $text, $m)) {
            $dist = (int) $m[1];
        }

        if (preg_match('/sur\\s+station:\\s*(oui|non)/i', $text, $m)) {
            $onStation = strtolower($m[1]) === 'oui';
        }

        return [
            'distance_meters' => $dist,
            'is_on_station' => $onStation,
            'distance_label' => $dist !== null ? ($dist . ' m') : 'N/A',
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

        return ['lat' => (float)$parts[0], 'lng' => (float)$parts[1]];
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
        if (count($parts) !== 2) return null;
        [$lat1, $lng1] = $parts;

        $stations = Station::query()->withoutGlobalScopes()->whereNotNull('latlng')->get();

        return $stations
            ->map(function (Station $station) use ($lat1, $lng1) {
                $coords = array_map('trim', explode(',', (string) $station->latlng));
                if (count($coords) !== 2) return null;
                [$lat2, $lng2] = $coords;
                $station->distance = $this->calculateDistanceMeters((float)$lat1, (float)$lng1, (float)$lat2, (float)$lng2);
                return $station;
            })
            ->filter(fn ($s) => $s && isset($s->distance) && $s->distance <= 500)
            ->sortBy('distance')
            ->first();
    }

    private function getHoraireForAgent(Agent $agent, Carbon $now, ?int $stationId = null): ?PresenceHoraire
    {
        $date = $now->toDateString();
        $yesterday = $now->copy()->subDay()->toDateString();

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

        $planningFor = function (string $d, ?int $sid = null) use ($agent, $groupIdFor) {
            $gid = $groupIdFor($d);
            return AgentGroupPlanning::withoutGlobalScopes()
                ->where('agent_id', $agent->id)
                ->when($gid !== null, fn ($q) => $q->where('agent_group_id', $gid))
                ->when($sid !== null, fn ($q) => $q->where('site_id', $sid))
                ->whereDate('date', $d)
                ->where('is_rest_day', false)
                ->first();
        };

        $planningYesterday = $planningFor($yesterday, $stationId);
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

        if ($isFlexibleYesterday && $planningYesterday && empty($planningYesterday->horaire_id)) {
            return null;
        }

        $planning = $planningFor($date, $stationId);
        if ($planning?->horaire_id) {
            return PresenceHoraire::find($planning->horaire_id);
        }

        if ($isFlexibleToday) {
            return null;
        }

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
     * Utilisé pour charger le journal de pointage (Web Admin)
     */
    public function getPresencesBySiteAndDate(Request $request): JsonResponse
    {
        $data = $request->validate([
            'date' => 'nullable|date',
            'station_id' => 'nullable|integer',
        ]);

        $date = $data['date'] ?? Carbon::today()->toDateString();
        $stationId = $data['station_id'] ?? null;

        $query = PresenceAgents::withoutGlobalScopes()
            ->with(['agent.station', 'horaire', 'stationCheckIn', 'stationCheckOut', 'assignedStation'])
            ->whereDate('date_reference', $date);

        if ($stationId !== null) {
            $query->where(function ($q) use ($stationId) {
                $q->where('site_id', (int) $stationId)
                    ->orWhere('station_check_in_id', (int) $stationId)
                    ->orWhere('station_check_out_id', (int) $stationId);
            });
        }

        $presences = $query->orderByDesc('started_at')->get();

        $presences->each(function($p) {
            $this->attachPresenceDistanceMeta($p);
            $otMin = $this->attendanceService->calculateOvertime($p, $p->horaire);
            $normMin = $this->attendanceService->calculateNormalHours($p, $otMin);
            $p->setAttribute('overtime_minutes', $otMin);
            $p->setAttribute('overtime_display', $this->attendanceService->formatOvertime($otMin));
            $p->setAttribute('normal_hours_display', $this->attendanceService->formatOvertime($normMin));
        });

        return response()->json([
            'status' => 'success',
            'presences' => $presences,
        ]);
    }

    public function dailyReport(Request $request, AttendanceReportService $service): JsonResponse
    {
        $data = $request->validate([
            'date' => 'nullable|date',
            'station_id' => 'nullable|integer',
            'agent_id' => 'nullable|integer',
            'group_id' => 'nullable|integer',
            'per_page' => 'nullable|integer|min:1|max:200',
        ]);

        $date = Carbon::parse($data['date'] ?? Carbon::today()->toDateString())->toDateString();
        $filters = [
            'station_id' => $data['station_id'] ?? null,
            'agent_id' => $data['agent_id'] ?? null,
            'group_id' => $data['group_id'] ?? null,
        ];

        $matrix = $service->buildDailyMatrix(Carbon::parse($date), $filters);

        $expectedAgents = 0;
        $present = 0;
        $late = 0;
        $absent = 0;
        $conges = 0;
        $authorizations = 0;
        $absenceJustifiee = 0;
        $off = 0;
        $countByStation = [];

        $agentsByMatrixKey = collect($matrix['agents'] ?? [])
            ->mapWithKeys(function (Agent $a) {
                $key = $a->fullname . ' (' . $a->matricule . ')';
                return [$key => $a];
            });

        foreach (($matrix['data'] ?? []) as $agentKey => $row) {
            $cell = $row[$date] ?? null;
            $status = is_array($cell) ? ($cell['status'] ?? null) : null;
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
                ];
            }
            $countByStation[$stationKey]['agents'] += 1;

            if ($status === 'off' || $status === 'future' || $status === 'unplanned') {
                $off += 1;
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
            if ($status === 'conge') $conges += 1;
            if ($status === 'autorisation') $authorizations += 1;
            if ($status === 'absence_justifiee') $absenceJustifiee += 1;
        }

        $presencesQuery = PresenceAgents::withoutGlobalScopes()
            ->with(['agent.station', 'horaire', 'stationCheckIn', 'stationCheckOut', 'assignedStation'])
            ->whereDate('date_reference', $date)
            ->when(!empty($data['station_id']), fn ($q) => $q->where('site_id', $data['station_id']));

        $perPage = (int) ($data['per_page'] ?? 25);
        $presences = $presencesQuery->orderByDesc('started_at')->paginate($perPage);

        $presences->getCollection()->each(function($p) use ($service) {
            $this->attachPresenceDistanceMeta($p);
            $otMin = $service->calculateOvertime($p, $p->horaire);
            $p->setAttribute('overtime_display', $service->formatOvertime($otMin));

            $normMin = $service->calculateNormalHours($p, $otMin);
            $p->setAttribute('normal_hours_display', $service->formatOvertime($normMin));
        });

        $this->attachPresenceMotifs($presences->getCollection(), $date);

        return response()->json([
            'status' => 'success',
            'date' => $date,
            'count' => [
                'agents' => count($matrix['agents']),
                'agents_expected' => $expectedAgents,
                'presences' => $present,
                'retards' => $late,
                'absents' => $absent,
                'off' => $off,
                'conges' => $conges,
                'authorizations' => $authorizations,
                'absence_justifiee' => $absenceJustifiee,
            ],
            'count_by_station' => array_values($countByStation),
            'summary' => $matrix['data'],
            'presences' => $presences,
        ]);
    }

    public function agentHistory(Request $request, AttendanceReportService $service): JsonResponse
    {
        $data = $request->validate([
            'agent_id' => 'required|integer|exists:agents,id',
            'from' => 'nullable|date',
            'to' => 'nullable|date',
            'per_page' => 'nullable|integer|min:1|max:500',
            'station_id' => 'nullable|integer',
        ]);

        $query = PresenceAgents::withoutGlobalScopes()
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
            ->orderByDesc('started_at');

        $perPage = (int) ($data['per_page'] ?? 15);
        $page = $query->paginate($perPage);
        $page->getCollection()->each(function($p) use ($service) {
            $this->attachPresenceDistanceMeta($p);
            $otMin = $service->calculateOvertime($p, $p->horaire);
            $p->setAttribute('overtime_display', $service->formatOvertime($otMin));

            $normMin = $service->calculateNormalHours($p, $otMin);
            $p->setAttribute('normal_hours_display', $service->formatOvertime($normMin));

            $p->date_reference_iso = $p->getRawOriginal('date_reference');
            $p->started_at_raw = $p->getRawOriginal('started_at');
            $p->mid_check_raw = $p->getRawOriginal('mid_check');
            $p->ended_at_raw = $p->getRawOriginal('ended_at');
        });

        return response()->json(['status' => 'success', 'history' => $page]);
    }

    public function agentAttendanceSummary(Request $request): JsonResponse
    {
        $data = $request->validate([
            'agent_id' => 'required|integer|exists:agents,id',
            'hours_date' => 'nullable|date',
            'month' => 'nullable|date_format:Y-m',
        ]);

        $now = Carbon::now()->setTimezone('Africa/Kinshasa');
        $agent = Agent::withoutGlobalScopes()->with(['station', 'horaire', 'groupe.horaire'])->findOrFail((int) $data['agent_id']);

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

        $dailyRows = PresenceAgents::where('agent_id', $agent->id)->whereDate('date_reference', $dateReferenceString)->get();

        $totalMinutes = 0;
        foreach ($dailyRows as $row) {
            $start = $row->getRawOriginal('started_at');
            if (!$start) continue;
            $end = $row->getRawOriginal('ended_at') ? Carbon::parse($row->getRawOriginal('ended_at')) : $now;
            $totalMinutes += Carbon::parse($start)->diffInMinutes($end, false);
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

        $isOffDay = AgentGroupPlanning::withoutGlobalScopes()
            ->where('agent_id', $agent->id)
            ->whereDate('date', $dateReferenceString)
            ->where('is_rest_day', true)
            ->exists();

        $todayStatus = $isOffDay ? 'off' : ($isOnLeave ? 'conge' : ($hasPresenceToday ? 'present' : 'absent'));

        $expectedStart = $horaire ? (string) $horaire->getRawOriginal('started_at') : null;
        $expectedMidCheck = $horaire ? (string) $horaire->getRawOriginal('mid_check') : null;
        $expectedEnd = $horaire ? (string) $horaire->getRawOriginal('ended_at') : null;

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
                'expected_start' => $expectedStart ? substr($expectedStart, 0, 5) : null,
                'expected_mid_check' => $expectedMidCheck ? substr($expectedMidCheck, 0, 5) : null,
                'expected_end' => $expectedEnd ? substr($expectedEnd, 0, 5) : null,
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
            ]
        ]);
    }

    public function agentMaintenanceHistory(Request $request): JsonResponse
    {
        $data = $request->validate([
            'agent_id' => 'required|integer|exists:agents,id',
            'per_page' => 'nullable|integer|min:1|max:500',
        ]);

        $query = MaintenanceAgent::query()
            ->with(['station'])
            ->where('agent_id', $data['agent_id'])
            ->orderByDesc('started_at');

        $perPage = (int) ($data['per_page'] ?? 15);
        $page = $query->paginate($perPage);

        $page->getCollection()->each(function($m) {
            $m->date_maintenance_iso = $m->getRawOriginal('date_maintenance');
            $m->started_at_raw = $m->getRawOriginal('started_at');
            $m->end_at_raw = $m->getRawOriginal('end_at');
            $this->attachMaintenanceMeta($m);
        });

        return response()->json(['status' => 'success', 'history' => $page]);
    }

    public function maintenanceAgents(Request $request): JsonResponse
    {
        $stationId = $request->query('station_id');

        $agents = Agent::query()
            ->when($stationId, fn($q) => $q->where('site_id', $stationId))
            ->orderBy('fullname')
            ->get(['id', 'fullname', 'matricule']);

        return response()->json([
            'status' => 'success',
            'agents' => $agents
        ]);
    }

    public function maintenanceReport(Request $request): JsonResponse
    {
        $data = $request->validate([
            'date' => 'nullable|date',
            'from' => 'nullable|date',
            'to' => 'nullable|date',
            'station_id' => 'nullable|integer',
            'agent_id' => 'nullable|integer',
            'per_page' => 'nullable|integer|min:1|max:500',
            'only_active' => 'nullable|boolean',
        ]);

        $baseQuery = MaintenanceAgent::query()
            ->when(!empty($data['only_active']), fn($q) => $q->whereNull('end_at'))
            ->when(!empty($data['station_id']), fn ($q) => $q->where('station_id', (int) $data['station_id']))
            ->when(!empty($data['agent_id']), fn ($q) => $q->where('agent_id', (int) $data['agent_id']));

        if (!empty($data['from']) || !empty($data['to']) || !empty($data['date']) || empty($data['agent_id'])) {
            $baseDate = Carbon::parse($data['date'] ?? Carbon::today()->toDateString());
            $start = !empty($data['from']) ? Carbon::parse($data['from'])->startOfDay() : $baseDate->copy()->startOfDay();
            $end = !empty($data['to']) ? Carbon::parse($data['to'])->endOfDay() : $baseDate->copy()->endOfDay();

            if ($start->gt($end)) [$start, $end] = [$end, $start];

            $baseQuery->whereDate('date_maintenance', '>=', $start->toDateString())
                      ->whereDate('date_maintenance', '<=', $end->toDateString());
        }

        $total = (clone $baseQuery)->count();
        $completed = (clone $baseQuery)->whereNotNull('end_at')->count();
        $ongoing = max($total - $completed, 0);

        $onStation = 0;
        $offStation = 0;
        foreach ((clone $baseQuery)->get(['commentaire']) as $row) {
            $meta = $this->extractMaintenanceMeta((string) $row->commentaire);
            if ($meta['is_on_station'] === true) $onStation += 1;
            elseif ($meta['is_on_station'] === false) $offStation += 1;
        }

        $perPage = (int) ($data['per_page'] ?? 200);
        $maintenances = $baseQuery
            ->with(['agent.station', 'station'])
            ->orderByDesc('date_maintenance')
            ->orderByDesc('started_at')
            ->paginate($perPage);

        $maintenances->getCollection()->transform(function (MaintenanceAgent $m) {
            $m->date_maintenance_iso = $m->getRawOriginal('date_maintenance');
            $m->started_at_raw = $m->getRawOriginal('started_at');
            $m->end_at_raw = $m->getRawOriginal('end_at');
            $this->attachMaintenanceMeta($m);
            return $m;
        });

        return response()->json([
            'status' => 'success',
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

    private function attachMaintenanceMeta(MaintenanceAgent $m): void
    {
        $meta = $this->extractMaintenanceMeta($m->commentaire);
        $m->setAttribute('distance_label', $meta['distance_label']);
        $m->setAttribute('is_on_station', $meta['is_on_station']);
    }

    public function scanStation(Request $request): JsonResponse
    {
        $data = $request->validate([
            'station_id' => 'required|integer',
            'latlng' => 'nullable|string',
        ]);

        $station = Station::withoutGlobalScopes()->find((int) $data['station_id']);
        if (!$station) return response()->json(['status' => 'error', 'errors' => ['Station introuvable.']], 200);

        if (!empty($data['latlng'])) {
            $station->update(['latlng' => $data['latlng']]);
        }

        return response()->json([
            'status' => 'success',
            'station' => $station
        ]);
    }

    public function punchAgent(Request $request): JsonResponse
    {
        return $this->createPresenceAgent($request);
    }

    public function getAllHoraires(Request $request): JsonResponse
    {
        $horaires = PresenceHoraire::when($request->site_id, fn($q) => $q->where('site_id', $request->site_id))->get();
        return response()->json(['status' => 'success', 'horaires' => $horaires]);
    }

    public function createHoraire(Request $request): JsonResponse
    {
        $h = PresenceHoraire::updateOrCreate(['id' => $request->id], $request->all());
        return response()->json(['status' => 'success', 'result' => $h]);
    }

    public function getAllGroups()
    {
        return response()->json(['status' => 'success', 'groups' => AgentGroup::with('horaire')->get()]);
    }

    public function createGroup(Request $request): JsonResponse
    {
        $data = $request->validate([
            'id' => 'nullable|integer',
            'libelle' => 'required|string|max:255',
            'horaire_id' => 'nullable|integer|exists:presence_horaires,id',
            'status' => 'required|string|in:actif,inactif',
        ]);

        $group = AgentGroup::updateOrCreate(['id' => $data['id'] ?? null], [
            'libelle' => $data['libelle'],
            'horaire_id' => $data['horaire_id'],
            'status' => $data['status'],
        ]);

        return response()->json(['status' => 'success', 'result' => $group]);
    }

    private function normalizePunchPhoto($value, $dir): ?string
    {
        if (!$value) return null;
        if (is_string($value)) return $value;
        if ($value instanceof \Illuminate\Http\UploadedFile && $value->isValid()) {
            $filename = 'punch_' . uniqid() . '.' . $value->getClientOriginalExtension();

            // Compression logic using native PHP GD library
            $tempPath = $value->getRealPath();
            $info = getimagesize($tempPath);
            $mime = $info['mime'] ?? '';

            $source = null;
            if ($mime === 'image/jpeg') $source = imagecreatefromjpeg($tempPath);
            elseif ($mime === 'image/png') $source = imagecreatefrompng($tempPath);
            elseif ($mime === 'image/webp') $source = imagecreatefromwebp($tempPath);

            if ($source) {
                // Resize if too large (max width 1200px)
                $width = imagesx($source);
                $height = imagesy($source);
                $maxW = 1200;

                if ($width > $maxW) {
                    $newW = $maxW;
                    $newH = floor($height * ($maxW / $width));
                    $target = imagecreatetruecolor($newW, $newH);
                    imagecopyresampled($target, $source, 0, 0, 0, 0, $newW, $newH, $width, $height);
                    imagedestroy($source);
                    $source = $target;
                }

                $destDir = public_path("punches/$dir");
                if (!file_exists($destDir)) mkdir($destDir, 0755, true);

                $fullPath = "$destDir/$filename";
                // Save as JPEG with 70% quality for optimization
                imagejpeg($source, $fullPath, 70);
                imagedestroy($source);

                return url("punches/$dir/$filename");
            }

            // Fallback if GD fails
            $value->move(public_path("punches/$dir"), $filename);
            return url("punches/$dir/$filename");
        }
        return null;
    }

    public function weeklyReport(Request $request, AttendanceReportService $service): JsonResponse
    {
        $data = $request->validate([
            'date' => 'nullable|date',
            'station_id' => 'nullable|integer',
        ]);

        $baseDate = Carbon::parse($data['date'] ?? Carbon::today()->toDateString());
        $start = $baseDate->copy()->startOfWeek(Carbon::MONDAY);
        $end = $start->copy()->addDays(6);

        $filters = [
            'station_id' => $data['station_id'] ?? null,
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

    public function monthlyReport(Request $request, AttendanceReportService $service): \Symfony\Component\HttpFoundation\Response
    {
        $data = $request->validate([
            'month' => 'nullable|integer|min:1|max:12',
            'year' => 'nullable|integer|min:2000|max:2100',
            'station_id' => 'nullable|integer',
            'agent_id' => 'nullable|integer',
            'group_id' => 'nullable|integer',
            'matricule_prefix' => 'nullable|string',
        ]);

        $month = (int) ($data['month'] ?? Carbon::now()->month);
        $year = (int) ($data['year'] ?? Carbon::now()->year);

        $filters = [
            'station_id' => $data['station_id'] ?? null,
            'agent_id' => $data['agent_id'] ?? null,
            'group_id' => $data['group_id'] ?? null,
            'matricule_prefix' => $data['matricule_prefix'] ?? null,
        ];

        $matrix = $service->buildMonthlyMatrix($month, $year, $filters);

        $showMatriculeFilter = str_contains($request->getHost(), 'premierbet');
        $prefixes = [];

        if ($showMatriculeFilter) {
            $prefixes = Agent::query()
                ->whereNotNull('matricule')
                ->get(['matricule'])
                ->map(function ($a) {
                    $m = (string) $a->matricule;
                    if (str_contains($m, '-')) {
                        return explode('-', $m)[0];
                    }
                    if (preg_match('/^[A-Za-z]+/', $m, $matches)) {
                        return strtoupper($matches[0]);
                    }
                    return strtoupper(substr($m, 0, 2));
                })
                ->unique()
                ->filter()
                ->values()
                ->all();
        }

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
            'show_matricule_filter' => $showMatriculeFilter,
            'prefixes' => $prefixes,
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

    public function dailyAbsenceReport(Request $request, AbsenceReportService $service): JsonResponse
    {
        $data = $request->validate([
            'date' => 'nullable|date',
            'from' => 'nullable|date',
            'to' => 'nullable|date',
            'station_id' => 'nullable|integer',
            'per_page' => 'nullable|integer|min:1|max:2000',
            'page' => 'nullable|integer|min:1',
        ]);

        $base = Carbon::parse($data['date'] ?? Carbon::today()->toDateString());
        $start = !empty($data['from']) ? Carbon::parse($data['from'])->startOfDay() : $base->copy()->startOfDay();
        $end = !empty($data['to']) ? Carbon::parse($data['to'])->startOfDay() : $base->copy()->endOfDay();
        if ($start->gt($end)) [$start, $end] = [$end, $start];

        $rows = $service->buildAbsenceRows($start, $end, $data['station_id'] ?? null);

        $perPage = (int) ($data['per_page'] ?? 500);
        $page = (int) ($data['page'] ?? 1);
        $paginator = new LengthAwarePaginator(
            array_slice($rows, max(($page - 1) * $perPage, 0), $perPage),
            count($rows),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return response()->json(['status' => 'success', 'from' => $start->toDateString(), 'to' => $end->toDateString(), 'absences' => $paginator]);
    }

    public function dailyLateReport(Request $request, LateReportService $service): JsonResponse
    {
        $data = $request->validate([
            'period' => 'nullable|string|in:daily,weekly,monthly',
            'from' => 'nullable|date',
            'to' => 'nullable|date',
            'station_id' => 'nullable|integer',
            'per_page' => 'nullable|integer|min:1|max:2000',
            'page' => 'nullable|integer|min:1',
        ]);

        $period = (string) ($data['period'] ?? 'daily');
        $start = !empty($data['from']) ? Carbon::parse($data['from'])->startOfDay() : Carbon::today()->startOfDay();
        $end = !empty($data['to']) ? Carbon::parse($data['to'])->startOfDay() : $start->copy();
        if ($start->gt($end)) [$start, $end] = [$end, $start];

        if ($period === 'weekly') {
            $start = $start->copy()->startOfWeek(Carbon::MONDAY);
            $end = $end->copy()->endOfWeek(Carbon::MONDAY)->startOfDay();
        } elseif ($period === 'monthly') {
            $start = $start->copy()->startOfMonth();
            $end = $end->copy()->endOfMonth()->startOfDay();
        }

        $rows = $service->buildLateRows($start, $end, $data['station_id'] ?? null);

        $perPage = (int) ($data['per_page'] ?? 500);
        $page = (int) ($data['page'] ?? 1);
        $paginator = new LengthAwarePaginator(
            array_slice($rows, max(($page - 1) * $perPage, 0), $perPage),
            count($rows),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return response()->json(['status' => 'success', 'period' => $period, 'from' => $start->toDateString(), 'to' => $end->toDateString(), 'retards' => $paginator]);
    }

    public function cumulativeAlertsReport(Request $request, CumulativeAlertService $service): JsonResponse
    {
        $data = $request->validate([
            'period' => 'nullable|string|in:daily,weekly,monthly',
            'from' => 'nullable|date',
            'to' => 'nullable|date',
            'station_id' => 'nullable|integer',
            'threshold' => 'nullable|integer|min:1|max:31',
        ]);

        $range = $service->resolveRange($data);
        $threshold = (int) ($data['threshold'] ?? 3);
        $alerts = $service->buildAlerts($range['start'], $range['end'], $data['station_id'] ?? null, $threshold);

        return response()->json([
            'status' => 'success',
            'period' => $range['period'],
            'period_label' => $range['label'],
            'from' => $range['start']->toDateString(),
            'to' => $range['end']->toDateString(),
            'threshold' => $threshold,
            'absences' => $alerts['absences'] ?? [],
            'retards' => $alerts['retards'] ?? [],
            'departs' => $alerts['departs'] ?? [],
            'counts' => ['absences' => count($alerts['absences'] ?? []), 'retards' => count($alerts['retards'] ?? []), 'departs' => count($alerts['departs'] ?? [])],
        ]);
    }

    private function attachPresenceMotifs(\Illuminate\Support\Collection $rows, string $date): void
    {
        $agentIds = $rows->pluck('agent_id')->filter()->unique()->values()->all();
        if (empty($agentIds)) return;

        $authorizations = AttendanceAuthorization::query()->whereIn('agent_id', $agentIds)->where('status', 'approved')->whereDate('date_reference', $date)->get()->groupBy('agent_id');
        $justifications = AttendanceJustification::query()->whereIn('agent_id', $agentIds)->where('status', 'approved')->whereDate('date_reference', $date)->get()->groupBy('agent_id');
        $conges = Conge::query()->whereIn('agent_id', $agentIds)->where('status', 'approved')->whereDate('date_debut', '<=', $date)->whereDate('date_fin', '>=', $date)->get()->groupBy('agent_id');

        foreach ($rows as $p) {
            $motifs = [];
            if ($auth = optional($authorizations->get($p->agent_id))->first()) {
                $label = 'Autorisation';
                if (!empty($auth->reason)) $label .= ': ' . $auth->reason;
                elseif (!empty($auth->type)) $label .= ': ' . strtoupper((string) $auth->type);
                $motifs[] = $label;
            }
            if ($justif = optional($justifications->get($p->agent_id))->first()) {
                $label = $justif->kind === 'retard' ? 'Retard justifie' : 'Absence justifiee';
                if (!empty($justif->justification)) $label .= ': ' . $justif->justification;
                $motifs[] = $label;
            }
            if ($conge = optional($conges->get($p->agent_id))->first()) {
                $label = 'Conge';
                if (!empty($conge->motif)) $label .= ': ' . $conge->motif;
                $motifs[] = $label;
            }
            if (($p->retard ?? '') === 'oui' && !isset($justif)) $motifs[] = 'Retard';
            $p->setAttribute('motif', implode(' | ', $motifs));
        }
    }
}
