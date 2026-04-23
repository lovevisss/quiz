<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SurveyTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    public function questions()
    {
        return $this->hasMany(SurveyQuestion::class);
    }
}
