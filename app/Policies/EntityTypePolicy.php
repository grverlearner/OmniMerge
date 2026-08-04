<?php

namespace App\Policies;

use App\Models\EntityType;
use App\Models\User;

class EntityTypePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isActive();
    }

    public function view(
        User $user,
        EntityType $entityType
    ): bool {
        return $user->id === $entityType->user_id;
    }

    public function create(User $user): bool
    {
        return $user->isActive();
    }

    public function update(
        User $user,
        EntityType $entityType
    ): bool {
        return $user->id === $entityType->user_id;
    }

    public function delete(
        User $user,
        EntityType $entityType
    ): bool {
        return $user->id === $entityType->user_id;
    }

    public function restore(
        User $user,
        EntityType $entityType
    ): bool {
        return $user->id === $entityType->user_id;
    }

    public function forceDelete(
        User $user,
        EntityType $entityType
    ): bool {
        return $user->isAdmin();
    }
}