<?php

namespace App\Http\Requests\Tournaments;

use App\Models\TournamentTemplate;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTournamentGraphPositionRequest
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


    public function rules(): array
    {
        return [
            'x_position' => [
                'required',
                'integer',
                'min:0',
                'max:10000',
            ],

            'y_position' => [
                'required',
                'integer',
                'min:0',
                'max:10000',
            ],
        ];
    }
}
