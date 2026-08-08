<?php

namespace App\Policies;

use App\Models\AttributeOption;
use App\Models\User;

class AttributeOptionPolicy
{
    public function viewAny(
        User $user
    ): bool {

        return $user->isActive();
    }


    public function view(
        User $user,
        AttributeOption $attributeOption
    ): bool {

        return $user->isActive()

            &&

            $attributeOption->user_id
            === $user->id;
    }


    public function create(
        User $user
    ): bool {

        return $user->isActive();
    }


    public function update(
        User $user,
        AttributeOption $attributeOption
    ): bool {

        return $user->isActive()

            &&

            $attributeOption->user_id
            === $user->id;
    }


    public function delete(
        User $user,
        AttributeOption $attributeOption
    ): bool {

        return $user->isActive()

            &&

            $attributeOption->user_id
            === $user->id;
    }
}
