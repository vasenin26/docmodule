<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectGenerationModel extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'model_id',
        'generation_type',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function model(): BelongsTo
    {
        return $this->belongsTo(GenerationModel::class, 'model_id');
    }
}


