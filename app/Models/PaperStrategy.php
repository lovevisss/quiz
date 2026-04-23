<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaperStrategy extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'mode', // fixed or random
        'config', // JSON config for strategy
        'status', // enabled/disabled
    ];

    protected $casts = [
        'config' => 'array',
        'status' => 'boolean',
    ];

    /**
     * @return HasMany<Activity, PaperStrategy>
     */
    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class, 'paper_strategy_id');
    }
}
