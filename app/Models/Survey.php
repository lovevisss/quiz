<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Survey extends Model
{
    use HasFactory;

    protected $fillable = [
        'activity_id',
        'title',
        'structure_json',
    ];

    protected $casts = [
        'structure_json' => 'array',
    ];

    public function responses()
    {
        return $this->hasMany(SurveyResponse::class);
    }
}
