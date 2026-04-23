<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuizActivity extends Model
{
    use HasFactory;

    protected $table = 'quiz_activities';

    protected $fillable = [
        'title',
        'description',
        'start_time',
        'end_time',
        'is_active',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'is_active' => 'boolean',
    ];
}