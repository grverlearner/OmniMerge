<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Version;

class VersionPolicy
{
    public function viewAny(
        User $user
    ): bool {

        return $user->isActive();
    }


    public function view(
        User $user,
        Version $version
    ): bool {

        return $user->id
            ===
            $version->user_id;
    }


    public function create(
        User $user
    ): bool {

        return $user->isActive();
    }


    public function update(
        User $user,
        Version $version
    ): bool {

        return $user->id
            ===
            $version->user_id;
    }


    public function delete(
        User $user,
        Version $version
    ): bool {

        return $user->id
            ===
            $version->user_id;
    }
}
