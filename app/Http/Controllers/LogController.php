<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\PhoneLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LogController extends Controller
{
 
    /**
     * Permet d'enregistrer les logs du telephone de l'agent
     * @param Request $request
     * @return JsonResponse
     */
    public function createPhoneLog(Request $request): JsonResponse
    {
        try {
            // Validation des données
            $data = $request->validate([
                "reason" => "required|string",
                "battery_level" => "required|string",
                "date_and_time" => "required|string",
                "agent_id"=>"required|int|exists:agents,id",
                "site_id"=>"required|int|exists:sites,id",
            ]);
            $result = PhoneLog::create($data);
            return response()->json([
                "status"=>"success",
                "result"=> $result
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $errors = $e->validator->errors()->all();
            return response()->json(['errors' => $errors], );
        } catch (\Illuminate\Database\QueryException $e) {
            return response()->json(['errors' => $e->getMessage()], );
        }
    }


     /**
     * Affichage de la liste des logs telephones
     * @param Request $request
     * @return JsonResponse
     */
    public function getPhoneLogs(Request $request){
        $date = $request->query("date") ?? null;

        $q = PhoneLog::with("agent")->with("site");

        if($date){
            $q->whereDate("date_and_time", $date);
        }

        $response = $q->orderByDesc("id")->paginate(10);
        
        return response()->json([
            "status"=>"success",
            "logs"=>$response
        ]);
    }

}
