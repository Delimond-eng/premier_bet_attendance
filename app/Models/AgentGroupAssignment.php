<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgentGroupAssignment extends Model
{
    use HasFactory;
    protected $fillable = ['agent_id', 'agent_group_id', 'start_date', 'end_date'];

    public function agent()
    {
        return $this->belongsTo(Agent::class);
    }

    public function group()
    {
        return $this->belongsTo(AgentGroup::class, 'agent_group_id');
    }
}
