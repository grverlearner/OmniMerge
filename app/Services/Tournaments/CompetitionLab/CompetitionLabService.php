<?php

namespace App\Services\Tournaments\CompetitionLab;

use App\Models\TournamentTemplate;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class CompetitionLabService
{
    public function __construct(
        private readonly
        LabStateFactory $stateFactory,

        private readonly
        LabStateTokenService $tokenService
    ) {}

    public function initialize(
        TournamentTemplate $template,
        User $user,
        array $configuration
    ): array {
        $state =
            $this->stateFactory
            ->create(
                $template,
                $user,
                $configuration
            );

        return $this->response(
            $state
        );
    }

    public function execute(
        TournamentTemplate $template,
        User $user,
        string $token,
        string $action
    ): array {
        $state =
            $this->tokenService
            ->decode(
                $token
            );

        $this->validateOwnership(
            $state,
            $template,
            $user
        );

        $state =
            match ($action) {
                'START' =>
                $this->start(
                    $state
                ),

                'PAUSE' =>
                $this->pause(
                    $state
                ),

                'RESUME' =>
                $this->resume(
                    $state
                ),

                'RESET' =>
                $this->reset(
                    $state
                ),

                default =>
                $this->fail(
                    'La acción solicitada no está disponible.'
                ),
            };

        return $this->response(
            $state
        );
    }

    private function start(
        array $state
    ): array {
        if (
            $state['status']
            !==
            'READY'
        ) {
            $this->fail(
                'El Lab solo puede iniciarse desde el estado READY.'
            );
        }

        $state['status'] =
            'RUNNING';

        foreach (
            $state['participants']
            as
            &$participant
        ) {
            $participant['status'] =
                'ACTIVE';
        }

        unset($participant);

        $this->addEvent(
            $state,
            'LAB_STARTED',
            'SUCCESS',
            'La prueba temporal comenzó. Los participantes están activos en sus Starts.'
        );

        return $state;
    }

    private function pause(
        array $state
    ): array {
        if (
            $state['status']
            !==
            'RUNNING'
        ) {
            $this->fail(
                'Solo puede pausarse un Lab en ejecución.'
            );
        }

        $state['status'] =
            'PAUSED';

        $this->addEvent(
            $state,
            'LAB_PAUSED',
            'WARNING',
            'La prueba temporal fue pausada.'
        );

        return $state;
    }

    private function resume(
        array $state
    ): array {
        if (
            $state['status']
            !==
            'PAUSED'
        ) {
            $this->fail(
                'Solo puede reanudarse un Lab pausado.'
            );
        }

        $state['status'] =
            'RUNNING';

        $this->addEvent(
            $state,
            'LAB_RESUMED',
            'SUCCESS',
            'La prueba temporal fue reanudada.'
        );

        return $state;
    }

    private function reset(
        array $state
    ): array {
        $state['status'] =
            'READY';

        foreach (
            $state['participants']
            as
            &$participant
        ) {
            $participant['status'] =
                'WAITING';

            $participant['statistics'] = [
                'matches' => 0,
                'wins' => 0,
                'draws' => 0,
                'losses' => 0,
                'points' => 0,
            ];

            $participant['journey'] =
                array_slice(
                    $participant['journey'],
                    0,
                    1
                );

            $participant['current_location'] =
                $participant['journey'][0];
        }

        unset($participant);

        foreach (
            $state['nodes']
            as
            &$node
        ) {
            $node['status'] =
                'LOCKED';

            $node['participant_ids'] =
                [];

            foreach (
                $node['entry_ports']
                as
                &$port
            ) {
                $port['status'] =
                    'EMPTY';

                $port['participant_ids'] =
                    [];
            }

            unset($port);
        }

        unset($node);

        foreach (
            $state['terminals']
            as
            &$terminal
        ) {
            $terminal['status'] =
                'EMPTY';

            $terminal['participant_ids'] =
                [];
        }

        unset($terminal);

        $state['timeline'] = [];

        $this->addEvent(
            $state,
            'LAB_RESET',
            'INFO',
            'El Competition Lab volvió a su estado inicial.'
        );

        return $state;
    }

    private function validateOwnership(
        array $state,
        TournamentTemplate $template,
        User $user
    ): void {
        if (
            ($state['schema_version'] ?? null)
            !==
            1
        ) {
            $this->fail(
                'La versión del estado temporal no es compatible.'
            );
        }

        if (
            (int) (
                $state['user_id']
                ??
                0
            )
            !==
            (int) $user->id
        ) {
            $this->fail(
                'El estado temporal pertenece a otro usuario.'
            );
        }

        if (
            (int) (
                $state['tournament_template_id']
                ??
                0
            )
            !==
            (int) $template->id
        ) {
            $this->fail(
                'El estado temporal pertenece a otra plantilla.'
            );
        }
    }

    private function addEvent(
        array &$state,
        string $type,
        string $level,
        string $message
    ): void {
        $state['timeline'][] = [
            'step' =>
            count(
                $state['timeline']
            )
                +
                1,

            'type' =>
            $type,

            'level' =>
            $level,

            'message' =>
            $message,

            'created_at' =>
            now()->toIso8601String(),
        ];

        $state['updated_at'] =
            now()->toIso8601String();
    }

    private function response(
        array $state
    ): array {
        return [
            'state' =>
            $state,

            'state_token' =>
            $this->tokenService
                ->encode(
                    $state
                ),
        ];
    }

    private function fail(
        string $message
    ): never {
        throw ValidationException::withMessages([
            'lab' => [
                $message,
            ],
        ]);
    }
}
