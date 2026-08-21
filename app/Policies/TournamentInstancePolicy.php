<?php

namespace App\Policies;

use App\Models\TournamentInstance;
use App\Models\User;

/*
|--------------------------------------------------------------------------
| TournamentInstancePolicy
|--------------------------------------------------------------------------
|
| Mismo patrón que UniversePolicy: la propiedad se resuelve por el
| Universo al que pertenece la competición. No hay sistema de permisos
| paralelo.
|
| El aislamiento entre Universos lo garantiza además scopeBindings() en
| las rutas: una competición de otro Universo devuelve 404 antes de
| llegar aquí.
|
*/

class TournamentInstancePolicy
{
    public function viewAny(
        User $user
    ): bool {

        return $user->isActive();
    }

    public function view(
        User $user,
        TournamentInstance $instance
    ): bool {

        return $this->owns(
            $user,
            $instance
        );
    }

    public function create(
        User $user
    ): bool {

        return $user->isActive();
    }

    /*
     * Cubre iniciar, registrar resultados, pausar, reanudar y cancelar:
     * todo lo que hace avanzar una competición.
     */
    public function update(
        User $user,
        TournamentInstance $instance
    ): bool {

        return $this->owns(
            $user,
            $instance
        );
    }

    public function delete(
        User $user,
        TournamentInstance $instance
    ): bool {

        return $this->owns(
            $user,
            $instance
        );
    }

    private function owns(
        User $user,
        TournamentInstance $instance
    ): bool {

        return (int) $instance->universe?->user_id
            === (int) $user->id;
    }
}
