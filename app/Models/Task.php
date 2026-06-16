<?php

namespace App\Models;

use App\Support\ManagerStationContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Task extends Model
{
    use HasFactory;

    protected $fillable = [
        'station_id',
        'title',
        'description',
        'priority',
        'status',
        'is_global',
        'start_date',
        'due_date',
        'completed_at',
    ];

    protected $casts = [
        'start_date' => 'date',
        'due_date' => 'date',
        'completed_at' => 'datetime',
        'is_global' => 'boolean',
    ];

    // Indispensable pour que VueJS reçoive ces champs via l'API
    protected $appends = ['progress', 'is_overdue'];

    protected static function booted(): void
    {
        static::addGlobalScope('manager_station', function (Builder $builder) {
            $stationId = ManagerStationContext::stationId();
            if ($stationId === null) {
                return;
            }

            $builder->where($builder->qualifyColumn('station_id'), $stationId);
        });
    }

    public function station(): BelongsTo
    {
        return $this->belongsTo(Station::class, 'station_id');
    }

    public function agents(): BelongsToMany
    {
        return $this->belongsToMany(Agent::class, 'task_agent');
    }

    public function subtasks(): HasMany
    {
        return $this->hasMany(TaskSubtask::class);
    }

    public function evidences(): HasMany
    {
        return $this->hasMany(TaskEvidence::class);
    }

    /**
     * Calcul du pourcentage de progression basé sur les sous-tâches.
     */
    public function getProgressAttribute(): int
    {
        $total = $this->subtasks()->count();
        if ($total === 0) {
            return $this->status === 'completed' ? 100 : 0;
        }

        $completed = $this->subtasks()->where('is_completed', true)->count();
        return (int) (($completed / $total) * 100);
    }

    /**
     * Vérifie si la tâche est en retard.
     */
    public function getIsOverdueAttribute(): bool
    {
        if ($this->status === 'completed') {
            return false;
        }
        return now()->gt($this->due_date);
    }
}
