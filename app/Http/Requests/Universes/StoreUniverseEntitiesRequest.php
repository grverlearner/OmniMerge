<?php

namespace App\Http\Requests\Universes;

use App\Models\Universe;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUniverseEntitiesRequest extends FormRequest
{
    public function authorize(): bool
    {
        $universe =
            $this->route('universe');

        return
            $universe
            instanceof
            Universe

            &&
            (
                $this->user()
                ?->can(
                    'update',
                    $universe
                )
                ?? false
            );
    }

    public function rules(): array
    {
        return [

            'entity_ids' => [
                'required',
                'array',
                'min:1',
            ],

            /*
             * Solo Entidades del propietario del Universo.
             */
            'entity_ids.*' => [
                'integer',

                Rule::exists('entities', 'id')
                    ->where(
                        'user_id',
                        $this->user()?->id
                    )
                    ->whereNull('deleted_at'),
            ],
        ];
    }

    public function messages(): array
    {
        return [

            'entity_ids.required' =>
            'Selecciona al menos una entidad para incorporarla al Universo.',

            'entity_ids.*.exists' =>
            'Alguna de las entidades seleccionadas no es válida.',
        ];
    }
}
