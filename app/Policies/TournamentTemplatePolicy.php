<?php

namespace App\Policies;

use App\Models\TournamentTemplate;
use App\Models\User;


class TournamentTemplatePolicy
{
    public function viewAny(
        User $user
    ): bool {

        return $user->isActive();
    }


    public function view(
        User $user,
        TournamentTemplate $tournamentTemplate
    ): bool {

        return
            $tournamentTemplate->user_id
            ===
            $user->id
            ||
            $tournamentTemplate->isPublished();
    }


    public function create(
        User $user
    ): bool {

        return $user->isActive();
    }


    public function update(
        User $user,
        TournamentTemplate $tournamentTemplate
    ): bool {

        return
            $tournamentTemplate->user_id
            ===
            $user->id;
    }


    public function delete(
        User $user,
        TournamentTemplate $tournamentTemplate
    ): bool {

        return
            $tournamentTemplate->user_id
            ===
            $user->id;
    }
}
