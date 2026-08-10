<?php

namespace App\Http\Controllers\Attributes;

use App\Http\Controllers\Controller;

use App\Models\Attribute;
use App\Models\AttributeContextRule;
use App\Models\AttributeOptionRelationship;
use App\Models\AttributeRelationship;

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

                    'level' =>
                    $attribute
                        ->hierarchy_level,

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
                            ]
                        )
                        ->values()
                        ->all(),
                ]
            )
            ->values()
            ->all();


        $stats = [
            'attributes' =>
            $attributes->count(),

            'relationships' =>
            $relationships->count(),

            'rules' =>
            $rules->count(),

            'option_relationships' =>
            $optionRelationships->count(),
        ];


        return view(
            'attributes.structure',
            compact(
                'attributes',
                'relationships',
                'rules',
                'optionRelationships',
                'attributePayload',
                'stats'
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
