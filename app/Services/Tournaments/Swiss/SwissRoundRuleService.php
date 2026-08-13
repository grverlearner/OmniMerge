<?php

namespace App\Services\Tournaments\Swiss;

use App\Models\PhaseSwissRoundRule;
use App\Models\PhaseTemplate;
use Illuminate\Support\Facades\DB;

class SwissRoundRuleService
{
    public function create(
        PhaseTemplate $phaseTemplate,
        array $data
    ): PhaseSwissRoundRule {
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
                            ->swissRoundRules()
                            ->max(
                                'sort_order'
                            )
                    )
                    +
                    10;

                return $phaseTemplate
                    ->swissRoundRules()
                    ->create(
                        $data
                    );
            }
        );
    }

    public function update(
        PhaseSwissRoundRule $rule,
        array $data
    ): PhaseSwissRoundRule {
        $rule->update(
            $this->normalize(
                $data
            )
        );

        return $rule->fresh();
    }

    public function delete(
        PhaseSwissRoundRule $rule
    ): void {
        $rule->delete();
    }

    private function normalize(
        array $data
    ): array {
        $type =
            $data['trigger_type'];

        if (
            $type
            !==
            'ROUND_NUMBER'
        ) {
            $data['round_number'] =
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

        return $data;
    }
}
