<?php

namespace App\Http\Requests\Tournaments;

class UpdateSingleEliminationGraphEncounterRequest extends StoreSingleEliminationGraphEncounterRequest
{
    public function rules(): array
    {
        $rules = parent::rules();
        unset($rules['round_id']);

        return $rules;
    }
}
