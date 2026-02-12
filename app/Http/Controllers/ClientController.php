<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Patrol;
use App\Models\PresenceAgents;
use App\Models\Site;
use App\Models\Token;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class ClientController extends Controller
{
    /**
     * Login client
     * @param Request $request
     * @return JsonResponse
    */
    public function loginClient(Request $request)
    {
        try {
            $data = $request->validate([
                "code" => "required|string",
            ]);

            $client = Site::where("code", $data["code"])->first();
            
            if ($client) {
                // Génération OTP
                $otp = rand(10000, 99999);
                $client->otp = $otp;
                $client->save();

                // Envoi de l'email
                Mail::raw("Votre code OTP est : $otp", function ($message) use ($client) {
                    $message->to($client->client_email)
                            ->subject('Code OTP de connexion');
                });

                $emailMasque = $this->maskEmail($client->client_email);

                return response()->json([
                    "status" => "success",
                    "message" => "Un code OTP a été envoyé à l'adresse $emailMasque",
                ]);
            
            } else {
                return response()->json(['errors' => 'Code du site non reconnu !']);
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            $errors = $e->validator->errors()->all();
            return response()->json(['errors' => $errors]);
        } catch (\Illuminate\Database\QueryException $e) {
            return response()->json(['errors' => $e->getMessage()]);
        }
    }

    /**
     * Verification de l'otp du client
     * @param Request $request
     * @return JsonResponse
    */
    public function verifyOtp(Request $request)
    {
        $data = $request->validate([
            "code" => "required|string",
            "otp" => "required|digits:5"
        ]);

        $client = Site::where("code", $data["code"])
                    ->where("otp", $data["otp"])
                    ->first();

        if ($client) {
            // Optionnel : invalider l'OTP après utilisation
            $client->otp = null;
            $client->save();

            return response()->json([
                "status" => "success",
                "client" => $client
            ]);
        }

        return response()->json([
            "errors" => "OTP invalide ou expiré."
        ]);
    }


    /**
     * Masque un email de client et retourne une partie
     * @param string $email
     * @return string
    */
    private function maskEmail($email) {
        [$name, $domain] = explode('@', $email);
        $visible = substr($name, 0, 2);
        return $visible . str_repeat('*', 3) . '@' . $domain;
    }


    /**
     * Update client Token
     * @param Request $request
     * @return JsonResponse
    */
    public function  updateFcmToken(Request $request) {
        try {
            // Validation des données
            $data = $request->validate([
                "token" => "required|string",
                "id" => "required|int|exists:sites,id",
            ]);
            $site = Site::find($data["id"]);
            $site->update([
                "fcm_token"=>$data["token"]
            ]);
            $token = Token::updateOrCreate(["token"=>$data["token"]],[
                "site_id"=>$data["id"],
                "token"=>$data["token"]
            ]);
            return response()->json([
                "status"=>"success",
                "result"=>$token,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $errors = $e->validator->errors()->all();
            return response()->json(['errors' => $errors], );
        } catch (\Illuminate\Database\QueryException $e) {
            return response()->json(['errors' => $e->getMessage()], );
        }
    }



    /**
     * GET Client pending patrol
     * @return JsonResponse
    */
    public function getPendingPatrol(Request $request){
        $clientId = $request->query("id");

        $patrols = Patrol::with("site.areas")
            ->with("agent")
            ->with("scans.agent")
            ->with("scans.area")
            ->with("planning")
            ->where("status", "pending")
            ->where("site_id", $clientId)
            ->whereDate("started_at", Carbon::today()->setTimezone("Africa/Kinshasa"))
            ->get();

        return response()->json([
            "status" => "success",
            "result" => $patrols
        ]);
    }


    /**
     * GET Client patrol Histories
     * @return JsonResponse
    */
    public function getPatrolHistories(Request $request){
        $clientId = $request->query("id");

        $patrols = Patrol::with("site.areas")
            ->with("agent")
            ->with("scans.agent")
            ->with("scans.area")
            ->where("site_id", $clientId)
            ->orderByDesc("id")
            ->get();

        return response()->json([
            "status" => "success",
            "result" => $patrols
        ]);
    }

     /**
     * GET Client presence agent
     * @return JsonResponse
    */

    public function getAgentPresences(Request $request){
        $id = $request->query("id") ?? null;
        $targetDate = Carbon::today('Africa/Kinshasa')->startOfDay();
        $presences = PresenceAgents::with("agent")->
            whereIn('date_reference', [
                $targetDate->toDateString(),
                $targetDate->copy()->subDay()->toDateString()
            ])
            ->whereNotNull("started_at")
            ->whereNull("ended_at")
            ->where("site_id", $id)
            ->get();
        return response()->json([
            "status"=>"success",
            "presences"=>$presences
        ]);
    }
}
