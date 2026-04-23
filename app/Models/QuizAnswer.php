<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuizAnswer extends Model
{
    use HasFactory;

    protected $fillable = [
        'attempt_id',
        'question_id',
        'answer_payload_json',
        'is_correct',
        'awarded_score',
        'answered_at',
    ];

    protected $casts = [
        'answer_payload_json' => 'array',
        'answered_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<Question, QuizAnswer>
     */
    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }
}
