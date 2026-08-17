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

        $source->load([
            'graphNodes.entryPorts',

            'graphStarts',

            'graphTerminals',

            'graphConnections',
        ]);


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
| TOURNAMENT GRAPH
|--------------------------------------------------------------------------
|
| El grafo es el flujo vigente. TournamentPhase pertenece al sistema
| anterior y ya no se replica en nuevas copias.
|
*/

                    $nodeIdMap = [];
                    $entryPortIdMap = [];
                    $startIdMap = [];
                    $terminalIdMap = [];


                    /*
|--------------------------------------------------------------------------
| Nodes + Entry Ports
|--------------------------------------------------------------------------
*/

                    foreach (
                        $source->graphNodes
                        as
                        $sourceNode
                    ) {
                        $newNode =
                            $copy
                            ->graphNodes()
                            ->create([
                                'phase_template_id' =>
                                $sourceNode
                                    ->phase_template_id,

                                'sequence_number' =>
                                $sourceNode
                                    ->sequence_number,

                                'code' =>
                                $sourceNode->code,

                                'name' =>
                                $sourceNode->name,

                                'description' =>
                                $sourceNode
                                    ->description,

                                'x_position' =>
                                $sourceNode
                                    ->x_position,

                                'y_position' =>
                                $sourceNode
                                    ->y_position,

                                'status' =>
                                $sourceNode->status,

                                'settings' =>
                                $sourceNode
                                    ->settings,
                            ]);


                        $nodeIdMap[$sourceNode->id] =
                            $newNode->id;


                        foreach (
                            $sourceNode->entryPorts
                            as
                            $sourceEntry
                        ) {
                            $newEntry =
                                $newNode
                                ->entryPorts()
                                ->create([
                                    'sequence_number' =>
                                    $sourceEntry
                                        ->sequence_number,

                                    'code' =>
                                    $sourceEntry->code,

                                    'name' =>
                                    $sourceEntry->name,

                                    'description' =>
                                    $sourceEntry
                                        ->description,

                                    'merge_policy' =>
                                    $sourceEntry
                                        ->merge_policy,

                                    'is_required' =>
                                    $sourceEntry
                                        ->is_required,

                                    'accepts_multiple_connections' =>
                                    $sourceEntry
                                        ->accepts_multiple_connections,

                                    'min_participants' =>
                                    $sourceEntry
                                        ->min_participants,

                                    'max_participants' =>
                                    $sourceEntry
                                        ->max_participants,

                                    'exact_participants' =>
                                    $sourceEntry
                                        ->exact_participants,

                                    'sort_order' =>
                                    $sourceEntry
                                        ->sort_order,

                                    'status' =>
                                    $sourceEntry
                                        ->status,

                                    'settings' =>
                                    $sourceEntry
                                        ->settings,
                                ]);


                            $entryPortIdMap[$sourceEntry->id] =
                                $newEntry->id;
                        }
                    }


                    /*
|--------------------------------------------------------------------------
| Starts
|--------------------------------------------------------------------------
*/

                    foreach (
                        $source->graphStarts
                        as
                        $sourceStart
                    ) {
                        $newStart =
                            $copy
                            ->graphStarts()
                            ->create([
                                'sequence_number' =>
                                $sourceStart
                                    ->sequence_number,

                                'code' =>
                                $sourceStart->code,

                                'name' =>
                                $sourceStart->name,

                                'description' =>
                                $sourceStart
                                    ->description,

                                'source_type' =>
                                $sourceStart
                                    ->source_type,

                                'expected_participants' =>
                                $sourceStart
                                    ->expected_participants,

                                'x_position' =>
                                $sourceStart
                                    ->x_position,

                                'y_position' =>
                                $sourceStart
                                    ->y_position,

                                'status' =>
                                $sourceStart
                                    ->status,

                                'settings' =>
                                $sourceStart
                                    ->settings,
                            ]);


                        $startIdMap[$sourceStart->id] =
                            $newStart->id;
                    }


                    /*
|--------------------------------------------------------------------------
| Terminals
|--------------------------------------------------------------------------
*/

                    foreach (
                        $source->graphTerminals
                        as
                        $sourceTerminal
                    ) {
                        $newTerminal =
                            $copy
                            ->graphTerminals()
                            ->create([
                                'sequence_number' =>
                                $sourceTerminal
                                    ->sequence_number,

                                'code' =>
                                $sourceTerminal->code,

                                'name' =>
                                $sourceTerminal->name,

                                'description' =>
                                $sourceTerminal
                                    ->description,

                                'terminal_type' =>
                                $sourceTerminal
                                    ->terminal_type,

                                'expected_participants' =>
                                $sourceTerminal
                                    ->expected_participants,

                                'x_position' =>
                                $sourceTerminal
                                    ->x_position,

                                'y_position' =>
                                $sourceTerminal
                                    ->y_position,

                                'status' =>
                                $sourceTerminal
                                    ->status,

                                'settings' =>
                                $sourceTerminal
                                    ->settings,
                            ]);


                        $terminalIdMap[$sourceTerminal->id] =
                            $newTerminal->id;
                    }


                    /*
|--------------------------------------------------------------------------
| Connections
|--------------------------------------------------------------------------
*/

                    foreach (
                        $source->graphConnections
                        as
                        $sourceConnection
                    ) {
                        $copy
                            ->graphConnections()
                            ->create([
                                'sequence_number' =>
                                $sourceConnection
                                    ->sequence_number,

                                'code' =>
                                $sourceConnection
                                    ->code,

                                'label' =>
                                $sourceConnection
                                    ->label,

                                'description' =>
                                $sourceConnection
                                    ->description,

                                'source_type' =>
                                $sourceConnection
                                    ->source_type,

                                'source_start_id' =>
                                $sourceConnection
                                    ->source_start_id
                                    ?
                                    (
                                        $startIdMap[$sourceConnection
                                            ->source_start_id]
                                        ??
                                        null
                                    )
                                    :
                                    null,

                                'source_node_id' =>
                                $sourceConnection
                                    ->source_node_id
                                    ?
                                    (
                                        $nodeIdMap[$sourceConnection
                                            ->source_node_id]
                                        ??
                                        null
                                    )
                                    :
                                    null,

                                /*
             * PhaseExit pertenece al PhaseTemplate reutilizable,
             * por tanto conserva el mismo ID.
             */
                                'source_phase_exit_id' =>
                                $sourceConnection
                                    ->source_phase_exit_id,

                                'target_type' =>
                                $sourceConnection
                                    ->target_type,

                                'target_entry_port_id' =>
                                $sourceConnection
                                    ->target_entry_port_id
                                    ?
                                    (
                                        $entryPortIdMap[$sourceConnection
                                            ->target_entry_port_id]
                                        ??
                                        null
                                    )
                                    :
                                    null,

                                'target_terminal_id' =>
                                $sourceConnection
                                    ->target_terminal_id
                                    ?
                                    (
                                        $terminalIdMap[$sourceConnection
                                            ->target_terminal_id]
                                        ??
                                        null
                                    )
                                    :
                                    null,

                                'allocation_mode' =>
                                $sourceConnection
                                    ->allocation_mode,

                                'allocation_value' =>
                                $sourceConnection
                                    ->allocation_value,

                                'priority' =>
                                $sourceConnection
                                    ->priority,

                                'status' =>
                                $sourceConnection
                                    ->status,

                                'settings' =>
                                $sourceConnection
                                    ->settings,
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
