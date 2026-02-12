<?php

namespace App\Http\Controllers;

use App\Events\AudioBroadcast;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TalkieWalkieController extends Controller
{

    /**
     * Send talk audio
     * @param Request $request
     * @return JsonResponse
    */
    public function sendTalkAudio(Request $request):JsonResponse
    {
        // Valider le fichier audio
        $data = $request->validate([
            'audio' => 'required|file|mimes:mp3,wav,ogg,mp4',
            'user_id'=>'required|int',
            'sender'=> 'required|string'
        ]);
        // Stocker le fichier audio
        $path = $request->file('audio')->store('audios', 'public');

        // URL public du fichier audio
        $audioUrl = url("storage/{$path}");
        // Diffuser l'événement avec URL de audio
        event(new AudioBroadcast($audioUrl,$data['user_id'], $data['sender']));
        return response()->json([
            'status'=>'success',
            'audio_url' => $audioUrl
        ]);
    }


    /**
     * Supprimer le fichier audio spécifié.
     *
     * @param string $path
     * @return void
     */
    public function deleteAudio(string $path): void
    {
        // Supprimer le fichier audio
        Storage::disk('public')->delete($path);
    }
}
