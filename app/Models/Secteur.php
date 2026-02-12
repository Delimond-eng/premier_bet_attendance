<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Secteur extends Model
{
    use HasFactory;

    protected $fillable = [
        "libelle",
        "description"
    ];



    public function sites(){
        return $this->hasMany(Site::class, "secteur_id", "id");
    }
}
