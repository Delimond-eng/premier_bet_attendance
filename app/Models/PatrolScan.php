<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatrolScan extends Model
{
    use HasFactory;


    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'patrol_scans';

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
        "time",
        "latlng",
        "comment",
        "distance",
        "agent_id",
        "patrol_id",
        "area_id",
        "photo",
        "matricule",
        "status"
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
        'time'=>'datetime:d/m/Y H:i',
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
     * Belong to Patrol
     * @return BelongsTo
    */
    public function patrol() : BelongsTo{
        return $this->belongsTo(Patrol::class, foreignKey:"patrol_id",);
    }


    /**
     * Belong to Agent
     * @return BelongsTo
    */
    public function agent() : BelongsTo{
        return $this->belongsTo(Agent::class, foreignKey:"agent_id",);
    }


    /**
     * Belongs To Area
     * @return BelongsTo
    */
    public function area():BelongsTo  {
        return $this->belongsTo(Area::class, foreignKey:"area_id");
    }
}
