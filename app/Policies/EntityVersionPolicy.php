<?php

namespace App\Policies;

use App\Models\EntityVersion;
use App\Models\User;

class EntityVersionPolicy
{
    public function viewAny(
        User $user
    ): bool {

        return $user->isActive();
    }


    public function view(
        User $user,
        EntityVersion $entityVersion
    ): bool {

        return $user->id
            ===
            $entityVersion->user_id;
    }


    public function create(
        User $user
    ): bool {

        return $user->isActive();
    }


    public function update(
        User $user,
        EntityVersion $entityVersion
    ): bool {

        return $user->id
            ===
            $entityVersion->user_id;
    }


    public function delete(
        User $user,
        EntityVersion $entityVersion
    ): bool {

        return $user->id
            ===
            $entityVersion->user_id;
    }
}
