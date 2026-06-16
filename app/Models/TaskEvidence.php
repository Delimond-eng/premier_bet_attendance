<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskEvidence extends Model
{
    use HasFactory;

    // Définition explicite du nom de la table pour éviter l'erreur de pluriel automatique
    protected $table = 'task_evidences';

    protected $fillable = [
        'task_id',
        'agent_id',
        'image_path',
        'note',
        'location',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }
}
