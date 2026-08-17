<?php

namespace App\Http\Requests\Tournaments;

/**
 * La edición comparte exactamente el mismo contrato de autorización y
 * validación que la creación. Los Store* ya ignoran el registro actual
 * cuando corresponde, por lo que mantener dos copias de reglas solo
 * volvería a introducir divergencias.
 */
class UpdateSwissTiebreakerRequest extends StoreSwissTiebreakerRequest
{
}
