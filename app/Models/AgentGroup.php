<?php

namespace App\Models;

use App\Support\ManagerStationContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentGroup extends Model
{
    use HasFactory;
     /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'agent_groups';

    protected $primaryKey = 'id';

    protected $fillable = ["libelle", "horaire_id", "cycle_days", "status"];

    protected static function booted(): void
    {
        static::addGlobalScope('manager_station', function (Builder $builder) {
            $stationIds = ManagerStationContext::stationIds();
            if ($stationIds === null) {
                return;
            }

            $builder->where(function (Builder $query) use ($stationIds) {
                $query->whereHas('horaire', fn (Builder $horaire) => $horaire->whereIn('site_id', $stationIds))
                    ->orWhereHas('plannings', fn (Builder $planning) => $planning->whereIn('site_id', $stationIds))
                    ->orWhereHas('assignments.agent', fn (Builder $agent) => $agent->withoutGlobalScopes()->whereIn('site_id', $stationIds));
            });
        });
    }

    public function horaire():BelongsTo{
        return $this->belongsTo(PresenceHoraire::class, foreignKey:"horaire_id");
    }

    public function planningCycles()
    {
        return $this->hasMany(GroupPlanningCycle::class);
    }

    public function plannings()
    {
        return $this->hasMany(AgentGroupPlanning::class);
    }

    public function assignments()
    {
        return $this->hasMany(AgentGroupAssignment::class);
    }
}
