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
        static::addGlobalScope('manager_station', function (Builder $builder) {
            $stationId = ManagerStationContext::stationId();
            if ($stationId === null) {
                return;
            }

            // A manager should see maintenances performed at their station
            // OR maintenances performed by agents assigned to their station.
            $builder->where(function (Builder $q) use ($stationId) {
                $q->where($q->qualifyColumn('station_id'), $stationId)
                    ->orWhereHas('agent', function (Builder $qq) use ($stationId) {
                        $qq->where('site_id', $stationId);
                    });
            });
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
