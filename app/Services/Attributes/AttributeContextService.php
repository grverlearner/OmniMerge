<?php

namespace App\Services\Attributes;

use App\Models\Attribute;
use App\Models\AttributeContextRule;
use App\Models\AttributeOption;
use App\Models\AttributeOptionRelationship;
use App\Models\AttributeRelationship;
use App\Models\User;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AttributeContextService
{
    /*
    |--------------------------------------------------------------------------
    | CREAR REGLA
    |--------------------------------------------------------------------------
    */

    public function createRule(
        User $user,
        array $data
    ): AttributeContextRule {

        return DB::transaction(
            function () use (
                $user,
                $data
            ) {

                $target =
                    $this->ownedAttribute(
                        $user,
                        (int) $data['target_attribute_id']
                    );


                $conditions =
                    collect(
                        $data['conditions']
                            ?? []
                    )
                    ->values();


                if (
                    $conditions->isEmpty()
                ) {

                    throw ValidationException::withMessages([
                        'conditions' =>
                        'La regla necesita al menos una condición.',
                    ]);
                }


                $normalized = [];


                foreach (
                    $conditions
                    as $index => $condition
                ) {

                    $source =
                        $this->ownedAttribute(
                            $user,
                            (int) (
                                $condition['source_attribute_id']
                                ?? 0
                            )
                        );


                    if (
                        $source->id
                        ===
                        $target->id
                    ) {

                        throw ValidationException::withMessages([
                            "conditions.{$index}.source_attribute_id" =>
                            'Un Atributo no puede depender de sí mismo.',
                        ]);
                    }


                    $operator =
                        strtoupper(
                            (string) (
                                $condition['operator']
                                ?? 'EQUALS'
                            )
                        );


                    if (
                        ! in_array(
                            $operator,
                            [
                                'EQUALS',
                                'NOT_EQUALS',
                                'EXISTS',
                                'NOT_EXISTS',
                            ],
                            true
                        )
                    ) {

                        throw ValidationException::withMessages([
                            "conditions.{$index}.operator" =>
                            'El operador seleccionado no es válido.',
                        ]);
                    }


                    $optionId =
                        null;


                    if (
                        in_array(
                            $operator,
                            [
                                'EQUALS',
                                'NOT_EQUALS',
                            ],
                            true
                        )
                    ) {

                        if (
                            $source->data_type
                            !==
                            'OPTION'
                        ) {

                            throw ValidationException::withMessages([
                                "conditions.{$index}.source_attribute_id" =>
                                'Los operadores ES / NO ES necesitan un Atributo de tipo Catálogo.',
                            ]);
                        }


                        $option =
                            AttributeOption::query()
                            ->ownedBy(
                                $user
                            )
                            ->active()
                            ->where(
                                'attribute_id',
                                $source->id
                            )
                            ->find(
                                $condition['source_option_id']
                                    ?? null
                            );


                        if (! $option) {

                            throw ValidationException::withMessages([
                                "conditions.{$index}.source_option_id" =>
                                'Selecciona un elemento válido del Catálogo.',
                            ]);
                        }


                        $optionId =
                            $option->id;
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Evitar ciclos
                    |--------------------------------------------------------------------------
                    |
                    | Anime -> Aldea
                    | Aldea -> Clan
                    |
                    | no permitir:
                    | Clan -> Anime
                    |
                    */

                    $this->ensureNoCycle(
                        $user,
                        $source->id,
                        $target->id
                    );


                    $normalized[] = [
                        'source_attribute_id' =>
                        $source->id,

                        'operator' =>
                        $operator,

                        'source_option_id' =>
                        $optionId,

                        'sort_order' => (
                            $index
                            +
                            1
                        )
                            *
                            10,
                    ];
                }


                $action =
                    strtoupper(
                        (string) (
                            $data['action']
                            ?? 'SHOW'
                        )
                    );


                if (
                    ! in_array(
                        $action,
                        [
                            'SHOW',
                            'HIDE',
                            'REQUIRE',
                        ],
                        true
                    )
                ) {

                    throw ValidationException::withMessages([
                        'action' =>
                        'La acción seleccionada no es válida.',
                    ]);
                }


                $matchMode =
                    strtoupper(
                        (string) (
                            $data['match_mode']
                            ?? 'ALL'
                        )
                    );


                if (
                    ! in_array(
                        $matchMode,
                        [
                            'ALL',
                            'ANY',
                        ],
                        true
                    )
                ) {

                    throw ValidationException::withMessages([
                        'match_mode' =>
                        'El modo de coincidencia no es válido.',
                    ]);
                }


                $rule =
                    AttributeContextRule::query()
                    ->create([
                        'user_id' =>
                        $user->id,

                        'target_attribute_id' =>
                        $target->id,

                        'name' =>
                        trim(
                            (string) (
                                $data['name']
                                ?? ''
                            )
                        )
                            ?: null,

                        'action' =>
                        $action,

                        'match_mode' =>
                        $matchMode,

                        'priority' =>
                        (int) (
                            $data['priority']
                            ?? 0
                        ),

                        'is_active' =>
                        true,
                    ]);


                foreach (
                    $normalized
                    as $condition
                ) {

                    $rule
                        ->conditions()
                        ->create(
                            $condition
                        );


                    /*
                     * Registrar la relación estructural
                     * source -> target.
                     */
                    AttributeRelationship::query()
                        ->updateOrCreate(
                            [
                                'user_id' =>
                                $user->id,

                                'source_attribute_id' =>
                                $condition['source_attribute_id'],

                                'target_attribute_id' =>
                                $target->id,

                                'relationship_type' =>
                                'DEPENDS_ON',
                            ],
                            [
                                'is_active' =>
                                true,
                            ]
                        );
                }


                $this->recalculateHierarchyLevels(
                    $user
                );


                return $rule->load([
                    'targetAttribute',
                    'conditions.sourceAttribute',
                    'conditions.sourceOption',
                ]);
            }
        );
    }


    /*
    |--------------------------------------------------------------------------
    | ELIMINAR REGLA
    |--------------------------------------------------------------------------
    */

    public function deleteRule(
        User $user,
        AttributeContextRule $rule
    ): void {

        if (
            $rule->user_id
            !==
            $user->id
        ) {
            abort(404);
        }


        $rule->load(
            'conditions'
        );


        $targetId =
            $rule
            ->target_attribute_id;


        $sourceIds =
            $rule
            ->conditions
            ->pluck(
                'source_attribute_id'
            )
            ->unique();


        DB::transaction(
            function () use (
                $user,
                $rule,
                $sourceIds,
                $targetId
            ) {

                $rule->delete();


                foreach (
                    $sourceIds
                    as $sourceId
                ) {

                    $this->cleanupRelationship(
                        $user,
                        (int) $sourceId,
                        (int) $targetId
                    );
                }


                $this->recalculateHierarchyLevels(
                    $user
                );
            }
        );
    }


    /*
    |--------------------------------------------------------------------------
    | RELACIÓN ENTRE ELEMENTOS DE CATÁLOGO
    |--------------------------------------------------------------------------
    */

    public function createOptionRelationship(
        User $user,
        array $data
    ): AttributeOptionRelationship {

        $source =
            $this->ownedOption(
                $user,
                (int) $data['source_option_id']
            );


        $target =
            $this->ownedOption(
                $user,
                (int) $data['target_option_id']
            );


        if (
            $source->attribute_id
            ===
            $target->attribute_id
        ) {

            throw ValidationException::withMessages([
                'target_option_id' =>
                'Esta funcionalidad conecta elementos de Catálogos diferentes. Para jerarquía interna usa Padre del elemento.',
            ]);
        }


        $type =
            strtoupper(
                (string) (
                    $data['relationship_type']
                    ?? 'ALLOWS'
                )
            );


        if (
            ! in_array(
                $type,
                [
                    'ALLOWS',
                    'BLOCKS',
                ],
                true
            )
        ) {

            throw ValidationException::withMessages([
                'relationship_type' =>
                'El tipo de relación no es válido.',
            ]);
        }


        $this->ensureNoCycle(
            $user,
            $source->attribute_id,
            $target->attribute_id
        );


        return DB::transaction(
            function () use (
                $user,
                $source,
                $target,
                $type,
                $data
            ) {

                $relationship =
                    AttributeOptionRelationship::query()
                    ->updateOrCreate(
                        [
                            'user_id' =>
                            $user->id,

                            'source_option_id' =>
                            $source->id,

                            'target_option_id' =>
                            $target->id,

                            'relationship_type' =>
                            $type,
                        ],
                        [
                            'priority' =>
                            (int) (
                                $data['priority']
                                ?? 0
                            ),

                            'is_active' =>
                            true,
                        ]
                    );


                AttributeRelationship::query()
                    ->updateOrCreate(
                        [
                            'user_id' =>
                            $user->id,

                            'source_attribute_id' =>
                            $source
                                ->attribute_id,

                            'target_attribute_id' =>
                            $target
                                ->attribute_id,

                            'relationship_type' =>
                            'DEPENDS_ON',
                        ],
                        [
                            'is_active' =>
                            true,
                        ]
                    );


                $this->recalculateHierarchyLevels(
                    $user
                );


                return $relationship->load([
                    'sourceOption.attribute',
                    'targetOption.attribute',
                ]);
            }
        );
    }


    public function deleteOptionRelationship(
        User $user,
        AttributeOptionRelationship $relationship
    ): void {

        if (
            $relationship->user_id
            !==
            $user->id
        ) {
            abort(404);
        }


        $relationship->load([
            'sourceOption',
            'targetOption',
        ]);


        $sourceAttributeId =
            $relationship
            ->sourceOption
            ->attribute_id;


        $targetAttributeId =
            $relationship
            ->targetOption
            ->attribute_id;


        DB::transaction(
            function () use (
                $user,
                $relationship,
                $sourceAttributeId,
                $targetAttributeId
            ) {

                $relationship->delete();


                $this->cleanupRelationship(
                    $user,
                    $sourceAttributeId,
                    $targetAttributeId
                );


                $this->recalculateHierarchyLevels(
                    $user
                );
            }
        );
    }


    /*
    |--------------------------------------------------------------------------
    | FRONTEND PAYLOAD
    |--------------------------------------------------------------------------
    */

    public function frontendPayload(
        User $user
    ): array {

        $rules =
            AttributeContextRule::query()
            ->ownedBy(
                $user
            )
            ->active()
            ->with([
                'conditions',
            ])
            ->orderByDesc(
                'priority'
            )
            ->get();


        $optionRelationships =
            AttributeOptionRelationship::query()
            ->ownedBy(
                $user
            )
            ->active()
            ->with([
                'sourceOption:id,attribute_id',
                'targetOption:id,attribute_id',
            ])
            ->get();


        return [
            'rules' =>
            $rules
                ->map(
                    fn(
                        AttributeContextRule $rule
                    ) => [
                        'id' =>
                        (string) $rule->id,

                        'target_attribute_id' =>
                        (string) $rule
                            ->target_attribute_id,

                        'action' =>
                        $rule->action,

                        'match_mode' =>
                        $rule->match_mode,

                        'priority' =>
                        $rule->priority,

                        'conditions' =>
                        $rule
                            ->conditions
                            ->map(
                                fn($condition) => [
                                    'source_attribute_id' =>
                                    (string) $condition
                                        ->source_attribute_id,

                                    'operator' =>
                                    $condition
                                        ->operator,

                                    'source_option_id' =>
                                    $condition
                                        ->source_option_id
                                        ? (string) $condition
                                            ->source_option_id
                                        : null,
                                ]
                            )
                            ->values()
                            ->all(),
                    ]
                )
                ->values()
                ->all(),

            'option_relations' =>
            $optionRelationships
                ->filter(
                    fn($relationship) =>
                    $relationship
                        ->sourceOption
                        &&
                        $relationship
                        ->targetOption
                )
                ->map(
                    fn($relationship) => [
                        'source_option_id' =>
                        (string) $relationship
                            ->source_option_id,

                        'target_option_id' =>
                        (string) $relationship
                            ->target_option_id,

                        'source_attribute_id' =>
                        (string) $relationship
                            ->sourceOption
                            ->attribute_id,

                        'target_attribute_id' =>
                        (string) $relationship
                            ->targetOption
                            ->attribute_id,

                        'relationship_type' =>
                        $relationship
                            ->relationship_type,
                    ]
                )
                ->values()
                ->all(),
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | SANITIZAR VALORES DE UNA ENTIDAD
    |--------------------------------------------------------------------------
    */

    public function sanitizeSelection(
        User $user,
        array $selectedAttributeIds,
        array $inputs
    ): array {

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
                    ),
            ])
            ->get()
            ->keyBy(
                'id'
            );


        $selected =
            collect(
                $selectedAttributeIds
            )
            ->map(
                fn($id) =>
                (int) $id
            )
            ->filter()
            ->unique()
            ->values();


        /*
         * No permitir IDs manipulados.
         */
        $invalidIds =
            $selected
            ->diff(
                $attributes->keys()
            );


        if (
            $invalidIds->isNotEmpty()
        ) {

            throw ValidationException::withMessages([
                'selected_attribute_ids' =>
                'Uno o más Atributos seleccionados no son válidos.',
            ]);
        }


        $rules =
            AttributeContextRule::query()
            ->ownedBy(
                $user
            )
            ->active()
            ->with(
                'conditions'
            )
            ->orderByDesc(
                'priority'
            )
            ->get()
            ->groupBy(
                'target_attribute_id'
            );


        /*
        |--------------------------------------------------------------------------
        | Resolver dependencias encadenadas
        |--------------------------------------------------------------------------
        */

        $maximumRounds =
            max(
                1,
                $attributes->count()
                    +
                    1
            );


        for (
            $round = 0;
            $round < $maximumRounds;
            $round++
        ) {

            $changed =
                false;


            $optionIds =
                $this->selectedOptionIds(
                    $attributes,
                    $selected,
                    $inputs
                );


            $optionAttributeMap =
                $this->optionAttributeMap(
                    $user,
                    $optionIds
                );


            /*
             * Ocultar Atributos cuyas reglas ya
             * no se cumplan.
             */
            foreach (
                $selected->values()
                as $attributeId
            ) {

                $targetRules =
                    $rules->get(
                        $attributeId,
                        collect()
                    );


                if (
                    ! $this->visibleByRules(
                        $targetRules,
                        $optionIds,
                        $optionAttributeMap
                    )
                ) {

                    $selected =
                        $selected
                        ->reject(
                            fn($id) =>
                            (int) $id
                                ===
                                (int) $attributeId
                        )
                        ->values();


                    unset(
                        $inputs[$attributeId]
                    );


                    $changed =
                        true;
                }
            }


            /*
             * REQUIRE agrega automáticamente
             * el Atributo requerido.
             */
            foreach (
                $rules
                as $targetId => $targetRules
            ) {

                if (
                    $this->requiredByRules(
                        $targetRules,
                        $optionIds,
                        $optionAttributeMap
                    )
                    &&
                    ! $selected->contains(
                        (int) $targetId
                    )
                ) {

                    $selected->push(
                        (int) $targetId
                    );


                    $selected =
                        $selected
                        ->unique()
                        ->values();


                    $changed =
                        true;
                }
            }


            if (! $changed) {
                break;
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Validación final
        |--------------------------------------------------------------------------
        */

        $optionIds =
            $this->selectedOptionIds(
                $attributes,
                $selected,
                $inputs
            );


        $optionAttributeMap =
            $this->optionAttributeMap(
                $user,
                $optionIds
            );


        $optionRelationships =
            AttributeOptionRelationship::query()
            ->ownedBy(
                $user
            )
            ->active()
            ->with([
                'sourceOption',
                'targetOption',
            ])
            ->get();


        foreach (
            $selected
            as $attributeId
        ) {

            /** @var Attribute|null $attribute */
            $attribute =
                $attributes->get(
                    $attributeId
                );


            if (! $attribute) {
                continue;
            }


            $targetRules =
                $rules->get(
                    $attributeId,
                    collect()
                );


            $contextRequired =
                $this->requiredByRules(
                    $targetRules,
                    $optionIds,
                    $optionAttributeMap
                );


            $value =
                $inputs[$attributeId]
                ?? null;


            if (
                $contextRequired
                &&
                ! $this->hasValue(
                    $value
                )
            ) {

                throw ValidationException::withMessages([
                    "attributes.{$attributeId}" =>
                    "El Atributo {$attribute->name} es obligatorio en el contexto actual.",
                ]);
            }


            if (
                $attribute->data_type
                ===
                'OPTION'
                &&
                $this->hasValue(
                    $value
                )
            ) {

                $this->validateOptionRestrictions(
                    $attribute,
                    $value,
                    $optionIds,
                    $optionRelationships
                );
            }
        }


        return [
            'selected_attribute_ids' =>
            $selected
                ->values()
                ->all(),

            'inputs' =>
            $inputs,
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | EVALUACIÓN
    |--------------------------------------------------------------------------
    */

    private function visibleByRules(
        Collection $rules,
        Collection $selectedOptionIds,
        Collection $optionAttributeMap
    ): bool {

        if ($rules->isEmpty()) {
            return true;
        }


        /*
         * HIDE coincidente siempre oculta.
         */
        $hideMatches =
            $rules
            ->where(
                'action',
                'HIDE'
            )
            ->contains(
                fn($rule) =>
                $this->ruleMatches(
                    $rule,
                    $selectedOptionIds,
                    $optionAttributeMap
                )
            );


        if ($hideMatches) {
            return false;
        }


        /*
         * REQUIRE implica que debe ser visible.
         */
        $requireMatches =
            $rules
            ->where(
                'action',
                'REQUIRE'
            )
            ->contains(
                fn($rule) =>
                $this->ruleMatches(
                    $rule,
                    $selectedOptionIds,
                    $optionAttributeMap
                )
            );


        if ($requireMatches) {
            return true;
        }


        /*
         * Si existen SHOW,
         * al menos uno debe cumplirse.
         */
        $showRules =
            $rules->where(
                'action',
                'SHOW'
            );


        if (
            $showRules->isNotEmpty()
        ) {

            return $showRules->contains(
                fn($rule) =>
                $this->ruleMatches(
                    $rule,
                    $selectedOptionIds,
                    $optionAttributeMap
                )
            );
        }


        return true;
    }


    private function requiredByRules(
        Collection $rules,
        Collection $selectedOptionIds,
        Collection $optionAttributeMap
    ): bool {

        return $rules
            ->where(
                'action',
                'REQUIRE'
            )
            ->contains(
                fn($rule) =>
                $this->ruleMatches(
                    $rule,
                    $selectedOptionIds,
                    $optionAttributeMap
                )
            );
    }


    private function ruleMatches(
        AttributeContextRule $rule,
        Collection $selectedOptionIds,
        Collection $optionAttributeMap
    ): bool {

        $conditions =
            $rule->conditions;


        if (
            $conditions->isEmpty()
        ) {
            return false;
        }


        $results =
            $conditions
            ->map(
                function (
                    $condition
                ) use (
                    $selectedOptionIds,
                    $optionAttributeMap
                ) {

                    return match ($condition->operator) {

                        'EQUALS' =>
                        $condition
                            ->source_option_id
                            &&
                            $selectedOptionIds
                            ->contains(
                                (int) $condition
                                    ->source_option_id
                            ),

                        'NOT_EQUALS' =>
                        ! (
                            $condition
                            ->source_option_id
                            &&
                            $selectedOptionIds
                            ->contains(
                                (int) $condition
                                    ->source_option_id
                            )
                        ),

                        'EXISTS' =>
                        $optionAttributeMap
                            ->contains(
                                (int) $condition
                                    ->source_attribute_id
                            ),

                        'NOT_EXISTS' =>
                        ! $optionAttributeMap
                            ->contains(
                                (int) $condition
                                    ->source_attribute_id
                            ),

                        default =>
                        false,
                    };
                }
            );


        return $rule->match_mode === 'ANY'
            ? $results->contains(
                true
            )
            : ! $results->contains(
                false
            );
    }


    /*
    |--------------------------------------------------------------------------
    | RESTRICCIONES ENTRE CATÁLOGOS
    |--------------------------------------------------------------------------
    */

    private function validateOptionRestrictions(
        Attribute $attribute,
        mixed $value,
        Collection $selectedOptionIds,
        Collection $relationships
    ): void {

        $selectedTargetIds =
            collect(
                is_array($value)
                    ? $value
                    : [$value]
            )
            ->map(
                fn($id) =>
                (int) $id
            )
            ->filter()
            ->unique();


        $relevant =
            $relationships
            ->filter(
                function (
                    AttributeOptionRelationship $relationship
                ) use (
                    $attribute,
                    $selectedOptionIds
                ) {

                    return
                        $relationship
                        ->sourceOption
                        &&
                        $relationship
                        ->targetOption
                        &&
                        $selectedOptionIds
                        ->contains(
                            (int) $relationship
                                ->source_option_id
                        )
                        &&
                        (int) $relationship
                            ->targetOption
                            ->attribute_id
                        ===
                        (int) $attribute->id;
                }
            );


        if (
            $relevant->isEmpty()
        ) {
            return;
        }


        $allowed =
            $relevant
            ->where(
                'relationship_type',
                'ALLOWS'
            )
            ->pluck(
                'target_option_id'
            )
            ->map(
                fn($id) =>
                (int) $id
            );


        $blocked =
            $relevant
            ->where(
                'relationship_type',
                'BLOCKS'
            )
            ->pluck(
                'target_option_id'
            )
            ->map(
                fn($id) =>
                (int) $id
            );


        foreach (
            $selectedTargetIds
            as $targetId
        ) {

            if (
                $allowed->isNotEmpty()
                &&
                ! $allowed->contains(
                    $targetId
                )
            ) {

                throw ValidationException::withMessages([
                    "attributes.{$attribute->id}" =>
                    "El elemento seleccionado no está permitido para {$attribute->name} en el contexto actual.",
                ]);
            }


            if (
                $blocked->contains(
                    $targetId
                )
            ) {

                throw ValidationException::withMessages([
                    "attributes.{$attribute->id}" =>
                    "El elemento seleccionado está bloqueado para {$attribute->name} en el contexto actual.",
                ]);
            }
        }
    }


    /*
    |--------------------------------------------------------------------------
    | OPCIONES ACTIVAS EN LOS INPUTS
    |--------------------------------------------------------------------------
    */

    private function selectedOptionIds(
        Collection $attributes,
        Collection $selectedAttributeIds,
        array $inputs
    ): Collection {

        $ids =
            collect();


        foreach (
            $selectedAttributeIds
            as $attributeId
        ) {

            $attribute =
                $attributes->get(
                    $attributeId
                );


            if (
                ! $attribute
                ||
                $attribute->data_type
                !==
                'OPTION'
            ) {
                continue;
            }


            $values =
                $inputs[$attributeId]
                ?? null;


            foreach (
                (array) $values
                as $value
            ) {

                if (
                    is_numeric(
                        $value
                    )
                ) {
                    $ids->push(
                        (int) $value
                    );
                }
            }
        }


        return $ids
            ->filter()
            ->unique()
            ->values();
    }


    private function optionAttributeMap(
        User $user,
        Collection $optionIds
    ): Collection {

        if (
            $optionIds->isEmpty()
        ) {
            return collect();
        }


        return AttributeOption::query()
            ->ownedBy(
                $user
            )
            ->active()
            ->whereIn(
                'id',
                $optionIds
            )
            ->pluck(
                'attribute_id',
                'id'
            )
            ->map(
                fn($attributeId) =>
                (int) $attributeId
            );
    }


    /*
    |--------------------------------------------------------------------------
    | CICLOS
    |--------------------------------------------------------------------------
    */

    private function ensureNoCycle(
        User $user,
        int $sourceAttributeId,
        int $targetAttributeId
    ): void {

        if (
            $sourceAttributeId
            ===
            $targetAttributeId
        ) {

            throw ValidationException::withMessages([
                'relationship' =>
                'Un Atributo no puede depender de sí mismo.',
            ]);
        }


        /*
         * Crear source -> target produce ciclo
         * si target ya puede llegar a source.
         */

        $relationships =
            AttributeRelationship::query()
            ->ownedBy(
                $user
            )
            ->active()
            ->get([
                'source_attribute_id',
                'target_attribute_id',
            ]);


        $adjacency =
            $relationships
            ->groupBy(
                'source_attribute_id'
            );


        $queue = [
            $targetAttributeId,
        ];


        $visited = [];


        while (
            count($queue)
            >
            0
        ) {

            $current =
                array_shift(
                    $queue
                );


            if (
                isset(
                    $visited[$current]
                )
            ) {
                continue;
            }


            $visited[$current] =
                true;


            if (
                $current
                ===
                $sourceAttributeId
            ) {

                throw ValidationException::withMessages([
                    'relationship' =>
                    'No se puede crear la dependencia porque produciría un ciclo entre Atributos.',
                ]);
            }


            foreach (
                $adjacency->get(
                    $current,
                    collect()
                )
                as $relationship
            ) {

                $queue[] =
                    (int) $relationship
                        ->target_attribute_id;
            }
        }
    }


    /*
    |--------------------------------------------------------------------------
    | JERARQUÍA CALCULADA
    |--------------------------------------------------------------------------
    */

    private function recalculateHierarchyLevels(
        User $user
    ): void {

        $attributes =
            Attribute::query()
            ->ownedBy(
                $user
            )
            ->get([
                'id',
            ]);


        $relationships =
            AttributeRelationship::query()
            ->ownedBy(
                $user
            )
            ->active()
            ->get([
                'source_attribute_id',
                'target_attribute_id',
            ]);


        $incoming =
            $relationships
            ->groupBy(
                'target_attribute_id'
            );


        $memo = [];


        $resolveLevel =
            function (
                int $attributeId,
                array $path = []
            ) use (
                &$resolveLevel,
                &$memo,
                $incoming
            ): int {

                if (
                    isset(
                        $memo[$attributeId]
                    )
                ) {
                    return $memo[$attributeId];
                }


                if (
                    in_array(
                        $attributeId,
                        $path,
                        true
                    )
                ) {
                    return 0;
                }


                $parents =
                    $incoming->get(
                        $attributeId,
                        collect()
                    );


                if (
                    $parents->isEmpty()
                ) {

                    $memo[$attributeId] =
                        0;

                    return 0;
                }


                $path[] =
                    $attributeId;


                $level =
                    0;


                foreach (
                    $parents
                    as $relationship
                ) {

                    $parentLevel =
                        $resolveLevel(
                            (int) $relationship
                                ->source_attribute_id,
                            $path
                        );


                    $level =
                        max(
                            $level,
                            $parentLevel
                                +
                                1
                        );
                }


                $memo[$attributeId] =
                    $level;


                return $level;
            };


        foreach (
            $attributes
            as $attribute
        ) {

            Attribute::query()
                ->whereKey(
                    $attribute->id
                )
                ->update([
                    'hierarchy_level' =>
                    $resolveLevel(
                        (int) $attribute->id
                    ),
                ]);
        }
    }


    /*
    |--------------------------------------------------------------------------
    | LIMPIAR RELACIÓN ESTRUCTURAL SIN USO
    |--------------------------------------------------------------------------
    */

    private function cleanupRelationship(
        User $user,
        int $sourceAttributeId,
        int $targetAttributeId
    ): void {

        $usedByRule =
            AttributeContextRule::query()
            ->ownedBy(
                $user
            )
            ->where(
                'target_attribute_id',
                $targetAttributeId
            )
            ->whereHas(
                'conditions',
                fn($query) =>
                $query->where(
                    'source_attribute_id',
                    $sourceAttributeId
                )
            )
            ->exists();


        $usedByOptions =
            AttributeOptionRelationship::query()
            ->ownedBy(
                $user
            )
            ->whereHas(
                'sourceOption',
                fn($query) =>
                $query->where(
                    'attribute_id',
                    $sourceAttributeId
                )
            )
            ->whereHas(
                'targetOption',
                fn($query) =>
                $query->where(
                    'attribute_id',
                    $targetAttributeId
                )
            )
            ->exists();


        if (
            ! $usedByRule
            &&
            ! $usedByOptions
        ) {

            AttributeRelationship::query()
                ->ownedBy(
                    $user
                )
                ->where(
                    'source_attribute_id',
                    $sourceAttributeId
                )
                ->where(
                    'target_attribute_id',
                    $targetAttributeId
                )
                ->delete();
        }
    }


    private function ownedAttribute(
        User $user,
        int $id
    ): Attribute {

        $attribute =
            Attribute::query()
            ->ownedBy(
                $user
            )
            ->active()
            ->find(
                $id
            );


        if (! $attribute) {

            throw ValidationException::withMessages([
                'attribute' =>
                'El Atributo seleccionado no es válido.',
            ]);
        }


        return $attribute;
    }


    private function ownedOption(
        User $user,
        int $id
    ): AttributeOption {

        $option =
            AttributeOption::query()
            ->ownedBy(
                $user
            )
            ->active()
            ->find(
                $id
            );


        if (! $option) {

            throw ValidationException::withMessages([
                'option' =>
                'El elemento de Catálogo seleccionado no es válido.',
            ]);
        }


        return $option;
    }


    private function hasValue(
        mixed $value
    ): bool {

        if (
            is_array(
                $value
            )
        ) {

            return collect(
                $value
            )
                ->filter(
                    fn($item) =>
                    $item !== null
                        &&
                        $item !== ''
                )
                ->isNotEmpty();
        }


        return $value !== null
            &&
            $value !== '';
    }
}
