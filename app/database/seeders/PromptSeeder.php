<?php

namespace Database\Seeders;

use App\Common\Enums\PromptType;
use App\Models\Project;
use App\Models\Prompt;
use App\Models\User;
use Illuminate\Database\Seeder;

class PromptSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::first();
        $project = Project::first();

        if ($user && $project) {
            Prompt::create([
                'project_id' => $project->id,
                'type' => PromptType::TASK_MANAGER->value,
                'content' => 'Кастомный промпт для проекта {{project_name}}',
                'created_by' => $user->id,
            ]);
        }
    }
}
