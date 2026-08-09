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

        {{-- BÚSQUEDA --}}
        <div class="min-w-0 sm:col-span-2">

            <label class="mb-1.5 block text-[10px] font-black uppercase tracking-wider text-slate-400">
                Buscar
            </label>

            <input name="search" value="{{ $search }}" placeholder="Nombre, creador, tipo, atributo, valor..."
                class="
                    w-full
                    min-w-0
                    rounded-xl
                    border-slate-300
                    py-2.5
                    text-sm
                ">

        </div>


        {{-- CREADOR --}}
        @if ($tab !== 'creators')

            <div class="min-w-0">

                <label class="mb-1.5 block text-[10px] font-black uppercase tracking-wider text-slate-400">
                    Creador
                </label>

                <select name="creator" class="w-full min-w-0 rounded-xl border-slate-300 py-2.5 text-sm">
                    <option value="">
                        Cualquier creador
                    </option>

                    @foreach ($publicCreators as $creatorItem)
                        <option value="{{ $creatorItem->username }}" @selected($creator === $creatorItem->username)>
                            {{ $creatorItem->name }}
                            ·
                            {{ '@' . $creatorItem->username }}
                        </option>
                    @endforeach
                </select>

            </div>

        @endif


        {{-- ENTIDADES --}}
        @if ($tab === 'entities')

            <div class="min-w-0">

                <label class="mb-1.5 block text-[10px] font-black uppercase tracking-wider text-slate-400">
                    Tipo
                </label>

                <select name="entity_type" class="w-full min-w-0 rounded-xl border-slate-300 py-2.5 text-sm">
                    <option value="">
                        Todos los tipos
                    </option>

                    @foreach ($entityTypes as $entityType)
                        <option value="{{ $entityType->id }}" @selected($entityTypeId == $entityType->id)>
                            {{ $entityType->name }}
                        </option>
                    @endforeach
                </select>

            </div>


            <div class="min-w-0">

                <label class="mb-1.5 block text-[10px] font-black uppercase tracking-wider text-slate-400">
                    Atributo
                </label>

                <select name="filter_attribute" class="w-full min-w-0 rounded-xl border-slate-300 py-2.5 text-sm">
                    <option value="">
                        Cualquier atributo
                    </option>

                    @foreach ($publicAttributes as $attribute)
                        <option value="{{ $attribute->id }}" @selected($filterAttributeId == $attribute->id)>
                            {{ $attribute->name }}
                        </option>
                    @endforeach
                </select>

            </div>


            <div class="min-w-0">

                <label class="mb-1.5 block text-[10px] font-black uppercase tracking-wider text-slate-400">
                    Valor de Catálogo
                </label>

                <select name="filter_option" class="w-full min-w-0 rounded-xl border-slate-300 py-2.5 text-sm">
                    <option value="">
                        Cualquier valor
                    </option>

                    @foreach ($publicAttributes as $attribute)
                        @if ($attribute->options->isNotEmpty())
                            <optgroup label="{{ $attribute->name }}">

                                @foreach ($attribute->options as $option)
                                    <option value="{{ $option->id }}" @selected($filterOptionId == $option->id)>
                                        {{ $option->name }}
                                    </option>
                                @endforeach

                            </optgroup>
                        @endif
                    @endforeach
                </select>

            </div>

        @endif


        {{-- ATRIBUTOS --}}
        @if ($tab === 'attributes')

            <div class="min-w-0">

                <label class="mb-1.5 block text-[10px] font-black uppercase tracking-wider text-slate-400">
                    Tipo de dato
                </label>

                <select name="data_type" class="w-full min-w-0 rounded-xl border-slate-300 py-2.5 text-sm">
                    <option value="">
                        Todos
                    </option>

                    @foreach ([
        'OPTION' => 'Catálogo',
        'BOOLEAN' => 'Sí / No',
        'TEXT' => 'Texto',
        'LONG_TEXT' => 'Texto largo',
        'INTEGER' => 'Entero',
        'DECIMAL' => 'Decimal',
        'DATE' => 'Fecha',
        'COLOR' => 'Color',
    ] as $value => $label)
                        <option value="{{ $value }}" @selected($dataType === $value)>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>

            </div>


            <div class="min-w-0">

                <label class="mb-1.5 block text-[10px] font-black uppercase tracking-wider text-slate-400">
                    Selección
                </label>

                <select name="multiple" class="w-full min-w-0 rounded-xl border-slate-300 py-2.5 text-sm">
                    <option value="">
                        Cualquiera
                    </option>

                    <option value="yes" @selected($multiple === 'yes')>
                        Múltiple
                    </option>

                    <option value="no" @selected($multiple === 'no')>
                        Única
                    </option>
                </select>

            </div>


            <div class="min-w-0">

                <label class="mb-1.5 block text-[10px] font-black uppercase tracking-wider text-slate-400">
                    Catálogo
                </label>

                <select name="catalog_state" class="w-full min-w-0 rounded-xl border-slate-300 py-2.5 text-sm">
                    <option value="">
                        Cualquier estado
                    </option>

                    <option value="with" @selected($catalogState === 'with')>
                        Con elementos
                    </option>

                    <option value="empty" @selected($catalogState === 'empty')>
                        Sin elementos
                    </option>
                </select>

            </div>

        @endif


        {{-- CATÁLOGOS --}}
        @if ($tab === 'catalogs')

            <div class="min-w-0">

                <label class="mb-1.5 block text-[10px] font-black uppercase tracking-wider text-slate-400">
                    Catálogo padre
                </label>

                <select name="attribute" class="w-full min-w-0 rounded-xl border-slate-300 py-2.5 text-sm">
                    <option value="">
                        Todos
                    </option>

                    @foreach ($publicAttributes as $attribute)
                        @if ($attribute->usesCatalog())
                            <option value="{{ $attribute->id }}" @selected($attributeId == $attribute->id)>
                                {{ $attribute->name }}
                            </option>
                        @endif
                    @endforeach
                </select>

            </div>


            <div class="min-w-0">

                <label class="mb-1.5 block text-[10px] font-black uppercase tracking-wider text-slate-400">
                    Jerarquía
                </label>

                <select name="hierarchy" class="w-full min-w-0 rounded-xl border-slate-300 py-2.5 text-sm">
                    <option value="">
                        Cualquier nivel
                    </option>

                    <option value="root" @selected($hierarchy === 'root')>
                        Nivel principal
                    </option>

                    <option value="child" @selected($hierarchy === 'child')>
                        Subelemento
                    </option>

                    <option value="has_children" @selected($hierarchy === 'has_children')>
                        Con subelementos
                    </option>
                </select>

            </div>


            <div class="min-w-0">

                <label class="mb-1.5 block text-[10px] font-black uppercase tracking-wider text-slate-400">
                    Uso
                </label>

                <select name="usage" class="w-full min-w-0 rounded-xl border-slate-300 py-2.5 text-sm">
                    <option value="">
                        Cualquier uso
                    </option>

                    <option value="used" @selected($usage === 'used')>
                        En uso
                    </option>

                    <option value="unused" @selected($usage === 'unused')>
                        Sin utilizar
                    </option>
                </select>

            </div>

        @endif


        {{-- COLECCIONES --}}
        @if ($tab === 'collections')
            <div class="min-w-0">

                <label class="mb-1.5 block text-[10px] font-black uppercase tracking-wider text-slate-400">
                    Tamaño
                </label>

                <select name="collection_size" class="w-full min-w-0 rounded-xl border-slate-300 py-2.5 text-sm">
                    <option value="">
                        Cualquier tamaño
                    </option>

                    <option value="small" @selected($collectionSize === 'small')>
                        1–10 entidades
                    </option>

                    <option value="medium" @selected($collectionSize === 'medium')>
                        11–50 entidades
                    </option>

                    <option value="large" @selected($collectionSize === 'large')>
                        Más de 50
                    </option>
                </select>

            </div>
        @endif


        {{-- IMAGEN --}}
        @if (!in_array($tab, ['creators', 'all'], true))
            <div class="min-w-0">

                <label class="mb-1.5 block text-[10px] font-black uppercase tracking-wider text-slate-400">
                    Imagen
                </label>

                <select name="image" class="w-full min-w-0 rounded-xl border-slate-300 py-2.5 text-sm">
                    <option value="">
                        Cualquier imagen
                    </option>

                    <option value="yes" @selected($image === 'yes')>
                        Con imagen
                    </option>

                    <option value="no" @selected($image === 'no')>
                        Sin imagen
                    </option>
                </select>

            </div>
        @endif


        {{-- CLONACIÓN --}}
        @if (in_array($tab, ['entities', 'collections', 'attributes'], true))
            <div class="min-w-0">

                <label class="mb-1.5 block text-[10px] font-black uppercase tracking-wider text-slate-400">
                    Copiable
                </label>

                <select name="cloning" class="w-full min-w-0 rounded-xl border-slate-300 py-2.5 text-sm">
                    <option value="">
                        Cualquiera
                    </option>

                    <option value="yes" @selected($cloning === 'yes')>
                        Se puede copiar
                    </option>

                    <option value="no" @selected($cloning === 'no')>
                        Solo lectura
                    </option>
                </select>

            </div>
        @endif


        {{-- FECHA --}}
        @if (in_array($tab, ['entities', 'collections', 'attributes'], true))
            <div class="min-w-0">

                <label class="mb-1.5 block text-[10px] font-black uppercase tracking-wider text-slate-400">
                    Publicado
                </label>

                <select name="period" class="w-full min-w-0 rounded-xl border-slate-300 py-2.5 text-sm">
                    <option value="">
                        Cualquier fecha
                    </option>

                    <option value="today" @selected($period === 'today')>
                        Hoy
                    </option>

                    <option value="week" @selected($period === 'week')>
                        Esta semana
                    </option>

                    <option value="month" @selected($period === 'month')>
                        Este mes
                    </option>
                </select>

            </div>
        @endif


        {{-- AGRUPACIÓN --}}
        @if (in_array($tab, ['entities', 'collections', 'attributes', 'catalogs'], true))

            <div class="min-w-0">

                <label class="mb-1.5 block text-[10px] font-black uppercase tracking-wider text-slate-400">
                    Agrupar por
                </label>

                <select name="group_by" class="w-full min-w-0 rounded-xl border-slate-300 py-2.5 text-sm">
                    <option value="">
                        Sin agrupar
                    </option>

                    @if ($tab === 'entities')
                        <option value="type" @selected($groupBy === 'type')>
                            Tipo
                        </option>
                    @endif

                    @if ($tab === 'attributes')
                        <option value="data_type" @selected($groupBy === 'data_type')>
                            Tipo de dato
                        </option>
                    @endif

                    @if ($tab === 'catalogs')
                        <option value="attribute" @selected($groupBy === 'attribute')>
                            Catálogo padre
                        </option>
                    @endif

                    <option value="creator" @selected($groupBy === 'creator')>
                        Creador
                    </option>
                </select>

            </div>

        @endif


        {{-- ORDEN --}}
        <div class="min-w-0">

            <label class="mb-1.5 block text-[10px] font-black uppercase tracking-wider text-slate-400">
                Ordenar
            </label>

            <select name="sort" class="w-full min-w-0 rounded-xl border-slate-300 py-2.5 text-sm">

                <option value="popular" @selected($sort === 'popular')>
                    Más populares
                </option>

                <option value="trending" @selected($sort === 'trending')>
                    Tendencia
                </option>

                <option value="newest" @selected($sort === 'newest')>
                    Más recientes
                </option>

                <option value="oldest" @selected($sort === 'oldest')>
                    Más antiguos
                </option>

                <option value="name_asc" @selected($sort === 'name_asc')>
                    Nombre A → Z
                </option>

                <option value="name_desc" @selected($sort === 'name_desc')>
                    Nombre Z → A
                </option>

                @if (in_array($tab, ['entities', 'collections', 'attributes'], true))
                    <option value="viewed" @selected($sort === 'viewed')>
                        Más vistos
                    </option>

                    <option value="cloned" @selected($sort === 'cloned')>
                        Más copiados
                    </option>
                @endif

                @if ($tab === 'collections')
                    <option value="size_desc" @selected($sort === 'size_desc')>
                        Más entidades
                    </option>

                    <option value="size_asc" @selected($sort === 'size_asc')>
                        Menos entidades
                    </option>
                @endif

                @if ($tab === 'attributes')
                    <option value="usage_desc" @selected($sort === 'usage_desc')>
                        Más utilizados
                    </option>

                    <option value="catalog_desc" @selected($sort === 'catalog_desc')>
                        Catálogo más grande
                    </option>
                @endif

                @if ($tab === 'catalogs')
                    <option value="usage_desc" @selected($sort === 'usage_desc')>
                        Más utilizados
                    </option>

                    <option value="children_desc" @selected($sort === 'children_desc')>
                        Más subelementos
                    </option>
                @endif

            </select>

        </div>

    </div>


    {{-- ACCIONES --}}
    <div
        class="mt-4 flex flex-col gap-3 border-t border-slate-100 pt-4 sm:flex-row sm:items-center sm:justify-between">

        <a href="{{ route('community.index', ['tab' => $tab]) }}"
            class="text-xs font-bold text-slate-500 hover:text-indigo-600">
            × Limpiar filtros
        </a>


        <div class="flex w-full gap-2 sm:w-auto">

            <select name="per_page" class="min-w-0 flex-1 rounded-xl border-slate-300 py-2.5 text-sm sm:w-32">
                @foreach ([12, 24, 48, 96] as $number)
                    <option value="{{ $number }}" @selected($perPage === $number)>
                        {{ $number }}/pág.
                    </option>
                @endforeach
            </select>


            <button type="submit"
                class="shrink-0 rounded-xl bg-slate-900 px-5 py-2.5 text-sm font-black text-white hover:bg-slate-800">
                Aplicar
            </button>

        </div>

    </div>

</form>
