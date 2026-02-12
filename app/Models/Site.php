<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Site extends Model
{
    use HasFactory;


     /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'sites';

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
        "name",
        "code",
        "latlng",
        "adresse",
        "phone",
        "emails",
        "fcm_token",
        "client_email",
        "otp",
        "presence",
        "client_fcm_token",
        "secteur_id",
        "agency_id",
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
        'updated_at'=>'datetime:d/m/Y H:i'
    ];

    /**
     * Belongs to Agency
     * @return BelongsTo
    */
    public function agencie() : BelongsTo{
        return $this->belongsTo(Agencie::class, foreignKey:"agency_id");
    }


    /**
     * Belongs to Secteur
     * @return BelongsTo
    */
    public function secteur() : BelongsTo{
        return $this->belongsTo(Secteur::class, foreignKey:"secteur_id");
    }

    /**
     * has menu areas
     * @return HasMany
     * */
    public function areas() : HasMany{
        return $this->hasMany(Area::class, foreignKey: 'site_id', localKey: "id");
    }


    /**
     * has manu presences
     * @return HasMany
     * */
    public function presences() : HasMany{
        return $this->hasMany(PresenceAgents::class, foreignKey: 'site_id', localKey: "id");
    }

    
    /**
     * has manu agents
     * @return HasMany
     * */
    public function agents() : HasMany{
        return $this->hasMany(Agent::class, foreignKey: 'site_id', localKey: "id");
    }


    public function tokens(){
        return $this->hasMany(Site::class, foreignKey:"site_id", localKey:"id");
    }


    public function planningConfig()
    {
        return $this->hasOne(SitePlanningConfig::class, foreignKey: 'site_id', localKey: 'id');
    }

}
