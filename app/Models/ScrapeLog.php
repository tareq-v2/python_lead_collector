<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScrapeLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'source',
        'status',
        'records_found',
        'error_message',
        'duration_ms',
        'scraped_at',
    ];

    protected $casts = [
        'scraped_at' => 'datetime',
    ];
}
