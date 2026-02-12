<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PresenceSupervisorControl extends Model
{
    use HasFactory;

     /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'presence_supervisor_controls';

    protected $fillable = [
        'presence_id',
        'element_id',
        'agent_id',
        'note',
        'observation',
    ];

    public function element()
    {
        return $this->belongsTo(SupervisionControlElement::class, 'element_id');
    }

    public function presence()
    {
        return $this->belongsTo(PresenceSupervisorSite::class, 'presence_id');
    }

    public function agent(){
        return $this->belongsTo(Agent::class, 'agent_id');
    }
}
