<x-universe-layout :universe="$universe">

    <x-slot name="header">
        {{ $universe->name }}
    </x-slot>


    {{-- ============================================ --}}
    {{-- CABECERA DEL MUNDO --}}
    {{-- ============================================ --}}

    <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white">

        <div class="grid lg:grid-cols-[300px_1fr]">

            <div class="min-h-[220px] bg-gradient-to-br from-slate-950 via-indigo-950 to-violet-950">

                @if ($universe->image_url)
                    <img src="{{ $universe->image_url }}" alt="{{ $universe->name }}"
                        class="h-full min-h-[220px] w-full object-cover">
                @else
                    <div class="flex h-full min-h-[220px] items-center justify-center text-7xl">🌌</div>
                @endif

            </div>


            <div class="p-7">

                <div class="flex flex-wrap items-center gap-2">

                    <span class="rounded-full bg-slate-100 px-3 py-1 font-mono text-[9px] font-black text-slate-500">
                        {{ $universe->code }}
                    </span>

                    <span
                        class="rounded-full px-3 py-1 text-[9px] font-black uppercase
                            {{ $universe->status === 'ACTIVE'
                                ? 'bg-emerald-100 text-emerald-700'
                                : ($universe->status === 'DRAFT'
                                    ? 'bg-violet-100 text-violet-700'
                                    : 'bg-slate-200 text-slate-600') }}">
                        {{ $universe->status_label }}
                    </span>

                    @if ($activeSeason)
                        <a href="{{ route('universes.seasons.show', [$universe, $activeSeason]) }}"
                            class="rounded-full bg-violet-600 px-3 py-1 text-[9px] font-black uppercase text-white">
                            ◷ Temporada {{ $activeSeason->number }} en curso
                        </a>
                    @else
                        <span class="rounded-full bg-amber-100 px-3 py-1 text-[9px] font-black uppercase text-amber-700">
                            Sin temporada activa
                        </span>
                    @endif

                    @if ($statistics['competitions_running'] > 0)
                        <span class="rounded-full bg-emerald-100 px-3 py-1 text-[9px] font-black uppercase text-emerald-700">
                            ⚔ {{ $statistics['competitions_running'] }} en juego
                        </span>
                    @endif

                </div>


                <h2 class="mt-4 text-3xl font-black tracking-tight text-slate-900">
                    {{ $universe->name }}
                </h2>

                <p class="mt-3 max-w-3xl whitespace-pre-line text-sm leading-7 text-slate-500">
                    {{ $universe->description ?: 'Este Universo todavía no tiene descripción.' }}
                </p>


                @can('update', $universe)
                    <div class="mt-6 flex flex-wrap gap-2">

                        <a href="{{ route('universes.entities.create', $universe) }}"
                            class="rounded-xl bg-violet-600 px-4 py-2.5 text-xs font-black text-white shadow-lg shadow-violet-600/20">
                            + Añadir entidades
                        </a>

                        <a href="{{ route('universes.tournaments.create', $universe) }}"
                            class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-black text-slate-700">
                            + Añadir torneo
                        </a>

                        <a href="{{ route('universes.seasons.create', $universe) }}"
                            class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-black text-slate-700">
                            + Nueva temporada
                        </a>

                    </div>
                @endcan

            </div>

        </div>

    </section>


    {{-- CIFRAS --}}

    <section class="mt-5 grid grid-cols-2 gap-3 lg:grid-cols-4">

        @foreach ([
        ['Entidades', $statistics['entities'], '✦', route('universes.entities.index', $universe)],
        ['Temporadas', $statistics['seasons'], '◷', route('universes.seasons.index', $universe)],
        ['Torneos', $statistics['tournaments'], '🏆', route('universes.tournaments.index', $universe)],
        ['Competiciones', $statistics['competitions'], '⚔', route('universes.competitions.index', $universe)],
    ] as [$label, $value, $icon, $url])
            <a href="{{ $url }}"
                class="group rounded-2xl border border-slate-200 bg-white p-5 transition hover:border-violet-300 hover:shadow-lg hover:shadow-violet-950/5">

                <div class="flex items-center justify-between gap-2">

                    <div class="min-w-0">
                        <p class="text-[9px] font-black uppercase tracking-wider text-slate-400">{{ $label }}</p>
                        <p class="mt-1.5 text-3xl font-black text-slate-900">{{ $value }}</p>
                    </div>

                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-violet-50 text-violet-600 transition group-hover:bg-violet-600 group-hover:text-white">
                        {{ $icon }}
                    </span>

                </div>

            </a>
        @endforeach

    </section>


    <div class="mt-6 grid gap-6 xl:grid-cols-[minmax(0,1fr)_380px]">

        <div class="space-y-6">

            {{-- ============================================ --}}
            {{-- EN JUEGO AHORA --}}
            {{-- ============================================ --}}

            @if ($liveCompetitions->isNotEmpty())
                <section class="rounded-3xl border border-emerald-200 bg-gradient-to-br from-emerald-50/60 to-white p-6">

                    <div class="flex items-end justify-between gap-4">
                        <div>
                            <p class="text-xs font-black uppercase tracking-[0.18em] text-emerald-700">Ahora mismo</p>
                            <h3 class="mt-2 text-2xl font-black text-slate-900">⚔ En juego</h3>
                        </div>

                        <a href="{{ route('universes.competitions.index', $universe) }}"
                            class="text-xs font-black text-emerald-700">Ver todas →</a>
                    </div>

                    <div class="mt-5 grid gap-3 sm:grid-cols-2">

                        @foreach ($liveCompetitions as $competition)
                            <a href="{{ route('universes.competitions.show', [$universe, $competition]) }}"
                                class="group rounded-2xl bg-white p-4 shadow-sm transition hover:shadow-md">

                                <p class="truncate text-sm font-black text-slate-900">{{ $competition->name }}</p>

                                <p class="mt-1 truncate text-[10px] text-slate-400">
                                    {{ $competition->universeTournament?->name }}
                                </p>

                                <div class="mt-3 flex items-center justify-between">
                                    <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-[9px] font-black uppercase text-emerald-700">
                                        {{ $competition->status_label }}
                                    </span>

                                    <span class="text-[10px] font-bold text-slate-400">
                                        {{ $competition->participant_count }} participantes
                                    </span>
                                </div>

                            </a>
                        @endforeach

                    </div>

                </section>
            @endif


            {{-- ============================================ --}}
            {{-- CLASIFICACIÓN --}}
            {{-- ============================================ --}}

            <section class="rounded-3xl border border-slate-200 bg-white p-6">

                <div class="flex items-end justify-between gap-4">
                    <div>
                        <p class="text-xs font-black uppercase tracking-[0.18em] text-violet-600">Quién manda</p>
                        <h3 class="mt-2 text-2xl font-black text-slate-900">📊 Clasificación</h3>
                    </div>

                    <a href="{{ route('universes.ranking', $universe) }}"
                        class="text-xs font-black text-violet-600">Ver completa →</a>
                </div>


                @if ($ranking->isEmpty())
                    <p class="mt-5 rounded-2xl border border-dashed border-slate-200 p-8 text-center text-sm text-slate-400">
                        Todavía no se ha jugado nada. La clasificación aparecerá cuando termine la primera competición.
                    </p>
                @else
                    <div class="mt-5 space-y-2">

                        @foreach ($ranking as $row)
                            <a href="{{ route('universes.entities.show', [$universe, $row->entity]) }}"
                                class="group flex items-center gap-3 rounded-2xl {{ $row->position === 1 ? 'bg-violet-50' : 'bg-slate-50' }} p-3 transition hover:bg-violet-100">

                                <span
                                    class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl text-xs font-black
                                        {{ $row->position === 1 ? 'bg-violet-600 text-white' : 'bg-white text-slate-500' }}">
                                    {{ $row->position }}
                                </span>

                                <div class="flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-violet-100 text-violet-500">
                                    @if ($row->entity->image_url)
                                        <img src="{{ $row->entity->image_url }}" alt="{{ $row->entity->display_label }}"
                                            class="h-full w-full object-cover">
                                    @else
                                        ✦
                                    @endif
                                </div>

                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-black text-slate-900">{{ $row->entity->display_label }}</p>
                                    <p class="mt-0.5 text-[10px] text-slate-400">
                                        {{ $row->tournaments }} torneos · {{ $row->titles }} títulos
                                    </p>
                                </div>

                                <span class="shrink-0 text-right">
                                    <span class="block text-sm font-black text-slate-900 tabular-nums">{{ $row->points }}</span>
                                    <span class="block text-[9px] font-bold uppercase text-slate-400">pts</span>
                                </span>

                            </a>
                        @endforeach

                    </div>
                @endif

            </section>


            {{-- ============================================ --}}
            {{-- ÚLTIMOS CAMPEONES --}}
            {{-- ============================================ --}}

            @if ($recentChampions->isNotEmpty())
                <section class="rounded-3xl border border-slate-200 bg-white p-6">

                    <p class="text-xs font-black uppercase tracking-[0.18em] text-violet-600">Palmarés</p>
                    <h3 class="mt-2 text-2xl font-black text-slate-900">🏆 Últimos campeones</h3>

                    <div class="mt-5 grid gap-3 sm:grid-cols-2">

                        @foreach ($recentChampions as $champion)
                            <div class="flex items-center gap-3 rounded-2xl bg-slate-50 p-3">

                                <div class="flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-violet-100 text-xl text-violet-500 ring-2 ring-violet-500/20">
                                    @if ($champion->universeEntity?->image_url)
                                        <img src="{{ $champion->universeEntity->image_url }}"
                                            alt="{{ $champion->name }}" class="h-full w-full object-cover">
                                    @else
                                        ✦
                                    @endif
                                </div>

                                <div class="min-w-0">
                                    <p class="truncate text-sm font-black text-slate-900">{{ $champion->name }}</p>
                                    <p class="mt-0.5 truncate text-[10px] text-slate-400">
                                        {{ $champion->tournamentInstance?->name }}
                                        @if ($champion->tournamentInstance?->season)
                                            · T{{ $champion->tournamentInstance->season->number }}
                                        @endif
                                    </p>
                                </div>

                            </div>
                        @endforeach

                    </div>

                </section>
            @endif

        </div>


        {{-- ============================================ --}}
        {{-- LATERAL --}}
        {{-- ============================================ --}}

        <div class="space-y-6">

            {{-- PRÓXIMOS TORNEOS --}}

            <section class="rounded-3xl border border-slate-200 bg-white p-6">

                <p class="text-xs font-black uppercase tracking-[0.18em] text-violet-600">Calendario</p>
                <h3 class="mt-2 text-xl font-black text-slate-900">Toca jugar</h3>

                @if (!$activeSeason)
                    <p class="mt-4 rounded-2xl border border-dashed border-amber-300 bg-amber-50/60 p-4 text-xs text-amber-800">
                        Activa una temporada para saber qué torneos tocan.
                    </p>
                @elseif ($upcoming->isEmpty())
                    <p class="mt-4 rounded-2xl border border-dashed border-slate-200 p-4 text-center text-xs text-slate-400">
                        Nada pendiente en la Temporada {{ $activeSeason->number }}.
                    </p>
                @else
                    <div class="mt-4 space-y-2">

                        @foreach ($upcoming as $tournament)
                            <a href="{{ route('universes.tournaments.show', [$universe, $tournament]) }}"
                                class="group flex items-center gap-3 rounded-2xl bg-slate-50 p-3 transition hover:bg-violet-50">

                                <span class="text-lg">🏆</span>

                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-xs font-black text-slate-800">{{ $tournament->name }}</p>
                                    <p class="mt-0.5 truncate text-[10px] text-slate-400">
                                        {{ $tournament->recurrence_label }}
                                    </p>
                                </div>

                                <span class="text-violet-500 transition group-hover:translate-x-0.5">→</span>

                            </a>
                        @endforeach

                    </div>
                @endif

            </section>


            {{-- ACTIVIDAD --}}

            <section class="rounded-3xl border border-slate-200 bg-white p-6">

                <p class="text-xs font-black uppercase tracking-[0.18em] text-violet-600">Crónica</p>
                <h3 class="mt-2 text-xl font-black text-slate-900">Qué ha pasado</h3>

                @if ($activity->isEmpty())
                    <p class="mt-4 rounded-2xl border border-dashed border-slate-200 p-6 text-center text-xs text-slate-400">
                        Aquí aparecerá lo que vaya ocurriendo en el Universo.
                    </p>
                @else
                    <div class="mt-4 space-y-1">

                        @foreach ($activity as $item)
                            <div class="flex items-start gap-3 rounded-xl px-2 py-2.5 transition hover:bg-slate-50">

                                <span class="mt-0.5 shrink-0 text-sm">{{ $item->icon ?: '·' }}</span>

                                <div class="min-w-0 flex-1">
                                    <p class="text-xs leading-5 text-slate-700">{{ $item->message }}</p>
                                    <p class="mt-0.5 text-[10px] text-slate-400">
                                        {{ $item->occurred_at?->diffForHumans() }}
                                    </p>
                                </div>

                            </div>
                        @endforeach

                    </div>
                @endif

            </section>


            {{-- ACCESOS --}}

            <section class="rounded-3xl border border-slate-200 bg-white p-6">

                <p class="text-xs font-black uppercase tracking-[0.18em] text-violet-600">Explorar</p>

                <div class="mt-4 space-y-2">

                    @foreach ([
        ['🧭', 'Explorar el Universo', 'Por tipo y por atributo', route('universes.explorer', $universe)],
        ['📊', 'Clasificación', 'Quién va ganando', route('universes.ranking', $universe)],
        ['◷', 'Historial', 'Todo lo jugado', route('universes.history', $universe)],
    ] as [$icon, $title, $subtitle, $url])
                        <a href="{{ $url }}"
                            class="group flex items-center gap-3 rounded-2xl bg-slate-50 p-3 transition hover:bg-violet-50">

                            <span class="text-lg">{{ $icon }}</span>

                            <div class="min-w-0 flex-1">
                                <p class="truncate text-xs font-black text-slate-800">{{ $title }}</p>
                                <p class="mt-0.5 truncate text-[10px] text-slate-400">{{ $subtitle }}</p>
                            </div>

                            <span class="text-violet-500 transition group-hover:translate-x-0.5">→</span>

                        </a>
                    @endforeach

                </div>

            </section>

        </div>

    </div>

</x-universe-layout>
