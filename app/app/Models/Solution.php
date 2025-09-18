<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Solution extends Model
{
    protected $fillable = [
        'content',
    ];

    public function mergeRequests(): HasMany
    {
        return $this->hasMany(SolutionMergeRequest::class);
    }

    public function techplanes(): BelongsToMany
    {
        return $this->belongsToMany(Techplane::class, 'techplane_solution', 'solution_id', 'techplane_id');
    }
}


