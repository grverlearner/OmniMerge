@php

    $editingEntity = isset($entity) && $entity->exists;

    $existingAssignments = $editingEntity ? $entity->entityAttributes->keyBy('attribute_id') : collect();

    $selectedAttributeIds = old(
        'selected_attribute_ids',
        $editingEntity ? $entity->entityAttributes->pluck('attribute_id')->map(fn($id) => (string) $id)->all() : [],
    );

    $selectedAttributeIds = array_map('strval', $selectedAttributeIds);

    $contextPayload = $contextPayload ?? [
        'rules' => [],

        'option_relations' => [],
    ];

    $attributeMeta = $attributes
        ->mapWithKeys(
            fn($attribute) => [
                (string) $attribute->id => [
                    'multiple' => (bool) $attribute->allows_multiple,

                    'type' => $attribute->data_type,
                ],
            ],
        )
        ->all();

    $contextInitialSelections = [];

    foreach ($attributes as $attribute) {
        if ($attribute->data_type !== 'OPTION') {
            continue;
        }

        $assignment = $existingAssignments->get($attribute->id);

        $existing =
            $assignment?->values
                ?->pluck('attribute_option_id')
                ->filter()
                ->map(fn($id) => (string) $id)
                ->values()
                ->all() ?? [];

        $old = old("attributes.{$attribute->id}");

        if ($old !== null) {
            $existing = collect((array) $old)->map('strval')->values()->all();
        }

        $contextInitialSelections[(string) $attribute->id] = $attribute->allows_multiple
            ? $existing
            : $existing[0] ?? '';
    }
@endphp


