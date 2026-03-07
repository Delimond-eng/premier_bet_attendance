<?php

namespace App\Models;

use App\Support\ManagerStationContext;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Station extends Model
{
    use HasFactory;

    protected $table = 'sites';
    protected $fillable = [
        "name", "type", "code", "latlng", "adresse", "phone", "emails",
        "presence", "status"
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('manager_station', function (Builder $builder) {
            $stationId = ManagerStationContext::stationId();
            if ($stationId === null) {
                return;
            }

            $builder->where($builder->qualifyColumn('id'), $stationId);
        });
    }

    public function agents() : HasMany {
        return $this->hasMany(Agent::class, 'site_id');
    }

    public function presences() : HasMany {
        return $this->hasMany(PresenceAgents::class, 'site_id');
    }

    public function maintenances(): HasMany
    {
        return $this->hasMany(MaintenanceAgent::class, 'station_id');
    }
}
