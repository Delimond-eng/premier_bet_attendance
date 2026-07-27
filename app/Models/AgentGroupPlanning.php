<?php

namespace App\Models;

use App\Support\ManagerStationContext;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentGroupPlanning extends Model
{
    use HasFactory;

    protected $fillable = ['agent_group_id', 'agent_id', 'horaire_id', 'site_id', 'date', 'day_index', 'is_rest_day'];

    protected static function booted(): void
    {
        static::addGlobalScope('manager_restriction', function (Builder $builder) {
            // Restriction par station
            $stationId = ManagerStationContext::stationId();
            if ($stationId !== null) {
                $builder->where(function (Builder $query) use ($stationId) {
                    $query->where($query->qualifyColumn('site_id'), $stationId)
                          ->orWhereHas('agent', function (Builder $q) use ($stationId) {
                              $q->withoutGlobalScopes()->where('site_id', $stationId);
                          });
                });
            }

            // Restriction par sous-traitance (préfixe matricule)
            $prefix = ManagerStationContext::matriculePrefix();
            if ($prefix !== null) {
                $builder->whereHas('agent', function ($q) use ($prefix) {
                    $q->withoutGlobalScopes()->where('matricule', 'like', $prefix . '%');
                });
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

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class, "agent_id");
    }

    public function station(): BelongsTo
    {
        return $this->belongsTo(Station::class, 'site_id');
    }
}