<div x-data="{

    selectedAttributes: @js($selectedAttributeIds),
    context: @js($contextPayload),
    attributeMeta: @js($attributeMeta),
    contextSelections: @js($contextInitialSelections),
    attributeSearch: '',
    attributeType: 'ALL',
    attributeGroup: 'ALL',
    optionSearch: {},
    isSelected(id) {

        return this
            .selectedAttributes
            .includes(
                String(id)
            );
    },

    addAttribute(id) {
        id = String(id);

        if (
            !this.selectedAttributes.includes(id)
        ) {

            this.selectedAttributes.push(
                id
            );
        }
    },


    removeAttribute(id) {

        id =
            String(id);


        this.selectedAttributes =
            this
            .selectedAttributes
            .filter(
                value =>
                value !==
                id
            );


        if (
            this.attributeMeta[
                id
            ]
            ?.type ===
            'OPTION'
        ) {

            this.contextSelections[
                    id
                ] =
                this.attributeMeta[
                    id
                ]
                ?.multiple ? [] :
                '';
        }
    },

    selectedContextOptionIds() {

        const result = [];


        for (
            const [
                attributeId,
                value
            ] of Object.entries(
                this.contextSelections
            )
        ) {

            if (
                !this.isSelected(
                    attributeId
                )
            ) {
                continue;
            }


            if (
                Array.isArray(
                    value
                )
            ) {

                for (
                    const optionId of value
                ) {

                    if (
                        optionId !==
                        null &&
                        optionId !==
                        ''
                    ) {

                        result.push(
                            String(
                                optionId
                            )
                        );
                    }
                } else if (
                    value !== null &&
                    value !== ''
                ) {

                    result.push(
                        String(
                            value
                        )
                    );
                }
            }


            return [
                ...new Set(
                    result
                )
            ];
        },


        conditionMatches(
                condition
            ) {

                const attributeId =
                    String(
                        condition
                        .source_attribute_id
                    );


                const selected =
                    this.selectedContextOptionIds();


                const optionId =
                    condition
                    .source_option_id !==
                    null &&
                    condition
                    .source_option_id !==
                    undefined ?
                    String(
                        condition
                        .source_option_id
                    ) :
                    null;


                const sourceValue =
                    this.contextSelections[
                        attributeId
                    ];


                const sourceHasValue =
                    this.isSelected(
                        attributeId
                    ) &&
                    (
                        Array.isArray(
                            sourceValue
                        ) ?
                        sourceValue.length > 0 :
                        (
                            sourceValue !== null &&
                            sourceValue !== ''
                        )
                    );


                switch (
                    condition.operator
                ) {

                    case 'EQUALS':

                        return optionId !==
                            null &&
                            selected.includes(
                                optionId
                            );


                    case 'NOT_EQUALS':

                        return !(
                            optionId !==
                            null &&
                            selected.includes(
                                optionId
                            )
                        );


                    case 'EXISTS':

                        return sourceHasValue;


                    case 'NOT_EXISTS':

                        return !sourceHasValue;


                    default:

                        return false;
                }
            },


            ruleMatches(
                rule
            ) {

                const results =
                    (
                        rule.conditions || []
                    )
                    .map(
                        condition =>
                        this.conditionMatches(
                            condition
                        )
                    );


                if (
                    results.length ===
                    0
                ) {
                    return false;
                }


                if (
                    rule.match_mode ===
                    'ANY'
                ) {

                    return results.some(
                        Boolean
                    );
                }


                return results.every(
                    Boolean
                );
            },


            rulesFor(
                attributeId
            ) {

                return (
                        this.context.rules || []
                    )
                    .filter(
                        rule =>
                        String(
                            rule.target_attribute_id
                        ) ===
                        String(
                            attributeId
                        )
                    );
            },


            hasContextRules(
                attributeId
            ) {

                return this.rulesFor(
                        attributeId
                    ).length >
                    0;
            },


            isContextRequired(
                attributeId
            ) {

                return this
                    .rulesFor(
                        attributeId
                    )
                    .filter(
                        rule =>
                        rule.action ===
                        'REQUIRE'
                    )
                    .some(
                        rule =>
                        this.ruleMatches(
                            rule
                        )
                    );
            },


            isContextVisible(
                attributeId
            ) {

                const rules =
                    this.rulesFor(
                        attributeId
                    );


                if (
                    rules.length ===
                    0
                ) {
                    return true;
                }


                /*
                 * HIDE gana.
                 */
                const hide =
                    rules
                    .filter(
                        rule =>
                        rule.action ===
                        'HIDE'
                    )
                    .some(
                        rule =>
                        this.ruleMatches(
                            rule
                        )
                    );


                if (hide) {
                    return false;
                }


                /*
                 * REQUIRE obliga a mostrar.
                 */
                const required =
                    rules
                    .filter(
                        rule =>
                        rule.action ===
                        'REQUIRE'
                    )
                    .some(
                        rule =>
                        this.ruleMatches(
                            rule
                        )
                    );


                if (required) {
                    return true;
                }


                const showRules =
                    rules.filter(
                        rule =>
                        rule.action ===
                        'SHOW'
                    );


                if (
                    showRules.length >
                    0
                ) {

                    return showRules.some(
                        rule =>
                        this.ruleMatches(
                            rule
                        )
                    );
                }


                return true;
            },


            isOptionAllowed(
                attributeId,
                optionId
            ) {

                const selected =
                    this.selectedContextOptionIds();


                const relevant =
                    (
                        this.context
                        .option_relations || []
                    )
                    .filter(
                        relation =>
                        String(
                            relation
                            .target_attribute_id
                        ) ===
                        String(
                            attributeId
                        ) &&
                        selected.includes(
                            String(
                                relation
                                .source_option_id
                            )
                        )
                    );


                if (
                    relevant.length ===
                    0
                ) {
                    return true;
                }


                const allows =
                    relevant.filter(
                        relation =>
                        relation
                        .relationship_type ===
                        'ALLOWS'
                    );


                const blocks =
                    relevant.filter(
                        relation =>
                        relation
                        .relationship_type ===
                        'BLOCKS'
                    );


                if (
                    blocks.some(
                        relation =>
                        String(
                            relation
                            .target_option_id
                        ) ===
                        String(
                            optionId
                        )
                    )
                ) {
                    return false;
                }


                if (
                    allows.length >
                    0
                ) {

                    return allows.some(
                        relation =>
                        String(
                            relation
                            .target_option_id
                        ) ===
                        String(
                            optionId
                        )
                    );
                }


                return true;
            },


            matchesAttribute(
                name,
                code,
                dataType,
                groups
            ) {

                const search =
                    this
                    .attributeSearch
                    .toLowerCase();


                const text =
                    `${name} ${code}`
                    .toLowerCase();


                const matchesSearch = !search ||
                    text.includes(
                        search
                    );


                const matchesType =
                    this.attributeType === 'ALL' ||
                    this.attributeType === dataType;


                const matchesGroup =
                    this.attributeGroup === 'ALL' ||
                    groups.includes(
                        this.attributeGroup
                    );


                return matchesSearch &&
                    matchesType &&
                    matchesGroup;
            },


            matchesOption(
                attributeId,
                name,
                code
            ) {

                const search =
                    (
                        this.optionSearch[
                            attributeId
                        ] ||
                        ''
                    )
                    .toLowerCase();


                if (!search) {
                    return true;
                }


                return `${name} ${code}`
                    .toLowerCase()
                    .includes(
                        search
                    );
            }
    }">

    {{-- ========================================================= --}}
    {{-- INPUTS DE ATRIBUTOS SELECCIONADOS --}}
    {{-- ========================================================= --}}

    <template x-for="
            attributeId
            in selectedAttributes
        "
        :key="`selected-${attributeId}`">

        <input type="hidden" name="selected_attribute_ids[]" :value="attributeId">

    </template>


    {{-- ========================================================= --}}
    {{-- CABECERA --}}
    {{-- ========================================================= --}}

    <div
        class="
            flex
            flex-col
            justify-between
            gap-4
            sm:flex-row
            sm:items-end
        ">

        <div>

            <p
                class="
                    text-xs
                    font-black
                    uppercase
                    tracking-[0.16em]
                    text-indigo-600
                ">
                Características
            </p>


            <h3
                class="
                    mt-2
                    text-xl
                    font-black
                    text-slate-900
                ">
                Construye la entidad
            </h3>


            <p
                class="
                    mt-2
                    max-w-2xl
                    text-sm
                    leading-6
                    text-slate-500
                ">
                Añade únicamente los atributos que realmente
                pertenecen a esta entidad. Un atributo opcional
                puede permanecer sin valor.
            </p>

        </div>


        <span
            class="
                rounded-full
                bg-indigo-50
                px-3
                py-1.5
                text-xs
                font-black
                text-indigo-700
            ">
            <span x-text="
                    selectedAttributes.length
                "></span>
            seleccionados
        </span>

    </div>


    {{-- ========================================================= --}}
    {{-- ATRIBUTOS SELECCIONADOS --}}
    {{-- ========================================================= --}}

    <div class="
            mt-6
            space-y-4
        ">

        @foreach ($attributes as $attribute)

            @php

                $assignment = $existingAssignments->get($attribute->id);

                $values = $assignment?->values ?? collect();

                $selectedOptionIds = $values
                    ->pluck('attribute_option_id')
                    ->filter()
                    ->map(fn($id) => (string) $id)
                    ->all();

                $firstValue = $values->first();

                $inputName = "attributes[{$attribute->id}]";

                $oldValue = old("attributes.{$attribute->id}");

                if ($oldValue !== null) {
                    $selectedOptionIds = array_map('strval', (array) $oldValue);
                }
            @endphp


            <section x-cloak
                x-show="
                    isSelected(
                        '{{ $attribute->id }}'
                    )
                    &&
                    isContextVisible(
                        '{{ $attribute->id }}'
                    )
                "
                class="
                    overflow-hidden
                    rounded-2xl
                    border
                    border-slate-200
                    bg-white
                ">

                {{-- ============================================= --}}
                {{-- HEADER ATRIBUTO --}}
                {{-- ============================================= --}}

                <div
                    class="
                        flex
                        flex-col
                        gap-4
                        border-b
                        border-slate-100
                        bg-slate-50
                        p-4
                        sm:flex-row
                        sm:items-center
                    ">

                    <div
                        class="
                            h-14
                            w-14
                            shrink-0
                            overflow-hidden
                            rounded-xl
                            bg-white
                        ">

                        @if ($attribute->image_url)
                            <img src="{{ $attribute->image_url }}" alt="{{ $attribute->name }}"
                                class="
                                    h-full
                                    w-full
                                    object-cover
                                ">
                        @else
                            <div class="
                                    flex
                                    h-full
                                    items-center
                                    justify-center
                                    text-xl
                                    font-black
                                "
                                style="
                                    background-color:
                                        {{ $attribute->color ?? '#6366F1' }}20;

                                    color:
                                        {{ $attribute->color ?? '#6366F1' }};
                                ">
                                {{ $attribute->icon ?: $attribute->data_type_icon }}
                            </div>
                        @endif

                    </div>


                    <div class="min-w-0 flex-1">

                        <div
                            class="
                                flex
                                flex-wrap
                                items-center
                                gap-2
                            ">

                            <h4
                                class="
                                    font-black
                                    text-slate-900
                                ">
                                {{ $attribute->name }}
                            </h4>


                            <span
                                class="
                                    rounded-full
                                    bg-indigo-100
                                    px-2
                                    py-1
                                    text-[9px]
                                    font-black
                                    text-indigo-700
                                ">
                                {{ $attribute->data_type_label }}
                                <template
                                    x-if="
        hasContextRules(
            '{{ $attribute->id }}'
        )
    ">

                                    <span
                                        class="
            rounded-full
            bg-cyan-100
            px-2
            py-1
            text-[9px]
            font-black
            text-cyan-700
        ">
                                        CONTEXTUAL
                                    </span>

                                </template>


                                <template
                                    x-if="
        isContextRequired(
            '{{ $attribute->id }}'
        )
    ">

                                    <span
                                        class="
            rounded-full
            bg-amber-100
            px-2
            py-1
            text-[9px]
            font-black
            text-amber-700
        ">
                                        REQUERIDO AHORA
                                    </span>

                                </template>
                            </span>


                            @if ($attribute->allows_multiple)
                                <span
                                    class="
                                        rounded-full
                                        bg-violet-100
                                        px-2
                                        py-1
                                        text-[9px]
                                        font-black
                                        text-violet-700
                                    ">
                                    Múltiple
                                </span>
                            @endif


                            @if ($attribute->is_required)
                                <span
                                    class="
                                        rounded-full
                                        bg-red-100
                                        px-2
                                        py-1
                                        text-[9px]
                                        font-black
                                        text-red-700
                                    ">
                                    Requiere valor
                                </span>
                            @endif

                        </div>


                        <p
                            class="
                                mt-1
                                font-mono
                                text-[10px]
                                text-slate-400
                            ">
                            {{ $attribute->code }}
                        </p>


                        @if ($attribute->help_text)
                            <p
                                class="
                                    mt-2
                                    text-xs
                                    leading-5
                                    text-slate-500
                                ">
                                {{ $attribute->help_text }}
                            </p>
                        @endif

                    </div>


                    <button type="button"
                        @click="
                            removeAttribute(
                                '{{ $attribute->id }}'
                            )
                        "
                        class="
                            rounded-lg
                            border
                            border-red-100
                            bg-white
                            px-3
                            py-2
                            text-xs
                            font-bold
                            text-red-600
                            hover:bg-red-50
                        ">
                        Quitar
                    </button>

                </div>


                {{-- ============================================= --}}
                {{-- VALOR --}}
                {{-- ============================================= --}}

                <div class="p-5">

                    {{-- ========================================= --}}
                    {{-- OPTION / CATÁLOGO --}}
                    {{-- ========================================= --}}

                    @if ($attribute->data_type === 'OPTION')
                        <div>

                            <div
                                class="
                                    flex
                                    flex-col
                                    justify-between
                                    gap-3
                                    sm:flex-row
                                    sm:items-center
                                ">

                                <div>

                                    <p
                                        class="
                                            text-sm
                                            font-black
                                            text-slate-800
                                        ">
                                        Elementos del Catálogo
                                    </p>


                                    <p
                                        class="
                                            mt-1
                                            text-xs
                                            text-slate-500
                                        ">
                                        {{ $attribute->allows_multiple ? 'Puedes seleccionar varios elementos.' : 'Selecciona un elemento.' }}
                                    </p>

                                </div>


                                <input type="text"
                                    x-model="
                                        optionSearch[
                                            '{{ $attribute->id }}'
                                        ]
                                    "
                                    placeholder="Buscar elemento..."
                                    class="
                                        rounded-xl
                                        border-slate-300
                                        bg-white
                                        text-sm
                                        text-slate-900
                                        placeholder:text-slate-400
                                    ">

                            </div>


                            @if ($attribute->options->isNotEmpty())
                                <div
                                    class="
                                        mt-4
                                        grid
                                        max-h-[430px]
                                        gap-3
                                        overflow-y-auto
                                        pr-1
                                        grid-cols-2
                                        sm:grid-cols-3
                                        lg:grid-cols-4
                                        xl:grid-cols-5
                                    ">

                                    @foreach ($attribute->options as $option)
                                        @php

                                            $checked = in_array((string) $option->id, $selectedOptionIds);
                                        @endphp


                                        <label
                                            x-show="
                                                matchesOption(
                                                    '{{ $attribute->id }}',
                                                    @js($option->name),
                                                    @js($option->code)
                                                )
                                                &&
                                                isOptionAllowed(
                                                    '{{ $attribute->id }}',
                                                    '{{ $option->id }}'
                                                )
                                            "
                                            class="
                                                group
                                                relative
                                                cursor-pointer
                                                overflow-hidden
                                                rounded-xl
                                                border-2
                                                border-slate-200
                                                bg-white
                                                transition
                                                hover:border-indigo-300
                                                has-[:checked]:border-indigo-500
                                                has-[:checked]:bg-indigo-50
                                                has-[:checked]:ring-2
                                                has-[:checked]:ring-indigo-100
                                            ">

                                            <input type="{{ $attribute->allows_multiple ? 'checkbox' : 'radio' }}"
                                                name="{{ $attribute->allows_multiple ? $inputName . '[]' : $inputName }}"
                                                value="{{ $option->id }}"
                                                x-model="
                                                    contextSelections[
                                                        '{{ $attribute->id }}'
                                                    ]
                                                "
                                                @checked($checked)
                                                class="
                                                    absolute
                                                    right-2
                                                    top-2
                                                    z-10
                                                    border-slate-300
                                                    text-indigo-600
                                                ">


                                            <div
                                                class="
                                                    aspect-square
                                                    bg-slate-100
                                                ">

                                                @if ($option->image_url)
                                                    <img src="{{ $option->image_url }}" alt="{{ $option->name }}"
                                                        class="
                                                            h-full
                                                            w-full
                                                            object-cover
                                                        ">
                                                @else
                                                    <div class="
                                                            flex
                                                            h-full
                                                            items-center
                                                            justify-center
                                                            text-3xl
                                                        "
                                                        style="
                                                            background-color:
                                                                {{ $option->color ?? '#6366F1' }}20;

                                                            color:
                                                                {{ $option->color ?? '#6366F1' }};
                                                        ">
                                                        {{ $option->icon ?: '◆' }}
                                                    </div>
                                                @endif

                                            </div>


                                            <div class="p-3">

                                                <p
                                                    class="
                                                        truncate
                                                        text-xs
                                                        font-black
                                                        text-slate-800
                                                    ">
                                                    {{ $option->name }}
                                                </p>


                                                <p
                                                    class="
                                                        mt-1
                                                        truncate
                                                        font-mono
                                                        text-[9px]
                                                        text-slate-400
                                                    ">
                                                    {{ $option->code }}
                                                </p>


                                                @if ($option->parent)
                                                    <p
                                                        class="
                                                            mt-1
                                                            truncate
                                                            text-[9px]
                                                            text-slate-400
                                                        ">
                                                        ↳ {{ $option->parent->name }}
                                                    </p>
                                                @endif

                                            </div>

                                        </label>
                                    @endforeach

                                </div>
                            @else
                                <div
                                    class="
                                        mt-4
                                        rounded-xl
                                        border
                                        border-dashed
                                        border-slate-300
                                        bg-slate-50
                                        p-5
                                        text-sm
                                        text-slate-500
                                    ">
                                    Este atributo todavía no tiene
                                    elementos en su Catálogo.
                                </div>
                            @endif

                        </div>


                        {{-- ========================================= --}}
                        {{-- BOOLEAN --}}
                        {{-- ========================================= --}}
                    @elseif ($attribute->data_type === 'BOOLEAN')
                        @php

                            $booleanValue = old(
                                "attributes.{$attribute->id}",
                                $firstValue?->boolean_value === null ? '' : ($firstValue->boolean_value ? '1' : '0'),
                            );

                        @endphp


                        <div
                            class="
                                grid
                                gap-3
                                sm:grid-cols-3
                            ">

                            @foreach ([
        '' => 'Sin definir',

        '1' => 'Sí',

        '0' => 'No',
    ] as $value => $label)
                                <label
                                    class="
                                        cursor-pointer
                                        rounded-xl
                                        border
                                        border-slate-200
                                        p-4
                                        text-center
                                        font-bold
                                        text-slate-700
                                        has-[:checked]:border-indigo-500
                                        has-[:checked]:bg-indigo-50
                                        has-[:checked]:text-indigo-700
                                    ">

                                    <input type="radio" name="{{ $inputName }}" value="{{ $value }}"
                                        @checked((string) $booleanValue === (string) $value) class="sr-only">

                                    {{ $label }}

                                </label>
                            @endforeach

                        </div>


                        {{-- ========================================= --}}
                        {{-- INTEGER / DECIMAL --}}
                        {{-- ========================================= --}}
                    @elseif (in_array($attribute->data_type, ['INTEGER', 'DECIMAL'], true))
                        @php

                            $numericValue = old(
                                "attributes.{$attribute->id}",
                                $attribute->data_type === 'INTEGER'
                                    ? $firstValue?->integer_value
                                    : $firstValue?->decimal_value,
                            );

                        @endphp


                        <input type="number" name="{{ $inputName }}" value="{{ $numericValue }}"
                            step="{{ $attribute->data_type === 'INTEGER' ? '1' : 'any' }}"
                            @if ($attribute->min_numeric_value !== null) min="{{ $attribute->min_numeric_value }}" @endif
                            @if ($attribute->max_numeric_value !== null) max="{{ $attribute->max_numeric_value }}" @endif
                            placeholder="{{ $attribute->placeholder ?: 'Ingresa un valor' }}"
                            class="
                                w-full
                                rounded-xl
                                border-slate-300
                                bg-white
                                text-slate-900
                                placeholder:text-slate-400
                            ">


                        @if ($attribute->unit)
                            <p
                                class="
                                    mt-2
                                    text-xs
                                    text-slate-500
                                ">
                                Unidad:
                                <strong>
                                    {{ $attribute->unit }}
                                </strong>
                            </p>
                        @endif


                        {{-- ========================================= --}}
                        {{-- DATE --}}
                        {{-- ========================================= --}}
                    @elseif ($attribute->data_type === 'DATE')
                        <input type="date" name="{{ $inputName }}"
                            value="{{ old("attributes.{$attribute->id}", $firstValue?->date_value?->format('Y-m-d')) }}"
                            class="
                                w-full
                                rounded-xl
                                border-slate-300
                                bg-white
                                text-slate-900
                            ">


                        {{-- ========================================= --}}
                        {{-- COLOR --}}
                        {{-- ========================================= --}}
                    @elseif ($attribute->data_type === 'COLOR')
                        <div
                            class="
                                flex
                                gap-3
                            ">

                            <input type="color" name="{{ $inputName }}"
                                value="{{ old("attributes.{$attribute->id}", $firstValue?->color_value ?: '#6366F1') }}"
                                class="
                                    h-12
                                    w-20
                                    rounded-xl
                                    border
                                    border-slate-300
                                    bg-white
                                    p-1
                                ">

                        </div>


                        {{-- ========================================= --}}
                        {{-- LONG TEXT --}}
                        {{-- ========================================= --}}
                    @elseif ($attribute->data_type === 'LONG_TEXT')
                        <textarea name="{{ $inputName }}" rows="4"
                            placeholder="{{ $attribute->placeholder ?: 'Escribe un valor...' }}"
                            class="
                                w-full
                                rounded-xl
                                border-slate-300
                                bg-white
                                text-slate-900
                                placeholder:text-slate-400
                            ">{{ old("attributes.{$attribute->id}", $firstValue?->text_value) }}</textarea>


                        {{-- ========================================= --}}
                        {{-- TEXT --}}
                        {{-- ========================================= --}}
                    @else
                        <input type="text" name="{{ $inputName }}"
                            value="{{ old("attributes.{$attribute->id}", $firstValue?->text_value) }}"
                            placeholder="{{ $attribute->placeholder ?: 'Escribe un valor...' }}"
                            class="
                                w-full
                                rounded-xl
                                border-slate-300
                                bg-white
                                text-slate-900
                                placeholder:text-slate-400
                            ">
                    @endif


                    @error("attributes.{$attribute->id}")
                        <p
                            class="
                                mt-3
                                text-sm
                                font-bold
                                text-red-600
                            ">
                            {{ $message }}
                        </p>
                    @enderror

                </div>

            </section>

        @endforeach

    </div>


    {{-- ========================================================= --}}
    {{-- AÑADIR ATRIBUTO --}}
    {{-- ========================================================= --}}

    <section
        class="
            mt-6
            rounded-2xl
            border
            border-dashed
            border-slate-300
            bg-slate-50
            p-5
        ">

        <div
            class="
                flex
                flex-col
                justify-between
                gap-4
                md:flex-row
                md:items-center
            ">

            <div>

                <h4
                    class="
                        font-black
                        text-slate-900
                    ">
                    + Añadir característica
                </h4>


                <p
                    class="
                        mt-1
                        text-xs
                        text-slate-500
                    ">
                    Busca solamente las características
                    que deseas utilizar.
                </p>

            </div>


            <div
                class="
                    grid
                    flex-1
                    gap-2
                    md:max-w-3xl
                    md:grid-cols-3
                ">

                <input type="text" x-model="
                        attributeSearch
                    "
                    placeholder="Buscar atributo..."
                    class="
                        rounded-xl
                        border-slate-300
                        bg-white
                        text-sm
                        text-slate-900
                        placeholder:text-slate-400
                    ">


                <select x-model="
                        attributeType
                    "
                    class="
                        rounded-xl
                        border-slate-300
                        bg-white
                        text-sm
                        text-slate-900
                    ">

                    <option value="ALL">
                        Todos los tipos
                    </option>

                    <option value="OPTION">
                        Catálogo
                    </option>

                    <option value="BOOLEAN">
                        Sí / No
                    </option>

                    <option value="TEXT">
                        Texto
                    </option>

                    <option value="LONG_TEXT">
                        Texto largo
                    </option>

                    <option value="INTEGER">
                        Entero
                    </option>

                    <option value="DECIMAL">
                        Decimal
                    </option>

                    <option value="DATE">
                        Fecha
                    </option>

                    <option value="COLOR">
                        Color
                    </option>

                </select>


                <select x-model="
                        attributeGroup
                    "
                    class="
                        rounded-xl
                        border-slate-300
                        bg-white
                        text-sm
                        text-slate-900
                    ">

                    <option value="ALL">
                        Todos los grupos
                    </option>


                    @foreach ($groups as $group)
                        <option value="{{ (string) $group->id }}">
                            {{ $group->name }}
                        </option>
                    @endforeach

                </select>

            </div>

        </div>


        <div
            class="
                mt-5
                grid
                max-h-[420px]
                gap-3
                overflow-y-auto
                pr-1
                sm:grid-cols-2
                lg:grid-cols-3
            ">

            @foreach ($attributes as $attribute)
                @php

                    $groupIds = $attribute->groups->pluck('id')->map(fn($id) => (string) $id)->values()->all();
                @endphp


                <button type="button"
                    x-show="
                        ! isSelected(
                            '{{ $attribute->id }}'
                        )
                        &&
                        isContextVisible(
                            '{{ $attribute->id }}'
                        )
                        &&
                        matchesAttribute(
                            @js($attribute->name),
                            @js($attribute->code),
                            @js($attribute->data_type),
                            @js($groupIds)
                        )
                    "
                    @click="
                        addAttribute(
                            '{{ $attribute->id }}'
                        )
                    "
                    class="
                        flex
                        items-center
                        gap-3
                        rounded-xl
                        border
                        border-slate-200
                        bg-white
                        p-3
                        text-left
                        transition
                        hover:border-indigo-300
                        hover:bg-indigo-50
                    ">

                    <div
                        class="
                            h-12
                            w-12
                            shrink-0
                            overflow-hidden
                            rounded-xl
                            bg-slate-100
                        ">

                        @if ($attribute->image_url)
                            <img src="{{ $attribute->image_url }}"
                                class="
                                    h-full
                                    w-full
                                    object-cover
                                ">
                        @else
                            <div class="
                                    flex
                                    h-full
                                    items-center
                                    justify-center
                                    font-black
                                "
                                style="
                                    color:
                                        {{ $attribute->color ?? '#6366F1' }};
                                ">
                                {{ $attribute->icon ?: $attribute->data_type_icon }}
                            </div>
                        @endif

                    </div>


                    <div class="min-w-0 flex-1">

                        <p
                            class="
                                truncate
                                text-sm
                                font-black
                                text-slate-800
                            ">
                            {{ $attribute->name }}
                        </p>


                        <p
                            class="
                                mt-1
                                text-[10px]
                                font-bold
                                text-slate-400
                            ">
                            {{ $attribute->data_type_label }}

                            @if ($attribute->allows_multiple)
                                · Múltiple
                            @endif
                        </p>

                    </div>


                    <span
                        class="
                            text-lg
                            font-black
                            text-indigo-500
                        ">
                        +
                    </span>

                </button>
            @endforeach

        </div>

    </section>

</div>
