<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PhoneLog extends Model
{
    use HasFactory;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'phone_logs';

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
        "reason",
        "battery_level",
        "date_and_time",
        "agent_id",
        "site_id",
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
        'date_and_time'=>'datetime:d/m/Y H:i'
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'created_at',
        'updated_at',
        'date_and_time'
    ];


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

}
