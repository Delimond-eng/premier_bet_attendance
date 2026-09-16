<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeviceFaceList extends Model
{
    use HasFactory;

    protected $table = 'device_face_lists';

    protected $fillable = [
        'device_id',
        'device_imei',
        'request_id',
        'command',
        'status',
        'matricules',
        'count',
        'sent_at',
        'received_at',
        'response_payload',
    ];

    protected $casts = [
        'matricules' => 'array',
        'response_payload' => 'array',
        'sent_at' => 'datetime',
        'received_at' => 'datetime',
    ];

    public function device(): BelongsTo
    {
        return $this->belongsTo(MobileDevice::class, 'device_id');
    }
}
