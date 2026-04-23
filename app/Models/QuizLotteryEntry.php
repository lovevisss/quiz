<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuizLotteryEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'activity_id',
        'user_id',
        'quiz_attempt_id',
        'score',
        'eligible_at',
        'entered_at',
    ];

    protected $casts = [
        'eligible_at' => 'datetime',
        'entered_at' => 'datetime',
    ];
}
