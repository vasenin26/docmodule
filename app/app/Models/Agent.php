<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Agent extends Model
{
    use HasFactory;
    
    protected $fillable = ['name', 'token', 'project_id'];
    
    // Связи
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
    
    public function tasks(): HasMany
    {
        return $this->hasMany(AgentTask::class);
    }
    
    /**
     * Генерирует UUID для внешнего агента
     */
    public function generateUuid(): string
    {
        return \Illuminate\Support\Str::uuid()->toString();
    }
}
