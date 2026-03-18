<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\AgentBiometric;
use App\Models\MobileDevice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class BiometricApiController extends Controller
{
    /**
     * Enregistrer ou mettre à jour un terminal mobile.
     */
    public function registerDevice(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'imei' => 'required|string',
            'firebase_token' => 'required|string',
            'platform' => 'nullable|string',
            'device_name' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $device = MobileDevice::where('imei', $request->imei)->first();

        if ($device) {
            // ✅ On met à jour uniquement ce qu'on veut
            $device->update([
                'firebase_token' => $request->firebase_token,
                'last_seen_at' => now(),
            ]);
        } else {
            // ✅ On crée avec toutes les infos
            $device = MobileDevice::create([
                'imei' => $request->imei,
                'firebase_token' => $request->firebase_token,
                'platform' => $request->platform,
                'device_name' => $request->device_name,
                'last_seen_at' => now(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Device registered successfully',
            'data' => $device
        ]);
    }

    /**
     * Récupérer les embeddings biométriques pour une liste de matricules.
     */
    public function getEmbeddingsByMatricules(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'matricules' => 'required|array',
            'matricules.*' => 'string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // 🔥 Récupérer les biométriques
        $biometrics = AgentBiometric::whereIn('matricule', $request->matricules)
            ->where('status', 'active')
            ->get([
                'matricule',
                'embedding',
                'model_version',
                'quality_score',
                'status',
                'updated_at'
            ]);

        // 🔥 Récupérer les agents correspondants
        $agents = Agent::whereIn('matricule', $request->matricules)
            ->pluck('fullname', 'matricule'); // clé = matricule

        // 🔥 Ajouter le nom à chaque biométrique
        $biometrics = $biometrics->map(function ($item) use ($agents) {
            $item->name = $agents[$item->matricule] ?? null;
            return $item;
        });

        return response()->json([
            'success' => true,
            'count' => $biometrics->count(),
            'data' => $biometrics
        ]);
    }

    /**
     * Enrôler un agent avec son embedding biométrique.
     */
    public function enrollBiometric(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'matricule' => 'required|string|exists:agents,matricule',
            'embedding' => 'required|string',
            'model_version' => 'nullable|string',
            'quality_score' => 'nullable|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $agent = Agent::where('matricule', $request->matricule)->first();

        $biometric = AgentBiometric::updateOrCreate(
            ['matricule' => $request->matricule],
            [
                'agent_id' => $agent->id,
                'embedding' => $request->embedding,
                'model_version' => $request->model_version,
                'quality_score' => $request->quality_score,
                'status' => 'active',
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Biometric data enrolled successfully',
            'data' => $biometric
        ]);
    }
}
