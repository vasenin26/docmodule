<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Agent extends Model
{
    use HasFactory;
    
    protected $fillable = ['name', 'token', 'project_id', 'uuid', 'public_key', 'has_cross_project_access'];
    
    protected function casts(): array
    {
        return [
            'has_cross_project_access' => 'boolean',
        ];
    }
    
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
    
    /**
     * Проверка, имеет ли агент доступ ко всем проектам
     */
    public function hasCrossProjectAccess(): bool
    {
        return $this->has_cross_project_access === true;
    }
    
    /**
     * Scope для выборки агентов с доступом ко всем проектам
     */
    public function scopeWithCrossProjectAccess($query)
    {
        return $query->where('has_cross_project_access', true);
    }
}
