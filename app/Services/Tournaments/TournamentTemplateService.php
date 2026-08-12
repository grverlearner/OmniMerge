<?php

namespace App\Services\Tournaments;

use App\Models\TournamentTemplate;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;


class TournamentTemplateService
{
    /*
    |--------------------------------------------------------------------------
    | Preview de código
    |--------------------------------------------------------------------------
    */


    public function previewCode(
        User $user
    ): string {

        return TournamentTemplate::formatCode(
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
    ): TournamentTemplate {

        $imagePath =
            $image
            ?->store(
                'tournament-templates',
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
                        TournamentTemplate::formatCode(
                            $sequence
                        );


                    $data['slug'] =
                        $this->uniqueSlug(
                            $lockedUser->id,
                            $data['name']
                        );


                    $data['published_at'] =
                        $this->shouldPublish(
                            $data
                        )
                        ? now()
                        : null;


                    return $lockedUser
                        ->tournamentTemplates()
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
        TournamentTemplate $template,
        array $data,
        ?UploadedFile $image = null
    ): TournamentTemplate {

        $oldImage =
            $template->image;


        $newImage =
            null;


        if ($image) {

            $newImage =
                $image->store(
                    'tournament-templates',
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
                $template->user_id,
                $data['name'],
                $template->id
            );


        if (
            $this->shouldPublish(
                $data
            )
        ) {

            $data['published_at'] =
                $template->published_at
                ??
                now();
        } else {

            $data['published_at'] =
                null;
        }


        try {

            DB::transaction(
                function () use (
                    $template,
                    $data
                ) {

                    $template->update(
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


        return $template->fresh();
    }


    /*
    |--------------------------------------------------------------------------
    | Duplicar
    |--------------------------------------------------------------------------
    */


    public function duplicate(
        User $user,
        TournamentTemplate $source
    ): TournamentTemplate {

        $source->load(
            'phases'
        );


        $duplicatedImage =
            $this->duplicateImage(
                $source->image
            );


        try {

            return DB::transaction(
                function () use (
                    $user,
                    $source,
                    $duplicatedImage
                ) {

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


                    $name =
                        'Copia de '
                        .
                        $source->name;


                    $copy =
                        $lockedUser
                        ->tournamentTemplates()
                        ->create([

                            'source_tournament_template_id' =>
                            $source->id,

                            'sequence_number' =>
                            $sequence,

                            'code' =>
                            TournamentTemplate::formatCode(
                                $sequence
                            ),

                            'name' =>
                            $name,

                            'slug' =>
                            $this->uniqueSlug(
                                $lockedUser->id,
                                $name
                            ),

                            'description' =>
                            $source->description,

                            'image' =>
                            $duplicatedImage,

                            'min_participants' =>
                            $source->min_participants,

                            'max_participants' =>
                            $source->max_participants,

                            'allow_byes' =>
                            $source->allow_byes,

                            /*
                                 * Las copias internas empiezan
                                 * como borrador y privadas.
                                 */
                            'status' =>
                            'DRAFT',

                            'visibility' =>
                            'PRIVATE',

                            'allow_cloning' =>
                            true,

                            'views_count' =>
                            0,

                            'clones_count' =>
                            0,

                            'published_at' =>
                            null,

                            'settings' =>
                            $source->settings,

                            'metadata' =>
                            $source->metadata,
                        ]);


                    /*
                    |--------------------------------------------------------------------------
                    | Copiar fases
                    |--------------------------------------------------------------------------
                    */

                    foreach (
                        $source->phases
                        as
                        $phase
                    ) {

                        $copy
                            ->phases()
                            ->create([

                                'sequence_number' =>
                                $phase->sequence_number,

                                'code' =>
                                $phase->code,

                                'name' =>
                                $phase->name,

                                'description' =>
                                $phase->description,

                                'phase_type' =>
                                $phase->phase_type,

                                'sort_order' =>
                                $phase->sort_order,

                                'input_participants' =>
                                $phase->input_participants,

                                'qualifiers_count' =>
                                $phase->qualifiers_count,

                                'best_of' =>
                                $phase->best_of,

                                'allow_byes' =>
                                $phase->allow_byes,

                                'status' =>
                                $phase->status,

                                'settings' =>
                                $phase->settings,
                            ]);
                    }


                    return $copy;
                }
            );
        } catch (Throwable $exception) {

            if ($duplicatedImage) {

                Storage::disk(
                    'public'
                )->delete(
                    $duplicatedImage
                );
            }


            throw $exception;
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Archivar
    |--------------------------------------------------------------------------
    */


    public function archive(
        TournamentTemplate $template
    ): void {

        $template->update([

            'status' =>
            'ARCHIVED',

            'published_at' =>
            null,
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Eliminar
    |--------------------------------------------------------------------------
    */


    public function delete(
        TournamentTemplate $template
    ): void {

        /*
         * Soft Delete.
         * La portada se conserva.
         */
        $template->delete();
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
                TournamentTemplate::withTrashed()
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
            'torneo';


        $slug =
            $base;


        $counter =
            2;


        while (true) {

            $query =
                TournamentTemplate::withTrashed()
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


    /*
    |--------------------------------------------------------------------------
    | Publicación
    |--------------------------------------------------------------------------
    */


    private function shouldPublish(
        array $data
    ): bool {

        return (
                $data['visibility']
                ??
                null
            )
            ===
            'PUBLIC'

            &&
            (
                $data['status']
                ??
                null
            )
            ===
            'ACTIVE';
    }


    /*
    |--------------------------------------------------------------------------
    | Duplicar imagen
    |--------------------------------------------------------------------------
    */


    private function duplicateImage(
        ?string $sourcePath
    ): ?string {

        if (! $sourcePath) {
            return null;
        }


        $disk =
            Storage::disk(
                'public'
            );


        if (! $disk->exists(
            $sourcePath
        )) {
            return null;
        }


        $extension =
            pathinfo(
                $sourcePath,
                PATHINFO_EXTENSION
            );


        $targetPath =
            'tournament-templates/'
            .
            Str::uuid()
            .
            (
                $extension
                ? '.' . $extension
                : ''
            );


        $disk->copy(
            $sourcePath,
            $targetPath
        );


        return $targetPath;
    }
}
