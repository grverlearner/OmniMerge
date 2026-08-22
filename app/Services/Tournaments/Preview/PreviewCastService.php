<?php

namespace App\Services\Tournaments\Preview;

use App\Models\Entity;
use App\Models\UniverseEntity;
use App\Models\User;
use Illuminate\Support\Collection;

/*
|--------------------------------------------------------------------------
| PreviewCastService
|--------------------------------------------------------------------------
|
| Presta caras reales para dibujar.
|
| Diseñar una fase de grupos con "Participante 01, Participante 02…" no
| deja ver nada: los grupos se entienden cuando tienen cara. Este servicio
| toma prestadas entidades que el usuario ya tiene y devuelve solo su
| nombre y su imagen.
|
| NO GUARDA NADA. No crea participantes, no toca el Universo, no deja
| rastro. Es reparto de figurantes para una previsualización, y por eso
| cada elemento lleva `is_borrowed` — para que ninguna pantalla lo
| confunda con un competidor inscrito de verdad.
|
| Ver docs/md/31-Fase-14-Group-Stage.md
|
*/

class PreviewCastService
{
    /**
     * @return Collection<int, array{name: string, image_url: ?string, is_borrowed: bool}>
     */
    public function borrow(
        ?User $user,
        int $count
    ): Collection {

        if ($count < 1) {
            return collect();
        }

        $cast =
            $this->fromUniverses($user, $count);

        if ($cast->count() < $count) {

            $cast = $cast->concat(
                $this->fromLibrary($user, $count - $cast->count())
            );
        }

        /*
         * Si el usuario todavía no tiene nada, la previsualización no se
         * queda en blanco: se rellena con figurantes anónimos.
         */
        return $cast
            ->take($count)
            ->values()
            ->pad($count, null)
            ->map(
                fn(?array $member, int $index) =>
                $member ?? [
                    'name' => 'Participante ' . str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT),
                    'image_url' => null,
                    'is_borrowed' => false,
                ]
            );
    }

    /*
     * Se prefieren las entidades de Universo: ya tienen su copia propia de
     * imagen y nombre, así que no hay que ir a la Biblioteca.
     */
    private function fromUniverses(?User $user, int $count): Collection
    {
        if (! $user) {
            return collect();
        }

        return UniverseEntity::query()
            ->whereHas(
                'universe',
                fn($query) => $query->where('user_id', $user->id)
            )
            ->where('status', 'ACTIVE')
            ->inRandomOrder()
            ->limit($count)
            ->get()
            ->map(
                fn(UniverseEntity $entity) => [
                    'name' => $entity->display_label,
                    'image_url' => $entity->image_url,
                    'is_borrowed' => true,
                ]
            );
    }

    private function fromLibrary(?User $user, int $count): Collection
    {
        if (! $user || $count < 1) {
            return collect();
        }

        return Entity::query()
            ->where('user_id', $user->id)
            ->inRandomOrder()
            ->limit($count)
            ->get()
            ->map(
                fn(Entity $entity) => [
                    'name' => $entity->name,
                    'image_url' => $entity->image_url,
                    'is_borrowed' => true,
                ]
            );
    }
}
