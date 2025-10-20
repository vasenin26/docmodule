<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GenerationModel extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'context_size',
        'price_in',
        'price_out',
    ];

    public function projectMappings(): HasMany
    {
        return $this->hasMany(ProjectGenerationModel::class, 'model_id');
    }
}


