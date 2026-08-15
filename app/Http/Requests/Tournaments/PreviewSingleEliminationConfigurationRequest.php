<?php

namespace App\Http\Requests\Tournaments;

class PreviewSingleEliminationConfigurationRequest
extends UpdateSingleEliminationSettingsRequest
{
    public function rules(): array
    {
        return [
            ...parent::rules(),

            'participants' => [
                'required',
                'integer',
                'min:2',
                'max:512',
            ],
        ];
    }
}
