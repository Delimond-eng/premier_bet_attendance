<?php

namespace App\Models;

use App\Support\ManagerStationContext;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class AttendanceAuthorization extends Model
{
    use HasFactory;

    protected $table = 'attendance_authorizations';

    protected $fillable = [
        'agent_id',
        'date_reference',
        'type',
        'started_at',
        'ended_at',
        'minutes',
        'reason',
        'status',
        'approved_by',
    ];

    protected $casts = [
        'date_reference' => 'date',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('manager_restriction', function (Builder $builder) {
            // Restriction par station
            $stationId = ManagerStationContext::stationId();
            if ($stationId !== null) {
                $builder->whereHas('agent', function (Builder $query) use ($stationId) {
                    $query->withoutGlobalScopes()->where('site_id', $stationId);
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

    protected $appends = [
        'date_reference_label',
    ];

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class, 'agent_id');
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
