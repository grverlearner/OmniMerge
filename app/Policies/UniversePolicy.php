<?php

namespace App\Policies;

use App\Models\Universe;
use App\Models\User;


class UniversePolicy
{
    public function viewAny(
        User $user
    ): bool {

        return $user->isActive();
    }


    public function view(
        User $user,
        Universe $universe
    ): bool {

        return
            $universe->user_id
            ===
            $user->id;
    }


    public function create(
        User $user
    ): bool {

        return $user->isActive();
    }


    public function update(
        User $user,
        Universe $universe
    ): bool {

        return
            $universe->user_id
            ===
            $user->id;
    }


    public function delete(
        User $user,
        Universe $universe
    ): bool {

        return
            $universe->user_id
            ===
            $user->id;
    }
}
