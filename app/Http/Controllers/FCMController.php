<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\FcmService;
use Illuminate\Http\Request;

class FCMController extends Controller
{
    public function sendNotification(Request $request)
    {
        $deviceToken = $request->input('token');
        $title = $request->input("title");
        $body = $request->input("body");
        $fcm = new FcmService();
        $result = $fcm->sendNotification($deviceToken, $title, $body);
        return response()->json($result);
    }

}
