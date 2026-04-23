<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExportTask extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'status',
        'file_path',
        'type',
        'params',
    ];

    protected $casts = [
        'params' => 'array',
    ];
}
