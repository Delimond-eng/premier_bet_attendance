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

    protected $appends = [
        'is_protected',
    ];

    protected $casts = [
        'created_at' => 'datetime:d/m/Y H:i',
        'updated_at' => 'datetime:d/m/Y H:i',
    ];

    public function getIsProtectedAttribute(): bool
    {
        return $this->isProtectedType();
    }

    public function isProtectedType(): bool
    {
        $label = $this->normalizeLabel((string) $this->libelle);
        return in_array($label, self::protectedLabels(), true);
    }

    private static function normalizeLabel(string $label): string
    {
        $label = mb_strtolower(trim($label));
        return strtr($label, [
            'é' => 'e',
            'è' => 'e',
            'ê' => 'e',
            'à' => 'a',
            'â' => 'a',
            'ô' => 'o',
            'û' => 'u',
            'ù' => 'u',
            'ç' => 'c',
        ]);
    }

    private static function protectedLabels(): array
    {
        return [
            'maladie',
            'conge annuel',
            'conge de circonstance',
            'conge maternité',
            'conge maternite',
            'circonstance',
        ];
    }
}

