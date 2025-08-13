<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LLMChat extends Model
{
    use HasFactory;

    protected $fillable = [
        'messages',
    ];

    protected $casts = [
        'messages' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
