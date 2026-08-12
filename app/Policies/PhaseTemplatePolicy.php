<?php

namespace App\Policies;

use App\Models\PhaseTemplate;
use App\Models\User;

class PhaseTemplatePolicy
{
    public function viewAny(
        User $user
    ): bool {
        return $user->isActive();
    }

    public function view(
        User $user,
        PhaseTemplate $phaseTemplate
    ): bool {
        return $phaseTemplate->user_id === $user->id
            || $phaseTemplate->isPublished();
    }

    public function create(
        User $user
    ): bool {
        return $user->isActive();
    }

    public function update(
        User $user,
        PhaseTemplate $phaseTemplate
    ): bool {
        return $phaseTemplate->user_id === $user->id;
    }

    public function delete(
        User $user,
        PhaseTemplate $phaseTemplate
    ): bool {
        return $phaseTemplate->user_id === $user->id;
    }
}
