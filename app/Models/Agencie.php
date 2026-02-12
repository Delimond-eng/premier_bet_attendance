<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Agencie extends Model
{

    use HasFactory;

     /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'agencies';

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
        "adresse",
        "logo",
        "phone",
        "email"
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
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'created_at',
        'updated_at',
    ];


    /**
     * Agency has many sites
     * @return HasMany
    */
    public function agents() : HasMany{
        return $this->hasMany(Agent::class, foreignKey: "agency_id", localKey: "id");
    }

    /**
     * Agency Has many sites
     * @return HasMany
    */
    public function sites() : HasMany{
        return $this->hasMany(Site::class, foreignKey:"agency_id", localKey:"id");
    }
}
