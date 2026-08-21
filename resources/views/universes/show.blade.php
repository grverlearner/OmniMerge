<x-universe-layout :universe="$universe">

    <x-slot name="header">
        {{ $universe->name }}
    </x-slot>


    {{-- HERO --}}

    <section
        class="
            overflow-hidden
            rounded-3xl
            border
            border-slate-200
            bg-white
        ">

        <div class="
                grid
                lg:grid-cols-[340px_1fr]
            ">

            <div
                class="
                    min-h-[260px]
                    bg-gradient-to-br
                    from-slate-950
                    via-indigo-950
                    to-violet-950
                ">

                @if ($universe->image_url)
                    <img src="{{ $universe->image_url }}" alt="{{ $universe->name }}"
                        class="
                            h-full
                            min-h-[260px]
                            w-full
                            object-cover
                        ">
                @else
                    <div
                        class="
                            flex
                            h-full
                            min-h-[260px]
                            items-center
                            justify-center
                            text-7xl
                        ">
                        🌌
                    </div>
                @endif

            </div>


            <div class="
                    p-7
                    sm:p-8
                ">

                <div
                    class="
                        flex
                        flex-wrap
                        items-center
                        gap-2
                    ">

                    <span
                        class="
                            rounded-full
                            bg-slate-100
                            px-3
                            py-1
                            font-mono
                            text-[9px]
                            font-black
                            text-slate-500
                        ">
                        {{ $universe->code }}
                    </span>


                    <span
                        class="
                            rounded-full
                            px-3
                            py-1
                            text-[9px]
                            font-black
                            uppercase

                            {{ $universe->status === 'ACTIVE'
                                ? 'bg-emerald-100 text-emerald-700'
                                : ($universe->status === 'DRAFT'
                                    ? 'bg-violet-100 text-violet-700'
                                    : 'bg-slate-200 text-slate-600') }}
                        ">
                        {{ $universe->status_label }}
                    </span>


                    @if ($activeSeason)
                        <span
                            class="
                                rounded-full
                                bg-violet-100
                                px-3
                                py-1
                                text-[9px]
                                font-black
                                uppercase
                                text-violet-700
                            ">
                            ◷ Temporada {{ $activeSeason->number }} en curso
                        </span>
                    @endif

                </div>


                <h2
                    class="
                        mt-5
                        text-3xl
                        font-black
                        tracking-tight
                        text-slate-900
                    ">
                    {{ $universe->name }}
                </h2>


                <p
                    class="
                        mt-4
                        max-w-3xl
                        whitespace-pre-line
                        text-sm
                        leading-7
                        text-slate-500
                    ">
                    {{ $universe->description ?: 'Este Universo todavía no tiene descripción.' }}
                </p>


                @can('update', $universe)
                    <div
                        class="
                            mt-7
                            flex
                            flex-wrap
                            gap-3
                        ">

                        <a href="{{ route('universes.competitors.create', $universe) }}"
                            class="
                                rounded-xl
                                bg-violet-600
                                px-4
                                py-2.5
                                text-xs
                                font-black
                                text-white
                                shadow-lg
                                shadow-violet-600/20
                            ">
                            + Añadir competidores
                        </a>


                        <a href="{{ route('universes.seasons.create', $universe) }}"
                            class="
                                rounded-xl
                                border
                                border-slate-200
                                bg-white
                                px-4
                                py-2.5
                                text-xs
                                font-black
                                text-slate-700
                            ">
                            + Nueva temporada
                        </a>


                        <a href="{{ route('universes.tournaments.create', $universe) }}"
                            class="
                                rounded-xl
                                border
                                border-slate-200
                                bg-white
                                px-4
                                py-2.5
                                text-xs
                                font-black
                                text-slate-700
                            ">
                            + Añadir torneo
                        </a>

                    </div>
                @endcan

            </div>

        </div>

    </section>


    {{-- STATS --}}

    <section
        class="
            mt-6
            grid
            gap-3
            sm:grid-cols-2
            lg:grid-cols-4
        ">

        @foreach ([
        ['label' => 'Competidores', 'value' => $statistics['competitors'], 'icon' => '✦'],
        ['label' => 'Competidores activos', 'value' => $statistics['active_competitors'], 'icon' => '●'],
        ['label' => 'Temporadas', 'value' => $statistics['seasons'], 'icon' => '◷'],
        ['label' => 'Torneos', 'value' => $statistics['tournaments'], 'icon' => '🏆'],
    ] as $item)
            <article
                class="
                    rounded-2xl
                    border
                    border-slate-200
                    bg-white
                    p-5
                ">

                <div
                    class="
                        flex
                        items-center
                        justify-between
                        gap-3
                    ">

                    <div>

                        <p
                            class="
                                text-[9px]
                                font-black
                                uppercase
                                tracking-wider
                                text-slate-400
                            ">
                            {{ $item['label'] }}
                        </p>


                        <p
                            class="
                                mt-2
                                text-3xl
                                font-black
                                text-slate-900
                            ">
                            {{ number_format($item['value']) }}
                        </p>

                    </div>


                    <div
                        class="
                            flex
                            h-10
                            w-10
                            shrink-0
                            items-center
                            justify-center
                            rounded-xl
                            bg-violet-50
                            text-violet-700
                        ">
                        {{ $item['icon'] }}
                    </div>

                </div>

            </article>
        @endforeach

    </section>


    {{-- TEMPORADA ACTUAL --}}

    <section
        class="
            mt-8
            rounded-3xl
            border
            border-violet-200
            bg-gradient-to-br
            from-white
            to-violet-50/60
            p-6
        ">

        <div
            class="
                flex
                items-start
                justify-between
                gap-4
            ">

            <div>
                <p
                    class="
                        text-xs
                        font-black
                        uppercase
                        tracking-[0.18em]
                        text-violet-600
                    ">
                    Tiempo del Universo
                </p>


                <h3
                    class="
                        mt-2
                        text-2xl
                        font-black
                        text-slate-900
                    ">
                    ◷ Temporada actual
                </h3>
            </div>


            <a href="{{ route('universes.seasons.index', $universe) }}"
                class="
                    text-xs
                    font-black
                    text-violet-600
                ">
                Ver todas →
            </a>

        </div>


        @if ($activeSeason)

            <div
                class="
                    mt-5
                    rounded-2xl
                    bg-white
                    p-5
                    shadow-sm
                ">

                <p
                    class="
                        text-[10px]
                        font-black
                        uppercase
                        tracking-wider
                        text-violet-500
                    ">
                    Temporada {{ $activeSeason->number }}
                </p>


                <p
                    class="
                        mt-1
                        text-lg
                        font-black
                        text-slate-900
                    ">
                    {{ $activeSeason->name }}
                </p>


                <p
                    class="
                        mt-2
                        text-xs
                        text-slate-500
                    ">
                    {{ $activeSeason->period_label }}
                </p>

            </div>
        @else

            <div
                class="
                    mt-5
                    rounded-2xl
                    border
                    border-dashed
                    border-violet-300
                    bg-white/70
                    p-8
                    text-center
                ">

                <p
                    class="
                        text-sm
                        font-black
                        text-slate-700
                    ">
                    Este Universo todavía no tiene una temporada en curso
                </p>


                <p
                    class="
                        mt-2
                        text-xs
                        text-slate-500
                    ">
                    Las temporadas organizan el tiempo del Universo: cuándo se
                    juega cada torneo y a qué periodo pertenecen sus resultados.
                </p>


                @can('update', $universe)
                    <a href="{{ route('universes.seasons.create', $universe) }}"
                        class="
                            mt-4
                            inline-flex
                            rounded-xl
                            bg-violet-600
                            px-4
                            py-2.5
                            text-xs
                            font-black
                            text-white
                        ">
                        + Crear la primera temporada
                    </a>
                @endcan

            </div>
        @endif

    </section>


    {{-- CONTENIDO RECIENTE --}}

    <section class="
            mt-6
            grid
            gap-5
            lg:grid-cols-2
        ">

        {{-- COMPETIDORES --}}

        <article
            class="
                rounded-3xl
                border
                border-slate-200
                bg-white
                p-6
            ">

            <div
                class="
                    flex
                    items-start
                    justify-between
                    gap-4
                ">

                <div>
                    <p
                        class="
                            text-xs
                            font-black
                            uppercase
                            tracking-[0.18em]
                            text-violet-600
                        ">
                        Biblioteca en contexto
                    </p>


                    <h3
                        class="
                            mt-2
                            text-2xl
                            font-black
                            text-slate-900
                        ">
                        ✦ Competidores
                    </h3>
                </div>


                <a href="{{ route('universes.competitors.index', $universe) }}"
                    class="
                        text-xs
                        font-black
                        text-violet-600
                    ">
                    Ver todos →
                </a>

            </div>


            <p
                class="
                    mt-3
                    text-sm
                    leading-6
                    text-slate-500
                ">
                Entidades de tu Biblioteca incorporadas a este Universo.
                La entidad original no se copia ni se modifica.
            </p>


            <div class="
                    mt-6
                    space-y-2
                ">

                @forelse ($recentCompetitors as $competitor)
                    <div
                        class="
                            flex
                            items-center
                            gap-3
                            rounded-2xl
                            bg-slate-50
                            p-3
                        ">

                        <div
                            class="
                                flex
                                h-10
                                w-10
                                shrink-0
                                items-center
                                justify-center
                                overflow-hidden
                                rounded-xl
                                bg-violet-100
                                text-violet-500
                            ">

                            @if ($competitor->entity?->image_url)
                                <img src="{{ $competitor->entity->image_url }}"
                                    alt="{{ $competitor->display_label }}"
                                    class="h-full w-full object-cover">
                            @else
                                ✦
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
                                {{ $competitor->display_label }}
                            </p>


                            <p
                                class="
                                    mt-0.5
                                    font-mono
                                    text-[9px]
                                    text-slate-400
                                ">
                                {{ $competitor->entity?->code }}
                            </p>
                        </div>

                    </div>

                @empty

                    <div
                        class="
                            rounded-2xl
                            border
                            border-dashed
                            border-slate-200
                            p-6
                            text-center
                            text-sm
                            text-slate-400
                        ">
                        Todavía no hay competidores en este Universo.
                    </div>
                @endforelse

            </div>

        </article>


        {{-- TORNEOS --}}

        <article
            class="
                rounded-3xl
                border
                border-slate-200
                bg-white
                p-6
            ">

            <div
                class="
                    flex
                    items-start
                    justify-between
                    gap-4
                ">

                <div>
                    <p
                        class="
                            text-xs
                            font-black
                            uppercase
                            tracking-[0.18em]
                            text-violet-600
                        ">
                        Competiciones
                    </p>


                    <h3
                        class="
                            mt-2
                            text-2xl
                            font-black
                            text-slate-900
                        ">
                        🏆 Torneos
                    </h3>
                </div>


                <a href="{{ route('universes.tournaments.index', $universe) }}"
                    class="
                        text-xs
                        font-black
                        text-violet-600
                    ">
                    Ver todos →
                </a>

            </div>


            <p
                class="
                    mt-3
                    text-sm
                    leading-6
                    text-slate-500
                ">
                Plantillas de la Biblioteca de Torneos adoptadas por este
                Universo, con su nombre y contexto propios.
            </p>


            <div class="
                    mt-6
                    space-y-2
                ">

                @forelse ($recentTournaments as $universeTournament)
                    <div
                        class="
                            flex
                            items-center
                            justify-between
                            gap-4
                            rounded-2xl
                            bg-slate-50
                            p-4
                        ">

                        <div class="min-w-0">
                            <p
                                class="
                                    truncate
                                    text-sm
                                    font-black
                                    text-slate-800
                                ">
                                {{ $universeTournament->name }}
                            </p>


                            <p
                                class="
                                    mt-1
                                    text-[10px]
                                    text-slate-400
                                ">
                                {{ $universeTournament->tournamentTemplate?->name ?? 'Plantilla no disponible' }}
                            </p>
                        </div>


                        <span
                            class="
                                shrink-0
                                rounded-full
                                bg-white
                                px-2.5
                                py-1
                                text-[9px]
                                font-black
                                uppercase
                                text-slate-500
                            ">
                            {{ $universeTournament->status_label }}
                        </span>

                    </div>

                @empty

                    <div
                        class="
                            rounded-2xl
                            border
                            border-dashed
                            border-slate-200
                            p-6
                            text-center
                            text-sm
                            text-slate-400
                        ">
                        Todavía no hay torneos en este Universo.
                    </div>
                @endforelse

            </div>

        </article>

    </section>

</x-universe-layout>
