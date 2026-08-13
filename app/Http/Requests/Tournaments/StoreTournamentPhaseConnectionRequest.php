<?php

namespace App\Http\Requests\Tournaments;

use App\Models\TournamentTemplate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTournamentPhaseConnectionRequest
extends FormRequest
{
    public function authorize(): bool
    {
        $template =
            $this->route(
                'tournamentTemplate'
            );

        return
            $template
            instanceof
            TournamentTemplate
            &&
            (
                $this->user()
                ?->can(
                    'update',
                    $template
                )
                ??
                false
            );
    }


    protected function prepareForValidation(): void
    {
        $this->merge([
            'source_type' =>
            strtoupper(
                (string)
                $this->input(
                    'source_type'
                )
            ),

            'target_type' =>
            strtoupper(
                (string)
                $this->input(
                    'target_type'
                )
            ),

            'allocation_mode' =>
            strtoupper(
                (string)
                $this->input(
                    'allocation_mode',
                    'ALL'
                )
            ),

            'status' =>
            strtoupper(
                (string)
                $this->input(
                    'status',
                    'ACTIVE'
                )
            ),

            'priority' =>
            $this->integer(
                'priority',
                10
            ),
        ]);
    }


    public function rules(): array
    {
        $sourceStart =
            $this->input(
                'source_type'
            )
            ===
            'START';

        $sourceExit =
            $this->input(
                'source_type'
            )
            ===
            'PHASE_EXIT';

        $targetPort =
            $this->input(
                'target_type'
            )
            ===
            'ENTRY_PORT';

        $targetTerminal =
            $this->input(
                'target_type'
            )
            ===
            'TERMINAL';

        $requiresValue =
            in_array(
                $this->input(
                    'allocation_mode'
                ),
                [
                    'TAKE_N',
                    'PERCENTAGE',
                ],
                true
            );


        return [
            'label' => [
                'nullable',
                'string',
                'max:120',
            ],

            'description' => [
                'nullable',
                'string',
                'max:2000',
            ],


            'source_type' => [
                'required',

                Rule::in([
                    'START',
                    'PHASE_EXIT',
                ]),
            ],


            'source_start_id' => [
                Rule::excludeIf(
                    ! $sourceStart
                ),

                Rule::requiredIf(
                    $sourceStart
                ),

                'nullable',
                'integer',
                'exists:tournament_starts,id',
            ],


            'source_node_id' => [
                Rule::excludeIf(
                    ! $sourceExit
                ),

                Rule::requiredIf(
                    $sourceExit
                ),

                'nullable',
                'integer',
                'exists:tournament_phase_nodes,id',
            ],


            'source_phase_exit_id' => [
                Rule::excludeIf(
                    ! $sourceExit
                ),

                Rule::requiredIf(
                    $sourceExit
                ),

                'nullable',
                'integer',
                'exists:phase_exits,id',
            ],


            'target_type' => [
                'required',

                Rule::in([
                    'ENTRY_PORT',
                    'TERMINAL',
                ]),
            ],


            'target_entry_port_id' => [
                Rule::excludeIf(
                    ! $targetPort
                ),

                Rule::requiredIf(
                    $targetPort
                ),

                'nullable',
                'integer',
                'exists:phase_entry_ports,id',
            ],


            'target_terminal_id' => [
                Rule::excludeIf(
                    ! $targetTerminal
                ),

                Rule::requiredIf(
                    $targetTerminal
                ),

                'nullable',
                'integer',
                'exists:tournament_terminals,id',
            ],


            'allocation_mode' => [
                'required',

                Rule::in([
                    'ALL',
                    'TAKE_N',
                    'PERCENTAGE',
                    'REMAINDER',
                ]),
            ],


            'allocation_value' => [
                Rule::excludeIf(
                    ! $requiresValue
                ),

                Rule::requiredIf(
                    $requiresValue
                ),

                'nullable',
                'numeric',
                'gt:0',
                'max:512',
            ],


            'priority' => [
                'required',
                'integer',
                'min:0',
                'max:10000',
            ],


            'status' => [
                'required',

                Rule::in([
                    'ACTIVE',
                    'INACTIVE',
                ]),
            ],
        ];
    }
}
