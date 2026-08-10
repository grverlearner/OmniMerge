<x-app-layout>

    <x-slot name="header">
        Versiones
    </x-slot>


    @include('entities.partials.section-navigation')


    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>


    <div x-data="{
        view: localStorage.getItem(
                'omnimerge.versions.view'
            ) ||
            'grid',
    
        setView(value) {
            this.view = value;
    
            localStorage.setItem(
                'omnimerge.versions.view',
                value
            );
        }
    }">

        {{-- HERO --}}
        <section
            class="
                overflow-hidden
                rounded-3xl
                border
                border-slate-200
                bg-white
                shadow-sm
            ">

            <div
                class="
                    bg-gradient-to-br
                    from-violet-950
                    via-indigo-950
                    to-slate-950
                    p-6
                    text-white
                    sm:p-8
                ">

                <div
                    class="
                        flex
                        flex-col
                        gap-6
                        lg:flex-row
                        lg:items-start
                        lg:justify-between
                    ">

                    <div>

                        <span
                            class="
                                rounded-full
                                bg-white/10
                                px-3
                                py-1.5
                                text-[10px]
                                font-black
                                uppercase
                                tracking-wider
                                text-violet-200
                            ">
                            ◈ Entidades · Versiones
                        </span>


                        <h1
                            class="
                                mt-4
                                text-3xl
                                font-black
                                tracking-tight
                                sm:text-4xl
                            ">
                            Versiones
                        </h1>


                        <p
                            class="
                                mt-3
                                max-w-3xl
                                text-sm
                                leading-6
                                text-slate-300
                            ">
                            Define eras, edades, formas y transformaciones
                            reutilizables y asócialas a tus Entidades.
                        </p>

                    </div>


                    <a href="{{ route('versions.create') }}"
                        class="
                            rounded-xl
                            bg-white
                            px-5
                            py-3
                            text-center
                            text-sm
                            font-black
                            text-violet-800
                            shadow-xl
                        ">
                        + Nueva Versión
                    </a>

                </div>


                <div
                    class="
                        mt-7
                        grid
                        grid-cols-2
                        gap-2
                        md:grid-cols-5
                    ">

                    @foreach ([['Total', $stats['total']], ['Compartidas', $stats['shared']], ['Exclusivas', $stats['exclusive']], ['Automáticas', $stats['automatic']], ['Activas', $stats['active']]] as [$label, $value])
                        <article
                            class="
                                rounded-2xl
                                bg-white/10
                                p-4
                            ">
                            <p
                                class="
                                    text-[9px]
                                    font-black
                                    uppercase
                                    tracking-wider
                                    text-white/50
                                ">
                                {{ $label }}
                            </p>

                            <p
                                class="
                                    mt-1
                                    text-2xl
                                    font-black
                                ">
                                {{ $value }}
                            </p>
                        </article>
                    @endforeach

                </div>

            </div>

        </section>


        @if (session('success'))
            <div
                class="
                    mt-5
                    rounded-2xl
                    border
                    border-emerald-200
                    bg-emerald-50
                    p-4
                    text-sm
                    font-bold
                    text-emerald-700
                ">
                ✓ {{ session('success') }}
            </div>
        @endif


        {{-- FILTROS --}}
        <section
            class="
                mt-6
                rounded-3xl
                border
                border-slate-200
                bg-white
                p-5
                shadow-sm
            ">

            <form method="GET"
                class="
                    grid
                    min-w-0
                    gap-3
                    md:grid-cols-2
                    xl:grid-cols-6
                ">

                <div
                    class="
                        min-w-0
                        md:col-span-2
                    ">
                    <input type="search" name="search" value="{{ $search }}"
                        placeholder="Buscar por nombre, código..."
                        class="
                            w-full
                            min-w-0
                            rounded-xl
                            border-slate-300
                            text-sm
                        ">
                </div>


                <select name="kind"
                    class="
                        w-full
                        rounded-xl
                        border-slate-300
                        text-sm
                    ">
                    <option value="">
                        Todos los tipos
                    </option>

                    @foreach ([
        'ERA' => 'Era',
        'AGE' => 'Edad',
        'FORM' => 'Forma',
        'TRANSFORMATION' => 'Transformación',
        'OUTFIT' => 'Apariencia',
        'TIMELINE' => 'Línea temporal',
        'OTHER' => 'Otra',
    ] as $value => $label)
                        <option value="{{ $value }}" @selected($kind === $value)>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>


                <select name="scope"
                    class="
                        w-full
                        rounded-xl
                        border-slate-300
                        text-sm
                    ">
                    <option value="">
                        Cualquier ámbito
                    </option>

                    <option value="SHARED" @selected($scope === 'SHARED')>
                        Compartidas
                    </option>

                    <option value="EXCLUSIVE" @selected($scope === 'EXCLUSIVE')>
                        Exclusivas
                    </option>
                </select>


                <select name="activation"
                    class="
                        w-full
                        rounded-xl
                        border-slate-300
                        text-sm
                    ">
                    <option value="">
                        Cualquier activación
                    </option>

                    <option value="AUTO" @selected($activation === 'AUTO')>
                        Automática
                    </option>

                    <option value="MANUAL" @selected($activation === 'MANUAL')>
                        Manual
                    </option>

                    <option value="BOTH" @selected($activation === 'BOTH')>
                        Ambas
                    </option>
                </select>


                <div class="flex gap-2">

                    <button
                        class="
                            flex-1
                            rounded-xl
                            bg-slate-900
                            px-4
                            py-2.5
                            text-xs
                            font-black
                            text-white
                        ">
                        Filtrar
                    </button>


                    <a href="{{ route('versions.index') }}"
                        class="
                            rounded-xl
                            bg-slate-100
                            px-4
                            py-2.5
                            text-xs
                            font-black
                            text-slate-500
                        ">
                        ×
                    </a>

                </div>

            </form>

        </section>


        {{-- VISTAS --}}
        <div
            class="
                mt-5
                flex
                flex-wrap
                gap-2
            ">

            @foreach ([['gallery', '▣ Galería'], ['grid', '▦ Cuadrícula'], ['list', '≡ Lista'], ['table', '▤ Tabla'], ['tree', '⌘ Árbol']] as [$value, $label])
                <button type="button" @click="setView('{{ $value }}')"
                    class="
                        rounded-xl
                        px-4
                        py-2.5
                        text-xs
                        font-black
                        transition
                    "
                    :class="view === '{{ $value }}'
                        ?
                        'bg-violet-600 text-white' :
                        'bg-white text-slate-500 border border-slate-200'">
                    {{ $label }}
                </button>
            @endforeach

        </div>


        {{-- EMPTY --}}
        @if ($versions->isEmpty())
            <div
                class="
                    mt-6
                    rounded-3xl
                    border
                    border-dashed
                    border-slate-300
                    bg-white
                    py-24
                    text-center
                ">
                <div class="text-6xl">
                    ◈
                </div>

                <h2
                    class="
                        mt-5
                        text-xl
                        font-black
                        text-slate-700
                    ">
                    Todavía no tienes Versiones
                </h2>

                <a href="{{ route('versions.create') }}"
                    class="
                        mt-5
                        inline-flex
                        rounded-xl
                        bg-violet-600
                        px-5
                        py-3
                        text-sm
                        font-black
                        text-white
                    ">
                    Crear primera Versión
                </a>
            </div>
        @endif


        {{-- GALERÍA --}}
        <section x-show="view === 'gallery'" x-cloak
            class="
                mt-6
                grid
                gap-3
                grid-cols-2
                md:grid-cols-3
                lg:grid-cols-4
                2xl:grid-cols-6
            ">

            @foreach ($versions as $version)
                <a href="{{ route('versions.show', $version) }}"
                    class="
                        group
                        overflow-hidden
                        rounded-2xl
                        border
                        border-slate-200
                        bg-white
                        shadow-sm
                        transition
                        hover:-translate-y-1
                        hover:border-violet-300
                        hover:shadow-lg
                    ">

                    <div
                        class="
                            aspect-square
                            overflow-hidden
                            bg-slate-100
                        ">
                        <img src="{{ $version->image_url }}" alt="{{ $version->name }}"
                            class="
                                h-full
                                w-full
                                object-cover
                                transition
                                duration-300
                                group-hover:scale-105
                            ">
                    </div>


                    <div class="p-3">
                        <p
                            class="
                                truncate
                                text-sm
                                font-black
                                text-slate-800
                            ">
                            {{ $version->name }}
                        </p>

                        <p
                            class="
                                mt-1
                                truncate
                                text-[9px]
                                text-slate-400
                            ">
                            {{ $version->kind_label }}
                            ·
                            {{ $version->entity_versions_count }}
                            Entidades
                        </p>
                    </div>

                </a>
            @endforeach

        </section>


        {{-- GRID --}}
        <section x-show="view === 'grid'"
            class="
                mt-6
                grid
                gap-4
                md:grid-cols-2
                xl:grid-cols-3
            ">

            @foreach ($versions as $version)
                <article
                    class="
                        overflow-hidden
                        rounded-3xl
                        border
                        border-slate-200
                        bg-white
                        shadow-sm
                    ">

                    <a href="{{ route('versions.show', $version) }}"
                        class="
                            grid
                            grid-cols-[130px_minmax(0,1fr)]
                        ">

                        <img src="{{ $version->image_url }}"
                            class="
                                h-full
                                min-h-44
                                w-full
                                object-cover
                            ">


                        <div class="min-w-0 p-5">

                            <div
                                class="
                                    flex
                                    flex-wrap
                                    gap-1.5
                                ">
                                <span
                                    class="
                                        rounded-full
                                        bg-violet-50
                                        px-2
                                        py-1
                                        font-mono
                                        text-[8px]
                                        font-black
                                        text-violet-600
                                    ">
                                    {{ $version->code }}
                                </span>

                                <span
                                    class="
                                        rounded-full
                                        bg-slate-100
                                        px-2
                                        py-1
                                        text-[8px]
                                        font-black
                                        text-slate-500
                                    ">
                                    {{ $version->kind_label }}
                                </span>
                            </div>


                            <h2
                                class="
                                    mt-3
                                    truncate
                                    text-lg
                                    font-black
                                    text-slate-900
                                ">
                                {{ $version->name }}
                            </h2>


                            <p
                                class="
                                    mt-2
                                    line-clamp-2
                                    text-xs
                                    leading-5
                                    text-slate-500
                                ">
                                {{ $version->description ?: 'Sin descripción.' }}
                            </p>


                            <div
                                class="
                                    mt-4
                                    flex
                                    gap-3
                                    text-[9px]
                                    font-bold
                                    text-slate-400
                                ">
                                <span>
                                    {{ $version->entity_versions_count }}
                                    Entidades
                                </span>

                                <span>
                                    {{ $version->children_count }}
                                    hijas
                                </span>

                                <span>
                                    {{ $version->catalog_links_count }}
                                    relaciones
                                </span>
                            </div>

                        </div>

                    </a>

                </article>
            @endforeach

        </section>


        {{-- LIST --}}
        <section x-show="view === 'list'" x-cloak
            class="
                mt-6
                overflow-hidden
                rounded-3xl
                border
                border-slate-200
                bg-white
            ">

            @foreach ($versions as $version)
                <a href="{{ route('versions.show', $version) }}"
                    class="
                        flex
                        items-center
                        gap-4
                        border-b
                        border-slate-100
                        p-4
                        transition
                        last:border-0
                        hover:bg-slate-50
                    ">
                    <img src="{{ $version->image_url }}"
                        class="
                            h-14
                            w-14
                            shrink-0
                            rounded-xl
                            object-cover
                        ">

                    <div class="min-w-0 flex-1">
                        <p
                            class="
                                truncate
                                font-black
                                text-slate-800
                            ">
                            {{ $version->name }}
                        </p>

                        <p
                            class="
                                mt-1
                                truncate
                                text-[10px]
                                text-slate-400
                            ">
                            {{ $version->code }}
                            ·
                            {{ $version->kind_label }}
                            ·
                            {{ $version->scope_label }}
                            ·
                            {{ $version->entity_versions_count }} Entidades
                        </p>
                    </div>

                    <span
                        class="
                            hidden
                            text-xs
                            font-black
                            text-violet-600
                            sm:block
                        ">
                        Abrir →
                    </span>
                </a>
            @endforeach

        </section>


        {{-- TABLE --}}
        <section x-show="view === 'table'" x-cloak
            class="
                mt-6
                overflow-x-auto
                rounded-3xl
                border
                border-slate-200
                bg-white
            ">
            <table class="
                    min-w-[900px]
                    w-full
                ">
                <thead class="bg-slate-50">
                    <tr>
                        @foreach (['Versión', 'Código', 'Tipo', 'Ámbito', 'Activación', 'Entidades', 'Padre', ''] as $heading)
                            <th
                                class="
                                    px-4
                                    py-3
                                    text-left
                                    text-[9px]
                                    font-black
                                    uppercase
                                    text-slate-400
                                ">
                                {{ $heading }}
                            </th>
                        @endforeach
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">

                    @foreach ($versions as $version)
                        <tr>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <img src="{{ $version->image_url }}"
                                        class="
                                            h-10
                                            w-10
                                            rounded-lg
                                            object-cover
                                        ">
                                    <span
                                        class="
                                            font-black
                                            text-slate-800
                                        ">
                                        {{ $version->name }}
                                    </span>
                                </div>
                            </td>

                            <td
                                class="
                                    px-4
                                    py-3
                                    font-mono
                                    text-xs
                                    text-slate-400
                                ">
                                {{ $version->code }}
                            </td>

                            <td class="px-4 py-3 text-xs">
                                {{ $version->kind_label }}
                            </td>

                            <td class="px-4 py-3 text-xs">
                                {{ $version->scope_label }}
                            </td>

                            <td class="px-4 py-3 text-xs">
                                {{ $version->activation_label }}
                            </td>

                            <td
                                class="
                                    px-4
                                    py-3
                                    text-xs
                                    font-black
                                ">
                                {{ $version->entity_versions_count }}
                            </td>

                            <td class="px-4 py-3 text-xs">
                                {{ $version->parent?->name ?? '—' }}
                            </td>

                            <td class="px-4 py-3">
                                <a href="{{ route('versions.show', $version) }}"
                                    class="
                                        text-xs
                                        font-black
                                        text-violet-600
                                    ">
                                    Ver →
                                </a>
                            </td>
                        </tr>
                    @endforeach

                </tbody>
            </table>
        </section>


        {{-- TREE --}}
        <section x-show="view === 'tree'" x-cloak
            class="
                mt-6
                space-y-4
            ">

            @forelse ($treeVersions as $root)

                <article
                    class="
                        rounded-3xl
                        border
                        border-slate-200
                        bg-white
                        p-5
                        shadow-sm
                    ">

                    <div
                        class="
                            flex
                            items-center
                            gap-4
                        ">
                        <img src="{{ $root->image_url }}"
                            class="
                                h-16
                                w-16
                                rounded-2xl
                                object-cover
                            ">

                        <div>
                            <a href="{{ route('versions.show', $root) }}"
                                class="
                                    text-lg
                                    font-black
                                    text-slate-900
                                    hover:text-violet-600
                                ">
                                {{ $root->name }}
                            </a>

                            <p
                                class="
                                    mt-1
                                    text-[10px]
                                    text-slate-400
                                ">
                                {{ $root->kind_label }}
                                ·
                                {{ $root->entity_versions_count }} Entidades
                            </p>
                        </div>
                    </div>


                    @if ($root->children->isNotEmpty())
                        <div
                            class="
                                mt-5
                                ml-7
                                space-y-3
                                border-l-2
                                border-violet-100
                                pl-5
                            ">

                            @foreach ($root->children as $child)
                                <div>

                                    <a href="{{ route('versions.show', $child) }}"
                                        class="
                                            flex
                                            items-center
                                            gap-3
                                            rounded-xl
                                            bg-slate-50
                                            p-3
                                            font-black
                                            text-slate-700
                                            hover:bg-violet-50
                                            hover:text-violet-700
                                        ">
                                        <img src="{{ $child->image_url }}"
                                            class="
                                                h-10
                                                w-10
                                                rounded-lg
                                                object-cover
                                            ">

                                        {{ $child->name }}
                                    </a>


                                    @if ($child->children->isNotEmpty())
                                        <div
                                            class="
                                                ml-7
                                                mt-2
                                                space-y-2
                                                border-l
                                                border-slate-200
                                                pl-4
                                            ">
                                            @foreach ($child->children as $grandchild)
                                                <a href="{{ route('versions.show', $grandchild) }}"
                                                    class="
                                                        block
                                                        rounded-lg
                                                        px-3
                                                        py-2
                                                        text-xs
                                                        font-bold
                                                        text-slate-500
                                                        hover:bg-slate-50
                                                        hover:text-violet-600
                                                    ">
                                                    └ {{ $grandchild->name }}
                                                </a>
                                            @endforeach
                                        </div>
                                    @endif

                                </div>
                            @endforeach

                        </div>
                    @endif

                </article>

            @empty
            @endforelse

        </section>


        <div class="mt-6">
            {{ $versions->links() }}
        </div>

    </div>

</x-app-layout>
