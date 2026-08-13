<?php

namespace App\Services\Tournaments;

use App\Models\PhaseTemplate;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class PhaseTemplateService
{
    public function previewCode(
        User $user
    ): string {
        return PhaseTemplate::formatCode(
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
    ): PhaseTemplate {
        $imagePath = $image?->store(
            'phase-templates',
            'public'
        );

        if ($imagePath) {
            $data['image'] = $imagePath;
        }

        $data = $this->normalizeContract(
            $data
        );

        try {
            return DB::transaction(
                function () use (
                    $user,
                    $data
                ) {
                    $lockedUser = User::query()
                        ->whereKey($user->id)
                        ->lockForUpdate()
                        ->firstOrFail();

                    $sequence =
                        $this->nextSequence(
                            $lockedUser->id
                        );

                    $data['sequence_number'] =
                        $sequence;

                    $data['code'] =
                        PhaseTemplate::formatCode(
                            $sequence
                        );

                    $data['slug'] =
                        $this->uniqueSlug(
                            $lockedUser->id,
                            $data['name']
                        );

                    $data['published_at'] =
                        $this->shouldPublish($data)
                        ? now()
                        : null;

                    return $lockedUser
                        ->phaseTemplates()
                        ->create($data);
                }
            );
        } catch (Throwable $exception) {
            if ($imagePath) {
                Storage::disk('public')
                    ->delete($imagePath);
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
        PhaseTemplate $phaseTemplate,
        array $data,
        ?UploadedFile $image = null
    ): PhaseTemplate {
        $oldImage =
            $phaseTemplate->image;

        $newImage = null;

        if ($image) {
            $newImage = $image->store(
                'phase-templates',
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

        $data =
            $this->normalizeContract(
                $data
            );

        $data['slug'] =
            $this->uniqueSlug(
                $phaseTemplate->user_id,
                $data['name'],
                $phaseTemplate->id
            );

        if ($this->shouldPublish($data)) {
            $data['published_at'] =
                $phaseTemplate->published_at
                ?? now();
        } else {
            $data['published_at'] =
                null;
        }

        try {
            DB::transaction(
                function () use (
                    $phaseTemplate,
                    $data
                ) {
                    $phaseTemplate->update(
                        $data
                    );

                    /*
                    |--------------------------------------------------------------------------
                    | Mantener T1 y T2 sincronizados
                    |--------------------------------------------------------------------------
                    */

                    if (
                        $phaseTemplate->phase_type
                        ===
                        'SINGLE_ELIMINATION'
                        &&
                        $phaseTemplate
                        ->singleEliminationSetting()
                        ->exists()
                    ) {
                        $phaseTemplate
                            ->singleEliminationSetting()
                            ->update([
                                'default_best_of' =>
                                (int)
                                $phaseTemplate->best_of,
                            ]);
                    }
                }
            );
        } catch (Throwable $exception) {
            if ($newImage) {
                Storage::disk('public')
                    ->delete($newImage);
            }

            throw $exception;
        }

        if (
            $oldImage
            &&
            (
                $newImage
                ||
                (
                    array_key_exists(
                        'image',
                        $data
                    )
                    &&
                    $data['image'] === null
                )
            )
        ) {
            Storage::disk('public')
                ->delete($oldImage);
        }

        return $phaseTemplate->fresh();
    }

    /*
    |--------------------------------------------------------------------------
    | Duplicar
    |--------------------------------------------------------------------------
    */

    public function duplicate(
        User $user,
        PhaseTemplate $source
    ): PhaseTemplate {
        $source->load([
            'exits',
            'singleEliminationSetting',
            'singleEliminationRoundRules',
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
                        ->whereKey($user->id)
                        ->lockForUpdate()
                        ->firstOrFail();

                    $sequence =
                        $this->nextSequence(
                            $lockedUser->id
                        );

                    $name =
                        'Copia de '
                        . $source->name;

                    $copy =
                        $lockedUser
                        ->phaseTemplates()
                        ->create([
                            'source_phase_template_id' =>
                            $source->id,

                            'sequence_number' =>
                            $sequence,

                            'code' =>
                            PhaseTemplate::formatCode(
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

                            'phase_type' =>
                            $source->phase_type,

                            'participant_mode' =>
                            $source->participant_mode,

                            'min_participants' =>
                            $source->min_participants,

                            'max_participants' =>
                            $source->max_participants,

                            'exact_participants' =>
                            $source->exact_participants,

                            'participant_multiple' =>
                            $source->participant_multiple,

                            'allow_byes' =>
                            $source->allow_byes,

                            'best_of' =>
                            $source->best_of,

                            'status' =>
                            'DRAFT',

                            'visibility' =>
                            'PRIVATE',

                            'allow_cloning' =>
                            true,

                            'settings' =>
                            $source->settings,

                            'metadata' =>
                            $source->metadata,
                        ]);

                    foreach (
                        $source->exits
                        as
                        $exit
                    ) {
                        $copy->exits()
                            ->create([
                                'sequence_number' =>
                                $exit->sequence_number,

                                'code' =>
                                $exit->code,

                                'name' =>
                                $exit->name,

                                'description' =>
                                $exit->description,

                                'selector_type' =>
                                $exit->selector_type,

                                'selector_from' =>
                                $exit->selector_from,

                                'selector_to' =>
                                $exit->selector_to,

                                'priority' =>
                                $exit->priority,

                                'sort_order' =>
                                $exit->sort_order,

                                'status' =>
                                $exit->status,

                                'settings' =>
                                $exit->settings,
                            ]);
                    }

                    /*
|--------------------------------------------------------------------------
| Configuración SINGLE ELIMINATION
|--------------------------------------------------------------------------
*/

                    if (
                        $source->phase_type
                        ===
                        'SINGLE_ELIMINATION'
                        &&
                        $source->singleEliminationSetting
                    ) {
                        $sourceSettings =
                            $source->singleEliminationSetting;

                        $copy
                            ->singleEliminationSetting()
                            ->create([
                                'completion_mode' =>
                                $sourceSettings->completion_mode,

                                'target_survivors' =>
                                $sourceSettings->target_survivors,

                                'seeding_mode' =>
                                $sourceSettings->seeding_mode,

                                'pairing_mode' =>
                                $sourceSettings->pairing_mode,

                                'bye_assignment' =>
                                $sourceSettings->bye_assignment,

                                'reseed_each_round' =>
                                $sourceSettings->reseed_each_round,

                                'default_best_of' =>
                                $sourceSettings->default_best_of,

                                'settings' =>
                                $sourceSettings->settings,
                            ]);

                        /*
    |--------------------------------------------------------------------------
    | Overrides de ronda
    |--------------------------------------------------------------------------
    */

                        foreach (
                            $source->singleEliminationRoundRules
                            as
                            $roundRule
                        ) {
                            $copy
                                ->singleEliminationRoundRules()
                                ->create([
                                    'participants_in_round' =>
                                    $roundRule->participants_in_round,

                                    'best_of' =>
                                    $roundRule->best_of,

                                    'sort_order' =>
                                    $roundRule->sort_order,

                                    'settings' =>
                                    $roundRule->settings,
                                ]);
                        }
                    }

                    return $copy;
                }
            );
        } catch (Throwable $exception) {
            if ($duplicatedImage) {
                Storage::disk('public')
                    ->delete(
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
        PhaseTemplate $phaseTemplate
    ): void {
        $phaseTemplate->update([
            'status' => 'ARCHIVED',
            'published_at' => null,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Eliminar
    |--------------------------------------------------------------------------
    */

    public function delete(
        PhaseTemplate $phaseTemplate
    ): void {
        $phaseTemplate->delete();
    }

    /*
    |--------------------------------------------------------------------------
    | Contrato
    |--------------------------------------------------------------------------
    */

    private function normalizeContract(
        array $data
    ): array {
        $exact =
            $data['exact_participants']
            ?? null;

        if (
            $exact !== null
            &&
            $exact !== ''
        ) {
            $exact = (int) $exact;

            $data['exact_participants'] =
                $exact;

            $data['min_participants'] =
                $exact;

            $data['max_participants'] =
                $exact;
        } else {
            $data['exact_participants'] =
                null;
        }

        if (
            empty($data['max_participants'])
        ) {
            $data['max_participants'] =
                null;
        }

        if (
            empty($data['participant_multiple'])
        ) {
            $data['participant_multiple'] =
                null;
        }

        return $data;
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
            PhaseTemplate::withTrashed()
                ->where(
                    'user_id',
                    $userId
                )
                ->max(
                    'sequence_number'
                )
        ) + 1;
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
            Str::slug($name)
            ?: 'fase';

        $slug = $base;

        $counter = 2;

        while (true) {
            $query =
                PhaseTemplate::withTrashed()
                ->where(
                    'user_id',
                    $userId
                )
                ->where(
                    'slug',
                    $slug
                );

            if ($ignoreId) {
                $query->where(
                    'id',
                    '!=',
                    $ignoreId
                );
            }

            if (! $query->exists()) {
                break;
            }

            $slug =
                $base
                . '-'
                . $counter;

            $counter++;
        }

        return $slug;
    }

    private function shouldPublish(
        array $data
    ): bool {
        return (
            $data['visibility']
            ?? null
        ) === 'PUBLIC'
            &&
            (
                $data['status']
                ?? null
            ) === 'ACTIVE';
    }

    /*
    |--------------------------------------------------------------------------
    | Imagen
    |--------------------------------------------------------------------------
    */

    private function duplicateImage(
        ?string $sourcePath
    ): ?string {
        if (! $sourcePath) {
            return null;
        }

        $disk =
            Storage::disk('public');

        if (! $disk->exists($sourcePath)) {
            return null;
        }

        $extension =
            pathinfo(
                $sourcePath,
                PATHINFO_EXTENSION
            );

        $targetPath =
            'phase-templates/'
            . Str::uuid()
            . (
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
