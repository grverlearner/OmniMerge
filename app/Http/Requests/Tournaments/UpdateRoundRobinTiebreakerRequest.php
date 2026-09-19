<?php

namespace App\Http\Requests\Tournaments;

class UpdateRoundRobinTiebreakerRequest extends StoreRoundRobinTiebreakerRequest {

    public function messages(): array
    {
        return [
            'criterion.unique' =>
            'Ese criterio ya forma parte de la cadena de desempate.',
        ];
    }
}
