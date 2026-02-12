<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Secteur;
use App\Models\SupervisionControlElement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConfigController extends Controller
{

    /**
     * Affiche la liste de secteurs paginé
     * @return JsonResponse
    */
    public function viewAllPaginateSectors(Request $request){
        $key = $request->query("key") ?? "paginate";
        $query = Secteur::orderBy("libelle");
        $sectors = null;
        if($key==="all"){
            $sectors = $query->with("sites")->get();
        }
        else{
            $sectors = $query->paginate(10);
        }
        return response()->json([
            "status"=>"success",
            "sectors"=>$sectors
        ]);
    }

    /**
     * Cree & modifie un secteur
     * @param Request $request
     * @return JsonResponse
    */
    public function createSector(Request $request){
        try{
            $data = $request->validate([
                "libelle"=>"required|string",
            ]);
            $response = Secteur::updateOrCreate(
                [
                    "id"=>$request->id,
                ],
                $data
            );
            
            return response()->json([
                "status"=>"success",
                "result"=>$response
            ]);
        }
        catch (\Illuminate\Validation\ValidationException $e) {
            $errors = $e->validator->errors()->all();
            return response()->json(['errors' => $errors ]);
        }
        catch (\Illuminate\Database\QueryException $e){
            return response()->json(['errors' => $e->getMessage() ]);
        }
    }


    /**
     * Affiche la liste de element de control pour la supervision paginé
     * @return JsonResponse
    */
    public function viewAllPaginateElements(){
        $sectors = SupervisionControlElement::orderBy("libelle")->paginate(10);
        return response()->json([
            "status"=>"success",
            "elements"=>$sectors
        ]);
    }

    /**
     * Cree & modifie un secteur
     * @param Request $request
     * @return JsonResponse
    */
    public function createElement(Request $request){
        try{
            $data = $request->validate([
                "libelle"=>"required|string",
                "description"=>"nullable|string",
            ]);
            $response = SupervisionControlElement::updateOrCreate(
                [
                    "id"=>$request->id,
                ],
                $data
            );
            
            return response()->json([
                "status"=>"success",
                "result"=>$response
            ]);
        }
        catch (\Illuminate\Validation\ValidationException $e) {
            $errors = $e->validator->errors()->all();
            return response()->json(['errors' => $errors ]);
        }
        catch (\Illuminate\Database\QueryException $e){
            return response()->json(['errors' => $e->getMessage() ]);
        }
    }
}
