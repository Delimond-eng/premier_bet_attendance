<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Region extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'capital',
        'cities',
        'timezone',
    ];

    protected $casts = [
        'cities' => 'array',
    ];

    public function stations(): HasMany
    {
        return $this->hasMany(Station::class, 'region_id');
    }

    public function cityRecords(): HasMany
    {
        return $this->hasMany(City::class);
    }
}
