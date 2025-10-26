<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\ProjectGenerationModel;
use App\Models\GenerationModel;

class Project extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'owner_id',
        'public_key',
    ];



    /**
     * Get the owner that owns the project.
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * Get the pages for the project.
     */
    public function pages(): HasMany
    {
        return $this->hasMany(Page::class);
    }

    /**
     * The repositories that belong to the project.
     */
    public function repositories(): BelongsToMany
    {
        return $this->belongsToMany(Repository::class);
    }

    /**
     * Get the prompts for the project.
     */
    public function prompts(): HasMany
    {
        return $this->hasMany(Prompt::class);
    }

    /**
     * Get the agents for the project.
     */
    public function agents(): HasMany
    {
        return $this->hasMany(Agent::class);
    }

    public function generationModelMappings(): HasMany
    {
        return $this->hasMany(ProjectGenerationModel::class);
    }

    public function getGenerationModelForType(string $type): ?GenerationModel
    {
        $mapping = $this->generationModelMappings()
            ->where('generation_type', $type)
            ->with('model')
            ->first();
        return $mapping ? $mapping->model : null;
    }

    public function getGenerationModelNameForType(string $type): ?string
    {
        return $this->getGenerationModelForType($type)?->name ?? null;
    }

    /**
     * Проверяет, может ли пользователь получить доступ к проекту
     */
    public function canAccess(?User $user): bool
    {
        if(is_null($user)) {
            return false;
        }
        
        // Пользователь может получить доступ к проекту, если он является владельцем
        return $this->owner_id === $user->id;
    }

    /**
     * Проверяет, может ли агент получить доступ к проекту
     *
     * @param Agent|null $agent
     * @return bool
     */
    public function canAgentAccess(?Agent $agent): bool
    {
        // Нет агента в запросе — доступ запрещён
        if (is_null($agent)) {
            return false;
        }

        // Если агент имеет глобальный доступ — разрешаем
        if ($agent->hasCrossProjectAccess()) {
            return true;
        }

        // Иначе доступ только к своему проекту
        return $agent->project_id === $this->id;
    }

}
