<?php

namespace App\Http\Requests\Tournaments;

/*
|--------------------------------------------------------------------------
| UpdatePhaseEntryPortRequest
|--------------------------------------------------------------------------
|
| Editar una puerta de entrada de una fase valida exactamente lo mismo que crearla, asi que hereda de
| StorePhaseEntryPortRequest en vez de repetir sus reglas.
|
| Estaba sin implementar: `authorize()` devolvia false y `rules()` estaba
| vacio. Eso no daba un formulario a medias, daba un 403 -"This action is
| unauthorized"- al pulsar guardar, sin ninguna pista de que el problema no
| era de permisos sino de una clase que nadie habia escrito. Crear
| funcionaba y editar no, que es justo el sintoma mas desconcertante.
|
| Heredar y no copiar es deliberado: dos listas de reglas para la misma
| entidad divergen, y la que se quede corta lo hara en silencio.
|
*/
class UpdatePhaseEntryPortRequest extends StorePhaseEntryPortRequest
{
}
