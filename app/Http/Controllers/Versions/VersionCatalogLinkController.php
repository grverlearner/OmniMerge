<?php

namespace App\Http\Controllers\Versions;

use App\Http\Controllers\Controller;

use App\Models\Attribute;
use App\Models\AttributeOption;
use App\Models\Version;
use App\Models\VersionCatalogLink;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/*
|--------------------------------------------------------------------------
| Las reglas de catalogo de una definicion, una a una
|--------------------------------------------------------------------------
|
| Hasta ahora estas reglas solo se podian tocar desde el formulario completo
| de la definicion, que las borra todas y las vuelve a crear. Eso obliga a
| salir de la ficha, reconstruir la lista entera y volver, para anadir una
| sola regla.
|
| Aqui cada regla se anade, se corrige y se quita por su cuenta, sin tocar las
| demas y sin salir de la ficha.
|
*/

class VersionCatalogLinkController extends Controller
{
    public function store(
        Request $request,
        Version $version
    ): RedirectResponse {

        $this->authorize(
            'update',
            $version
        );


        $user =
            $request->user();


        $data =
            $request->validate(
                [
                    'attribute_id' => [
                        'required',
                        'integer',
                        Rule::exists('attributes', 'id')
                            ->where(
                                fn($q) =>
                                $q
                                    ->where('user_id', $user->id)
                                    ->whereNull('deleted_at')
                            ),
                    ],

                    'attribute_option_id' => [
                        'required',
                        'integer',
                        Rule::exists('attribute_options', 'id')
                            ->where(
                                fn($q) =>
                                $q
                                    ->where('user_id', $user->id)
                                    ->whereNull('deleted_at')
                            ),
                    ],

                    'relation_type' => [
                        'required',
                        Rule::in(['ACTIVATES', 'CONTEXT', 'RELATED']),
                    ],

                    'condition_group' => [
                        'nullable',
                        'integer',
                        'min:1',
                        'max:100',
                    ],

                    'logical_operator' => [
                        'nullable',
                        Rule::in(['AND', 'OR']),
                    ],
                ],
                [],
                [
                    'attribute_id' => 'catálogo',
                    'attribute_option_id' => 'valor',
                    'relation_type' => 'tipo de relación',
                ]
            );


        $attribute =
            Attribute::query()
            ->ownedBy($user)
            ->findOrFail(
                (int) $data['attribute_id']
            );


        $option =
            AttributeOption::query()
            ->ownedBy($user)
            ->where('attribute_id', $attribute->id)
            ->find(
                (int) $data['attribute_option_id']
            );


        if (! $option) {

            throw ValidationException::withMessages([
                'attribute_option_id' =>
                'Ese valor no pertenece al catálogo elegido.',
            ]);
        }


        /*
         * La tabla tiene un UNIQUE por (version, opcion, tipo). Decirlo en
         * castellano es mas util que dejar que reviente la base de datos.
         */

        $repetida =
            $version
            ->catalogLinks()
            ->where('attribute_option_id', $option->id)
            ->where('relation_type', $data['relation_type'])
            ->exists();


        if ($repetida) {

            throw ValidationException::withMessages([
                'attribute_option_id' =>
                'Esa regla ya existe: «' . $option->name . '» ya está enlazada con ese mismo tipo.',
            ]);
        }


        $version
            ->catalogLinks()
            ->create([
                'user_id' => $user->id,

                'attribute_id' => $attribute->id,
                'attribute_option_id' => $option->id,

                'relation_type' => $data['relation_type'],

                'condition_group' => max(
                    1,
                    (int) ($data['condition_group'] ?? 1)
                ),

                'logical_operator' => $data['logical_operator'] ?? 'AND',

                'is_required' => false,
                'priority' => 0,
            ]);


        return back()
            ->with(
                'success',
                'Regla añadida: ' . $attribute->name . ' → ' . $option->name . '.'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Corregir una regla ya puesta
    |--------------------------------------------------------------------------
    |
    | Equivocarse de tipo o de grupo es lo mas facil del mundo aqui, y sin esto
    | la unica salida seria borrar y volver a crear.
    |
    */

    public function update(
        Request $request,
        Version $version,
        VersionCatalogLink $link
    ): RedirectResponse {

        $this->authorize(
            'update',
            $version
        );


        $this->ensureLink(
            $version,
            $link
        );


        $data =
            $request->validate([
                'relation_type' => [
                    'required',
                    Rule::in(['ACTIVATES', 'CONTEXT', 'RELATED']),
                ],

                'condition_group' => [
                    'nullable',
                    'integer',
                    'min:1',
                    'max:100',
                ],

                'logical_operator' => [
                    'nullable',
                    Rule::in(['AND', 'OR']),
                ],
            ]);


        $repetida =
            $version
            ->catalogLinks()
            ->whereKeyNot($link->id)
            ->where('attribute_option_id', $link->attribute_option_id)
            ->where('relation_type', $data['relation_type'])
            ->exists();


        if ($repetida) {

            throw ValidationException::withMessages([
                'relation_type' =>
                'Ya hay otra regla con ese mismo valor y ese mismo tipo.',
            ]);
        }


        $link->update([
            'relation_type' => $data['relation_type'],

            'condition_group' => max(
                1,
                (int) ($data['condition_group'] ?? $link->condition_group)
            ),

            'logical_operator' => $data['logical_operator'] ?? $link->logical_operator,
        ]);


        return back()
            ->with(
                'success',
                'Regla actualizada.'
            );
    }


    public function destroy(
        Version $version,
        VersionCatalogLink $link
    ): RedirectResponse {

        $this->authorize(
            'update',
            $version
        );


        $this->ensureLink(
            $version,
            $link
        );


        $nombre =
            $link->option?->name
            ?? 'la regla';


        $link->delete();


        return back()
            ->with(
                'success',
                'Regla quitada: ' . $nombre . '.'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Que el enlace sea de esta definicion
    |--------------------------------------------------------------------------
    */

    private function ensureLink(
        Version $version,
        VersionCatalogLink $link
    ): void {

        abort_unless(
            $link->version_id === $version->id,
            404
        );
    }
}
