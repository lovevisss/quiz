<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    /**
     * @return BelongsTo<Activity, QuizAttempt>
     */
    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }

    /**
     * @return HasMany<QuizAnswer, QuizAttempt>
     */
    public function answers(): HasMany
    {
        return $this->hasMany(QuizAnswer::class, 'attempt_id');
    }

    /**
     * @return BelongsTo<User, QuizAttempt>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
