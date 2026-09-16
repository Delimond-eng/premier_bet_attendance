<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\AgentBiometric;
use App\Models\DeviceFaceList;
use App\Models\MobileDevice;
use App\Services\FcmService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DeviceManagementController extends Controller
{
    protected $fcmService;

    public function __construct(FcmService $fcmService)
    {
        $this->fcmService = $fcmService;
    }

    public function index()
    {
        $devices = MobileDevice::orderBy('last_seen_at', 'desc')->paginate(20);
        $biometrics = AgentBiometric::with('agent')->get();

        return view('devices', compact('devices', 'biometrics'));
    }

    public function update(Request $request, MobileDevice $device)
    {
        $request->validate([
            'device_name' => 'required|string|max:255',
        ]);

        $device->update([
            'device_name' => $request->device_name,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Terminal mis à jour avec succès.',
            'device' => $device
        ]);
    }

    public function destroy(MobileDevice $device)
    {
        try {
            $device->delete();
            return response()->json([
                'success' => true,
                'message' => 'Terminal supprimé avec succès.'
            ]);
        } catch (\Exception $e) {
            Log::error("Erreur suppression terminal", [
                'error' => $e->getMessage(),
                'device' => $device->id
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression : ' . $e->getMessage()
            ], 500);
        }
    }

    public function sync(Request $request, MobileDevice $device)
    {
        $request->validate([
            'matricules' => 'required|array',
            'matricules.*' => 'string',
        ]);

        Log::info("Début synchronisation biométrique", [
            'device_id' => $device->id,
            'imei' => $device->imei,
            'matricules' => $request->matricules
        ]);

        try {
            $this->fcmService->sendBiometricSync($device->firebase_token, $request->matricules);
            Log::info("Notification FCM de synchronisation envoyée avec succès.");

            return response()->json([
                'success' => true,
                'message' => 'Notification de synchronisation envoyée avec succès.'
            ]);
        } catch (\Exception $e) {
            Log::error("Erreur envoi FCM synchronisation", [
                'error' => $e->getMessage(),
                'device' => $device->id
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'envoi FCM : ' . $e->getMessage()
            ], 500);
        }
    }

    public function requestFaceList(MobileDevice $device)
    {
        if (empty($device->firebase_token)) {
            return response()->json([
                'success' => false,
                'message' => 'Ce terminal n\'a pas de token Firebase enregistré.'
            ], 422);
        }

        $requestId = (string) Str::uuid();

        try {
            $record = DeviceFaceList::create([
                'device_id' => $device->id,
                'device_imei' => $device->imei,
                'request_id' => $requestId,
                'command' => 'FACE_LIST',
                'status' => 'pending',
                'matricules' => [],
                'count' => 0,
                'sent_at' => now(),
            ]);

            $this->fcmService->sendFaceListRequest($device->firebase_token, $requestId);

            Log::info('Commande FACE_LIST envoyée au terminal', [
                'device_id' => $device->id,
                'imei' => $device->imei,
                'request_id' => $requestId,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Commande FACE_LIST envoyée au terminal.',
                'request_id' => $requestId,
                'record_id' => $record->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur envoi commande FACE_LIST', [
                'error' => $e->getMessage(),
                'device_id' => $device->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'envoi de la commande FACE_LIST : ' . $e->getMessage(),
            ], 500);
        }
    }

    protected function normalizeMatricules($rawMatricules): array
    {
        if (!is_array($rawMatricules)) {
            return [];
        }

        $normalized = array_map(function ($matricule) {
            if (!is_string($matricule) && !is_int($matricule)) {
                return null;
            }

            $clean = trim((string) $matricule);
            return $clean === '' ? null : $clean;
        }, $rawMatricules);

        $normalized = array_filter($normalized, fn ($value) => $value !== null && $value !== '');

        return array_values(array_unique($normalized));
    }

    public function handleFaceListResponse(Request $request)
    {
        $validated = $request->validate([
            'request_id' => 'required|string',
            'imei' => 'required|string',
            'command' => 'required|string',
            'data' => 'required|array',
        ]);

        $device = MobileDevice::where('imei', $validated['imei'])->first();

        if (!$device) {
            return response()->json([
                'success' => false,
                'message' => 'Terminal introuvable pour cet IMEI.',
            ], 404);
        }

        $matricules = $this->normalizeMatricules($request->input('data.matricules', []));
        $count = $request->input('data.count', count($matricules));
        $count = is_numeric($count) ? (int) $count : count($matricules);

        $record = DeviceFaceList::where('request_id', $validated['request_id'])
            ->where('device_imei', $validated['imei'])
            ->first();

        $payload = [
            'device_id' => $device->id,
            'device_imei' => $validated['imei'],
            'request_id' => $validated['request_id'],
            'command' => $validated['command'],
            'status' => 'received',
            'matricules' => $matricules,
            'count' => max($count, count($matricules)),
            'received_at' => now(),
            'response_payload' => $request->all(),
        ];

        if (!$record) {
            $record = DeviceFaceList::create($payload);
        } else {
            $record->update($payload);
        }

        $device->update(['last_seen_at' => now()]);

        Log::info('Réponse FACE_LIST reçue du terminal', [
            'device_id' => $device->id,
            'imei' => $device->imei,
            'request_id' => $validated['request_id'],
            'count' => $record->count,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Réponse FACE_LIST enregistrée.',
            'data' => [
                'request_id' => $record->request_id,
                'count' => $record->count,
                'matricules' => $record->matricules,
            ],
        ]);
    }

    public function getFaceListForDevice(MobileDevice $device)
    {
        $latest = DeviceFaceList::where('device_id', $device->id)
            ->where('command', 'FACE_LIST')
            ->orderByDesc('received_at')
            ->first();

        if (!$latest) {
            return response()->json([
                'success' => false,
                'message' => 'Aucune liste biométrique reçue pour ce terminal.',
            ], 404);
        }

        $matricules = $latest->matricules ?? [];
        $agents = Agent::whereIn('matricule', $matricules)
            ->select(['id', 'matricule', 'fullname', 'photo', 'site_id'])
            ->with('station:id,name')
            ->get()
            ->keyBy('matricule');

        $displayMatricules = array_values(array_map(function ($matricule) use ($agents) {
            $agent = $agents->get($matricule);

            return [
                'matricule' => $matricule,
                'fullname' => $agent?->fullname ?? 'N/A',
                'photo' => $agent?->photo ?: asset('assets/img/avatar.jpg'),
                'station_name' => $agent?->station?->name ?? '--',
            ];
        }, $matricules));

        return response()->json([
            'success' => true,
            'data' => [
                'device' => $device->device_name ?? $device->imei,
                'request_id' => $latest->request_id,
                'count' => $latest->count,
                'matricules' => $displayMatricules,
                'received_at' => $latest->received_at?->format('d/m/Y H:i:s'),
            ],
        ]);
    }

    public function deleteBiometricsFromDevice(Request $request, MobileDevice $device)
    {
        $request->validate([
            'matricules' => 'required|array',
            'matricules.*' => 'string',
        ]);

        if (empty($device->firebase_token)) {
            return response()->json([
                'success' => false,
                'message' => 'Ce terminal n\'a pas de token Firebase enregistré.'
            ], 422);
        }

        $matricules = $this->normalizeMatricules($request->input('matricules', []));

        if (empty($matricules)) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun matricule à supprimer sur ce terminal.'
            ], 422);
        }

        try {
            $this->fcmService->sendBiometricDelete($device->firebase_token, $matricules);

            Log::info('Commande biometric_delete envoyée au terminal', [
                'device_id' => $device->id,
                'imei' => $device->imei,
                'matricules' => $matricules,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Commande de suppression envoyée au terminal.',
                'count' => count($matricules),
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur envoi suppression biométrique au terminal', [
                'device_id' => $device->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'envoi de la suppression : ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Envoyer une commande FCM 'update' à un terminal spécifique ou à tous.
     * La route attend un paramètre 'imei' en GET si on veut cibler un seul appareil.
     */
    public function sendFcmUpdate(Request $request)
    {
        $imei = $request->query('imei');
        $updateUrl = 'https://md.salama-drc.com/terminal.apk';

        $query = MobileDevice::whereNotNull('firebase_token');

        if ($imei) {
            $query->where('imei', $imei);
        }

        $devices = $query->get();

        if ($devices->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Aucun terminal trouvé avec un token Firebase.'
            ], 404);
        }

        $sentCount = 0;
        foreach ($devices as $device) {
            try {
                $this->fcmService->sendMdmCommand($device->firebase_token, 'update', [
                    'url' => $updateUrl
                ]);
                $sentCount++;
            } catch (\Exception $e) {
                Log::error("Erreur envoi FCM update vers {$device->imei}: " . $e->getMessage());
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => "Commande update envoyée à $sentCount terminal(aux).",
            'url' => $updateUrl
        ]);
    }

    public function testFcm()
    {
        Log::info("Test FCM global initié.");

        $devices = MobileDevice::whereNotNull('firebase_token')->get();

        if ($devices->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'Aucun terminal avec token trouvé.']);
        }

        $successCount = 0;
        foreach ($devices as $device) {
            try {
                $this->fcmService->notify(
                    $device->firebase_token,
                    "Test de connexion",
                    "Le service de synchronisation est opérationnel sur ce terminal."
                );
                $successCount++;
            } catch (\Exception $e) {
                Log::error("Échec test FCM pour terminal " . $device->imei . ": " . $e->getMessage());
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Test envoyé à $successCount terminal(aux)."
        ]);
    }

    public function destroyBiometric($id)
    {
        try {
            $bio = AgentBiometric::findOrFail($id);
            $bio->delete();
            return response()->json([
                'success' => true,
                'message' => 'Donnée biométrique supprimée avec succès.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression : ' . $e->getMessage()
            ], 500);
        }
    }
}