<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SolutionMergeRequest extends Model
{
    protected $fillable = [
        'solution_id',
        'url',
        'created_by',
    ];

    public function solution(): BelongsTo
    {
        return $this->belongsTo(Solution::class);
    }
}


