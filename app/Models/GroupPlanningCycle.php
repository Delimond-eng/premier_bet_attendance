<?php

namespace App\Models;

use App\Support\ManagerStationContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GroupPlanningCycle extends Model
{
    use HasFactory;

    protected $fillable = ['agent_group_id', 'horaire_id', 'site_id', 'day_index', 'is_rest_day'];

    protected static function booted(): void
    {
        static::addGlobalScope('manager_station', function (Builder $builder) {
            $stationIds = ManagerStationContext::stationIds();
            if ($stationIds !== null) {
                $builder->whereIn($builder->qualifyColumn('site_id'), $stationIds);
            }
        });
    }

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
