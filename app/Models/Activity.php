<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Activity extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'start_date',
        'end_date',
        'enabled',
        'paper_strategy_id',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
    ];

    /**
     * @return BelongsTo<PaperStrategy, Activity>
     */
    public function paperStrategy(): BelongsTo
    {
        return $this->belongsTo(PaperStrategy::class, 'paper_strategy_id');
    }
}
