<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuizCertificate extends Model
{
    use HasFactory;

    protected $fillable = [
        'activity_id',
        'user_id',
        'quiz_attempt_id',
        'score',
        'issued_at',
    ];

    protected $casts = [
        'issued_at' => 'datetime',
    ];
}
