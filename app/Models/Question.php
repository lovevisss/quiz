<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Question extends Model
{
    use HasFactory;

    protected $fillable = [
        'content',
        'type',
        'options',
        'answer',
        'explanation',
        'option_explanations',
        'difficulty',
        'tags',
        'status',
    ];

    protected $casts = [
        'options' => 'array',
        'option_explanations' => 'array',
        'tags' => 'array',
    ];

    /**
     * @return HasMany<QuestionFeedback, Question>
     */
    public function feedback(): HasMany
    {
        return $this->hasMany(QuestionFeedback::class);
    }
}
