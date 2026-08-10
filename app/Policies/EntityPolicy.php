<?php

namespace App\Policies;

use App\Models\Entity;
use App\Models\User;

class EntityPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isActive();
    }
    public function updateAny(
        User $user
    ): bool {

        return $user->isActive();
    }

    public function view(User $user, Entity $entity): bool
    {
        return $user->id === $entity->user_id
            || $entity->visibility === 'PUBLIC';
    }

    public function create(User $user): bool
    {
        return $user->isActive();
    }

    public function update(User $user, Entity $entity): bool
    {
        return $user->id === $entity->user_id;
    }

    public function delete(User $user, Entity $entity): bool
    {
        return $user->id === $entity->user_id;
    }

    public function restore(User $user, Entity $entity): bool
    {
        return $user->id === $entity->user_id;
    }

    public function forceDelete(
        User $user,
        Entity $entity
    ): bool {
        return $user->isAdmin();
    }
}
