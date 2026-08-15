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

                    /*
|--------------------------------------------------------------------------
| Mantener T1 y T3 sincronizados
|--------------------------------------------------------------------------
*/

                    if (
                        $phaseTemplate->phase_type
                        ===
                        'ROUND_ROBIN'
                        &&
                        $phaseTemplate
                        ->roundRobinSetting()
                        ->exists()
                    ) {
                        $phaseTemplate
                            ->roundRobinSetting()
                            ->update([
                                'default_best_of' =>
                                (int)
                                $phaseTemplate->best_of,
                            ]);
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Mantener T1 y T4 sincronizados
                    |--------------------------------------------------------------------------
                    */

                    if (
                        $phaseTemplate->phase_type
                        ===
                        'GROUP_STAGE'
                        &&
                        $phaseTemplate
                        ->groupStageSetting()
                        ->exists()
                    ) {
                        $phaseTemplate
                            ->groupStageSetting()
                            ->update([
                                'internal_best_of' =>
                                (int)
                                $phaseTemplate->best_of,
                            ]);
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Mantener T1 y T5 sincronizados
                    |--------------------------------------------------------------------------
                    */

                    if (
                        $phaseTemplate->phase_type
                        ===
                        'SWISS'
                        &&
                        $phaseTemplate
                        ->swissSetting()
                        ->exists()
                    ) {
                        $swissSetting =
                            $phaseTemplate
                            ->swissSetting()
                            ->first();

                        $phaseTemplate
                            ->swissSetting()
                            ->update([
                                'default_best_of' =>
                                (int)
                                $phaseTemplate->best_of,

                                'bye_policy' =>
                                $phaseTemplate->allow_byes
                                    ? (
                                        $swissSetting->bye_policy
                                        ===
                                        'DISABLED'
                                        ? 'LOWEST_STANDING_WITHOUT_BYE'
                                        : $swissSetting->bye_policy
                                    )
                                    : 'DISABLED',
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

            'roundRobinSetting',
            'roundRobinTiebreakers',

            'groupStageSetting',
            'groupStageGroups',
            'groupStageAdvancementRules',
            'groupStageTiebreakers',

            'swissSetting',
            'swissTiebreakers',
            'swissRoundRules',
            'swissAdvancementRules',
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

                    $exitIdMap = [];

                    foreach (
                        $source->exits
                        as
                        $exit
                    ) {
                        $newExit =
                            $copy
                            ->exits()
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

                                'exit_timing' =>
                                $exit->exit_timing,

                                'selector_from' =>
                                $exit->selector_from,

                                'selector_to' =>
                                $exit->selector_to,

                                'selector_round_size' =>
                                $exit->selector_round_size,

                                'priority' =>
                                $exit->priority,

                                'sort_order' =>
                                $exit->sort_order,

                                'status' =>
                                $exit->status,

                                'settings' =>
                                $exit->settings,
                            ]);
                        $exitIdMap[$exit->id] =
                            $newExit->id;
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
                                'configuration_mode' =>
                                $sourceSettings->configuration_mode,

                                'input_mode' =>
                                $sourceSettings->input_mode,

                                'routing_mode' =>
                                $sourceSettings->routing_mode,

                                'entrants_per_match' =>
                                $sourceSettings->entrants_per_match,

                                'qualifiers_per_match' =>
                                $sourceSettings->qualifiers_per_match,

                                'encounter_profile' =>
                                $sourceSettings->encounter_profile,

                                'remainder_policy' =>
                                $sourceSettings->remainder_policy,
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

                                'series_format' =>
                                $sourceSettings->series_format,

                                'default_best_of' =>
                                $sourceSettings->default_best_of,

                                'fixed_games' =>
                                $sourceSettings->fixed_games,

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
                                    'entrants_per_match' =>
                                    $roundRule->entrants_per_match,

                                    'qualifiers_per_match' =>
                                    $roundRule->qualifiers_per_match,

                                    'encounter_profile' =>
                                    $roundRule->encounter_profile,

                                    'series_format' =>
                                    $roundRule->series_format,

                                    'best_of' =>
                                    $roundRule->best_of,

                                    'fixed_games' =>
                                    $roundRule->fixed_games,

                                    'sort_order' =>
                                    $roundRule->sort_order,

                                    'settings' =>
                                    $roundRule->settings,
                                ]);
                        }
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Configuración ROUND ROBIN
                    |--------------------------------------------------------------------------
                    */

                    if (
                        $source->phase_type
                        ===
                        'ROUND_ROBIN'
                        &&
                        $source->roundRobinSetting
                    ) {
                        $sourceSettings =
                            $source->roundRobinSetting;

                        $copy
                            ->roundRobinSetting()
                            ->create([
                                'cycles' =>
                                $sourceSettings->cycles,

                                'initial_order_mode' =>
                                $sourceSettings->initial_order_mode,

                                'schedule_mode' =>
                                $sourceSettings->schedule_mode,

                                'allow_draws' =>
                                $sourceSettings->allow_draws,

                                'win_points' =>
                                $sourceSettings->win_points,

                                'draw_points' =>
                                $sourceSettings->draw_points,

                                'loss_points' =>
                                $sourceSettings->loss_points,

                                'default_best_of' =>
                                $sourceSettings->default_best_of,

                                'cutoff_tie_policy' =>
                                $sourceSettings->cutoff_tie_policy,

                                'settings' =>
                                $sourceSettings->settings,
                            ]);

                        /*
                        |--------------------------------------------------------------------------
                        | Tiebreakers
                        |--------------------------------------------------------------------------
                        */

                        foreach (
                            $source->roundRobinTiebreakers
                            as
                            $tiebreaker
                        ) {
                            $copy
                                ->roundRobinTiebreakers()
                                ->create([
                                    'criterion' =>
                                    $tiebreaker->criterion,

                                    'direction' =>
                                    $tiebreaker->direction,

                                    'sort_order' =>
                                    $tiebreaker->sort_order,

                                    'settings' =>
                                    $tiebreaker->settings,
                                ]);
                        }
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Configuración GROUP STAGE
                    |--------------------------------------------------------------------------
                    */

                    if (
                        $source->phase_type
                        ===
                        'GROUP_STAGE'
                        &&
                        $source->groupStageSetting
                    ) {
                        $sourceSettings =
                            $source->groupStageSetting;

                        $copy
                            ->groupStageSetting()
                            ->create([
                                'group_count_mode' =>
                                $sourceSettings->group_count_mode,

                                'group_count' =>
                                $sourceSettings->group_count,

                                'target_group_size' =>
                                $sourceSettings->target_group_size,

                                'min_group_size' =>
                                $sourceSettings->min_group_size,

                                'max_group_size' =>
                                $sourceSettings->max_group_size,

                                'remainder_policy' =>
                                $sourceSettings->remainder_policy,

                                'distribution_mode' =>
                                $sourceSettings->distribution_mode,

                                'pot_count' =>
                                $sourceSettings->pot_count,

                                'internal_engine_type' =>
                                $sourceSettings->internal_engine_type,

                                'internal_cycles' =>
                                $sourceSettings->internal_cycles,

                                'internal_schedule_mode' =>
                                $sourceSettings->internal_schedule_mode,

                                'internal_allow_draws' =>
                                $sourceSettings->internal_allow_draws,

                                'internal_win_points' =>
                                $sourceSettings->internal_win_points,

                                'internal_draw_points' =>
                                $sourceSettings->internal_draw_points,

                                'internal_loss_points' =>
                                $sourceSettings->internal_loss_points,

                                'internal_best_of' =>
                                $sourceSettings->internal_best_of,

                                'cross_group_normalization' =>
                                $sourceSettings->cross_group_normalization,

                                'cutoff_tie_policy' =>
                                $sourceSettings->cutoff_tie_policy,

                                'completion_mode' =>
                                $sourceSettings->completion_mode,

                                'settings' =>
                                $sourceSettings->settings,
                            ]);

                        /*
    |--------------------------------------------------------------------------
    | Group Definitions
    |--------------------------------------------------------------------------
    */

                        $groupIdMap = [];

                        foreach (
                            $source->groupStageGroups
                            as
                            $group
                        ) {
                            $newGroup =
                                $copy
                                ->groupStageGroups()
                                ->create([
                                    'sequence_number' =>
                                    $group->sequence_number,

                                    'code' =>
                                    $group->code,

                                    'name' =>
                                    $group->name,

                                    'capacity' =>
                                    $group->capacity,

                                    'is_active' =>
                                    $group->is_active,

                                    'sort_order' =>
                                    $group->sort_order,

                                    'settings' =>
                                    $group->settings,
                                ]);

                            $groupIdMap[$group->id] =
                                $newGroup->id;
                        }

                        /*
    |--------------------------------------------------------------------------
    | Cross Group Tiebreakers
    |--------------------------------------------------------------------------
    */

                        foreach (
                            $source->groupStageTiebreakers
                            as
                            $tiebreaker
                        ) {
                            $copy
                                ->groupStageTiebreakers()
                                ->create([
                                    'criterion' =>
                                    $tiebreaker->criterion,

                                    'normalization' =>
                                    $tiebreaker->normalization,

                                    'direction' =>
                                    $tiebreaker->direction,

                                    'sort_order' =>
                                    $tiebreaker->sort_order,

                                    'settings' =>
                                    $tiebreaker->settings,
                                ]);
                        }

                        /*
    |--------------------------------------------------------------------------
    | Advancement Rules
    |--------------------------------------------------------------------------
    */

                        foreach (
                            $source->groupStageAdvancementRules
                            as
                            $rule
                        ) {
                            $copy
                                ->groupStageAdvancementRules()
                                ->create([
                                    'phase_exit_id' =>
                                    $rule->phase_exit_id
                                        ? (
                                            $exitIdMap[$rule->phase_exit_id]
                                            ??
                                            null
                                        )
                                        : null,

                                    'phase_group_stage_group_id' =>
                                    $rule->phase_group_stage_group_id
                                        ? (
                                            $groupIdMap[$rule->phase_group_stage_group_id]
                                            ??
                                            null
                                        )
                                        : null,

                                    'rule_type' =>
                                    $rule->rule_type,

                                    'position_from' =>
                                    $rule->position_from,

                                    'position_to' =>
                                    $rule->position_to,

                                    'take' =>
                                    $rule->take,

                                    'sort_order' =>
                                    $rule->sort_order,

                                    'status' =>
                                    $rule->status,

                                    'settings' =>
                                    $rule->settings,
                                ]);
                        }
                    }

                    /*
|--------------------------------------------------------------------------
| Configuración SWISS
|--------------------------------------------------------------------------
*/

                    if (
                        $source->phase_type
                        ===
                        'SWISS'
                        &&
                        $source->swissSetting
                    ) {
                        $sourceSettings =
                            $source->swissSetting;

                        $copy
                            ->swissSetting()
                            ->create([
                                'completion_mode' =>
                                $sourceSettings->completion_mode,

                                'fixed_rounds' =>
                                $sourceSettings->fixed_rounds,

                                'qualification_wins' =>
                                $sourceSettings->qualification_wins,

                                'elimination_losses' =>
                                $sourceSettings->elimination_losses,

                                'max_rounds' =>
                                $sourceSettings->max_rounds,

                                'pairing_algorithm' =>
                                $sourceSettings->pairing_algorithm,

                                'pairing_basis' =>
                                $sourceSettings->pairing_basis,

                                'first_round_mode' =>
                                $sourceSettings->first_round_mode,

                                'rematch_policy' =>
                                $sourceSettings->rematch_policy,

                                'floater_policy' =>
                                $sourceSettings->floater_policy,

                                'side_balance_policy' =>
                                $sourceSettings->side_balance_policy,

                                'allow_draws' =>
                                $sourceSettings->allow_draws,

                                'win_points' =>
                                $sourceSettings->win_points,

                                'draw_points' =>
                                $sourceSettings->draw_points,

                                'loss_points' =>
                                $sourceSettings->loss_points,

                                'default_best_of' =>
                                $sourceSettings->default_best_of,

                                'bye_policy' =>
                                $sourceSettings->bye_policy,

                                'bye_points' =>
                                $sourceSettings->bye_points,

                                'max_byes_per_participant' =>
                                $sourceSettings->max_byes_per_participant,

                                'initial_pairing_score_mode' =>
                                $sourceSettings->initial_pairing_score_mode,

                                'acceleration_mode' =>
                                $sourceSettings->acceleration_mode,

                                'acceleration_rounds' =>
                                $sourceSettings->acceleration_rounds,

                                'acceleration_seed_count' =>
                                $sourceSettings->acceleration_seed_count,

                                'acceleration_virtual_points' =>
                                $sourceSettings->acceleration_virtual_points,

                                'cutoff_tie_policy' =>
                                $sourceSettings->cutoff_tie_policy,

                                'fallback_policy' =>
                                $sourceSettings->fallback_policy,

                                'settings' =>
                                $sourceSettings->settings,
                            ]);

                        /*
    |--------------------------------------------------------------------------
    | Swiss Tiebreakers
    |--------------------------------------------------------------------------
    */

                        foreach (
                            $source->swissTiebreakers
                            as
                            $tiebreaker
                        ) {
                            $copy
                                ->swissTiebreakers()
                                ->create([
                                    'criterion' =>
                                    $tiebreaker->criterion,

                                    'parameter_int' =>
                                    $tiebreaker->parameter_int,

                                    'direction' =>
                                    $tiebreaker->direction,

                                    'sort_order' =>
                                    $tiebreaker->sort_order,

                                    'settings' =>
                                    $tiebreaker->settings,
                                ]);
                        }

                        /*
    |--------------------------------------------------------------------------
    | Swiss Round Rules
    |--------------------------------------------------------------------------
    */

                        foreach (
                            $source->swissRoundRules
                            as
                            $roundRule
                        ) {
                            $copy
                                ->swissRoundRules()
                                ->create([
                                    'trigger_type' =>
                                    $roundRule->trigger_type,

                                    'round_number' =>
                                    $roundRule->round_number,

                                    'record_wins' =>
                                    $roundRule->record_wins,

                                    'record_draws' =>
                                    $roundRule->record_draws,

                                    'record_losses' =>
                                    $roundRule->record_losses,

                                    'best_of' =>
                                    $roundRule->best_of,

                                    'allow_draws_override' =>
                                    $roundRule->allow_draws_override,

                                    'sort_order' =>
                                    $roundRule->sort_order,

                                    'status' =>
                                    $roundRule->status,

                                    'settings' =>
                                    $roundRule->settings,
                                ]);
                        }

                        /*
    |--------------------------------------------------------------------------
    | Swiss Advancement Rules
    |--------------------------------------------------------------------------
    */

                        foreach (
                            $source->swissAdvancementRules
                            as
                            $rule
                        ) {
                            $copy
                                ->swissAdvancementRules()
                                ->create([
                                    'phase_exit_id' =>
                                    $rule->phase_exit_id
                                        ? (
                                            $exitIdMap[$rule->phase_exit_id]
                                            ??
                                            null
                                        )
                                        : null,

                                    'rule_type' =>
                                    $rule->rule_type,

                                    'threshold_wins' =>
                                    $rule->threshold_wins,

                                    'threshold_losses' =>
                                    $rule->threshold_losses,

                                    'record_wins' =>
                                    $rule->record_wins,

                                    'record_draws' =>
                                    $rule->record_draws,

                                    'record_losses' =>
                                    $rule->record_losses,

                                    'rank_from' =>
                                    $rule->rank_from,

                                    'rank_to' =>
                                    $rule->rank_to,

                                    'take' =>
                                    $rule->take,

                                    'sort_order' =>
                                    $rule->sort_order,

                                    'status' =>
                                    $rule->status,

                                    'settings' =>
                                    $rule->settings,
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
