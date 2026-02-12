<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentHistory extends Model
{
    use HasFactory;


    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'agent_histories';

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
        "date",
        "agent_id",
        "site_id",
        "site_provenance_id",
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
        'date'=>'datetime:d/m/Y'
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'created_at',
        'updated_at',
        'date'
    ];


     /**
     * Belong to site
     * @return BelongsTo
    */
    public function site() : BelongsTo{
        return $this->belongsTo(Site::class, foreignKey:"site_id",);
    }

     /**
     * Belong to site
     * @return BelongsTo
    */
    public function from() : BelongsTo{
        return $this->belongsTo(Site::class, foreignKey:"site_provenance_id",);
    }


    /**
     * Belongs to Agent
     * @return BelongsTo
    */
    public function agent() : BelongsTo{
        return $this->belongsTo(Agent::class, foreignKey:"agent_id",);
    }

}
