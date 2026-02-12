<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentGroup extends Model
{
    use HasFactory;
     /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'agent_groups';

    protected $primaryKey = 'id';

    protected $fillable = ["libelle", "horaire_id"];

    public function horaire():BelongsTo{
        return $this->belongsTo(PresenceHoraire::class, foreignKey:"horaire_id");
    }

    public function planningCycles()
    {
        return $this->hasMany(GroupPlanningCycle::class);
    }

    public function plannings()
    {
        return $this->hasMany(AgentGroupPlanning::class);
    }

    public function assignments()
    {
        return $this->hasMany(AgentGroupAssignment::class);
    }
}
