<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class PresenceAgents
 * Gère les enregistrements de pointage des agents.
 */
class PresenceAgents extends Model
{
    use HasFactory;

    protected $table = 'presence_agents';

    protected $fillable = [
        'agent_id',
        'site_id', // ID de la station d'affectation
        'gps_site_id', // ID de la station où le pointage a été physiquement fait
        'horaire_id',
        'started_at',
        'ended_at',
        'duree',
        'retard',
        'photos_debut',
        'photos_fin',
        'status_photo_debut',
        'status_photo_fin',
        'commentaires',
        'status',
        'date_reference'
    ];

    protected $casts = [
        'created_at' => 'date:d/m/Y',
        'started_at' => 'datetime:H:i',
        'ended_at' => 'datetime:H:i',
        'date_reference' => 'date'
    ];

    /**
     * Agent ayant effectué le pointage.
     */
    public function agent() : BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    /**
     * Horaire de travail associé.
     */
    public function horaire() : BelongsTo
    {
        return $this->belongsTo(PresenceHoraire::class, 'horaire_id');
    }

    /**
     * Station de pointage effective.
     */
    public function station() : BelongsTo
    {
        return $this->belongsTo(Station::class, 'gps_site_id');
    }
}
