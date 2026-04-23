<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuizLotteryDraw extends Model
{
    use HasFactory;

    protected $fillable = [
        'activity_id',
        'user_id',
        'quiz_lottery_entry_id',
        'draw_batch',
        'winner_position',
        'drawn_at',
    ];

    protected $casts = [
        'drawn_at' => 'datetime',
    ];
}
