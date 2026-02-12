<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScheduleSupervisorSite extends Model
{
    use HasFactory;

    protected $fillable = [
        'schedule_id',
        'site_id',
        'order',
        'status',
    ];
    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'created_at'=>'datetime:d/m/Y H:i',
        'updated_at'=>'datetime:d/m/Y H:i',
    ];

    /**
     * Planning superviseur auquel ce site appartient.
     */
    public function schedule(): BelongsTo
    {
        return $this->belongsTo(ScheduleSupervisor::class, 'schedule_id');
    }

    /**
     * Site concerné par ce planning.
     */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class, 'site_id');
    }
    
}
