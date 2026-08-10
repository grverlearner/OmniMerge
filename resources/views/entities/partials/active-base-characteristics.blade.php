@php

    $effectiveGroups = $activeBaseEffectiveAttributes
        ->map(function ($item) {
            $attribute = $item['attribute'];

            $group = $attribute?->groups?->sortBy(fn($group) => $group->pivot->sort_order ?? 0)->first();

            return [
                'group' => $group?->name ?? 'Otros',

                'item' => $item,
            ];
        })
        ->groupBy('group');

@endphp


<section class="mt-10">

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

            <div
                class="
                    flex
                    flex-wrap
                    items-center
                    gap-2
                ">

                <p
                    class="
                        text-xs
                        font-black
                        uppercase
                        tracking-wider
                        text-violet-600
                    ">
                    Perfil dinámico
                </p>


                <span
                    class="
                        rounded-full
                        bg-violet-100
                        px-2.5
                        py-1
                        text-[8px]
                        font-black
                        text-violet-700
                    ">
                    ★ BASE ACTIVA
                </span>

            </div>


            <h2
                class="
                    mt-2
                    text-3xl
                    font-black
                    text-slate-900
                ">
                Características
            </h2>


            <p
                class="
                    mt-2
                    max-w-2xl
                    text-sm
                    text-slate-500
                ">
                Resultado efectivo de
                <strong>
                    {{ $activeBaseEntityVersion->name }}
                </strong>:
                Base original + herencia + sobrescrituras.
            </p>

        </div>


        <div class="
                flex
                flex-wrap
                gap-2
            ">

            @foreach ([
        'cards' => '▦ Tarjetas',
        'list' => '☰ Lista',
        'groups' => '▥ Grupos',
    ] as $value => $label)
                <button type="button"
                    @click="
                        setCharacteristicView(
                            '{{ $value }}'
                        )
                    "
                    :class="characteristicView === '{{ $value }}'
                        ?
                        'bg-violet-600 text-white' :
                        'bg-slate-100 text-slate-500'"
                    class="
                        rounded-lg
                        px-3
                        py-2
                        text-xs
                        font-bold
                    ">
                    {{ $label }}
                </button>
            @endforeach


            <a href="{{ route('entity-versions.attributes.edit', [$entity, $activeBaseEntityVersion]) }}"
                class="
                    rounded-lg
                    bg-indigo-600
                    px-3
                    py-2
                    text-xs
                    font-black
                    text-white
                ">
                Editar Base
            </a>

        </div>

    </div>


    @if ($activeBaseEffectiveAttributes->isEmpty())

        <div
            class="
                mt-5
                rounded-3xl
                border
                border-dashed
                border-slate-300
                bg-white
                p-12
                text-center
            ">

            <p class="
                    font-black
                    text-slate-700
                ">
                Sin características efectivas
            </p>


            <a href="{{ route('entity-versions.attributes.edit', [$entity, $activeBaseEntityVersion]) }}"
                class="
                    mt-4
                    inline-flex
                    rounded-xl
                    bg-violet-600
                    px-4
                    py-2.5
                    text-sm
                    font-black
                    text-white
                ">
                Configurar características
            </a>

        </div>
    @else
        {{-- ===================================================== --}}
        {{-- CARDS --}}
        {{-- ===================================================== --}}

        <div x-show="
                characteristicView === 'cards'
            "
            class="
                mt-5
                grid
                gap-4
                md:grid-cols-2
                xl:grid-cols-3
            ">

            @foreach ($activeBaseEffectiveAttributes as $item)
                @php

                    $attribute = $item['attribute'];

                @endphp


                <article
                    class="
                        rounded-2xl
                        border
                        border-slate-200
                        bg-white
                        p-5
                        shadow-sm
                    ">

                    <div
                        class="
                            flex
                            items-start
                            gap-3
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
                                <img src="{{ $attribute->image_url }}" alt="{{ $attribute->name }}"
                                    class="
                                        h-full
                                        w-full
                                        object-cover
                                    ">
                            @else
                                <div
                                    class="
                                        flex
                                        h-full
                                        items-center
                                        justify-center
                                        text-lg
                                        font-black
                                        text-violet-400
                                    ">
                                    {{ $attribute->icon ?: '◇' }}
                                </div>
                            @endif

                        </div>


                        <div
                            class="
                                min-w-0
                                flex-1
                            ">

                            <div
                                class="
                                    flex
                                    flex-wrap
                                    items-start
                                    justify-between
                                    gap-2
                                ">

                                <p
                                    class="
                                        text-sm
                                        font-black
                                        text-slate-800
                                    ">
                                    {{ $item['custom_label'] ?: $attribute->name }}
                                </p>


                                <span
                                    class="
                                        rounded-full
                                        px-2
                                        py-1
                                        text-[7px]
                                        font-black

                                        {{ $item['source'] === 'BASE' ? 'bg-slate-100 text-slate-500' : 'bg-violet-100 text-violet-700' }}
                                    ">
                                    {{ $item['source'] === 'BASE' ? 'HEREDADO' : 'VERSION' }}
                                </span>

                            </div>


                            <p
                                class="
                                    mt-3
                                    text-lg
                                    font-black
                                    text-slate-900
                                ">
                                {{ $item['display'] ?: 'Sin definir' }}
                            </p>


                            <p
                                class="
                                    mt-2
                                    text-[8px]
                                    text-slate-400
                                ">
                                Fuente:
                                {{ $item['source_name'] }}
                            </p>

                        </div>

                    </div>

                </article>
            @endforeach

        </div>


        {{-- ===================================================== --}}
        {{-- LIST --}}
        {{-- ===================================================== --}}

        <div x-cloak x-show="
                characteristicView === 'list'
            "
            class="
                mt-5
                overflow-hidden
                rounded-3xl
                border
                border-slate-200
                bg-white
            ">

            @foreach ($activeBaseEffectiveAttributes as $item)
                <div
                    class="
                        flex
                        flex-col
                        justify-between
                        gap-3
                        border-b
                        border-slate-100
                        p-4
                        last:border-b-0
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
                            {{ $item['custom_label'] ?: $item['attribute']->name }}
                        </p>


                        <p
                            class="
                                mt-1
                                text-[8px]
                                uppercase
                                tracking-wider
                                text-slate-400
                            ">
                            {{ $item['source_name'] }}
                        </p>

                    </div>


                    <p
                        class="
                            text-sm
                            font-black
                            text-slate-700
                        ">
                        {{ $item['display'] ?: 'Sin definir' }}
                    </p>

                </div>
            @endforeach

        </div>


        {{-- ===================================================== --}}
        {{-- GROUPS --}}
        {{-- ===================================================== --}}

        <div x-cloak x-show="
                characteristicView === 'groups'
            "
            class="
                mt-5
                space-y-6
            ">

            @foreach ($effectiveGroups as $groupName => $entries)
                <section
                    class="
                        rounded-2xl
                        border
                        border-slate-200
                        bg-white
                        p-5
                    ">

                    <h3
                        class="
                            text-sm
                            font-black
                            uppercase
                            tracking-wider
                            text-slate-500
                        ">
                        {{ $groupName }}
                    </h3>


                    <div
                        class="
                            mt-4
                            space-y-3
                        ">

                        @foreach ($entries as $entry)
                            @php

                                $item = $entry['item'];

                            @endphp


                            <div
                                class="
                                    flex
                                    flex-col
                                    justify-between
                                    gap-2
                                    rounded-xl
                                    bg-slate-50
                                    p-4
                                    sm:flex-row
                                    sm:items-center
                                ">

                                <div>

                                    <p
                                        class="
                                            font-bold
                                            text-slate-800
                                        ">
                                        {{ $item['custom_label'] ?: $item['attribute']->name }}
                                    </p>


                                    <p
                                        class="
                                            mt-1
                                            text-[8px]
                                            text-slate-400
                                        ">
                                        {{ $item['source_name'] }}
                                    </p>

                                </div>


                                <span
                                    class="
                                        text-xs
                                        font-black
                                        text-slate-700
                                    ">
                                    {{ $item['display'] ?: 'Sin definir' }}
                                </span>

                            </div>
                        @endforeach

                    </div>

                </section>
            @endforeach

        </div>

    @endif

</section>
