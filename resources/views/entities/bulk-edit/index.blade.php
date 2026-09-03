<x-app-layout title="Gestión masiva" surface="dark">

    <x-slot name="header">Gestión masiva de Entidades</x-slot>

    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>


    {{--
        El motor sigue siendo el mismo `bulkEditManager`: filtra, agrupa,
        marca, arma la matriz y ordena características. Lo único que se le
        añade desde el marcado es `pickView`, que decide si las entidades se
        eligen en fichas, en lista o en tabla.

        Cada acción es su propio formulario al mismo endpoint, con su
        `operation` y las marcadas en `entity_ids[]`. Eso no ha cambiado; lo
        que cambia es que ahora todas enseñan a quién van a afectar antes de
        pulsarlas.
    --}}

    <div x-data="Object.assign(bulkEditManager({

        entities: @js($entityPayload),

        attributes: @js($attributePayload),

        entityTypes: @js($typePayload),

        collections: @js($collectionPayload),

        initialRules: @js($attributeFilters),

        matchedCount: @js($matchedCount)
    }), { pickView: 'cards' })" x-init="init()" class="space-y-4">

        @include('entities.bulk-edit.partials.head')

        @include('entities.bulk-edit.partials.selection')

        @include('entities.bulk-edit.partials.actions')

    </div>


    {{-- ========================================================= --}}
    {{-- ALPINE --}}
    {{-- ========================================================= --}}

        <script>
            function bulkEditManager(
                config
            ) {

                return {

                    /*
                    |--------------------------------------------------------------------------
                    | Navegación
                    |--------------------------------------------------------------------------
                    */

                    activeTab: 'selection',

                    tabs: [

                        {
                            id: 'selection',

                            icon: '☑',

                            label: 'Selección'
                        },

                        {
                            id: 'quick',

                            icon: '⚡',

                            label: 'Rápida'
                        },

                        {
                            id: 'matrix',

                            icon: '▦',

                            label: 'Matriz'
                        },

                        {
                            id: 'attributes',

                            icon: '◆',

                            label: 'Características'
                        },

                        {
                            id: 'collections',

                            icon: '▤',

                            label: 'Colecciones'
                        },

                        {
                            id: 'structure',

                            icon: '☷',

                            label: 'Estructura'
                        },

                        {
                            id: 'publication',

                            icon: '◉',

                            label: 'Publicación'
                        },

                        {
                            id: 'danger',

                            icon: '⚠',

                            label: 'Peligro'
                        },
                    ],


                    /*
                    |--------------------------------------------------------------------------
                    | Recursos
                    |--------------------------------------------------------------------------
                    */

                    entities: config.entities ?? [],

                    attributes: config.attributes ?? [],

                    entityTypes: config.entityTypes ?? [],

                    collections: config.collections ?? [],


                    /*
                    |--------------------------------------------------------------------------
                    | Selección
                    |--------------------------------------------------------------------------
                    */

                    selectedIds: [],


                    /*
                    |--------------------------------------------------------------------------
                    | Agrupación
                    |--------------------------------------------------------------------------
                    */

                    groupLevel1: localStorage.getItem(
                            'omnimerge.bulk-edit.group1'
                        ) ??
                        '',

                    groupLevel2: localStorage.getItem(
                            'omnimerge.bulk-edit.group2'
                        ) ??
                        '',


                    /*
                    |--------------------------------------------------------------------------
                    | Filter rules
                    |--------------------------------------------------------------------------
                    */

                    filterRules: [],


                    /*
                    |--------------------------------------------------------------------------
                    | Matrix
                    |--------------------------------------------------------------------------
                    */

                    matrixAttributeIds: [],

                    matrixSubmitting: false,


                    /*
                    |--------------------------------------------------------------------------
                    | Attribute operation
                    |--------------------------------------------------------------------------
                    */

                    selectedAttributeId: '',

                    attributeOperation: 'set_attribute',

                    attributeValue: '',


                    /*
                    |--------------------------------------------------------------------------
                    | Structure
                    |--------------------------------------------------------------------------
                    */

                    orderAttributeIds: [],


                    /*
                    |--------------------------------------------------------------------------
                    | INIT
                    |--------------------------------------------------------------------------
                    */

                    init() {

                        /*
                        |--------------------------------------------------------------------------
                        | Preparar Entidades editables
                        |--------------------------------------------------------------------------
                        */

                        this.entities =
                            this.entities
                            .map(
                                entity => {

                                    entity.edit = {

                                        name: entity.name,

                                        description: entity.description,

                                        entity_type_id: String(
                                            entity.entity_type_id ??
                                            ''
                                        ),

                                        status: entity.status,

                                        visibility: entity.visibility,

                                        allow_cloning:
                                            !!entity.allow_cloning,

                                        attribute_values: JSON.parse(
                                            JSON.stringify(
                                                entity.attribute_values ?? {}
                                            )
                                        ),
                                    };


                                    /*
                                     * Asegurar valor para todos
                                     * los Atributos.
                                     */
                                    this.attributes
                                        .forEach(
                                            attribute => {

                                                const id =
                                                    String(
                                                        attribute.id
                                                    );


                                                if (
                                                    entity
                                                    .edit
                                                    .attribute_values[
                                                        id
                                                    ] ===
                                                    undefined
                                                ) {

                                                    entity
                                                        .edit
                                                        .attribute_values[
                                                            id
                                                        ] =
                                                        attribute
                                                        .allows_multiple

                                                        ?
                                                        []

                                                        :
                                                        '';
                                                }
                                            }
                                        );


                                    return entity;
                                }
                            );


                        /*
                        |--------------------------------------------------------------------------
                        | Reglas cargadas desde GET
                        |--------------------------------------------------------------------------
                        */

                        const oldRules =
                            config.initialRules ?? [];


                        if (
                            oldRules.length >
                            0
                        ) {

                            this.filterRules =
                                oldRules.map(
                                    (
                                        rule,
                                        index
                                    ) => ({

                                        key: `rule-${Date.now()}-${index}`,

                                        logic: rule.logic ??
                                            'AND',

                                        attribute_id: rule.attribute_id ?
                                            String(
                                                rule.attribute_id
                                            ) : '',

                                        operator: rule.operator ??
                                            'eq',

                                        value: rule.value ??
                                            '',

                                        value2: rule.value2 ??
                                            '',
                                    })
                                );
                        }


                        /*
                        |--------------------------------------------------------------------------
                        | Persistir agrupación
                        |--------------------------------------------------------------------------
                        */

                        this.$watch(
                            'groupLevel1',
                            value => {

                                localStorage.setItem(
                                    'omnimerge.bulk-edit.group1',
                                    value
                                );
                            }
                        );


                        this.$watch(
                            'groupLevel2',
                            value => {

                                localStorage.setItem(
                                    'omnimerge.bulk-edit.group2',
                                    value
                                );
                            }
                        );
                    },


                    /*
                    |--------------------------------------------------------------------------
                    | GETTERS
                    |--------------------------------------------------------------------------
                    */

                    get selectedCount() {

                        return this
                            .selectedIds
                            .length;
                    },


                    get selectedEntities() {

                        return this.entities
                            .filter(
                                entity =>
                                this.isSelected(
                                    entity.id
                                )
                            );
                    },


                    get currentAttribute() {

                        if (
                            !this
                            .selectedAttributeId
                        ) {
                            return null;
                        }


                        return this.attributes
                            .find(
                                attribute =>
                                String(
                                    attribute.id
                                ) ===
                                String(
                                    this.selectedAttributeId
                                )
                            ) ??
                            null;
                    },


                    get matrixAttributes() {

                        return this.attributes
                            .filter(
                                attribute =>
                                this
                                .matrixAttributeIds
                                .includes(
                                    String(
                                        attribute.id
                                    )
                                )
                            );
                    },


                    get groupedLevel1() {

                        return this.groupEntities(
                            this.entities,
                            this.groupLevel1
                        );
                    },


                    /*
                    |--------------------------------------------------------------------------
                    | Selection
                    |--------------------------------------------------------------------------
                    */

                    isSelected(
                        id
                    ) {

                        return this
                            .selectedIds
                            .includes(
                                String(
                                    id
                                )
                            );
                    },


                    toggleSelection(
                        id
                    ) {

                        id =
                            String(
                                id
                            );


                        if (
                            this.isSelected(
                                id
                            )
                        ) {

                            this.selectedIds =
                                this.selectedIds
                                .filter(
                                    value =>
                                    value !== id
                                );


                            return;
                        }


                        this.selectedIds
                            .push(
                                id
                            );
                    },


                    selectAll() {

                        this.selectedIds =
                            this.entities
                            .map(
                                entity =>
                                String(
                                    entity.id
                                )
                            );
                    },


                    clearSelection() {

                        this.selectedIds = [];
                    },


                    selectEntities(
                        entities
                    ) {

                        const ids =
                            entities.map(
                                entity =>
                                String(
                                    entity.id
                                )
                            );


                        this.selectedIds = [
                            ...new Set([
                                ...this.selectedIds,
                                ...ids
                            ])
                        ];
                    },


                    /*
                    |--------------------------------------------------------------------------
                    | GROUPING
                    |--------------------------------------------------------------------------
                    */

                    groupLabel(
                        entity,
                        key
                    ) {

                        if (!key) {

                            return 'Todas';
                        }


                        if (
                            key === 'type'
                        ) {

                            return entity
                                .entity_type_name ??
                                'Sin tipo';
                        }


                        if (
                            key === 'status'
                        ) {

                            return entity
                                .status_label ??
                                entity.status;
                        }


                        if (
                            key === 'visibility'
                        ) {

                            return entity
                                .visibility_label ??
                                entity.visibility;
                        }


                        if (
                            key === 'image'
                        ) {

                            return entity.image_url ?
                                'Con imagen' :
                                'Sin imagen';
                        }


                        if (
                            key === 'collection'
                        ) {

                            return entity
                                .collections[
                                    0
                                ]
                                ?.name ??
                                'Sin Colección';
                        }


                        if (
                            key.startsWith(
                                'attribute:'
                            )
                        ) {

                            const id =
                                key.split(
                                    ':'
                                )[
                                    1
                                ];


                            return entity
                                .attribute_displays[
                                    id
                                ] ||
                                'Sin valor';
                        }


                        return 'Otros';
                    },


                    groupEntities(
                        entities,
                        key
                    ) {

                        const groups = {};


                        entities.forEach(
                            entity => {

                                const label =
                                    this.groupLabel(
                                        entity,
                                        key
                                    );


                                if (
                                    !groups[
                                        label
                                    ]
                                ) {

                                    groups[
                                        label
                                    ] = [];
                                }


                                groups[
                                        label
                                    ]
                                    .push(
                                        entity
                                    );
                            }
                        );


                        return groups;
                    },


                    groupSecond(
                        entities
                    ) {

                        return this.groupEntities(
                            entities,
                            this.groupLevel2
                        );
                    },


                    /*
                    |--------------------------------------------------------------------------
                    | FILTER RULES
                    |--------------------------------------------------------------------------
                    */

                    addFilterRule() {

                        if (
                            this.filterRules.length >=
                            3
                        ) {
                            return;
                        }


                        this.filterRules.push({

                            key: `rule-${Date.now()}-${Math.random()}`,

                            logic: 'AND',

                            attribute_id: '',

                            operator: 'eq',

                            value: '',

                            value2: '',
                        });
                    },


                    removeFilterRule(
                        index
                    ) {

                        this.filterRules.splice(
                            index,
                            1
                        );
                    },


                    filterAttribute(
                        rule
                    ) {

                        return this.attributes
                            .find(
                                attribute =>
                                String(
                                    attribute.id
                                ) ===
                                String(
                                    rule.attribute_id
                                )
                            ) ??
                            null;
                    },


                    normalizeFilterRule(
                        rule
                    ) {

                        rule.value =
                            '';

                        rule.value2 =
                            '';


                        const attribute =
                            this.filterAttribute(
                                rule
                            );


                        if (!attribute) {
                            return;
                        }


                        if (
                            attribute.data_type ===
                            'OPTION'
                        ) {

                            rule.operator =
                                'eq';
                        }
                    },


                    /*
                    |--------------------------------------------------------------------------
                    | ATTRIBUTE OP
                    |--------------------------------------------------------------------------
                    */

                    resetAttributeValue() {

                        if (
                            this.currentAttribute &&
                            this
                            .currentAttribute
                            .allows_multiple
                        ) {

                            this.attributeValue = [];

                        } else {

                            this.attributeValue =
                                '';
                        }
                    },


                    /*
                    |--------------------------------------------------------------------------
                    | ORDER
                    |--------------------------------------------------------------------------
                    */

                    loadOrderAttributes() {

                        const used =
                            new Set();


                        this.selectedEntities
                            .forEach(
                                entity => {

                                    (
                                        entity.attribute_ids ?? []
                                    )
                                    .forEach(
                                        id =>
                                        used.add(
                                            String(
                                                id
                                            )
                                        )
                                    );
                                }
                            );


                        this.orderAttributeIds =
                            this.attributes
                            .filter(
                                attribute =>
                                used.has(
                                    String(
                                        attribute.id
                                    )
                                )
                            )
                            .sort(
                                (
                                    a,
                                    b
                                ) =>
                                Number(
                                    a.sort_order ??
                                    0
                                ) -
                                Number(
                                    b.sort_order ??
                                    0
                                )
                            )
                            .map(
                                attribute =>
                                String(
                                    attribute.id
                                )
                            );
                    },


                    attributeName(
                        id
                    ) {

                        return this.attributes
                            .find(
                                attribute =>
                                String(
                                    attribute.id
                                ) ===
                                String(
                                    id
                                )
                            )
                            ?.name ??
                            'Atributo';
                    },


                    moveOrderUp(
                        index
                    ) {

                        if (
                            index <= 0
                        ) {
                            return;
                        }


                        const copy = [
                            ...this.orderAttributeIds
                        ];


                        [
                            copy[
                                index - 1
                            ],
                            copy[
                                index
                            ]
                        ] = [
                            copy[
                                index
                            ],
                            copy[
                                index - 1
                            ]
                        ];


                        this.orderAttributeIds =
                            copy;
                    },


                    moveOrderDown(
                        index
                    ) {

                        if (
                            index >=
                            this.orderAttributeIds.length -
                            1
                        ) {
                            return;
                        }


                        const copy = [
                            ...this.orderAttributeIds
                        ];


                        [
                            copy[
                                index + 1
                            ],
                            copy[
                                index
                            ]
                        ] = [
                            copy[
                                index
                            ],
                            copy[
                                index + 1
                            ]
                        ];


                        this.orderAttributeIds =
                            copy;
                    },


                    /*
                    |--------------------------------------------------------------------------
                    | MATRIX PAYLOAD
                    |--------------------------------------------------------------------------
                    */

                    matrixPayload() {

                        const result = {};


                        this.selectedEntities
                            .forEach(
                                entity => {

                                    const attributes = {};


                                    this.matrixAttributeIds
                                        .forEach(
                                            attributeId => {

                                                attributes[
                                                        attributeId
                                                    ] =
                                                    entity
                                                    .edit
                                                    .attribute_values[
                                                        attributeId
                                                    ];
                                            }
                                        );


                                    result[
                                        entity.id
                                    ] = {

                                        properties: {

                                            name: entity
                                                .edit
                                                .name,

                                            description: entity
                                                .edit
                                                .description,

                                            entity_type_id: entity
                                                .edit
                                                .entity_type_id,

                                            status: entity
                                                .edit
                                                .status,

                                            visibility: entity
                                                .edit
                                                .visibility,

                                            allow_cloning: entity
                                                .edit
                                                .allow_cloning,
                                        },

                                        attributes: attributes,
                                    };
                                }
                            );


                        return result;
                    }
                };
            }
        </script>

</x-app-layout>
