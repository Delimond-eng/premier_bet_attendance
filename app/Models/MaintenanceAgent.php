<?php

namespace App\Models;

use App\Support\ManagerStationContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaintenanceAgent extends Model
{
    use HasFactory;

    protected $table = 'maintenance_agents';

    protected $fillable = [
        'agent_id',
        'station_id',
        'started_at',
        'end_at',
        'date_maintenance',
        'photo_debut',
        'photo_fin',
        'latlng',
        'commentaire',
    ];

    protected $casts = [
        'created_at' => 'date:d/m/Y',
        'date_maintenance' => 'date:d/m/Y',
        'started_at' => 'datetime:H:i',
        'end_at' => 'datetime:H:i',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('manager_restriction', function (Builder $builder) {
            $stationIds = ManagerStationContext::stationIds();
            $prefix = ManagerStationContext::matriculePrefix();
            if ($stationIds !== null || $prefix !== null) {
                $builder->where(function (Builder $q) use ($stationIds, $prefix) {
                    if ($stationIds !== null) {
                        $q->whereIn($q->qualifyColumn('station_id'), $stationIds)
                            ->orWhere(function (Builder $unassignedMaintenance) use ($stationIds) {
                                $unassignedMaintenance->whereNull($unassignedMaintenance->qualifyColumn('station_id'))
                                    ->whereHas('agent', function (Builder $qq) use ($stationIds) {
                                        $qq->withoutGlobalScopes()->whereIn('site_id', $stationIds);
                                    });
                            });
                    }

                    // Restriction par sous-traitance (préfixe matricule)
                    if ($prefix !== null) {
                        $q->whereHas('agent', function ($qqq) use ($prefix) {
                            $qqq->withoutGlobalScopes()->where('matricule', 'like', $prefix . '%');
                        });
                    }
                });
            }
        });
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class, 'agent_id');
    }

    public function station(): BelongsTo
    {
        return $this->belongsTo(Station::class, 'station_id');
    }
}
