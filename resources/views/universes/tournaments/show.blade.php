@php
    /*
     * LA FICHA DE UN TORNEO OFICIAL
     *
     * Lo que ves al abrir un torneo del universo. Es la marca presentada:
     * qué es, con qué se juega, qué se lleva quien gana, quién puede entrar,
     * y todas sus ediciones.
     *
     * Se lee de arriba abajo:
     *
     *   portada        qué es y sus cifras
     *   cómo se pelea  el juego y el formato de batalla
     *   qué se gana    trofeos y premios
     *   quién compite  las reglas, con los competidores que hoy cumplen
     *   ediciones      cada competición, con su temporada y su gente
     *
     * Todo lo de arriba es lo que HEREDA cada edición. Abajo se ve qué pasó
     * de verdad en cada una.
     */

    $t = $universeTournament;

    $recurrence = match ($t->recurrence_mode) {
        'EVERY_SEASON' => 'Cada temporada',
        'EVERY_N_SEASONS' => 'Cada ' . $t->recurrence_interval . ' temporadas',
        'MANUAL' => 'Cuando se convoque',
        default => $t->recurrence_mode,
    };

    $battle = $t->series_format === 'FIXED_GAMES'
        ? ($t->fixed_games === 1 ? 'Un solo juego' : $t->fixed_games . ' juegos fijos')
        : ($t->best_of === 1 ? 'A un juego' : 'Al mejor de ' . $t->best_of);
@endphp

