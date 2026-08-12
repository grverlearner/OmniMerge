<x-tournament-layout>

    <x-slot name="header">
        Mis plantillas
    </x-slot>


    <div
        class="
            flex
            flex-col
            justify-between
            gap-5
            sm:flex-row
            sm:items-start
        ">

        <div>

            <p
                class="
                    text-xs
                    font-black
                    uppercase
                    tracking-wider
                    text-amber-600
                ">
                Torneos · Plantillas
            </p>


            <h2
                class="
                    mt-2
                    text-3xl
                    font-black
                    text-slate-900
                ">
                Mis plantillas
            </h2>


            <p
                class="
                    mt-2
                    max-w-2xl
                    text-slate-500
                ">
                Diseña estructuras competitivas reutilizables.
                Aquí defines cómo funciona el torneo, no quién
                participa realmente.
            </p>

        </div>


        <a href="{{ route('tournaments.templates.create') }}"
            class="
                rounded-xl
                bg-amber-500
                px-5
                py-3
                text-sm
                font-black
                text-white
                shadow-lg
                shadow-amber-500/20
                transition
                hover:bg-amber-600
            ">
            + Nueva plantilla
        </a>

    </div>


    {{-- STATS --}}

    <div
        class="
            mt-7
            grid
            grid-cols-2
            gap-3
            lg:grid-cols-4
        ">

        @foreach ([['Total', $stats['total']], ['Activas', $stats['active']], ['Borradores', $stats['draft']], ['Públicas', $stats['public']]] as [$label, $value])
            <article
                class="
                    rounded-2xl
                    border
                    border-slate-200
                    bg-white
                    p-4
                ">

                <p
                    class="
                        text-[9px]
                        font-black
                        uppercase
                        tracking-wider
                        text-slate-400
                    ">
                    {{ $label }}
                </p>


                <p
                    class="
                        mt-2
                        text-2xl
                        font-black
                        text-slate-900
                    ">
                    {{ $value }}
                </p>

            </article>
        @endforeach

    </div>


    {{-- FILTERS --}}

    <form method="GET"
        class="
            mt-6
            grid
            gap-3
            rounded-2xl
            border
            border-slate-200
            bg-white
            p-4
            md:grid-cols-2
            xl:grid-cols-5
        ">

        <input type="search" name="search" value="{{ $search }}" placeholder="Buscar plantilla..."
            class="
                rounded-xl
                border-slate-300
                text-sm
                text-slate-900
                placeholder:text-slate-400
                focus:border-amber-400
                focus:ring-amber-400
            ">


        <select name="status"
            class="
                rounded-xl
                border-slate-300
                bg-white
                text-sm
                text-slate-900
                focus:border-amber-400
                focus:ring-amber-400
            ">

            <option value="">
                Todo estado
            </option>

            <option value="DRAFT" @selected($status === 'DRAFT')>
                Borrador
            </option>

            <option value="ACTIVE" @selected($status === 'ACTIVE')>
                Activa
            </option>

            <option value="ARCHIVED" @selected($status === 'ARCHIVED')>
                Archivada
            </option>

        </select>


        <select name="visibility"
            class="
                rounded-xl
                border-slate-300
                bg-white
                text-sm
                text-slate-900
                focus:border-amber-400
                focus:ring-amber-400
            ">

            <option value="">
                Toda visibilidad
            </option>

            <option value="PRIVATE" @selected($visibility === 'PRIVATE')>
                Privada
            </option>

            <option value="PUBLIC" @selected($visibility === 'PUBLIC')>
                Pública
            </option>

            <option value="UNLISTED" @selected($visibility === 'UNLISTED')>
                No listada
            </option>

        </select>


        <select name="sort"
            class="
                rounded-xl
                border-slate-300
                bg-white
                text-sm
                text-slate-900
                focus:border-amber-400
                focus:ring-amber-400
            ">

            @foreach ([
        'newest' => 'Más recientes',
        'oldest' => 'Más antiguas',
        'name_asc' => 'Nombre A → Z',
        'name_desc' => 'Nombre Z → A',
        'phases_desc' => 'Más fases',
    ] as $value => $label)
                <option value="{{ $value }}" @selected($sort === $value)>
                    {{ $label }}
                </option>
            @endforeach

        </select>


        <button
            class="
                rounded-xl
                bg-slate-950
                px-4
                py-3
                text-sm
                font-black
                text-white
            ">
            Aplicar
        </button>

    </form>


    @if ($templates->isEmpty())

        <div
            class="
                mt-8
                rounded-3xl
                border
                border-dashed
                border-slate-300
                bg-white
                p-12
                text-center
            ">

            <div class="
                    text-5xl
                ">
                🏆
            </div>


            <h3
                class="
                    mt-4
                    text-xl
                    font-black
                    text-slate-900
                ">
                No encontramos plantillas
            </h3>


            <p
                class="
                    mt-2
                    text-sm
                    text-slate-500
                ">
                Crea una plantilla nueva o cambia los filtros.
            </p>

        </div>
    @else
        <div
            class="
                mt-6
                grid
                gap-5
                sm:grid-cols-2
                lg:grid-cols-3
            ">

            @foreach ($templates as $template)
                @include('tournaments.partials.template-card', [
                    'template' => $template,
                ])
            @endforeach

        </div>


        <div class="
                mt-8
            ">
            {{ $templates->links() }}
        </div>

    @endif

</x-tournament-layout>
