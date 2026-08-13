<?php

namespace App\Services\Tournaments\GroupStage;

use App\Models\PhaseGroupStageAdvancementRule;
use App\Models\PhaseTemplate;
use Illuminate\Support\Facades\DB;

class GroupStageAdvancementRuleService
{
    public function create(
        PhaseTemplate $phaseTemplate,
        array $data
    ): PhaseGroupStageAdvancementRule {
        $this->ensureCorrectType(
            $phaseTemplate
        );

        return DB::transaction(
            function () use (
                $phaseTemplate,
                $data
            ) {
                PhaseTemplate::query()
                    ->whereKey(
                        $phaseTemplate->id
                    )
                    ->lockForUpdate()
                    ->firstOrFail();

                $sortOrder =
                    (
                        (int)
                        $phaseTemplate
                            ->groupStageAdvancementRules()
                            ->max(
                                'sort_order'
                            )
                    )
                    +
                    10;

                $data =
                    $this->normalize(
                        $data
                    );

                $data['sort_order'] =
                    $sortOrder;

                return $phaseTemplate
                    ->groupStageAdvancementRules()
                    ->create(
                        $data
                    );
            }
        );
    }

    public function update(
        PhaseGroupStageAdvancementRule $rule,
        array $data
    ): PhaseGroupStageAdvancementRule {
        $rule->update(
            $this->normalize(
                $data
            )
        );

        return $rule->fresh();
    }

    public function delete(
        PhaseGroupStageAdvancementRule $rule
    ): void {
        $rule->delete();
    }

    public function move(
        PhaseTemplate $phaseTemplate,
        PhaseGroupStageAdvancementRule $rule,
        string $direction
    ): void {
        DB::transaction(
            function () use (
                $phaseTemplate,
                $rule,
                $direction
            ) {
                $items =
                    $phaseTemplate
                    ->groupStageAdvancementRules()
                    ->lockForUpdate()
                    ->get();

                $currentIndex =
                    $items->search(
                        fn($item) =>
                        $item->id
                            ===
                            $rule->id
                    );

                if ($currentIndex === false) {
                    return;
                }

                $targetIndex =
                    $direction === 'UP'
                    ? $currentIndex - 1
                    : $currentIndex + 1;

                if (
                    $targetIndex < 0
                    ||
                    $targetIndex
                    >=
                    $items->count()
                ) {
                    return;
                }

                $current =
                    $items[$currentIndex];

                $target =
                    $items[$targetIndex];

                $currentOrder =
                    $current->sort_order;

                $current->update([
                    'sort_order' =>
                    $target->sort_order,
                ]);

                $target->update([
                    'sort_order' =>
                    $currentOrder,
                ]);
            }
        );
    }

    private function normalize(
        array $data
    ): array {
        $type =
            $data['rule_type'];

        if (
            ! in_array(
                $type,
                [
                    'EACH_GROUP_POSITION',
                    'EACH_GROUP_RANGE',

                    'CROSS_GROUP_POSITION_TOP_N',
                    'CROSS_GROUP_POSITION_BOTTOM_N',

                    'SPECIFIC_GROUP_POSITION',
                    'SPECIFIC_GROUP_RANGE',
                ],
                true
            )
        ) {
            $data['position_from'] =
                null;
        }

        if (
            ! in_array(
                $type,
                [
                    'EACH_GROUP_RANGE',
                    'SPECIFIC_GROUP_RANGE',
                ],
                true
            )
        ) {
            $data['position_to'] =
                null;
        }

        if (
            ! in_array(
                $type,
                [
                    'EACH_GROUP_TOP_N',
                    'EACH_GROUP_BOTTOM_N',

                    'CROSS_GROUP_POSITION_TOP_N',
                    'CROSS_GROUP_POSITION_BOTTOM_N',

                    'BEST_REMAINING',
                    'WORST_REMAINING',
                ],
                true
            )
        ) {
            $data['take'] =
                null;
        }

        if (
            ! in_array(
                $type,
                [
                    'SPECIFIC_GROUP_POSITION',
                    'SPECIFIC_GROUP_RANGE',
                ],
                true
            )
        ) {
            $data['phase_group_stage_group_id'] =
                null;
        }

        return $data;
    }

    private function ensureCorrectType(
        PhaseTemplate $phaseTemplate
    ): void {
        abort_unless(
            $phaseTemplate->phase_type
                ===
                'GROUP_STAGE',
            404
        );
    }
}
