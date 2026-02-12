<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Mail\MailNotify;
use Illuminate\Support\Facades\Mail;

class EmailController extends Controller
{
    public function sendMail($data){
        $emails = explode(";", $data["emails"]);
        foreach ($emails as $email) {
            if(!empty($email)){
                Mail::to($email)->send(new MailNotify(titre: $data["title"],
                    photo: $data["photo"],
                    agent: $data["agent"],
                    site: $data["site"],
                    datetime: $data["date"]
                ));
            }
        }
        return true;
    }
}
