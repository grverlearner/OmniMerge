<?php

namespace App\Http\Requests\Tournaments;

class UpdateGroupStageTiebreakerRequest extends StoreGroupStageTiebreakerRequest {

    public function messages(): array
    {
        return [
            'criterion.unique' =>
            'Ese criterio ya forma parte de la cadena de desempate.',
        ];
    }
}
