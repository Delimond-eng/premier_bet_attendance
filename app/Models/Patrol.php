<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Patrol extends Model
{
    use HasFactory;

     /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'patrols';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * Attributes that should be mass-assignable.
     *
     * @var array
     */
    protected $fillable = [
        "started_at",
        "ended_at",
        "comment_text",
        "comment_audio",
        "site_id",
        "agent_id",
        "schedule_id",
        "photo",
        "agency_id",
        "status",
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [

    ];


    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'created_at'=>'datetime:d/m/Y H:i',
        'updated_at'=>'datetime:d/m/Y H:i',
        'started_at'=>'datetime:d/m/Y H:i',
        'ended_at'=>'datetime:d/m/Y H:i'
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'created_at',
        'updated_at',
    ];

    /**
     * Has many scans
     * @return HasMany
    */
    public function scans():HasMany{
        return $this->hasMany(PatrolScan::class, foreignKey:"patrol_id", localKey:"id");
    }


    /**
     * Belong to site
     * @return BelongsTo
    */
    public function site() : BelongsTo{
        return $this->belongsTo(Site::class, foreignKey:"site_id",);
    }


    /**
     * Belongs to Agent
     * @return BelongsTo
    */
    public function agent() : BelongsTo{
        return $this->belongsTo(Agent::class, foreignKey:"agent_id",);
    }

    public function planning(): BelongsTo{
        return $this->belongsTo(Schedules::class, foreignKey:"schedule_id");
    }
}