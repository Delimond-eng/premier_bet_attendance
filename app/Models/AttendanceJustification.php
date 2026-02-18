<?php

namespace App\Models;

use App\Support\ManagerStationContext;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class AttendanceJustification extends Model
{
    use HasFactory;

    protected $table = 'attendance_justifications';

    protected $fillable = [
        'agent_id',
        'presence_agent_id',
        'date_reference',
        'kind',
        'justification',
        'status',
        'approved_by',
    ];

    protected $casts = [
        'date_reference' => 'date',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('manager_station', function (Builder $builder) {
            $stationId = ManagerStationContext::stationId();
            if ($stationId === null) {
                return;
            }

            $builder->whereHas('agent', function (Builder $query) use ($stationId) {
                $query->withoutGlobalScopes()->where('site_id', $stationId);
            });
        });
    }

    protected $appends = [
        'date_reference_label',
    ];

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class, 'agent_id');
    }

    public function presence(): BelongsTo
    {
        return $this->belongsTo(PresenceAgents::class, 'presence_agent_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    protected function dateReferenceLabel(): Attribute
    {
        return Attribute::get(function () {
            if (!$this->date_reference) {
                return null;
            }
            return Carbon::parse($this->date_reference)->format('d/m/Y');
        });
    }
}
