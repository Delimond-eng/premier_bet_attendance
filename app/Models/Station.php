<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Station extends Model
{
    use HasFactory;

    protected $table = 'sites';
    protected $fillable = [
        "name", "code", "latlng", "adresse", "phone", "emails",
        "presence", "status"
    ];

    public function agents() : HasMany {
        return $this->hasMany(Agent::class, 'site_id');
    }

    public function presences() : HasMany {
        return $this->hasMany(PresenceAgents::class, 'site_id');
    }
}
