<?php

namespace App\Policies;

use App\Models\AttributeGroup;
use App\Models\User;

class AttributeGroupPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isActive();
    }

    public function view(
        User $user,
        AttributeGroup $attributeGroup
    ): bool {
        return $attributeGroup->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->isActive();
    }

    public function update(
        User $user,
        AttributeGroup $attributeGroup
    ): bool {
        return $attributeGroup->user_id === $user->id;
    }

    public function delete(
        User $user,
        AttributeGroup $attributeGroup
    ): bool {
        return $attributeGroup->user_id === $user->id;
    }
}