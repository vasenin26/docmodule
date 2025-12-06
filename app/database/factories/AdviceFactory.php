<?php

namespace Database\Factories;

use App\Models\Advice;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

class AdviceFactory extends Factory
{
    protected $model = Advice::class;

    public function definition()
    {
        return [
            'project_id' => Project::factory(),
            'group' => $this->faker->regexify('[A-Za-z0-9\-_]{1,64}'),
            'content' => $this->faker->paragraph(),
        ];
    }
}
