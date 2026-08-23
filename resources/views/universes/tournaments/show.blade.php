<x-universe-layout :universe="$universe">

    <x-slot name="header">
        {{ $universeTournament->name }}
    </x-slot>


    <div class="mb-7">

        <a href="{{ route('universes.tournaments.index', $universe) }}"
            class="
                text-xs
                font-black
                text-slate-400
                hover:text-violet-600
            ">
            ← Torneos
        </a>


        {{-- Consecuencias del torneo (Fase 12) --}}

        <a href="{{ route('universes.tournaments.rewards', [$universe, $universeTournament]) }}"
            class="group relative mt-4 block overflow-hidden rounded-3xl bg-gradient-to-br from-amber-500 via-orange-500 to-rose-500 p-[1.5px] shadow-lg shadow-amber-500/20 transition hover:shadow-xl hover:shadow-amber-500/30">

            <span class="relative flex items-center gap-4 rounded-[22px] bg-white px-5 py-4 transition group-hover:bg-amber-50/40">

                {{-- Halo decorativo --}}
                <span class="pointer-events-none absolute -right-8 -top-10 h-32 w-32 rounded-full bg-amber-400/10 blur-2xl"></span>

                <span
                    class="relative flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-amber-500 to-orange-600 text-2xl shadow-lg shadow-amber-600/30 transition group-hover:scale-105">
                    🏆
                </span>

                <span class="relative min-w-0 flex-1">

                    <span class="block text-[10px] font-black uppercase tracking-[0.18em] text-amber-600">
                        Consecuencias
                    </span>

                    <span class="mt-0.5 block text-base font-black text-slate-900">
                        Recompensas y bonus
                    </span>

                    <span class="mt-1 block text-xs leading-relaxed text-slate-500">
                        Qué se llevan los competidores al terminar, qué ventajas hay durante el juego,
                        y sobre qué juego actúa cada una.
                    </span>

                    {{-- Lo que hay configurado, de un vistazo --}}
                    <span class="mt-2.5 flex flex-wrap items-center gap-1.5">

                        @php
                            $totalRewards = $universeTournament->rewards()->count();
                            $totalModifiers = $universeTournament->modifiers()->count();
                        @endphp

                        <span class="rounded-full bg-amber-100 px-2.5 py-0.5 text-[10px] font-black text-amber-700">
                            {{ $totalRewards }} {{ $totalRewards === 1 ? 'recompensa' : 'recompensas' }}
                        </span>

                        <span class="rounded-full bg-sky-100 px-2.5 py-0.5 text-[10px] font-black text-sky-700">
                            {{ $totalModifiers }} {{ $totalModifiers === 1 ? 'bonus' : 'bonus' }}
                        </span>

                        @if ($totalRewards === 0 && $totalModifiers === 0)
                            <span class="text-[10px] font-bold text-slate-400">
                                · sin configurar todavía
                            </span>
                        @endif

                    </span>

                </span>

                <span
                    class="relative flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-slate-950 text-sm font-black text-white transition group-hover:translate-x-0.5">
                    →
                </span>

            </span>

        </a>

    </div>


    {{-- CABECERA: ESTO ES CONFIGURACIÓN, NO UNA COMPETICIÓN --}}

    <section
        class="
            rounded-3xl
            border
            border-slate-200
            bg-white
            p-7
        ">

        <div
            class="
                flex
                flex-col
                justify-between
                gap-5
                lg:flex-row
                lg:items-start
            ">

            <div>

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
                            text-[9px]
                            font-black
                            uppercase
                            tracking-wider
                            text-slate-500
                        ">
                        ⚙ Configuración
                    </span>


                    <span
                        class="
                            rounded-full
                            px-3
                            py-1
                            text-[9px]
                            font-black
                            uppercase

                            {{ match ($universeTournament->status) {
                                'ACTIVE' => 'bg-emerald-100 text-emerald-700',
                                'DRAFT' => 'bg-amber-100 text-amber-700',
                                default => 'bg-slate-200 text-slate-600',
                            } }}
                        ">
                        {{ $universeTournament->status_label }}
                    </span>

                </div>


                <h2
                    class="
                        mt-4
                        text-3xl
                        font-black
                        text-slate-900
                    ">
                    {{ $universeTournament->name }}
                </h2>


                <p
                    class="
                        mt-3
                        max-w-2xl
                        whitespace-pre-line
                        text-sm
                        leading-7
                        text-slate-500
                    ">
                    {{ $universeTournament->description ?: 'Este torneo no tiene descripción.' }}
                </p>


                @if ($universeTournament->tournamentTemplate)
                    <div
                        class="
                            mt-5
                            inline-flex
                            flex-wrap
                            items-center
                            gap-2
                            rounded-2xl
                            bg-slate-50
                            px-4
                            py-3
                        ">

                        <span class="text-xs text-slate-500">
                            Usa la plantilla
                        </span>

                        <span class="text-sm font-black text-slate-800">
                            {{ $universeTournament->tournamentTemplate->name }}
                        </span>

                        <a href="{{ route('tournaments.templates.show', $universeTournament->tournamentTemplate) }}"
                            class="text-xs font-black text-violet-600">
                            Ver diseño →
                        </a>

                    </div>
                @else
                    <p class="mt-5 text-sm font-black text-red-600">
                        La plantilla original ya no está disponible.
                    </p>
                @endif

            </div>


            @can('update', $universe)
                @if ($universeTournament->tournamentTemplate)
                    <a href="{{ route('universes.competitions.create', [$universe, 'universe_tournament_id' => $universeTournament->id]) }}"
                        class="
                            shrink-0
                            rounded-xl
                            bg-violet-600
                            px-5
                            py-3.5
                            text-sm
                            font-black
                            text-white
                            shadow-lg
                            shadow-violet-600/20
                            transition
                            hover:bg-violet-700
                        ">
                        ⚔ Iniciar nueva competición
                    </a>
                @endif
            @endcan

        </div>

    </section>


    {{-- EXPLICACIÓN DE LA FRONTERA --}}

    <section
        class="
            mt-5
            rounded-2xl
            border
            border-violet-200
            bg-violet-50/60
            p-5
        ">

        <p class="text-sm leading-6 text-violet-900">
            Lo de arriba es <strong>configuración</strong>: define cómo se
            jugará. Lo de abajo son <strong>competiciones reales</strong>:
            cada una congela esta configuración al empezar, así que editar
            la plantilla más tarde no altera una competición ya iniciada.
        </p>

    </section>


    {{-- COMPETICIONES --}}

    <section class="mt-8">

        <div
            class="
                flex
                items-end
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
                    Ejecuciones
                </p>

                <h3
                    class="
                        mt-2
                        text-2xl
                        font-black
                        text-slate-900
                    ">
                    ⚔ Competiciones de este torneo
                </h3>
            </div>


            <a href="{{ route('universes.competitions.index', $universe) }}"
                class="text-xs font-black text-violet-600">
                Ver todas →
            </a>

        </div>


        @if ($competitions->isEmpty())

            <div
                class="
                    mt-5
                    rounded-3xl
                    border
                    border-dashed
                    border-slate-300
                    bg-white
                    p-10
                    text-center
                ">

                <div class="text-4xl">
                    ⚔
                </div>


                <p
                    class="
                        mt-3
                        text-sm
                        font-black
                        text-slate-700
                    ">
                    Este torneo todavía no se ha jugado nunca
                </p>


                <p
                    class="
                        mx-auto
                        mt-2
                        max-w-md
                        text-xs
                        text-slate-500
                    ">
                    Al iniciar una competición elegirás qué competidores
                    del Universo participan y en qué temporada ocurre.
                </p>

            </div>
        @else

            <div
                class="
                    mt-5
                    grid
                    gap-4
                    lg:grid-cols-2
                ">

                @foreach ($competitions as $competition)
                    @include('universes.competitions.partials.competition-card', [
                        'competition' => $competition,
                    ])
                @endforeach

            </div>


            <div class="mt-6">
                {{ $competitions->links() }}
            </div>
        @endif

    </section>

</x-universe-layout>
