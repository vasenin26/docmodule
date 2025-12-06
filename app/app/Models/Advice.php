<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Advice extends Model
{
    use HasFactory;

    protected $table = 'advices';

    protected $fillable = [
        'project_id',
        'group',
        'content',
    ];

    /**
     * Optionally, cast group to string (db-level constraint is string(64))
     */
    protected $casts = [
        'group' => 'string',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
