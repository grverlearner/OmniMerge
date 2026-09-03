<x-app-layout title="Creación masiva" surface="dark">

    <x-slot name="header">Creación masiva</x-slot>

    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>


    @php

        /*
        |--------------------------------------------------------------------------
        | Atributos para Alpine
        |--------------------------------------------------------------------------
        */

        $attributePayload = $attributes
            ->map(
                fn($attribute) => [
                    'id' => (string) $attribute->id,

                    'code' => $attribute->code,

                    'name' => $attribute->name,

                    'data_type' => $attribute->data_type,

                    'data_type_label' => $attribute->data_type_label,

                    'icon' => $attribute->icon ?: $attribute->data_type_icon,

                    'color' => $attribute->color ?: '#6366F1',

                    'image_url' => $attribute->image_url,

                    'allows_multiple' => (bool) $attribute->allows_multiple,

                    'is_required' => (bool) $attribute->is_required,

                    'min_numeric_value' => $attribute->min_numeric_value,

                    'max_numeric_value' => $attribute->max_numeric_value,

                    'groups' => $attribute->groups->pluck('name')->values()->all(),

                    'options' => $attribute->options
                        ->map(
                            fn($option) => [
                                'id' => (string) $option->id,

                                'code' => $option->code,

                                'name' => $option->name,

                                'image_url' => $option->image_url,

                                'icon' => $option->icon ?: '◆',

                                'color' => $option->color ?: '#7C3AED',
                            ],
                        )
                        ->values()
                        ->all(),
                ],
            )
            ->values()
            ->all();

        /*
        |--------------------------------------------------------------------------
        | Tipos
        |--------------------------------------------------------------------------
        */

        $typePayload = $entityTypes
            ->map(
                fn($type) => [
                    'id' => (string) $type->id,

                    'name' => $type->name,

                    'code' => $type->code,

                    'image_url' => $type->image_url,

                    'icon' => $type->icon ?: '◇',

                    'color' => $type->color ?: '#6366F1',
                ],
            )
            ->values()
            ->all();

        /*
        |--------------------------------------------------------------------------
        | Colecciones
        |--------------------------------------------------------------------------
        */

        $collectionPayload = $collections
            ->map(
                fn($collection) => [
                    'id' => (string) $collection->id,

                    'name' => $collection->name,

                    'image_url' => $collection->image_url,

                    'icon' => $collection->icon ?: '▤',

                    'entities_count' => $collection->entities_count,
                ],
            )
            ->values()
            ->all();

        /*
        |--------------------------------------------------------------------------
        | OLD
        |--------------------------------------------------------------------------
        */

        $oldPayload = [
            'batch_name' => old('batch_name', ''),

            'entity_type_id' => old('entity_type_id', ''),

            'status' => old('status', 'ACTIVE'),

            'visibility' => old('visibility', 'PUBLIC'),

            'allow_cloning' => old('allow_cloning', true),

            'duplicate_strategy' => old('duplicate_strategy', 'create'),

            'collection_ids' => old('collection_ids', []),

            'selected_attribute_ids' => old('selected_attribute_ids', []),

            'common_attribute_ids' => old('common_attribute_ids', []),

            'common_attributes' => old('common_attributes', []),

            'rows' => old('rows'),
        ];
    @endphp


    {{--
        El motor sigue siendo el mismo `bulkEntityBuilder`: llena filas, pega
        de una hoja, empareja imágenes por su nombre de archivo y guarda un
        borrador en el navegador. Lo único que se le añadió desde el marcado
        es `rowView`, que decide si la lista se ve como tabla o como fichas.
    --}}

    <form method="POST" action="{{ route('entities.bulk.store') }}" enctype="multipart/form-data"
        x-data="Object.assign(bulkEntityBuilder({

            attributes: @js($attributePayload),

            entityTypes: @js($typePayload),

            collections: @js($collectionPayload),

            template: @js($templatePayload),

            old: @js($oldPayload),

            existingNames: @js($existingEntityNames),

            createUrl: @js(route('entities.bulk.create'))
        }), { rowView: 'table' })" x-init="init()" @submit="prepareSubmit()" class="space-y-4">

        @csrf

        @include('entities.bulk.partials.body')

    </form>


    {{-- ========================================================= --}}
    {{-- ALPINE --}}
    {{-- ========================================================= --}}

    <script>
        function bulkEntityBuilder(
            config
        ) {

            const draftKey =
                'omnimerge.entities.bulk.draft';


            return {

                /*
                |--------------------------------------------------------------------------
                | Recursos
                |--------------------------------------------------------------------------
                */

                attributes: config.attributes ??
                    [],

                entityTypes: config.entityTypes ??
                    [],

                collections: config.collections ??
                    [],


                /*
                |--------------------------------------------------------------------------
                | Configuración
                |--------------------------------------------------------------------------
                */

                batchName: '',

                entityTypeId: '',

                status: 'ACTIVE',

                visibility: 'PUBLIC',

                allowCloning: true,

                duplicateStrategy: 'create',

                collectionIds: [],


                /*
                |--------------------------------------------------------------------------
                | Características
                |--------------------------------------------------------------------------
                */

                selectedAttributeIds: [],

                commonAttributeIds: [],

                commonValues: {},

                attributeSearch: '',


                /*
                |--------------------------------------------------------------------------
                | Filas
                |--------------------------------------------------------------------------
                */

                rows: [],


                /*
                |--------------------------------------------------------------------------
                | Bulk
                |--------------------------------------------------------------------------
                */

                bulkAttributeId: '',

                bulkValue: '',


                /*
                |--------------------------------------------------------------------------
                | Import
                |--------------------------------------------------------------------------
                */

                submitting: false,

                pasteOpen: false,

                pasteText: '',


                /*
                |--------------------------------------------------------------------------
                | Existentes
                |--------------------------------------------------------------------------
                */

                existingNames: new Set(
                    (
                        config.existingNames ??
                        []
                    )
                    .map(
                        value =>
                        thisNormalize(
                            value
                        )
                    )
                ),


                /*
                |--------------------------------------------------------------------------
                | INIT
                |--------------------------------------------------------------------------
                */

                init() {

                    /*
                    |--------------------------------------------------------------------------
                    | OLD de Laravel tiene prioridad
                    |--------------------------------------------------------------------------
                    */

                    if (
                        config.old &&
                        config.old.rows
                    ) {

                        this.restoreOld(
                            config.old
                        );

                        this.registerAutoDraft();

                        return;
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Plantilla
                    |--------------------------------------------------------------------------
                    */

                    if (
                        config.template
                    ) {

                        this.applyTemplate(
                            config.template
                        );

                        this.registerAutoDraft();

                        return;
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Borrador local
                    |--------------------------------------------------------------------------
                    */

                    /*
                     * Si hay un borrador, se guarda A UN LADO para ofrecerlo,
                     * no se aplica. Un lote nuevo empieza vacio siempre: que
                     * se aplicara solo era el motivo de que al entrar a crear
                     * un lote aparecieran las entidades del lote anterior, que
                     * ya estaban creadas.
                     */
                    const draft =
                        this.readDraft();


                    if (draft) {

                        this.pendingDraft =
                            draft;
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Nuevo lote
                    |--------------------------------------------------------------------------
                    */

                    this.addRows(
                        5
                    );


                    this.registerAutoDraft();
                },


                /*
                |--------------------------------------------------------------------------
                | GETTERS
                |--------------------------------------------------------------------------
                */

                get selectedAttributes() {

                    return this.attributes
                        .filter(
                            attribute =>
                            this
                            .selectedAttributeIds
                            .includes(
                                String(
                                    attribute.id
                                )
                            )
                        );
                },


                get commonAttributes() {

                    return this
                        .selectedAttributes
                        .filter(
                            attribute =>
                            this
                            .commonAttributeIds
                            .includes(
                                String(
                                    attribute.id
                                )
                            )
                        );
                },


                get individualAttributes() {

                    return this
                        .selectedAttributes
                        .filter(
                            attribute =>
                            !this
                            .commonAttributeIds
                            .includes(
                                String(
                                    attribute.id
                                )
                            )
                        );
                },


                get filteredAttributes() {

                    const search =
                        this
                        .attributeSearch
                        .trim()
                        .toLowerCase();


                    if (!search) {
                        return this.attributes;
                    }


                    return this.attributes
                        .filter(
                            attribute => {

                                const text =
                                    `
                                        ${attribute.name}
                                        ${attribute.code}
                                        ${attribute.data_type_label}
                                        ${(attribute.groups ?? []).join(' ')}
                                    `
                                    .toLowerCase();


                                return text.includes(
                                    search
                                );
                            }
                        );
                },


                get selectedRowsCount() {

                    return this.rows
                        .filter(
                            row =>
                            row.selected
                        )
                        .length;
                },


                get readyCount() {

                    return this.rows
                        .filter(
                            row =>
                            row
                            .name
                            .trim() !==
                            ''
                        )
                        .length;
                },


                get rowsWithoutImage() {

                    return this.rows
                        .filter(
                            row =>
                            row
                            .name
                            .trim() !==
                            '' &&
                            !row.imagePreview &&
                            !row.bulkImagePreview
                        )
                        .length;
                },


                get existingNameCount() {

                    return this.rows
                        .filter(
                            row =>
                            this.isExistingName(
                                row.name
                            )
                        )
                        .length;
                },


                get importWarningCount() {

                    return this.rows
                        .reduce(
                            (
                                total,
                                row
                            ) =>
                            total +
                            (
                                row.importWarnings
                                ?.length ??
                                0
                            ),

                            0
                        );
                },


                get bulkAttribute() {

                    if (
                        !this.bulkAttributeId
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
                                this.bulkAttributeId
                            )
                        ) ??
                        null;
                },


                /*
                |--------------------------------------------------------------------------
                | ROW
                |--------------------------------------------------------------------------
                */

                uid() {

                    return 'r_' +
                        Date.now()
                        .toString(36) +
                        '_' +
                        Math.random()
                        .toString(36)
                        .slice(
                            2,
                            9
                        );
                },


                newRow(
                    seed = {}
                ) {

                    const attributes =
                        JSON.parse(
                            JSON.stringify(
                                seed.attributes ??
                                {}
                            )
                        );


                    const row = {

                        key: seed.key ??
                            this.uid(),

                        selected: false,

                        name: seed.name ??
                            '',

                        description: seed.description ??
                            '',

                        entity_type_id: String(
                            seed.entity_type_id ??
                            ''
                        ),

                        attributes: attributes,

                        imagePreview: null,

                        bulkImagePreview: null,

                        bulkImageName: null,

                        importWarnings: seed.importWarnings ??
                            [],
                    };


                    this.ensureRowValues(
                        row
                    );


                    return row;
                },


                addRows(
                    amount
                ) {

                    const available =
                        Math.max(
                            0,
                            200 -
                            this.rows.length
                        );


                    const count =
                        Math.min(
                            amount,
                            available
                        );


                    for (
                        let index = 0; index < count; index++
                    ) {

                        this.rows.push(
                            this.newRow()
                        );
                    }
                },


                removeRow(
                    index
                ) {

                    this.rows.splice(
                        index,
                        1
                    );


                    if (
                        this.rows.length === 0
                    ) {

                        this.addRows(
                            1
                        );
                    }
                },


                toggleSelectAll() {

                    const shouldSelect =
                        this.rows.some(
                            row =>
                            !row.selected &&
                            row
                            .name
                            .trim() !==
                            ''
                        );


                    this.rows
                        .forEach(
                            row => {

                                if (
                                    row
                                    .name
                                    .trim() !==
                                    ''
                                ) {

                                    row.selected =
                                        shouldSelect;
                                }
                            }
                        );
                },


                duplicateSelected() {

                    const selected =
                        this.rows
                        .filter(
                            row =>
                            row.selected
                        );


                    if (
                        selected.length === 0
                    ) {
                        return;
                    }


                    const remaining =
                        200 -
                        this.rows.length;


                    selected
                        .slice(
                            0,
                            remaining
                        )
                        .forEach(
                            row => {

                                const clone =
                                    this.newRow({

                                        name: row.name ?
                                            row.name +
                                            ' copia' :
                                            '',

                                        description: row.description,

                                        entity_type_id: row.entity_type_id,

                                        attributes: row.attributes,
                                    });


                                this.rows.push(
                                    clone
                                );
                            }
                        );


                    selected
                        .forEach(
                            row =>
                            row.selected =
                            false
                        );
                },


                removeSelected() {

                    this.rows =
                        this.rows
                        .filter(
                            row =>
                            !row.selected
                        );


                    if (
                        this.rows.length === 0
                    ) {

                        this.addRows(
                            1
                        );
                    }
                },


                /*
                |--------------------------------------------------------------------------
                | ATTRIBUTES
                |--------------------------------------------------------------------------
                */

                isAttributeSelected(
                    id
                ) {

                    return this
                        .selectedAttributeIds
                        .includes(
                            String(
                                id
                            )
                        );
                },


                isCommon(
                    id
                ) {

                    return this
                        .commonAttributeIds
                        .includes(
                            String(
                                id
                            )
                        );
                },


                toggleAttribute(
                    attribute
                ) {

                    const id =
                        String(
                            attribute.id
                        );


                    if (
                        this.isAttributeSelected(
                            id
                        )
                    ) {

                        this.removeAttribute(
                            id
                        );

                    } else {

                        this.selectedAttributeIds
                            .push(
                                id
                            );


                        this.rows
                            .forEach(
                                row =>
                                this.ensureRowAttribute(
                                    row,
                                    attribute
                                )
                            );
                    }
                },


                removeAttribute(
                    id
                ) {

                    id =
                        String(
                            id
                        );


                    this.selectedAttributeIds =
                        this
                        .selectedAttributeIds
                        .filter(
                            value =>
                            value !==
                            id
                        );


                    this.commonAttributeIds =
                        this
                        .commonAttributeIds
                        .filter(
                            value =>
                            value !==
                            id
                        );


                    delete this.commonValues[
                        id
                    ];


                    this.rows
                        .forEach(
                            row => {

                                delete row.attributes[
                                    id
                                ];
                            }
                        );
                },


                setAttributeMode(
                    id,
                    mode
                ) {

                    id =
                        String(
                            id
                        );


                    const attribute =
                        this.attributes
                        .find(
                            item =>
                            String(
                                item.id
                            ) ===
                            id
                        );


                    if (!attribute) {
                        return;
                    }


                    if (
                        mode === 'common'
                    ) {

                        if (
                            !this
                            .commonAttributeIds
                            .includes(
                                id
                            )
                        ) {

                            this.commonAttributeIds
                                .push(
                                    id
                                );
                        }


                        this.ensureCommonValue(
                            attribute
                        );

                        return;
                    }


                    this.commonAttributeIds =
                        this
                        .commonAttributeIds
                        .filter(
                            value =>
                            value !== id
                        );


                    this.rows
                        .forEach(
                            row =>
                            this.ensureRowAttribute(
                                row,
                                attribute
                            )
                        );
                },


                ensureCommonValue(
                    attribute
                ) {

                    const id =
                        String(
                            attribute.id
                        );


                    if (
                        this.commonValues[
                            id
                        ] !==
                        undefined
                    ) {
                        return;
                    }


                    this.commonValues[
                            id
                        ] =
                        attribute.allows_multiple ?
                        [] :
                        '';
                },


                ensureRowAttribute(
                    row,
                    attribute
                ) {

                    const id =
                        String(
                            attribute.id
                        );


                    if (
                        row.attributes[
                            id
                        ] !==
                        undefined
                    ) {
                        return;
                    }


                    row.attributes[
                            id
                        ] =
                        attribute.allows_multiple ?
                        [] :
                        '';
                },


                ensureRowValues(
                    row
                ) {

                    this.selectedAttributes
                        .forEach(
                            attribute =>
                            this.ensureRowAttribute(
                                row,
                                attribute
                            )
                        );
                },


                /*
                |--------------------------------------------------------------------------
                | BULK VALUE
                |--------------------------------------------------------------------------
                */

                resetBulkValue() {

                    if (
                        this.bulkAttribute &&
                        this.bulkAttribute
                        .allows_multiple
                    ) {

                        this.bulkValue = [];

                    } else {

                        this.bulkValue =
                            '';
                    }
                },


                applyBulkValue() {

                    const attribute =
                        this.bulkAttribute;


                    if (!attribute) {

                        alert(
                            'Selecciona un atributo.'
                        );

                        return;
                    }


                    if (
                        this.selectedRowsCount ===
                        0
                    ) {

                        alert(
                            'Selecciona al menos una fila.'
                        );

                        return;
                    }


                    this.rows
                        .filter(
                            row =>
                            row.selected
                        )
                        .forEach(
                            row => {

                                row.attributes[
                                        String(
                                            attribute.id
                                        )
                                    ] =
                                    JSON.parse(
                                        JSON.stringify(
                                            this.bulkValue
                                        )
                                    );
                            }
                        );
                },


                copyDown(
                    attributeId
                ) {

                    attributeId =
                        String(
                            attributeId
                        );


                    const source =
                        this.rows
                        .find(
                            row =>
                            this.hasValue(
                                row.attributes[
                                    attributeId
                                ]
                            )
                        );


                    if (!source) {

                        alert(
                            'No existe ningún valor para copiar.'
                        );

                        return;
                    }


                    const value =
                        JSON.parse(
                            JSON.stringify(
                                source.attributes[
                                    attributeId
                                ]
                            )
                        );


                    this.rows
                        .forEach(
                            row => {

                                row.attributes[
                                        attributeId
                                    ] =
                                    JSON.parse(
                                        JSON.stringify(
                                            value
                                        )
                                    );
                            }
                        );
                },


                hasValue(
                    value
                ) {

                    if (
                        Array.isArray(
                            value
                        )
                    ) {

                        return value.length > 0;
                    }


                    return value !== null &&
                        value !== undefined &&
                        String(
                            value
                        ) !== '';
                },


                /*
                |--------------------------------------------------------------------------
                | IMAGES
                |--------------------------------------------------------------------------
                */

                previewIndividualImage(
                    event,
                    row
                ) {

                    const file =
                        event.target.files[
                            0
                        ];


                    if (!file) {
                        return;
                    }


                    row.imagePreview =
                        URL.createObjectURL(
                            file
                        );
                },


                matchBulkImages(
                    event
                ) {

                    const files =
                        Array.from(
                            event.target.files ??
                            []
                        );


                    /*
                     * Limpiar previews masivos anteriores.
                     */
                    this.rows
                        .forEach(
                            row => {

                                row.bulkImagePreview =
                                    null;

                                row.bulkImageName =
                                    null;
                            }
                        );


                    files
                        .forEach(
                            file => {

                                const fileBase =
                                    file.name
                                    .replace(
                                        /\.[^/.]+$/,
                                        ''
                                    );


                                const normalized =
                                    this.normalize(
                                        fileBase
                                    );


                                const row =
                                    this.rows
                                    .find(
                                        candidate =>

                                        !candidate
                                        .imagePreview

                                        &&

                                        !candidate
                                        .bulkImagePreview

                                        &&

                                        this.normalize(
                                            candidate.name
                                        ) ===
                                        normalized
                                    );


                                if (!row) {
                                    return;
                                }


                                row.bulkImagePreview =
                                    URL.createObjectURL(
                                        file
                                    );


                                row.bulkImageName =
                                    file.name;
                            }
                        );
                },


                /*
                |--------------------------------------------------------------------------
                | TEMPLATE
                |--------------------------------------------------------------------------
                */

                loadTemplate(
                    id
                ) {

                    const url =
                        new URL(
                            config.createUrl,
                            window.location.origin
                        );


                    if (id) {

                        url.searchParams.set(
                            'template_entity',
                            id
                        );
                    }


                    window.location.href =
                        url.toString();
                },


                applyTemplate(
                    template
                ) {

                    this.entityTypeId =
                        String(
                            template.entity_type_id ??
                            ''
                        );


                    this.collectionIds =
                        (
                            template.collection_ids ??
                            []
                        )
                        .map(
                            String
                        );


                    this.selectedAttributeIds =
                        (
                            template.selected_attribute_ids ??
                            []
                        )
                        .map(
                            String
                        );


                    this.commonAttributeIds = [];


                    this.commonValues = {};


                    /*
                     * Cinco filas listas para trabajar,
                     * todas con la estructura y valores
                     * de la Entidad base.
                     */

                    for (
                        let index = 0; index < 5; index++
                    ) {

                        this.rows.push(
                            this.newRow({

                                attributes: template.values ??
                                    {},
                            })
                        );
                    }
                },


                /*
                |--------------------------------------------------------------------------
                | DUPLICATES
                |--------------------------------------------------------------------------
                */

                isExistingName(
                    name
                ) {

                    const normalized =
                        this.normalize(
                            name
                        );


                    if (!normalized) {
                        return false;
                    }


                    return this
                        .existingNames
                        .has(
                            normalized
                        );
                },


                /*
                |--------------------------------------------------------------------------
                | CSV / EXCEL
                |--------------------------------------------------------------------------
                */

                async importCsv(
                    event
                ) {

                    const file =
                        event.target.files[
                            0
                        ];


                    if (!file) {
                        return;
                    }


                    const text =
                        await file.text();


                    this.pasteText =
                        text;


                    this.importPasted();


                    event.target.value =
                        '';
                },


                importPasted() {

                    const text =
                        this
                        .pasteText
                        .trim();


                    if (!text) {
                        return;
                    }


                    const delimiter =
                        text.includes(
                            '\t'
                        ) ?
                        '\t' :
                        ',';


                    const lines =
                        text
                        .split(
                            /\r?\n/
                        )
                        .filter(
                            line =>
                            line.trim() !==
                            ''
                        );


                    if (
                        lines.length === 0
                    ) {
                        return;
                    }


                    let matrix =
                        lines
                        .map(
                            line =>
                            this.parseCsvLine(
                                line,
                                delimiter
                            )
                        );


                    let headers =
                        matrix[
                            0
                        ]
                        .map(
                            value =>
                            this.normalize(
                                value
                            )
                        );


                    const nameHeaders = [
                        'name',
                        'nombre'
                    ];


                    let hasHeader =
                        headers
                        .some(
                            header =>
                            nameHeaders
                            .includes(
                                header
                            )
                        );


                    let dataRows;


                    if (hasHeader) {

                        dataRows =
                            matrix.slice(
                                1
                            );

                    } else {

                        headers = [
                            'nombre',
                            'descripcion'
                        ];


                        dataRows =
                            matrix;
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Mapear encabezados a Atributos
                    |--------------------------------------------------------------------------
                    */

                    const mappedAttributes = {};


                    headers
                        .forEach(
                            (
                                header,
                                index
                            ) => {

                                if (
                                    [
                                        'name',
                                        'nombre',
                                        'description',
                                        'descripcion'
                                    ]
                                    .includes(
                                        header
                                    )
                                ) {
                                    return;
                                }


                                const attribute =
                                    this.attributes
                                    .find(
                                        item =>

                                        this.normalize(
                                            item.name
                                        ) ===
                                        header

                                        ||

                                        this.normalize(
                                            item.code
                                        ) ===
                                        header
                                    );


                                if (!attribute) {
                                    return;
                                }


                                mappedAttributes[
                                        index
                                    ] =
                                    attribute;


                                const id =
                                    String(
                                        attribute.id
                                    );


                                if (
                                    !this
                                    .selectedAttributeIds
                                    .includes(
                                        id
                                    )
                                ) {

                                    this.selectedAttributeIds
                                        .push(
                                            id
                                        );
                                }
                            }
                        );


                    const imported = [];


                    dataRows
                        .slice(
                            0,
                            200
                        )
                        .forEach(
                            values => {

                                const row =
                                    this.newRow();


                                headers
                                    .forEach(
                                        (
                                            header,
                                            index
                                        ) => {

                                            const raw =
                                                values[
                                                    index
                                                ] ??
                                                '';


                                            if (
                                                [
                                                    'name',
                                                    'nombre'
                                                ]
                                                .includes(
                                                    header
                                                )
                                            ) {

                                                row.name =
                                                    raw.trim();

                                                return;
                                            }


                                            if (
                                                [
                                                    'description',
                                                    'descripcion'
                                                ]
                                                .includes(
                                                    header
                                                )
                                            ) {

                                                row.description =
                                                    raw.trim();

                                                return;
                                            }


                                            const attribute =
                                                mappedAttributes[
                                                    index
                                                ];


                                            if (!attribute) {
                                                return;
                                            }


                                            row.attributes[
                                                    String(
                                                        attribute.id
                                                    )
                                                ] =
                                                this.parseAttributeCell(
                                                    attribute,
                                                    raw,
                                                    row
                                                );
                                        }
                                    );


                                imported.push(
                                    row
                                );
                            }
                        );


                    /*
                     * Si solo había filas vacías,
                     * reemplazarlas.
                     */
                    const existingUseful =
                        this.rows.some(
                            row =>
                            row
                            .name
                            .trim() !==
                            ''
                        );


                    if (!existingUseful) {

                        this.rows =
                            imported;

                    } else {

                        const available =
                            Math.max(
                                0,
                                200 -
                                this.rows.length
                            );


                        this.rows.push(
                            ...imported.slice(
                                0,
                                available
                            )
                        );
                    }


                    this.rows
                        .forEach(
                            row =>
                            this.ensureRowValues(
                                row
                            )
                        );


                    this.pasteOpen =
                        false;


                    this.pasteText =
                        '';
                },


                parseAttributeCell(
                    attribute,
                    raw,
                    row
                ) {

                    raw =
                        String(
                            raw ??
                            ''
                        )
                        .trim();


                    if (!raw) {

                        return attribute
                            .allows_multiple ?
                            [] :
                            '';
                    }


                    if (
                        attribute.data_type ===
                        'OPTION'
                    ) {

                        const rawValues =
                            attribute.allows_multiple

                            ?
                            raw
                            .split(
                                /[|;]/
                            )
                            .map(
                                value =>
                                value.trim()
                            )
                            .filter(
                                Boolean
                            )

                            :
                            [
                                raw
                            ];


                        const ids = [];


                        rawValues
                            .forEach(
                                rawValue => {

                                    const normalized =
                                        this.normalize(
                                            rawValue
                                        );


                                    const option =
                                        attribute.options
                                        .find(
                                            item =>

                                            this.normalize(
                                                item.name
                                            ) ===
                                            normalized

                                            ||

                                            this.normalize(
                                                item.code
                                            ) ===
                                            normalized
                                        );


                                    if (option) {

                                        ids.push(
                                            String(
                                                option.id
                                            )
                                        );

                                    } else {

                                        row.importWarnings
                                            .push(
                                                `${attribute.name}: "${rawValue}" no existe en el Catálogo.`
                                            );
                                    }
                                }
                            );


                        return attribute
                            .allows_multiple ?
                            ids :
                            (
                                ids[
                                    0
                                ] ??
                                ''
                            );
                    }


                    if (
                        attribute.data_type ===
                        'BOOLEAN'
                    ) {

                        const normalized =
                            this.normalize(
                                raw
                            );


                        if (
                            [
                                'si',
                                'yes',
                                'true',
                                '1'
                            ]
                            .includes(
                                normalized
                            )
                        ) {

                            return '1';
                        }


                        if (
                            [
                                'no',
                                'false',
                                '0'
                            ]
                            .includes(
                                normalized
                            )
                        ) {

                            return '0';
                        }


                        row.importWarnings
                            .push(
                                `${attribute.name}: "${raw}" no es Sí/No.`
                            );


                        return '';
                    }


                    return raw;
                },


                parseCsvLine(
                    line,
                    delimiter
                ) {

                    const result = [];

                    let current = '';

                    let quoted =
                        false;


                    for (
                        let index = 0; index < line.length; index++
                    ) {

                        const char =
                            line[
                                index
                            ];


                        if (
                            char === '"'
                        ) {

                            if (
                                quoted &&
                                line[
                                    index + 1
                                ] === '"'
                            ) {

                                current +=
                                    '"';

                                index++;

                            } else {

                                quoted = !quoted;
                            }


                            continue;
                        }


                        if (
                            char === delimiter &&
                            !quoted
                        ) {

                            result.push(
                                current
                            );

                            current =
                                '';

                            continue;
                        }


                        current +=
                            char;
                    }


                    result.push(
                        current
                    );


                    return result;
                },


                /*
                |--------------------------------------------------------------------------
                | DRAFT
                |--------------------------------------------------------------------------
                */

                registerAutoDraft() {

                    window.addEventListener(
                        'beforeunload',
                        () => {

                            /*
                             * Enviar el formulario tambien dispara este
                             * evento. Sin esta guarda, el borrador que se
                             * acaba de borrar al enviar se volveria a
                             * escribir con el lote que se esta creando.
                             */
                            if (this.submitting) {
                                return;
                            }

                            /*
                             * Y tampoco se guarda un lote vacio: entrar y
                             * recargar sin escribir nada machacaria con diez
                             * filas en blanco el borrador que se ofrece
                             * recuperar arriba.
                             */
                            if (
                                this.readyCount === 0
                                &&
                                ! this.batchName.trim()
                            ) {
                                return;
                            }

                            this.saveDraft(
                                false
                            );
                        }
                    );
                },


                draftData() {

                    return {

                        batchName: this.batchName,

                        entityTypeId: this.entityTypeId,

                        status: this.status,

                        visibility: this.visibility,

                        allowCloning: this.allowCloning,

                        duplicateStrategy: this.duplicateStrategy,

                        collectionIds: this.collectionIds,

                        selectedAttributeIds: this.selectedAttributeIds,

                        commonAttributeIds: this.commonAttributeIds,

                        commonValues: this.commonValues,

                        rows: this.rows
                            .map(
                                row => ({

                                    key: row.key,

                                    name: row.name,

                                    description: row.description,

                                    entity_type_id: row.entity_type_id,

                                    attributes: row.attributes,

                                    importWarnings: row.importWarnings,
                                })
                            ),
                    };
                },


                saveDraft(
                    notify = true
                ) {

                    localStorage.setItem(
                        draftKey,

                        JSON.stringify(
                            this.draftData()
                        )
                    );


                    /*
                     * Sin `alert`: la pantalla ya enseña cuando se guardó.
                     * Un cuadro del sistema para decir «hecho» interrumpe
                     * justo a quien estaba escribiendo deprisa.
                     */
                    if (notify) {

                        this.draftSavedAt =
                            new Date();
                    }
                },


                readDraft() {

                    try {

                        const raw =
                            localStorage.getItem(
                                draftKey
                            );


                        return raw ?
                            JSON.parse(
                                raw
                            ) :
                            null;

                    } catch (
                        error
                    ) {

                        return null;
                    }
                },


                /* Recuperar el lote a medias que se ofrecio al entrar */
                resumeDraft() {

                    if (! this.pendingDraft) {
                        return;
                    }

                    this.restoreDraft(
                        this.pendingDraft
                    );

                    this.pendingDraft =
                        null;
                },


                restoreDraft(
                    draft
                ) {

                    this.batchName =
                        draft.batchName ??
                        '';

                    this.entityTypeId =
                        String(
                            draft.entityTypeId ??
                            ''
                        );

                    this.status =
                        draft.status ??
                        'ACTIVE';

                    this.visibility =
                        draft.visibility ??
                        'PUBLIC';

                    this.allowCloning =
                        draft.allowCloning ??
                        true;

                    this.duplicateStrategy =
                        draft.duplicateStrategy ??
                        'create';

                    this.collectionIds =
                        (
                            draft.collectionIds ??
                            []
                        )
                        .map(
                            String
                        );

                    this.selectedAttributeIds =
                        (
                            draft.selectedAttributeIds ??
                            []
                        )
                        .map(
                            String
                        );

                    this.commonAttributeIds =
                        (
                            draft.commonAttributeIds ??
                            []
                        )
                        .map(
                            String
                        );

                    this.commonValues =
                        draft.commonValues ??
                        {};


                    this.rows =
                        (
                            draft.rows ??
                            []
                        )
                        .map(
                            row =>
                            this.newRow(
                                row
                            )
                        );


                    if (
                        this.rows.length === 0
                    ) {

                        this.addRows(
                            5
                        );
                    }
                },


                clearDraft() {

                    localStorage.removeItem(
                        draftKey
                    );


                    this.pendingDraft =
                        null;

                    this.draftSavedAt =
                        null;
                },


                /*
                |--------------------------------------------------------------------------
                | OLD
                |--------------------------------------------------------------------------
                */

                restoreOld(
                    old
                ) {

                    this.batchName =
                        old.batch_name ??
                        '';

                    this.entityTypeId =
                        String(
                            old.entity_type_id ??
                            ''
                        );

                    this.status =
                        old.status ??
                        'ACTIVE';

                    this.visibility =
                        old.visibility ??
                        'PUBLIC';

                    this.allowCloning = !!old.allow_cloning;

                    this.duplicateStrategy =
                        old.duplicate_strategy ??
                        'create';

                    this.collectionIds =
                        (
                            old.collection_ids ??
                            []
                        )
                        .map(
                            String
                        );

                    this.selectedAttributeIds =
                        (
                            old.selected_attribute_ids ??
                            []
                        )
                        .map(
                            String
                        );

                    this.commonAttributeIds =
                        (
                            old.common_attribute_ids ??
                            []
                        )
                        .map(
                            String
                        );

                    this.commonValues =
                        old.common_attributes ??
                        {};


                    this.rows =
                        Object
                        .entries(
                            old.rows ??
                            {}
                        )
                        .map(
                            (
                                [
                                    key,
                                    row
                                ]
                            ) =>
                            this.newRow({

                                key: key,

                                name: row.name ??
                                    '',

                                description: row.description ??
                                    '',

                                entity_type_id: row.entity_type_id ??
                                    '',

                                attributes: row.attributes ??
                                    {},
                            })
                        );


                    if (
                        this.rows.length === 0
                    ) {

                        this.addRows(
                            5
                        );
                    }
                },


                /*
                |--------------------------------------------------------------------------
                | SUBMIT
                |--------------------------------------------------------------------------
                */

                prepareSubmit(
                    event
                ) {

                    if (
                        this.readyCount === 0
                    ) {

                        event.preventDefault();


                        alert(
                            'Debes ingresar al menos una Entidad con nombre.'
                        );


                        return;
                    }


                    /*
                     * Al enviar se BORRA el borrador, no se guarda.
                     *
                     * Si el servidor rechaza el lote, Laravel devuelve las
                     * filas en `old` y el propio init() las repone: el
                     * borrador no hace falta como red de seguridad. Guardarlo
                     * aqui era lo que hacia que el siguiente lote naciera con
                     * las entidades del anterior, ya creadas.
                     */
                    this.submitting =
                        true;

                    localStorage.removeItem(
                        draftKey
                    );
                },


                /*
                |--------------------------------------------------------------------------
                | NORMALIZE
                |--------------------------------------------------------------------------
                */

                normalize(
                    value
                ) {

                    return thisNormalize(
                        value
                    );
                }
            };


            /*
            |--------------------------------------------------------------------------
            | Helper fuera del objeto
            |--------------------------------------------------------------------------
            */

            function thisNormalize(
                value
            ) {

                return String(
                        value ??
                        ''
                    )
                    .normalize(
                        'NFD'
                    )
                    .replace(
                        /[\u0300-\u036f]/g,
                        ''
                    )
                    .toLowerCase()
                    .replace(
                        /[^a-z0-9]+/g,
                        '-'
                    )
                    .replace(
                        /^-+|-+$/g,
                        ''
                    );
            }
        }
    </script>

</x-app-layout>
