<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentBiometric extends Model
{
    use HasFactory;

    protected $fillable = [
        'agent_id',
        'matricule',
        'embedding',
        'model_version',
        'quality_score',
        'status',
    ];

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }
}
