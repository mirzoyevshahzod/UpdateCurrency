<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Currency extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'value',
        'date',
    ];

    // Disable updated_at auto-timestamp updates
    const UPDATED_AT = null;
}