<x-universe-layout :universe="$universe" surface="dark">

    <x-slot name="header">{{ $t->name }}</x-slot>

    {{-- ==================== PORTADA ==================== --}}

    <section class="relative mb-4 overflow-hidden rounded-3xl border border-amber-500/30 bg-slate-900/60">

        <div class="pointer-events-none absolute -right-24 -top-24 h-64 w-64 rounded-full bg-amber-500/10 blur-3xl"></div>

        <div class="relative flex flex-col gap-5 p-5 lg:flex-row lg:items-center">

            <div class="h-24 w-24 shrink-0 overflow-hidden rounded-2xl border border-amber-500/30 bg-slate-950 sm:h-28 sm:w-28">
                @if ($t->image_url)
                    <img src="{{ $t->image_url }}" alt="" class="h-full w-full object-cover">
                @else
                    <div class="flex h-full w-full items-center justify-center text-4xl text-amber-400">🏆</div>
                @endif
            </div>

            <div class="min-w-0 flex-1">

                <div class="flex flex-wrap items-center gap-2">
                    <span class="rounded-full bg-amber-500/15 px-2.5 py-1 text-[9px] font-black uppercase tracking-[0.18em] text-amber-300">
                        Torneo oficial
                    </span>

                    @if ($t->context)
                        <span class="rounded-full bg-slate-800/70 px-2.5 py-1 text-[9px] font-black uppercase tracking-wider text-slate-300">
                            {{ $t->context }}
                        </span>
                    @endif

                    <span class="rounded-full px-2.5 py-1 text-[9px] font-black uppercase tracking-wider
                        {{ $t->status === 'ACTIVE' ? 'bg-emerald-500/15 text-emerald-300' : 'bg-slate-700/50 text-slate-400' }}">
                        {{ $t->status === 'ACTIVE' ? 'Activo' : 'Inactivo' }}
                    </span>
                </div>

                <h1 class="mt-1.5 text-2xl font-black leading-tight text-slate-100">{{ $t->name }}</h1>

                @if ($t->description)
                    <p class="mt-2 max-w-2xl text-xs leading-relaxed text-slate-400">{{ $t->description }}</p>
                @endif
            </div>

            {{-- Las cifras: la respuesta corta --}}

            <div class="grid shrink-0 grid-cols-2 gap-1.5 sm:grid-cols-4 lg:grid-cols-2 xl:grid-cols-4">

                @foreach ([
                    ['Ediciones', $competitions->total(), 'text-amber-300'],
                    ['Pueden competir', $eligibilityPreview['matching'], 'text-rose-300'],
                    ['Trofeos', $rewards->whereNotNull('universe_trophy_id')->count(), 'text-violet-300'],
                    ['Fases', $t->tournamentTemplate?->graphNodes()->count() ?? 0, 'text-sky-300'],
                ] as [$label, $value, $tone])
                    <div class="rounded-xl border border-slate-800 bg-slate-950/60 px-3 py-2 lg:min-w-[92px]">
                        <p class="text-[8px] font-black uppercase tracking-wider text-slate-500">{{ $label }}</p>
                        <p class="font-mono text-xl font-black {{ $tone }}">{{ $value }}</p>
                    </div>
                @endforeach

            </div>

        </div>

        <div class="relative flex flex-wrap items-center gap-2 border-t border-slate-800 bg-slate-950/40 px-5 py-2.5">

            <span class="flex items-center gap-1.5 rounded-full bg-cyan-500/15 px-2.5 py-1 text-[9px] font-black uppercase tracking-wider text-cyan-300">
                ↻ {{ $recurrence }}
            </span>

            @if ($t->first_season_number && $t->recurrence_mode !== 'MANUAL')
                <span class="text-[10px] text-slate-500">desde la temporada {{ $t->first_season_number }}</span>
            @endif

            <p class="mr-auto"></p>

            @can('update', $universe)
                <a href="{{ route('universes.tournaments.edit', [$universe, $t]) }}"
                    class="rounded-lg border border-slate-700 px-3 py-1.5 text-[11px] font-black text-slate-300 transition hover:border-amber-500 hover:text-amber-300">
                    ✎ Editar
                </a>

                <a href="{{ route('universes.competitions.create', ['universe' => $universe, 'universe_tournament_id' => $t->id]) }}"
                    class="rounded-lg bg-amber-500 px-3 py-1.5 text-[11px] font-black text-slate-950 transition hover:bg-amber-400">
                    + Nueva edición
                </a>
            @endcan

        </div>

    </section>


    <div class="grid gap-3 xl:grid-cols-[1.3fr_1fr]">

        {{-- ==================== CÓMO SE PELEA ==================== --}}

        <section class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/40">

            <div class="flex items-center gap-2 border-b border-slate-800 bg-emerald-500/10 px-4 py-2">
                <span class="text-[11px]">🎲</span>
                <h2 class="text-[11px] font-black uppercase tracking-wider text-emerald-300">Cómo se pelea</h2>
            </div>

            <div class="p-4">

                @if ($game)
                    <div class="flex items-start gap-3">
                        <span class="text-3xl">{{ $game['icon'] }}</span>

                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-1.5">
                                <p class="text-[14px] font-black text-slate-100">{{ $game['name'] }}</p>

                                @if ($t->game_mode === 'VARIED')
                                    <span class="rounded-full bg-slate-800 px-2 py-0.5 text-[8px] font-black uppercase tracking-wider text-slate-400">
                                        sugerido · cada edición elige
                                    </span>
                                @else
                                    <span class="rounded-full bg-emerald-500/15 px-2 py-0.5 text-[8px] font-black uppercase tracking-wider text-emerald-300">
                                        siempre este
                                    </span>
                                @endif
                            </div>

                            <p class="mt-1 text-[11px] leading-relaxed text-slate-400">{{ $game['tagline'] }}</p>
                        </div>
                    </div>

                    <div class="mt-3 rounded-xl border border-emerald-500/20 bg-emerald-500/5 p-2.5">
                        <p class="text-[9px] font-black uppercase tracking-wider text-emerald-300">Cómo se gana</p>
                        <p class="mt-1 text-[10px] leading-relaxed text-slate-300">{{ $game['win_condition'] }}</p>
                    </div>
                @else
                    <p class="text-[11px] text-slate-500">Sin juego asignado.</p>
                @endif


                {{-- El formato de batalla --}}

                <div class="mt-3 grid gap-1.5 sm:grid-cols-2">

                    <div class="rounded-xl border border-amber-500/25 bg-amber-500/5 p-2.5">
                        <p class="text-[9px] font-black uppercase tracking-wider text-amber-300">Cada batalla</p>
                        <p class="mt-0.5 text-[13px] font-black text-slate-100">{{ $battle }}</p>
                        <p class="text-[9px] text-slate-500">
                            {{ $t->battle_participants ? $t->battle_participants . ' competidores' : 'los que diga cada fase' }}
                        </p>
                    </div>

                    <div class="rounded-xl border p-2.5
                        {{ $t->decision_mode === 'POINTS_ONLY' ? 'border-violet-500/25 bg-violet-500/5' : 'border-sky-500/25 bg-sky-500/5' }}">
                        <p class="text-[9px] font-black uppercase tracking-wider
                            {{ $t->decision_mode === 'POINTS_ONLY' ? 'text-violet-300' : 'text-sky-300' }}">
                            Gana quien
                        </p>
                        <p class="mt-0.5 text-[13px] font-black text-slate-100">
                            {{ $t->decision_mode === 'POINTS_ONLY' ? 'más anote' : 'gane más enfrentamientos' }}
                        </p>
                        <p class="text-[9px] leading-relaxed text-slate-500">
                            {{ $t->decision_mode === 'POINTS_ONLY'
                                ? 'Solo cuenta el total de puntos.'
                                : 'Si empatan, deciden las anotaciones.' }}
                        </p>
                    </div>

                </div>

                <p class="mt-2 text-[9px] text-slate-600">
                    {{ $t->allow_draws
                        ? 'Una batalla puede quedar en empate.'
                        : 'Una batalla siempre acaba con un ganador.' }}
                </p>


                {{-- La forma del recorrido --}}

                @if ($t->tournamentTemplate)
                    <a href="{{ route('tournaments.templates.show', $t->tournamentTemplate) }}"
                        class="mt-3 flex items-center gap-2 rounded-xl border border-slate-800 bg-slate-950/50 p-2.5 transition hover:border-sky-500/40">
                        <span class="text-lg text-sky-400">⛯</span>
                        <span class="min-w-0 flex-1">
                            <span class="block text-[9px] font-black uppercase tracking-wider text-slate-500">Recorrido por defecto</span>
                            <span class="block truncate text-[12px] font-black text-slate-200">{{ $t->tournamentTemplate->name }}</span>
                        </span>
                        <span class="shrink-0 text-[10px] text-slate-600">ver →</span>
                    </a>
                @endif

            </div>

        </section>


        {{-- ==================== QUÉ SE GANA ==================== --}}

        <section class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/40">

            <div class="flex items-center gap-2 border-b border-slate-800 bg-violet-500/10 px-4 py-2">
                <span class="text-[11px]">🏆</span>
                <h2 class="text-[11px] font-black uppercase tracking-wider text-violet-300">Qué se gana</h2>
                <span class="ml-auto font-mono text-[10px] text-slate-600">{{ $rewards->count() }}</span>
            </div>

            <div class="p-4">

                @forelse ($rewards as $reward)
                    <div class="mb-1.5 flex flex-wrap items-center gap-2 rounded-xl border border-violet-500/25 bg-violet-500/5 px-3 py-2">

                        @if ($reward->trophy)
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-slate-950 text-lg">
                                @if ($reward->trophy->image_url)
                                    <img src="{{ $reward->trophy->image_url }}" alt="" class="h-full w-full object-cover">
                                @else
                                    {{ $reward->trophy->icon ?: '🏆' }}
                                @endif
                            </span>
                        @endif

                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-[12px] font-black text-slate-100">
                                {{ $reward->label ?: ($reward->trophy->name ?? 'Premio') }}
                            </span>
                            <span class="block text-[9px] text-slate-500">
                                {{ $reward->trigger }}@if ($reward->threshold) · {{ $reward->threshold }}@endif
                            </span>
                        </span>

                        @if ($reward->stat_key)
                            <span class="shrink-0 rounded bg-slate-950/60 px-1.5 py-0.5 font-mono text-[9px] text-emerald-300">
                                {{ $reward->stat_key }} {{ $reward->operation }} {{ $reward->amount }}
                            </span>
                        @endif
                    </div>
                @empty
                    <div class="rounded-xl border border-dashed border-slate-700 px-4 py-6 text-center">
                        <p class="text-[11px] font-black text-slate-400">Ganar esto no da nada todavía</p>
                        <p class="mx-auto mt-1 max-w-xs text-[10px] leading-relaxed text-slate-600">
                            Sin trofeo ni recompensas, la victoria no deja rastro en el
                            historial de nadie.
                        </p>

                        @can('update', $universe)
                            <a href="{{ route('universes.tournaments.rewards', [$universe, $t]) }}"
                                class="mt-3 inline-block rounded-lg bg-violet-600 px-3 py-1.5 text-[11px] font-black text-white transition hover:bg-violet-500">
                                Configurar premios
                            </a>
                        @endcan
                    </div>
                @endforelse

            </div>

        </section>

    </div>


    {{-- ==================== QUIÉN PUEDE COMPETIR ==================== --}}

    <section class="mt-3 overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/40">

        <div class="flex flex-wrap items-center gap-2 border-b border-slate-800 bg-rose-500/10 px-4 py-2">
            <span class="text-[11px]">⚑</span>
            <h2 class="text-[11px] font-black uppercase tracking-wider text-rose-300">Quién puede competir</h2>

            <span class="ml-auto font-mono text-[11px] font-black text-slate-300">
                {{ $eligibilityPreview['matching'] }}
                <span class="text-slate-600">/ {{ $eligibilityPreview['total'] }}</span>
            </span>
        </div>

        <div class="p-4">

            @if (empty($eligibilityText))
                <p class="text-[11px] font-black text-emerald-300">Abierto a todos</p>
                <p class="mt-0.5 text-[10px] leading-relaxed text-slate-500">
                    Cualquier competidor del universo puede entrar. Sin reglas que lo
                    restrinjan, la lista de abajo es el universo entero.
                </p>
            @else
                <div class="flex flex-wrap items-center gap-1.5">
                    @foreach ($eligibilityText as $i => $rule)
                        @if ($i > 0)
                            <span class="text-[9px] font-black uppercase tracking-wider text-slate-600">
                                {{ ($eligibilityRules['mode'] ?? 'ALL') === 'ANY' ? 'o' : 'y' }}
                            </span>
                        @endif

                        <span class="rounded-full border border-rose-500/40 bg-rose-500/10 px-2.5 py-1 text-[11px] font-black text-rose-200">
                            {{ $rule['text'] }}
                        </span>
                    @endforeach
                </div>

                <p class="mt-1.5 text-[9px] leading-relaxed text-slate-600">
                    La regla es lo permanente; la lista cambia sola cuando el universo crece.
                </p>
            @endif

            {{-- Los competidores que hoy cumplen --}}

            <div class="mt-3 flex flex-wrap gap-1.5">
                @foreach ($eligibilityPreview['sample'] as $entity)
                    <div class="flex items-center gap-1.5 rounded-xl border border-slate-800 bg-slate-950/50 px-2 py-1.5">
                        <span class="h-7 w-7 shrink-0 overflow-hidden rounded-lg bg-slate-800">
                            @if ($entity['image_url'])
                                <img src="{{ $entity['image_url'] }}" alt="" class="h-full w-full object-cover">
                            @endif
                        </span>
                        <span class="text-[11px] font-bold text-slate-200">{{ $entity['name'] }}</span>
                    </div>
                @endforeach

                @if ($eligibilityPreview['matching'] > count($eligibilityPreview['sample']))
                    <span class="self-center px-2 font-mono text-[10px] text-slate-600">
                        y {{ $eligibilityPreview['matching'] - count($eligibilityPreview['sample']) }} más
                    </span>
                @endif

                @if ($eligibilityPreview['matching'] === 0)
                    <p class="w-full rounded-xl bg-rose-500/10 px-3 py-2 text-[10px] font-bold text-rose-300">
                        Ningún competidor cumple estas reglas. Este torneo no podría
                        celebrarse tal y como está.
                    </p>
                @endif
            </div>

        </div>

    </section>


    {{-- ==================== LAS EDICIONES ==================== --}}

    <section class="mt-3">

        <div class="mb-2 flex flex-wrap items-center gap-2">
            <span class="h-3 w-1 rounded-full bg-slate-600"></span>
            <h2 class="text-[10px] font-black uppercase tracking-[0.18em] text-slate-400">Ediciones</h2>
            <span class="text-[10px] text-slate-600">— cada vez que este torneo se ha jugado</span>
            <span class="ml-auto font-mono text-[10px] text-slate-600">{{ $competitions->total() }}</span>
        </div>

        @if ($competitions->isEmpty())
            <div class="rounded-2xl border border-dashed border-slate-700 px-4 py-10 text-center">
                <p class="text-[12px] font-black text-slate-300">Todavía no se ha jugado nunca</p>
                <p class="mx-auto mt-1 max-w-md text-[10px] leading-relaxed text-slate-600">
                    Un torneo es una marca; una edición es lo que ocurre en una temporada.
                    Crea la primera para que empiece a tener historia.
                </p>

                @can('update', $universe)
                    <a href="{{ route('universes.competitions.create', ['universe' => $universe, 'universe_tournament_id' => $t->id]) }}"
                        class="mt-3 inline-block rounded-lg bg-amber-500 px-3 py-1.5 text-[11px] font-black text-slate-950 transition hover:bg-amber-400">
                        Crear la primera edición
                    </a>
                @endcan
            </div>
        @else
            <div class="grid gap-2 sm:grid-cols-2 xl:grid-cols-3">

                @foreach ($competitions as $competition)
                    <a href="{{ route('universes.competitions.show', [$universe, $competition]) }}"
                        class="group overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50 transition hover:border-amber-500/40">

                        <div class="flex items-center gap-2 border-b border-slate-800 px-3 py-2">

                            <span class="rounded-full px-2 py-0.5 text-[8px] font-black uppercase tracking-wider
                                {{ match ($competition->runtime_status) {
                                    'COMPLETED' => 'bg-emerald-500/15 text-emerald-300',
                                    'RUNNING' => 'bg-amber-500/15 text-amber-300',
                                    default => 'bg-slate-700/50 text-slate-400',
                                } }}">
                                {{ $competition->runtime_status ?: 'READY' }}
                            </span>

                            @if ($competition->season)
                                <span class="rounded-full bg-cyan-500/15 px-2 py-0.5 text-[8px] font-black uppercase tracking-wider text-cyan-300">
                                    T{{ $competition->season->number }}
                                </span>
                            @endif

                            <span class="ml-auto font-mono text-[9px] text-slate-600">{{ $competition->code }}</span>
                        </div>

                        <div class="p-3">
                            <p class="truncate text-[13px] font-black text-slate-100 transition group-hover:text-amber-200">
                                {{ $competition->name }}
                            </p>

                            <p class="mt-0.5 text-[9px] text-slate-500">
                                {{ $competition->participant_count }} competidores
                                @if ($competition->started_at)
                                    · {{ $competition->started_at->diffForHumans() }}
                                @endif
                            </p>

                            {{-- Las caras de quienes jugaron --}}

                            <div class="mt-2 flex -space-x-1.5">
                                @foreach ($competition->participants->take(8) as $participant)
                                    <span class="h-6 w-6 overflow-hidden rounded-full border border-slate-900 bg-slate-800">
                                        @if ($participant->image_url ?? null)
                                            <img src="{{ $participant->image_url }}" alt="" class="h-full w-full object-cover">
                                        @endif
                                    </span>
                                @endforeach

                                @if ($competition->participants->count() > 8)
                                    <span class="flex h-6 items-center pl-3 font-mono text-[9px] text-slate-600">
                                        +{{ $competition->participants->count() - 8 }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    </a>
                @endforeach

            </div>

            <div class="mt-3">{{ $competitions->links() }}</div>
        @endif

    </section>

</x-universe-layout>
