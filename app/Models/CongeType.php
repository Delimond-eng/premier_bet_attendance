<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CongeType extends Model
{
    use HasFactory;

    protected $table = 'conge_types';

    protected $fillable = [
        'libelle',
        'description',
        'status',
    ];

    protected $casts = [
        'created_at' => 'datetime:d/m/Y H:i',
        'updated_at' => 'datetime:d/m/Y H:i',
    ];
}

