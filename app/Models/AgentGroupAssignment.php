<?php

namespace App\Models;

use App\Support\ManagerStationContext;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class AgentGroupAssignment extends Model
{
    use HasFactory;
    protected $fillable = ['agent_id', 'agent_group_id', 'start_date', 'end_date'];

    protected static function booted(): void
    {
        static::addGlobalScope('manager_station', function (Builder $builder) {
            $stationIds = ManagerStationContext::stationIds();
            if ($stationIds === null) {
                return;
            }

            $builder->whereHas('agent', function (Builder $query) use ($stationIds) {
                $query->withoutGlobalScopes()->whereIn('site_id', $stationIds);
            });
        });
    }

    public function agent()
    {
        return $this->belongsTo(Agent::class);
    }

    public function group()
    {
        return $this->belongsTo(AgentGroup::class, 'agent_group_id');
    }
}
