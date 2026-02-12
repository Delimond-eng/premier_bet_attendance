<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PresenceAgents extends Model
{
    use HasFactory;

    protected $table = 'presence_agents';

    protected $fillable = [
        'agent_id',
        'site_id', // Station d'affectation au moment de la présence
        'gps_site_id', // Legacy (ancienne station effective)
        'station_check_in_id',
        'station_check_out_id',
        'horaire_id',
        'started_at',
        'ended_at',
        'duree',
        'retard',
        'photos_debut', // Legacy (plus utilisé)
        'photos_fin', // Legacy (plus utilisé)
        'commentaires',
        'status',
        'date_reference',
    ];

    protected $casts = [
        'created_at' => 'date:d/m/Y',
        'started_at' => 'datetime:H:i',
        'ended_at' => 'datetime:H:i',
        'date_reference' => 'date:d/ M/Y',
    ];

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function horaire(): BelongsTo
    {
        return $this->belongsTo(PresenceHoraire::class, 'horaire_id');
    }

    public function stationCheckIn(): BelongsTo
    {
        return $this->belongsTo(Station::class, 'station_check_in_id');
    }

    public function stationCheckOut(): BelongsTo
    {
        return $this->belongsTo(Station::class, 'station_check_out_id');
    }

    public function assignedStation(): BelongsTo
    {
        return $this->belongsTo(Station::class, 'site_id');
    }

    public function station(): BelongsTo
    {
        return $this->belongsTo(Station::class, 'gps_site_id');
    }
}

