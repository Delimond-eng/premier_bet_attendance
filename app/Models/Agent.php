<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

/**
 * Class Agent
 * Représente un agent/employé du système.
 */
class Agent extends Model
{
    use HasFactory;

    protected $table = 'agents';

    protected $fillable = [
        "photo",
        "matricule",
        "fullname",
        "fonction",
        "password",
        "role",
        "agency_id",
        "site_id", // Toujours lié à la colonne site_id pour la compatibilité
        "groupe_id",
        "horaire_id",
        "status"
    ];

    protected $casts = [
        'created_at' => 'datetime:d/m/Y H:i',
        'updated_at' => 'datetime:d/m/Y H:i'
    ];

    /**
     * Station d'affectation de l'agent.
     */
    public function station() : BelongsTo
    {
        return $this->belongsTo(Station::class, 'site_id');
    }

    /**
     * Pour compatibilité avec le code existant si nécessaire.
     */
    public function site() : BelongsTo
    {
        return $this->belongsTo(Station::class, 'site_id');
    }

    public function agencie() : BelongsTo
    {
        return $this->belongsTo(Agencie::class, 'agency_id');
    }

    public function groupe() : BelongsTo
    {
        return $this->belongsTo(AgentGroup::class, 'groupe_id');
    }

    public function horaire() : BelongsTo
    {
        return $this->belongsTo(PresenceHoraire::class, 'horaire_id');
    }

    public function stories()
    {
        return $this->hasMany(AgentHistory::class, "agent_id");
    }

    public function plannings()
    {
        return $this->hasMany(AgentGroupPlanning::class, "agent_id");
    }
}
