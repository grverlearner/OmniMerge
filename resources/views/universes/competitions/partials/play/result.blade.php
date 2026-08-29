@php
    /*
     * ETAPA 4 · El cierre.
     *
     * Regla que gobierna esta pantalla: solo se da por firme la posición
     * que el torneo DISPUTÓ. El motor marca cada plaza como RANKED —se
     * decidió en un enfrentamiento— o TIED_BAND —un rango sin desempatar—.
     * Sin esa distinción se inventaba un tercer puesto que nadie ganó.
     */

    $championEntity = $champion?->universeEntity;

    $championTrophies = $championEntity
        ? $championEntity->trophyAwards()
            ->where('tournament_instance_id', $competition->id)
            ->with('trophy')
            ->get()
        : collect();

    $championRewards = $championEntity
        ? \App\Models\UniverseStatChange::query()
            ->where('tournament_instance_id', $competition->id)
            ->where('universe_entity_id', $championEntity->id)
            ->get()
        : collect();

    /* Plazas realmente disputadas, sin contar la del campeón */
    $decided = $standings
        ->filter(fn($row) => ($bands[$row->runtime_key]['definitive'] ?? false))
        ->filter(fn($row) => (int) $row->placement > 1)
        ->sortBy('placement')
        ->values();

    $decidedKeys = $decided->pluck('runtime_key')->all();

    /* Todo lo demás: se muestra por rango, no por medalla */
    $undecided = $standings
        ->filter(fn($row) => $row->outcome !== 'CHAMPION')
        ->reject(fn($row) => in_array($row->runtime_key, $decidedKeys, true))
        ->sortBy('placement')
        ->values();

    $medals = [2 => '🥈', 3 => '🥉'];

    /*
     * Las salidas por puesto que se quedaron vacías, y por qué.
     *
     * Configurar un «#3 lugar» y no ver a nadie salir por ahí desconcierta.
     * No está roto: ese puesto no se disputó, y decirlo vale más que dejar
     * la salida muda.
     */
    $sinResolver = collect($unresolvedExits ?? []);

    $bandLabel = function ($row) use ($bands) {
        $band = $bands[$row->runtime_key] ?? null;

        if (! $band || $band['from'] === $band['to']) {
            return ($row->placement ?? '—') . '.º';
        }

        return $band['from'] . '.º–' . $band['to'] . '.º';
    };
@endphp

