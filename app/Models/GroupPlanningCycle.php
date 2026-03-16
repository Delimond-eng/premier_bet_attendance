<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GroupPlanningCycle extends Model
{
    use HasFactory;

    protected $fillable = ['agent_group_id', 'horaire_id', 'site_id', 'day_index', 'is_rest_day'];

    public function group(): BelongsTo
    {
        return $this->belongsTo(AgentGroup::class, 'agent_group_id');
    }

    public function horaire(): BelongsTo
    {
        return $this->belongsTo(PresenceHoraire::class, 'horaire_id');
    }

    public function station(): BelongsTo
    {
        return $this->belongsTo(Station::class, 'site_id');
    }
}
