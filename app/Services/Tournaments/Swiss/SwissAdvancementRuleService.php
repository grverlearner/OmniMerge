<?php

namespace App\Services\Tournaments\Swiss;

use App\Models\PhaseSwissAdvancementRule;
use App\Models\PhaseTemplate;
use Illuminate\Support\Facades\DB;

class SwissAdvancementRuleService
{
    public function create(
        PhaseTemplate $phaseTemplate,
        array $data
    ): PhaseSwissAdvancementRule {
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

                $data =
                    $this->normalize(
                        $data
                    );

                $data['sort_order'] =
                    (
                        (int)
                        $phaseTemplate
                            ->swissAdvancementRules()
                            ->max(
                                'sort_order'
                            )
                    )
                    +
                    10;

                return $phaseTemplate
                    ->swissAdvancementRules()
                    ->create(
                        $data
                    );
            }
        );
    }

    public function update(
        PhaseSwissAdvancementRule $rule,
        array $data
    ): PhaseSwissAdvancementRule {
        $rule->update(
            $this->normalize(
                $data
            )
        );

        return $rule->fresh();
    }

    public function delete(
        PhaseSwissAdvancementRule $rule
    ): void {
        $rule->delete();
    }

    public function move(
        PhaseTemplate $phaseTemplate,
        PhaseSwissAdvancementRule $rule,
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
                    ->swissAdvancementRules()
                    ->lockForUpdate()
                    ->get();

                $index =
                    $items->search(
                        fn($item) =>
                        $item->id
                            ===
                            $rule->id
                    );

                if ($index === false) {
                    return;
                }

                $targetIndex =
                    $direction
                    ===
                    'UP'
                    ? $index - 1
                    : $index + 1;

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
                    $items[$index];

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
            $type
            !==
            'WIN_THRESHOLD'
        ) {
            $data['threshold_wins'] =
                null;
        }

        if (
            $type
            !==
            'LOSS_THRESHOLD'
        ) {
            $data['threshold_losses'] =
                null;
        }

        if (
            $type
            !==
            'EXACT_RECORD'
        ) {
            $data['record_wins'] =
                null;

            $data['record_draws'] =
                null;

            $data['record_losses'] =
                null;
        }

        if (
            ! in_array(
                $type,
                [
                    'FINAL_RANK_POSITION',
                    'FINAL_RANK_RANGE',
                ],
                true
            )
        ) {
            $data['rank_from'] =
                null;
        }

        if (
            $type
            !==
            'FINAL_RANK_RANGE'
        ) {
            $data['rank_to'] =
                null;
        }

        if (
            ! in_array(
                $type,
                [
                    'FINAL_TOP_N',
                    'FINAL_BOTTOM_N',
                ],
                true
            )
        ) {
            $data['take'] =
                null;
        }

        return $data;
    }
}
