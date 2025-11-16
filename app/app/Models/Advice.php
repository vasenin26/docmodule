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

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
