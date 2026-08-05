<?php

namespace App\Policies;

use App\Models\Attribute;
use App\Models\User;

class AttributePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isActive();
    }

    public function view(
        User $user,
        Attribute $attribute
    ): bool {
        return $attribute->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->isActive();
    }

    public function update(
        User $user,
        Attribute $attribute
    ): bool {
        return $attribute->user_id === $user->id;
    }

    public function delete(
        User $user,
        Attribute $attribute
    ): bool {
        return $attribute->user_id === $user->id;
    }
}