<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuestionFeedback extends Model
{
    use HasFactory;

    protected $table = 'question_feedback';

    protected $fillable = [
        'question_id',
        'user_id',
        'liked',
        'correction_text',
        'correction_status',
    ];

    protected $casts = [
        'liked' => 'boolean',
    ];

    /**
     * @return BelongsTo<Question, QuestionFeedback>
     */
    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    /**
     * @return BelongsTo<User, QuestionFeedback>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

