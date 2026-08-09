<form method="GET" action="{{ route('community.index') }}"
    class="
        mt-5
        min-w-0
        rounded-2xl
        border
        border-slate-200
        bg-white
        p-4
        shadow-sm
    ">

    <input type="hidden" name="tab" value="{{ $tab }}">


    <div
        class="
            grid
            min-w-0
            grid-cols-1
            gap-3
            sm:grid-cols-2
            xl:grid-cols-4
        ">

        {{-- ===================================================== --}}
        {{-- BÚSQUEDA --}}
        {{-- ===================================================== --}}

        <div class="
                min-w-0
                sm:col-span-2
            ">

            <label
                class="
                    mb-1.5
                    block
                    text-[10px]
                    font-black
                    uppercase
                    tracking-wider
                    text-slate-400
                ">
                Búsqueda
            </label>


            <input type="search" name="search" value="{{ $search }}"
                placeholder="Nombre, descripción o creador..."
                class="
                    w-full
                    min-w-0
                    rounded-xl
                    border-slate-300
                    bg-white
                    py-2.5
                    text-sm
                    text-slate-900
                    placeholder:text-slate-400
                    focus:border-indigo-500
                    focus:ring-indigo-500
                ">

        </div>


        {{-- ===================================================== --}}
        {{-- FILTRO SEGÚN PESTAÑA --}}
        {{-- ===================================================== --}}

        @if ($tab === 'entities')

            <div class="min-w-0">

                <label
                    class="
                        mb-1.5
                        block
                        text-[10px]
                        font-black
                        uppercase
                        tracking-wider
                        text-slate-400
                    ">
                    Tipo de entidad
                </label>


                <select name="entity_type"
                    class="
                        w-full
                        min-w-0
                        rounded-xl
                        border-slate-300
                        bg-white
                        py-2.5
                        text-sm
                        text-slate-900
                        focus:border-indigo-500
                        focus:ring-indigo-500
                    ">
                    <option value="">
                        Todos los tipos
                    </option>

                    @foreach ($entityTypes as $entityType)
                        <option value="{{ $entityType->id }}" @selected($entityTypeId === $entityType->id)>
                            {{ $entityType->name }}
                        </option>
                    @endforeach
                </select>

            </div>
        @elseif ($tab === 'attributes')
            <div class="min-w-0">

                <label
                    class="
                        mb-1.5
                        block
                        text-[10px]
                        font-black
                        uppercase
                        tracking-wider
                        text-slate-400
                    ">
                    Tipo de dato
                </label>


                <select name="data_type"
                    class="
                        w-full
                        min-w-0
                        rounded-xl
                        border-slate-300
                        bg-white
                        py-2.5
                        text-sm
                        text-slate-900
                        focus:border-indigo-500
                        focus:ring-indigo-500
                    ">
                    <option value="">
                        Todos los tipos
                    </option>

                    @foreach ([
        'TEXT' => 'Texto',
        'LONG_TEXT' => 'Texto largo',
        'INTEGER' => 'Número entero',
        'DECIMAL' => 'Decimal',
        'BOOLEAN' => 'Sí o no',
        'DATE' => 'Fecha',
        'COLOR' => 'Color',
        'OPTION' => 'Catálogo',
    ] as $value => $label)
                        <option value="{{ $value }}" @selected($dataType === $value)>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>

            </div>
        @else
            <div class="hidden xl:block"></div>

        @endif


        {{-- ===================================================== --}}
        {{-- ORDEN --}}
        {{-- ===================================================== --}}

        <div class="min-w-0">

            <label
                class="
                    mb-1.5
                    block
                    text-[10px]
                    font-black
                    uppercase
                    tracking-wider
                    text-slate-400
                ">
                Ordenar
            </label>


            <select name="sort"
                class="
                    w-full
                    min-w-0
                    rounded-xl
                    border-slate-300
                    bg-white
                    py-2.5
                    text-sm
                    text-slate-900
                    focus:border-indigo-500
                    focus:ring-indigo-500
                ">
                @foreach ([
        'popular' => 'Más populares',
        'newest' => 'Más recientes',
        'oldest' => 'Más antiguos',
        'name' => 'Nombre A–Z',
        'cloned' => 'Más clonados',
        'viewed' => 'Más vistos',
    ] as $value => $label)
                    <option value="{{ $value }}" @selected($sort === $value)>
                        {{ $label }}
                    </option>
                @endforeach
            </select>

        </div>

    </div>


    {{-- ========================================================= --}}
    {{-- ACCIONES --}}
    {{-- ========================================================= --}}

    <div
        class="
            mt-3
            flex
            justify-end
            gap-2
            border-t
            border-slate-100
            pt-3
        ">

        <a href="{{ route('community.index', [
            'tab' => $tab,
        ]) }}"
            title="Limpiar filtros"
            class="
                flex
                h-10
                w-10
                shrink-0
                items-center
                justify-center
                rounded-xl
                border
                border-slate-300
                text-sm
                font-bold
                text-slate-600
                hover:bg-slate-50
            ">
            ×
        </a>


        <button type="submit"
            class="
                rounded-xl
                bg-slate-900
                px-5
                py-2.5
                text-sm
                font-black
                text-white
                hover:bg-slate-800
            ">
            Aplicar filtros
        </button>

    </div>

</form>
