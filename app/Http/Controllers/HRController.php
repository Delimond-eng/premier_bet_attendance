<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\AttendanceAuthorization;
use App\Models\AttendanceJustification;
use App\Models\Conge;
use App\Models\CongeType;
use App\Services\AttendanceReportService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HRController extends Controller
{
    /**
     * Congés (TYPES) : liste.
     */
    public function congesIndex(Request $request): JsonResponse
    {
        $query = CongeType::query()
            ->when($request->query('status'), fn ($q) => $q->where('status', $request->query('status')))
            ->orderByDesc('id');

        $perPage = (int) ($request->query('per_page', 15));

        return response()->json([
            'status' => 'success',
            // Pour garder le flow existant côté Vue, on conserve la clé "conges"
            'conges' => $query->paginate($perPage),
        ]);
    }

    /**
     * Congés (TYPES) : création / modification.
     */
    public function congesStore(Request $request): JsonResponse
    {
        $data = $request->validate([
            'id' => 'nullable|integer|exists:conge_types,id',
            'libelle' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'nullable|string|in:actif,inactif',
        ]);

        $type = CongeType::updateOrCreate(
            ['id' => $data['id'] ?? null],
            [
                'libelle' => $data['libelle'],
                'description' => $data['description'] ?? null,
                'status' => $data['status'] ?? 'actif',
            ]
        );

        return response()->json([
            'status' => 'success',
            'result' => $type,
        ]);
    }

    /**
     * Congés (TYPES) : suppression.
     */
    public function congesDelete(Request $request): JsonResponse
    {
        $data = $request->validate([
            'id' => 'required|integer|exists:conge_types,id',
        ]);

        CongeType::whereKey($data['id'])->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Type de congé supprimé.',
        ]);
    }

    public function authorizationsIndex(Request $request): JsonResponse
    {
        $query = AttendanceAuthorization::query()
            ->with(['agent.station'])
            ->when($request->query('agent_id'), fn ($q) => $q->where('agent_id', $request->query('agent_id')))
            ->when($request->query('status'), fn ($q) => $q->where('status', $request->query('status')))
            ->when($request->query('type'), fn ($q) => $q->where('type', $request->query('type')))
            ->when($request->query('from'), fn ($q) => $q->whereDate('date_reference', '>=', $request->query('from')))
            ->when($request->query('to'), fn ($q) => $q->whereDate('date_reference', '<=', $request->query('to')))
            ->orderByDesc('id');

        $perPage = (int) ($request->query('per_page', 15));

        return response()->json([
            'status' => 'success',
            'authorizations' => $query->paginate($perPage),
        ]);
    }

    public function authorizationsStore(Request $request): JsonResponse
    {
        $data = $request->validate([
            'id' => 'nullable|integer|exists:attendance_authorizations,id',
            'agent_id' => 'required|integer|exists:agents,id',
            'date_reference' => 'required|date',
            'type' => 'required|string',
            'started_at' => 'nullable|date_format:H:i',
            'ended_at' => 'nullable|date_format:H:i',
            'minutes' => 'nullable|integer|min:0',
            'reason' => 'nullable|string',
            'status' => 'nullable|string|in:pending,approved,rejected',
        ]);

        $data['approved_by'] = ($data['status'] ?? null) === 'approved' ? auth()->id() : null;

        $auth = AttendanceAuthorization::updateOrCreate(['id' => $data['id'] ?? null], $data);

        return response()->json([
            'status' => 'success',
            'result' => $auth->load(['agent.station']),
        ]);
    }

    public function authorizationsDelete(Request $request): JsonResponse
    {
        $data = $request->validate([
            'id' => 'required|integer|exists:attendance_authorizations,id',
        ]);

        AttendanceAuthorization::whereKey($data['id'])->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Autorisation supprimée.',
        ]);
    }

    public function justificationsIndex(Request $request): JsonResponse
    {
        $query = AttendanceJustification::query()
            ->with(['agent.station', 'presence.assignedStation'])
            ->when($request->query('agent_id'), fn ($q) => $q->where('agent_id', $request->query('agent_id')))
            ->when($request->query('status'), fn ($q) => $q->where('status', $request->query('status')))
            ->when($request->query('kind'), fn ($q) => $q->where('kind', $request->query('kind')))
            ->when($request->query('from'), fn ($q) => $q->whereDate('date_reference', '>=', $request->query('from')))
            ->when($request->query('to'), fn ($q) => $q->whereDate('date_reference', '<=', $request->query('to')))
            ->orderByDesc('id');

        $perPage = (int) ($request->query('per_page', 15));

        return response()->json([
            'status' => 'success',
            'justifications' => $query->paginate($perPage),
        ]);
    }

    public function justificationsStore(Request $request): JsonResponse
    {
        $data = $request->validate([
            'id' => 'nullable|integer|exists:attendance_justifications,id',
            'agent_id' => 'required|integer|exists:agents,id',
            'presence_agent_id' => 'nullable|integer|exists:presence_agents,id',
            'date_reference' => 'required|date',
            'kind' => 'required|string|in:retard,absence',
            'justification' => 'required|string',
            'status' => 'nullable|string|in:pending,approved,rejected',
        ]);

        $data['approved_by'] = ($data['status'] ?? null) === 'approved' ? auth()->id() : null;

        $justif = AttendanceJustification::updateOrCreate(['id' => $data['id'] ?? null], $data);

        return response()->json([
            'status' => 'success',
            'result' => $justif->load(['agent.station', 'presence.assignedStation']),
        ]);
    }

    public function justificationsDelete(Request $request): JsonResponse
    {
        $data = $request->validate([
            'id' => 'required|integer|exists:attendance_justifications,id',
        ]);

        AttendanceJustification::whereKey($data['id'])->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Justification supprimée.',
        ]);
    }

    /**
     * Pointage mensuel regroupé par stations (avec impact RH basique).
     */
    public function monthlyTimesheet(Request $request, AttendanceReportService $service): JsonResponse
    {
        $data = $request->validate([
            'month' => 'nullable|integer|min:1|max:12',
            'year' => 'nullable|integer|min:2000|max:2100',
            'station_id' => 'nullable|integer|exists:sites,id',
            'group_id' => 'nullable|integer|exists:agent_groups,id',
        ]);

        $month = (int) ($data['month'] ?? Carbon::now()->month);
        $year = (int) ($data['year'] ?? Carbon::now()->year);

        $stationId = $data['station_id'] ?? null;
        $groupId = $data['group_id'] ?? null;

        $stations = $stationId
            ? \App\Models\Station::query()->where('id', $stationId)->get()
            : \App\Models\Station::query()->orderBy('name')->get();

        $result = [];
        foreach ($stations as $station) {
            $matrix = $service->buildMonthlyMatrix($month, $year, [
                'station_id' => $station->id,
                'group_id' => $groupId,
            ]);

            $result[] = [
                'station' => $station,
                'data' => $matrix['data'],
            ];
        }

        return response()->json([
            'status' => 'success',
            'month' => $month,
            'year' => $year,
            'stations' => $result,
        ]);
    }

    /**
     * Attributions : liste des congés assignés aux agents.
     */
    public function attributionsIndex(Request $request): JsonResponse 
    { 
        $tz = 'Africa/Kinshasa'; 
        $today = Carbon::now($tz)->startOfDay(); 
 
        $query = Conge::query() 
            ->with(['agent.station', 'congeType']) 
            ->when($request->query('agent_id'), fn ($q) => $q->where('agent_id', $request->query('agent_id'))) 
            ->when($request->query('conge_type_id'), fn ($q) => $q->where('conge_type_id', $request->query('conge_type_id'))) 
            ->when($request->query('status'), fn ($q) => $q->where('status', $request->query('status'))) 
            ->when($request->query('from'), fn ($q) => $q->whereDate('date_fin', '>=', $request->query('from'))) 
            ->when($request->query('to'), fn ($q) => $q->whereDate('date_debut', '<=', $request->query('to'))) 
            ->orderByDesc('date_debut') 
            ->orderByDesc('id'); 
 
        $perPage = (int) ($request->query('per_page', 15)); 
 
        $page = $query->paginate($perPage); 
        $page->getCollection()->transform(function (Conge $c) use ($today, $tz) { 
            try { 
                $start = Carbon::parse($c->date_debut, $tz)->startOfDay(); 
                $end = Carbon::parse($c->date_fin, $tz)->startOfDay(); 
 
                // Jours totaux (inclusif)
                $totalDays = $start->diffInDays($end) + 1; 
 
                // Jours consommés: du début jusqu'à aujourd'hui inclus, sans dépasser la fin.
                $consumeTo = $today->lt($start) ? null : ($today->lt($end) ? $today : $end); 
                $daysConsumed = $consumeTo ? ($start->diffInDays($consumeTo) + 1) : 0; 
 
                // Statut de période (indépendant du workflow pending/approved/rejected)
                $periodStatus = $today->lt($start) ? 'a_venir' : ($today->gt($end) ? 'termine' : 'en_cours'); 
 
                $c->days_total = $totalDays; 
                $c->days_consumed = min($daysConsumed, $totalDays); 
                $c->period_status = $periodStatus; 
            } catch (\Throwable $_) { 
                $c->days_total = null; 
                $c->days_consumed = null; 
                $c->period_status = null; 
            } 
            return $c; 
        }); 
 
        return response()->json([ 
            'status' => 'success', 
            'attributions' => $page, 
        ]); 
    } 

    /**
     * Attributions : assigner un congé à un agent.
     */
    public function attributionsStore(Request $request): JsonResponse
    {
        $data = $request->validate([
            'id' => 'nullable|integer|exists:conges,id',
            'agent_id' => 'required|integer|exists:agents,id',
            'conge_type_id' => 'required|integer|exists:conge_types,id',
            'date_debut' => 'required|date',
            'date_fin' => 'required|date|after_or_equal:date_debut',
            'motif' => 'nullable|string',
            'status' => 'nullable|string|in:pending,approved,rejected',
        ]);

        $type = CongeType::findOrFail($data['conge_type_id']);

        $payload = [
            'agent_id' => $data['agent_id'],
            'conge_type_id' => $data['conge_type_id'],
            'type' => $type->libelle, // compat legacy
            'date_debut' => $data['date_debut'],
            'date_fin' => $data['date_fin'],
            'motif' => $data['motif'] ?? null,
            'status' => $data['status'] ?? 'pending',
            'approved_by' => ($data['status'] ?? null) === 'approved' ? auth()->id() : null,
        ];

        $conge = Conge::updateOrCreate(['id' => $data['id'] ?? null], $payload);

        return response()->json([
            'status' => 'success',
            'result' => $conge->load(['agent.station', 'congeType']),
        ]);
    }

    /**
     * Attributions : supprimer une assignation.
     */
    public function attributionsDelete(Request $request): JsonResponse
    {
        $data = $request->validate([
            'id' => 'required|integer|exists:conges,id',
        ]);

        Conge::whereKey($data['id'])->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Attribution supprimée.',
        ]);
    }

    /**
     * Listes de référence (agents + types) pour les formulaires Vue.
     */
    public function referenceData(): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'agents' => Agent::query()->with('station')->orderBy('fullname')->get(),
            'types' => CongeType::query()->where('status', 'actif')->orderBy('libelle')->get(),
        ]);
    }
}
