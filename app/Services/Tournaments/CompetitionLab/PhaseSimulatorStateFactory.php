<?php

namespace App\Services\Tournaments\CompetitionLab;

use App\Models\PhaseTemplate;
use App\Models\User;
use Illuminate\Support\Str;

class PhaseSimulatorStateFactory
{
    public function __construct(
        private readonly SimulatorParticipantFactory $participantFactory
    ) {}

    public function create(
        PhaseTemplate $phaseTemplate,
        User $user,
        array $participants
    ): array {
        $generated = $this->participantFactory->generate($participants);

        $participants = [];

        foreach ($generated as $participant) {
            $labId = 'SIM-' . $participant['preview_id'];

            $participant['lab_id'] = $labId;
            $participant['status'] = 'WAITING';

            $participant['current_location'] = [
                'type' => 'SIMULATOR',
                'name' => 'Participantes de la simulación',
            ];

            $participants[$labId] = $participant;
        }

        return [
            'schema_version' => 1,

            'simulation_id' => (string) Str::uuid(),

            'user_id' => (int) $user->id,

            'phase_template_id' => (int) $phaseTemplate->id,

            'phase' => [
                'id' => (int) $phaseTemplate->id,
                'code' => $phaseTemplate->code,
                'name' => $phaseTemplate->name,
                'phase_type' => $phaseTemplate->phase_type,
            ],

            'status' => 'READY',

            'participants' => $participants,

            'runtime' => null,

            'exits_summary' => null,

            'timeline' => [
                [
                    'step' => 1,
                    'type' => 'SIMULATION_CREATED',
                    'level' => 'SUCCESS',
                    'message' => 'Simulación creada con ' . count($participants) . ' participantes ficticios.',
                    'created_at' => now()->toIso8601String(),
                ],
            ],

            'created_at' => now()->toIso8601String(),

            'updated_at' => now()->toIso8601String(),
        ];
    }
}
