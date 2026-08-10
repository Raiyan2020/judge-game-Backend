<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Hearing extends Model
{
    protected $fillable = [
        'legal_case_id',
        'room_id',
        'created_by',
        'scheduled_at',
        'status',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
    ];

    public function legalCase()
    {
        return $this->belongsTo(LegalCase::class);
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
