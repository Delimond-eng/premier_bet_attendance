<?php

namespace App\Http\Controllers;

use App\Events\PatrolNotificationEvent;
use Illuminate\Http\Request;

class PatrolNotificationController extends Controller
{
    public function runNotification(Request $request){
        $message = [
            "title"=>$request->input('title'),
            "content"=>$request->input('content')
        ];
        event(new PatrolNotificationEvent($message));
        return response()->json([
            "datas"=>$message
        ]);
    }
}
