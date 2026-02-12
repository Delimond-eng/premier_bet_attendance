<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Carbon\Carbon;

class Conge extends Model
{
    use HasFactory;

     /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'conges';

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
        "agent_id",
        "conge_type_id",
        "type",
        "date_debut",
        "date_fin",
        "motif",
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
        'created_at'=>'datetime:d M/Y H:i',
        'updated_at'=>'datetime:d M/Y H:i',
        'date_debut' => 'date',
        'date_fin' => 'date',
    ];

    protected $appends = [
        'date_debut_label',
        'date_fin_label',
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
     * Agency Belongs to site
     * @return BelongsTo
    */
    public function agent() : BelongsTo{
        return $this->belongsTo(Agent::class, foreignKey:"agent_id",);
    }

    public function congeType(): BelongsTo
    {
        return $this->belongsTo(CongeType::class, 'conge_type_id');
    }

    protected function dateDebutLabel(): Attribute
    {
        return Attribute::get(function () {
            if (!$this->date_debut) {
                return null;
            }
            return Carbon::parse($this->date_debut)->format('d/m/Y');
        });
    }

    protected function dateFinLabel(): Attribute
    {
        return Attribute::get(function () {
            if (!$this->date_fin) {
                return null;
            }
            return Carbon::parse($this->date_fin)->format('d/m/Y');
        });
    }
}
