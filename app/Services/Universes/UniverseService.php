<?php

namespace App\Services\Universes;

use App\Models\Universe;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;


class UniverseService
{
    /*
    |--------------------------------------------------------------------------
    | Preview de código
    |--------------------------------------------------------------------------
    */


    public function previewCode(
        User $user
    ): string {

        return Universe::formatCode(
            $this->nextSequence(
                $user->id
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Crear
    |--------------------------------------------------------------------------
    */


    public function create(
        User $user,
        array $data,
        ?UploadedFile $image = null
    ): Universe {

        $imagePath =
            $image
            ?->store(
                'universes',
                'public'
            );

        if ($imagePath) {
            $data['image'] =
                $imagePath;
        }

        try {

            return DB::transaction(
                function () use (
                    $user,
                    $data
                ) {

                    /*
                     * Bloqueamos el User para evitar
                     * generar dos secuencias iguales
                     * simultáneamente.
                     */
                    $lockedUser =
                        User::query()
                        ->whereKey(
                            $user->id
                        )
                        ->lockForUpdate()
                        ->firstOrFail();

                    $sequence =
                        $this->nextSequence(
                            $lockedUser->id
                        );

                    $data['sequence_number'] =
                        $sequence;

                    $data['code'] =
                        Universe::formatCode(
                            $sequence
                        );

                    $data['slug'] =
                        $this->uniqueSlug(
                            $lockedUser->id,
                            $data['name']
                        );

                    return $lockedUser
                        ->universes()
                        ->create(
                            $data
                        );
                }
            );
        } catch (Throwable $exception) {

            if ($imagePath) {

                Storage::disk(
                    'public'
                )->delete(
                    $imagePath
                );
            }

            throw $exception;
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Actualizar
    |--------------------------------------------------------------------------
    */


    public function update(
        Universe $universe,
        array $data,
        ?UploadedFile $image = null
    ): Universe {

        $oldImage =
            $universe->image;

        $newImage =
            null;

        if ($image) {

            $newImage =
                $image->store(
                    'universes',
                    'public'
                );

            $data['image'] =
                $newImage;
        } elseif (
            $data['remove_image']
            ?? false
        ) {

            $data['image'] =
                null;
        }

        unset(
            $data['remove_image']
        );

        $data['slug'] =
            $this->uniqueSlug(
                $universe->user_id,
                $data['name'],
                $universe->id
            );

        try {

            DB::transaction(
                function () use (
                    $universe,
                    $data
                ) {

                    $universe->update(
                        $data
                    );
                }
            );
        } catch (Throwable $exception) {

            if ($newImage) {

                Storage::disk(
                    'public'
                )->delete(
                    $newImage
                );
            }

            throw $exception;
        }

        /*
         * Eliminamos la portada anterior
         * únicamente después de guardar correctamente.
         */
        if (
            $oldImage
            &&
            (
                $newImage
                ||
                array_key_exists(
                    'image',
                    $data
                )
                &&
                $data['image'] === null
            )
        ) {

            Storage::disk(
                'public'
            )->delete(
                $oldImage
            );
        }

        return $universe->fresh();
    }


    /*
    |--------------------------------------------------------------------------
    | Archivar
    |--------------------------------------------------------------------------
    */


    public function archive(
        Universe $universe
    ): void {

        $universe->update([

            'status' =>
            'ARCHIVED',
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Eliminar
    |--------------------------------------------------------------------------
    */


    public function delete(
        Universe $universe
    ): void {

        /*
         * Soft Delete. Las plantillas de torneo que
         * pertenecían a este Universo quedan
         * desasociadas (universe_id nulo), no se
         * eliminan (ver migración
         * add_universe_id_to_tournament_templates_table).
         */
        $universe->delete();
    }


    /*
    |--------------------------------------------------------------------------
    | Secuencia
    |--------------------------------------------------------------------------
    */


    private function nextSequence(
        int $userId
    ): int {

        return (
            (int)
            Universe::withTrashed()
                ->where(
                    'user_id',
                    $userId
                )
                ->max(
                    'sequence_number'
                )
        )
            +
            1;
    }


    /*
    |--------------------------------------------------------------------------
    | Slug
    |--------------------------------------------------------------------------
    */


    private function uniqueSlug(
        int $userId,
        string $name,
        ?int $ignoreId = null
    ): string {

        $base =
            Str::slug(
                $name
            )
            ?:
            'universo';

        $slug =
            $base;

        $counter =
            2;

        while (true) {

            $query =
                Universe::withTrashed()
                ->where(
                    'user_id',
                    $userId
                )
                ->where(
                    'slug',
                    $slug
                );

            if ($ignoreId) {

                $query->whereKeyNot(
                    $ignoreId
                );
            }

            if (! $query->exists()) {
                break;
            }

            $slug =
                $base
                .
                '-'
                .
                $counter;

            $counter++;
        }

        return $slug;
    }
}
