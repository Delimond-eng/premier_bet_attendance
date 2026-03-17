<?php

namespace App\Http\Controllers;

use App\Models\AgentBiometric;
use App\Models\MobileDevice;
use App\Services\FcmService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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

    public function testFcm()
    {
        Log::info("Test FCM global initié par l'utilisateur: " . auth()->user()->name);

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
                Log::info("Test FCM envoyé avec succès au terminal: " . $device->imei);
            } catch (\Exception $e) {
                Log::error("Échec test FCM pour terminal " . $device->imei . ": " . $e->getMessage());
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Test envoyé à $successCount terminal(aux). Vérifiez les logs pour les détails."
        ]);
    }
}
