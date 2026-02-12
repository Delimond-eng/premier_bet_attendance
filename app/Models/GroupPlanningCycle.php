<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GroupPlanningCycle extends Model
{
    use HasFactory;

    protected $fillable = ['agent_group_id', 'horaire_id', 'day_index', 'is_rest_day'];

    public function group()
    {
        return $this->belongsTo(AgentGroup::class, 'agent_group_id');
    }

    public function horaire()
    {
        return $this->belongsTo(PresenceHoraire::class, 'horaire_id');
    }
}
