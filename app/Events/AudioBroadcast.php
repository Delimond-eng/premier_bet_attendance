<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AudioBroadcast implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $audioUrl;
    public int $userId;
    public string $sender;// Ajoute un champ pour l'ID utilisateur

    /**
     * Create a new event instance.
     *
     * @param string $audioUrl
     * @param int $userId
     * @param string $sender
     */
    public function __construct(string $audioUrl, int $userId, string $sender)
    {
        $this->audioUrl = $audioUrl;
        $this->sender = $sender;
        $this->userId = $userId; // Initialisation de l'ID utilisateur
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel
     */
    public function broadcastOn(): Channel
    {
        return new Channel('talkie-walkie');
    }

    public function broadcastAs():string
    {
        return 'audio.sent';
    }
}