<div class="mx-auto max-w-5xl px-5 py-8">

    @if (!$competition->isClosed())

        <div class="flex min-h-[70vh] items-center justify-center">
            <div class="max-w-md text-center">
                <div class="text-6xl opacity-25">⏳</div>
                <h3 class="mt-6 text-xl font-black text-white">La competición sigue en marcha</h3>
                <p class="mt-2 text-sm text-slate-400">
                    Aquí aparecerán el campeón, el podio y las recompensas cuando termine.
                </p>
                <button type="button" @click="stage = 2"
                    class="mt-6 rounded-xl bg-violet-500 px-6 py-3 text-xs font-black text-white hover:bg-violet-400">
                    Volver a la estructura
                </button>
            </div>
        </div>
    @else

        {{-- ============================================ --}}
        {{-- CAMPEÓN --}}
        {{-- ============================================ --}}

        @if ($champion)

            <div class="relative overflow-hidden rounded-[32px] border border-amber-500/30 bg-gradient-to-b from-amber-500/10 via-slate-950 to-slate-950 p-10 text-center">

                <div class="pointer-events-none absolute -top-24 left-1/2 h-64 w-64 -translate-x-1/2 rounded-full bg-amber-500/20 blur-3xl"></div>

                <div class="relative">

                    <p class="text-[11px] font-black uppercase tracking-[0.35em] text-amber-400">Campeón</p>

                    <div class="mx-auto mt-6 h-56 w-56 overflow-hidden rounded-[32px] border-4 border-amber-400/70 bg-slate-800 shadow-2xl shadow-amber-900/50 xl:h-64 xl:w-64">
                        @if ($championEntity?->image_url)
                            <img src="{{ $championEntity->image_url }}" alt="{{ $champion->name }}"
                                class="h-full w-full object-cover">
                        @else
                            <div class="flex h-full w-full items-center justify-center text-6xl opacity-30">✦</div>
                        @endif
                    </div>

                    <h2 class="mt-6 text-5xl font-black tracking-tight text-white">{{ $champion->name }}</h2>

                    {{-- Cifras del torneo --}}
                    <div class="mx-auto mt-6 grid max-w-lg grid-cols-3 gap-px overflow-hidden rounded-2xl bg-amber-500/20">

                        <div class="bg-slate-950/80 px-4 py-3">
                            <p class="font-mono text-2xl font-black text-white">{{ $champion->matches }}</p>
                            <p class="text-[9px] font-black uppercase tracking-wider text-slate-500">Batallas</p>
                        </div>

                        <div class="bg-slate-950/80 px-4 py-3">
                            <p class="font-mono text-2xl font-black text-emerald-400">{{ $champion->wins }}</p>
                            <p class="text-[9px] font-black uppercase tracking-wider text-slate-500">Ganadas</p>
                        </div>

                        <div class="bg-slate-950/80 px-4 py-3">
                            <p class="font-mono text-2xl font-black text-white">
                                {{ $champion->matches > 0 ? round($champion->wins * 100 / $champion->matches) : 0 }}<span
                                    class="text-sm">%</span>
                            </p>
                            <p class="text-[9px] font-black uppercase tracking-wider text-slate-500">Acierto</p>
                        </div>

                    </div>

                    @if ($competition->season)
                        <p class="mt-4 text-xs text-slate-500">Temporada {{ $competition->season->number }}</p>
                    @endif

                    {{-- Trofeos --}}
                    @if ($championTrophies->isNotEmpty())
                        <div class="mt-6 flex flex-wrap justify-center gap-3">
                            @foreach ($championTrophies as $award)
                                <div class="flex items-center gap-2 rounded-2xl border border-amber-500/30 bg-amber-500/10 px-4 py-2">
                                    <span class="text-xl">{{ $award->trophy?->display_icon ?? '🏆' }}</span>
                                    <span class="text-xs font-black text-amber-200">{{ $award->trophy?->name }}</span>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    {{-- Recompensas --}}
                    @if ($championRewards->isNotEmpty())
                        <div class="mt-4 flex flex-wrap justify-center gap-2">
                            @foreach ($championRewards as $change)
                                <span class="rounded-full bg-emerald-500/15 px-3 py-1 text-[10px] font-black text-emerald-300">
                                    {{ $change->delta_label }} {{ $change->stat_key }}
                                </span>
                            @endforeach
                        </div>
                    @endif

                </div>

            </div>
        @endif


        {{-- ============================================ --}}
        {{-- EL CAMINO DEL CAMPEÓN --}}
        {{-- ============================================ --}}

        @if ($championRoute->isNotEmpty())

            <section class="mt-8">

                <h3 class="mb-4 text-center text-[10px] font-black uppercase tracking-[0.25em] text-slate-500">
                    El camino hasta el título
                </h3>

                <div class="space-y-2">

                    @foreach ($championRoute as $step)

                        <div @class([
                            'flex items-center gap-4 rounded-2xl border px-4 py-3',
                            'border-emerald-500/30 bg-emerald-950/20' => $step['won'],
                            'border-slate-800 bg-slate-900/40' => !$step['won'],
                        ])>

                            {{-- Fase y ronda --}}
                            <div class="w-32 shrink-0">
                                <p class="truncate text-[10px] font-black uppercase tracking-wider text-violet-400">
                                    {{ $step['phase'] }}
                                </p>
                                <p class="text-[9px] text-slate-600">
                                    @if ($step['group'])
                                        {{ $step['group'] }} ·
                                    @endif
                                    @if ($step['round'])
                                        Ronda {{ $step['round'] }}
                                    @endif
                                </p>
                            </div>

                            {{-- Rival --}}
                            <div class="flex min-w-0 flex-1 items-center gap-3">

                                <span class="shrink-0 text-[10px] font-bold text-slate-600">contra</span>

                                <div class="h-10 w-10 shrink-0 overflow-hidden rounded-xl bg-slate-800">
                                    @if ($step['rival_entity']?->image_url)
                                        <img src="{{ $step['rival_entity']->image_url }}" alt=""
                                            class="h-full w-full object-cover">
                                    @endif
                                </div>

                                <p class="min-w-0 truncate text-sm font-black text-white">
                                    {{ $step['rival_name'] ?: '—' }}
                                </p>

                            </div>

                            {{-- Marcador --}}
                            @if ($step['score'])
                                <span class="shrink-0 font-mono text-lg font-black"
                                    @class([
                                        'text-emerald-400' => $step['won'],
                                        'text-slate-500' => !$step['won'],
                                    ])>
                                    {{ $step['score'][0] }}–{{ $step['score'][1] }}
                                </span>
                            @endif

                            <span class="w-6 shrink-0 text-center text-sm">
                                {{ $step['won'] ? '✔' : '✕' }}
                            </span>

                        </div>
                    @endforeach

                </div>

            </section>
        @endif


        {{-- ============================================ --}}
        {{-- SALIDAS POR PUESTO QUE NO SE PUDIERON SERVIR --}}
        {{-- ============================================ --}}

        @if ($sinResolver->isNotEmpty())

            <section class="mt-8">

                <div class="rounded-2xl border border-amber-500/30 bg-amber-500/5 p-4">

                    <h3 class="text-[10px] font-black uppercase tracking-[0.25em] text-amber-300">
                        Salidas que quedaron sin servir
                    </h3>

                    <div class="mt-2 space-y-2">
                        @foreach ($sinResolver as $fila)
                            <div class="rounded-xl bg-slate-950/60 px-3 py-2">

                                <p class="text-[11px] font-black text-slate-200">
                                    «{{ $fila['exit_name'] }}»
                                    <span class="font-mono text-[10px] text-slate-500">
                                        pedía {{ $fila['wanted_from'] }}{{ $fila['wanted_to'] !== $fila['wanted_from'] ? '–' . $fila['wanted_to'] : '' }}.º
                                    </span>
                                </p>

                                <p class="mt-0.5 text-[10px] leading-relaxed text-slate-400">
                                    {{ $fila['reason'] }}
                                </p>

                                @php
                                    $banda = $fila['candidates'][0] ?? null;
                                @endphp

                                @if ($banda)
                                    <p class="mt-1 text-[10px] text-slate-500">
                                        {{ count($fila['candidates']) }} competidores comparten el
                                        <span class="font-mono text-slate-300">{{ $banda['band_from'] }}.º–{{ $banda['band_to'] }}.º</span>.
                                        Para separarlos haría falta un partido entre ellos.
                                    </p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>

            </section>

        @endif


        {{-- ============================================ --}}
        {{-- PLAZAS DISPUTADAS --}}
        {{-- ============================================ --}}

        @if ($decided->isNotEmpty())

            <section class="mt-8">

                <h3 class="mb-4 text-center text-[10px] font-black uppercase tracking-[0.25em] text-slate-500">
                    Plazas disputadas
                </h3>

                <div class="grid gap-3 {{ $decided->count() > 1 ? 'sm:grid-cols-2' : '' }}">

                    @foreach ($decided as $row)
                        <div @class([
                            'flex items-center gap-4 rounded-2xl border p-4',
                            'border-slate-500/40 bg-slate-800/30' => (int) $row->placement === 2,
                            'border-orange-700/40 bg-orange-950/20' => (int) $row->placement === 3,
                            'border-slate-800 bg-slate-900/40' => (int) $row->placement > 3,
                        ])>

                            <span class="shrink-0 text-3xl">
                                {{ $medals[(int) $row->placement] ?? $row->placement . '.º' }}
                            </span>

                            <div class="h-14 w-14 shrink-0 overflow-hidden rounded-2xl bg-slate-800">
                                @if ($row->universeEntity?->image_url)
                                    <img src="{{ $row->universeEntity->image_url }}" alt=""
                                        class="h-full w-full object-cover">
                                @endif
                            </div>

                            <div class="min-w-0 flex-1">
                                <p class="truncate text-base font-black text-white">{{ $row->name }}</p>
                                <p class="text-[10px] text-slate-500">
                                    {{ $row->wins }}V · {{ $row->losses }}D en {{ $row->matches }} batallas
                                </p>
                            </div>

                        </div>
                    @endforeach

                </div>

            </section>
        @endif


        {{-- ============================================ --}}
        {{-- EL RESTO, POR RANGO --}}
        {{-- ============================================ --}}

        @if ($undecided->isNotEmpty())

            <section class="mt-8">

                <h3 class="mb-1 text-center text-[10px] font-black uppercase tracking-[0.25em] text-slate-500">
                    Resto de la clasificación
                </h3>

                <p class="mb-4 text-center text-[10px] text-slate-600">
                    Estas plazas no se disputaron entre sí: se muestran como rango, no como puesto exacto.
                </p>

                <div class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/40">

                    @foreach ($undecided as $row)
                        <div class="flex items-center gap-3 border-b border-slate-800/60 px-4 py-2.5 last:border-0">

                            <span class="w-14 shrink-0 text-center font-mono text-[11px] font-black text-slate-600">
                                {{ $bandLabel($row) }}
                            </span>

                            <div class="h-8 w-8 shrink-0 overflow-hidden rounded-lg bg-slate-800">
                                @if ($row->universeEntity?->image_url)
                                    <img src="{{ $row->universeEntity->image_url }}" alt=""
                                        class="h-full w-full object-cover">
                                @endif
                            </div>

                            <span class="min-w-0 flex-1 truncate text-xs font-semibold text-slate-300">
                                {{ $row->name }}
                            </span>

                            <span class="shrink-0 font-mono text-[11px] text-slate-500">
                                {{ $row->wins }}V·{{ $row->losses }}D
                            </span>

                        </div>
                    @endforeach

                </div>

            </section>
        @endif


        <div class="mt-8 flex flex-wrap justify-center gap-3">

            <button type="button" @click="stage = 2"
                class="rounded-xl border border-slate-700 px-6 py-3 text-xs font-black text-slate-300 transition hover:border-slate-500 hover:text-white">
                Revisar el recorrido
            </button>

            <a href="{{ route('universes.competitions.show', [$universe, $competition]) }}"
                class="rounded-xl bg-slate-800 px-6 py-3 text-xs font-black text-slate-200 transition hover:bg-slate-700">
                Ficha completa
            </a>

        </div>
    @endif

</div>
