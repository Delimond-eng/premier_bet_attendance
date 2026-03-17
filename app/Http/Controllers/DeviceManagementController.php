<?php

namespace App\Http\Controllers;

use App\Models\AgentBiometric;
use App\Models\MobileDevice;
use App\Services\FcmService;
use Illuminate\Http\Request;

class DeviceManagementController extends Controller
{
    protected $fcmService;

    public function __construct(FcmService $fcmService)
    {
        $this->fcmService = $fcmService;
    }

    /**
     * Liste des terminaux mobiles.
     */
    public function index()
    {
        $devices = MobileDevice::orderBy('last_seen_at', 'desc')->paginate(20);
        $biometrics = AgentBiometric::with('agent')->get();

        return view('devices', compact('devices', 'biometrics'));
    }

    /**
     * Mettre à jour le nom du terminal.
     */
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

    /**
     * Envoyer une notification de synchronisation à un terminal.
     */
    public function sync(Request $request, MobileDevice $device)
    {
        $request->validate([
            'matricules' => 'required|array',
            'matricules.*' => 'string',
        ]);

        try {
            $this->fcmService->sendBiometricSync($device->firebase_token, $request->matricules);

            return response()->json([
                'success' => true,
                'message' => 'Notification de synchronisation envoyée avec succès.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'envoi FCM : ' . $e->getMessage()
            ], 500);
        }
    }
}
