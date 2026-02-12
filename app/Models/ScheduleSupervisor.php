<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ScheduleSupervisor extends Model
{
    use HasFactory;


    protected $fillable = [
        'title',
        'date',
        'status',
        'comment',
        'agent_id',
        'user_id',
    ];


    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'created_at'=>'datetime:d/m/Y H:i',
        'updated_at'=>'datetime:d/m/Y H:i',
        'date'=>'date:d/m/Y',
    ];

    /**
     * Relation avec l'agent assigné (superviseur).
     */
    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    /**
     * Relation avec l'utilisateur (créateur).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relation avec les sites planifiés pour cette mission.
     */
    public function sites(): HasMany
    {
        return $this->hasMany(ScheduleSupervisorSite::class, 'schedule_id');
    }

    public function presences(): HasMany{
        return $this->hasMany(PresenceSupervisorSite::class, 'schedule_id', 'id');
    }
}
