<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;

class ProjectPolicy
{
    public function view(User $user, Project $project): bool
    {
        return $project->canAccess($user);
    }

    public function update(User $user, Project $project): bool
    {
        return $project->canAccess($user);
    }
}
