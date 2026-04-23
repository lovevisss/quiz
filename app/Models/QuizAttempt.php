<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuizAttempt extends Model
{
    use HasFactory;

    protected $fillable = [
        'activity_id',
        'user_id',
        'status',
        'started_at',
        'expires_at',
        'submitted_at',
        'score',
        'duration_seconds',
        'anti_cheat_flags',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'expires_at' => 'datetime',
        'submitted_at' => 'datetime',
        'anti_cheat_flags' => 'array',
    ];
}