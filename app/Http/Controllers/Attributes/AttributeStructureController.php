<?php

namespace App\Http\Controllers\Attributes;

use App\Http\Controllers\Controller;

use App\Models\Attribute;
use App\Models\AttributeContextRule;
use App\Models\AttributeOptionRelationship;
use App\Models\AttributeRelationship;
use App\Models\EntityAttribute;

use App\Services\Attributes\AttributeContextService;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AttributeStructureController extends Controller
{
    public function index(
        Request $request
    ): View {

        $user =
            $request->user();


        $attributes =
            Attribute::query()
            ->ownedBy(
                $user
            )
            ->active()
            ->with([
                'options' =>
                fn($query) =>
                $query
                    ->where(
                        'status',
                        'ACTIVE'
                    )
                    ->orderBy(
                        'sort_order'
                    )
                    ->orderBy(
                        'name'
                    ),
            ])
            ->orderBy(
                'hierarchy_level'
            )
            ->orderBy(
                'sort_order'
            )
            ->orderBy(
                'name'
            )
            ->get();


        $relationships =
            AttributeRelationship::query()
            ->ownedBy(
                $user
            )
            ->active()
            ->with([
                'sourceAttribute',
                'targetAttribute',
            ])
            ->orderBy(
                'sort_order'
            )
            ->get();


        $rules =
            AttributeContextRule::query()
            ->ownedBy(
                $user
            )
            ->with([
                'targetAttribute',

                'conditions.sourceAttribute',
                'conditions.sourceOption',
            ])
            ->orderByDesc(
                'priority'
            )
            ->latest()
            ->get();


        $optionRelationships =
            AttributeOptionRelationship::query()
            ->ownedBy(
                $user
            )
            ->with([
                'sourceOption.attribute',
                'targetOption.attribute',
            ])
            ->latest()
            ->get();


        /*
        |--------------------------------------------------------------------------
        | Cuantas entidades usan cada atributo
        |--------------------------------------------------------------------------
        |
        | Hace falta para avisar de algo que hoy no se ve: una regla sobre un
        | atributo que no tiene ninguna entidad es correcta y no hace nada. Se
        | cuenta de una vez para todos, no uno por uno.
        |
        */

        $usoPorAtributo =
            EntityAttribute::query()
            ->whereIn(
                'attribute_id',
                $attributes->pluck('id')
            )
            ->selectRaw(
                'attribute_id, COUNT(DISTINCT entity_id) as total'
            )
            ->groupBy(
                'attribute_id'
            )
            ->pluck(
                'total',
                'attribute_id'
            );


        /*
        |--------------------------------------------------------------------------
        | El material que consume el constructor
        |--------------------------------------------------------------------------
        |
        | Con imagen y color: los selectores de la pantalla se eligen por la cara
        | del atributo y la del valor, no por un desplegable de texto.
        |
        */

        $attributePayload =
            $attributes
            ->map(
                fn($attribute) => [
                    'id' =>
                    (string) $attribute->id,

                    'name' =>
                    $attribute->name,

                    'code' =>
                    $attribute->code,

                    'data_type' =>
                    $attribute->data_type,

                    'type_label' =>
                    $attribute->data_type_label,

                    'level' =>
                    $attribute
                        ->hierarchy_level,

                    'image' =>
                    $attribute->image_url,

                    'icon' =>
                    $attribute->icon
                        ?: $attribute->data_type_icon,

                    'color' =>
                    $attribute->color
                        ?: '#6366f1',

                    'uses' =>
                    (int) (
                        $usoPorAtributo[$attribute->id]
                        ?? 0
                    ),

                    'options' =>
                    $attribute
                        ->options
                        ->map(
                            fn($option) => [
                                'id' =>
                                (string) $option->id,

                                'name' =>
                                $option->name,

                                'code' =>
                                $option->code,

                                'image' =>
                                $option->image_url,

                                'icon' =>
                                $option->icon
                                    ?: '',

                                'color' =>
                                $option->color
                                    ?: (
                                        $attribute->color
                                        ?: '#6366f1'
                                    ),
                            ]
                        )
                        ->values()
                        ->all(),
                ]
            )
            ->values()
            ->all();


        /*
        |--------------------------------------------------------------------------
        | El mapa por niveles
        |--------------------------------------------------------------------------
        |
        | El nivel no se elige: sale de las reglas. Agrupados asi, la pantalla
        | puede dibujar el recorrido —lo que no depende de nada, lo que depende
        | de eso, y asi— en vez de una lista plana.
        |
        */

        $niveles =
            $attributes
            ->groupBy(
                fn($attribute) =>
                (int) $attribute->hierarchy_level
            )
            ->sortKeys();


        /*
        |--------------------------------------------------------------------------
        | Quien esta metido en alguna regla
        |--------------------------------------------------------------------------
        */

        $comoObjetivo =
            $rules
            ->groupBy(
                'target_attribute_id'
            );


        $comoCondicion =
            $rules
            ->flatMap(
                fn($rule) =>
                $rule
                    ->conditions
                    ->pluck(
                        'source_attribute_id'
                    )
            )
            ->countBy();


        /*
         * Los que no aparecen en ninguna regla. No es un error —la mayoria de
         * atributos son asi— pero saber cuales son responde de un vistazo a
         * «¿por que este siempre se ve?».
         */
        $sueltos =
            $attributes
            ->filter(
                fn($attribute) =>
                ! $comoObjetivo->has($attribute->id)
                && ! $comoCondicion->has($attribute->id)
            )
            ->values();


        /*
        |--------------------------------------------------------------------------
        | Conflictos
        |--------------------------------------------------------------------------
        |
        | Dos cosas que hoy se pueden guardar y que nadie avisa:
        |
        |   · dos reglas que sobre el mismo atributo dicen «mostrar» y «ocultar»
        |   · el mismo par de valores marcado a la vez como permitido y bloqueado
        |
        | Ninguna de las dos rompe nada, pero el resultado depende de la
        | prioridad y deja de ser evidente. Merece un aviso.
        |
        */

        $conflictosDeReglas =
            $comoObjetivo
            ->filter(
                fn($grupo) =>
                $grupo
                    ->pluck('action')
                    ->unique()
                    ->intersect(['SHOW', 'HIDE'])
                    ->count() === 2
            );


        $conflictosDeCatalogo =
            $optionRelationships
            ->groupBy(
                fn($relacion) =>
                $relacion->source_option_id
                . '-'
                . $relacion->target_option_id
            )
            ->filter(
                fn($grupo) =>
                $grupo
                    ->pluck('relationship_type')
                    ->unique()
                    ->count() > 1
            );


        /*
         * Reglas que no pueden hacer nada porque el atributo al que apuntan no
         * lo tiene ninguna entidad.
         */
        $reglasInertes =
            $rules
            ->filter(
                fn($rule) =>
                (int) (
                    $usoPorAtributo[$rule->target_attribute_id]
                    ?? 0
                ) === 0
            );


        $stats = [
            'attributes' =>
            $attributes->count(),

            'relationships' =>
            $relationships->count(),

            'rules' =>
            $rules->count(),

            'option_relationships' =>
            $optionRelationships->count(),

            'sueltos' =>
            $sueltos->count(),

            'conflictos' =>
            $conflictosDeReglas->count()
            + $conflictosDeCatalogo->count(),

            'inertes' =>
            $reglasInertes->count(),

            'niveles' =>
            $niveles->count(),
        ];


        return view(
            'attributes.structure',
            compact(
                'attributes',
                'relationships',
                'rules',
                'optionRelationships',
                'attributePayload',
                'stats',
                'niveles',
                'sueltos',
                'comoObjetivo',
                'comoCondicion',
                'conflictosDeReglas',
                'conflictosDeCatalogo',
                'reglasInertes',
                'usoPorAtributo'
            )
        );
    }


    public function storeRule(
        Request $request,
        AttributeContextService $service
    ): RedirectResponse {

        $data =
            $request->validate([
                'name' => [
                    'nullable',
                    'string',
                    'max:150',
                ],

                'target_attribute_id' => [
                    'required',
                    'integer',
                ],

                'action' => [
                    'required',

                    Rule::in([
                        'SHOW',
                        'HIDE',
                        'REQUIRE',
                    ]),
                ],

                'match_mode' => [
                    'required',

                    Rule::in([
                        'ALL',
                        'ANY',
                    ]),
                ],

                'priority' => [
                    'nullable',
                    'integer',
                ],

                'conditions' => [
                    'required',
                    'array',
                    'min:1',
                    'max:20',
                ],

                'conditions.*.source_attribute_id' => [
                    'required',
                    'integer',
                ],

                'conditions.*.operator' => [
                    'required',

                    Rule::in([
                        'EQUALS',
                        'NOT_EQUALS',
                        'EXISTS',
                        'NOT_EXISTS',
                    ]),
                ],

                'conditions.*.source_option_id' => [
                    'nullable',
                    'integer',
                ],
            ]);


        $service->createRule(
            $request->user(),
            $data
        );


        return back()
            ->with(
                'success',
                'Regla contextual creada correctamente.'
            );
    }


    public function destroyRule(
        Request $request,
        AttributeContextRule $rule,
        AttributeContextService $service
    ): RedirectResponse {

        $service->deleteRule(
            $request->user(),
            $rule
        );


        return back()
            ->with(
                'success',
                'Regla eliminada.'
            );
    }


    public function storeOptionRelationship(
        Request $request,
        AttributeContextService $service
    ): RedirectResponse {

        $data =
            $request->validate([
                'source_option_id' => [
                    'required',
                    'integer',
                ],

                'target_option_id' => [
                    'required',
                    'integer',
                ],

                'relationship_type' => [
                    'required',

                    Rule::in([
                        'ALLOWS',
                        'BLOCKS',
                    ]),
                ],

                'priority' => [
                    'nullable',
                    'integer',
                ],
            ]);


        $service->createOptionRelationship(
            $request->user(),
            $data
        );


        return back()
            ->with(
                'success',
                'Relación entre Catálogos creada correctamente.'
            );
    }


    public function destroyOptionRelationship(
        Request $request,
        AttributeOptionRelationship $relationship,
        AttributeContextService $service
    ): RedirectResponse {

        $service
            ->deleteOptionRelationship(
                $request->user(),
                $relationship
            );


        return back()
            ->with(
                'success',
                'Relación entre elementos eliminada.'
            );
    }
}
