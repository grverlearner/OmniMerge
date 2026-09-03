@php
    /*
     * La tira de ranking — arriba, en fila, y siempre a la vista.
     *
     * Mientras se juega hacen falta dos respuestas, y ninguna estaba a mano:
     *
     *   ¿cómo va ESTA competición?   quién manda hoy, aquí
     *   ¿cómo va el UNIVERSO?        quién manda en total, con su historia
     *
     * Son dos preguntas distintas y por eso son dos pestañas, no una tabla
     * mezclada: el campeón de hoy puede ser el 18.º del universo, y ver las
     * dos cifras juntas es justo lo interesante.
     *
     * Va en fila y se desplaza a lo ancho porque es un vistazo, no una
     * consulta: quien quiera el detalle tiene la etapa 04 y la página del
     * ranking. Se puede plegar, y se recuerda plegada.
     */

    /* ---------------- ESTA COMPETICIÓN ---------------- */

    $enJuego = $standings
        ->sortBy(fn($row) => $row->placement ?? PHP_INT_MAX)
        ->values();

    /* ---------------- EL UNIVERSO ---------------- */

    /*
     * Quién de los del universo está compitiendo aquí. Marcarlos es lo que
     * convierte una lista ajena en una lista que te habla: se ve de un
     * vistazo si tu campeón viene de arriba o de abajo del todo.
     */
    $enEstaCompeticion = $standings
        ->pluck('universe_entity_id')
        ->filter()
        ->map(fn($id) => (int) $id)
        ->flip();

    $rankingUniverso = collect($ranking)->sortBy('position')->values();

    $llave = 'omnimerge.arena.' . $competition->id . '.ranking';
@endphp

<div x-data="{
        open: true,
        tab: 'competition',

        init() {
            try {
                const guardado = localStorage.getItem(@js($llave));
                if (guardado) {
                    const estado = JSON.parse(guardado);
                    this.open = estado.open !== false;
                    this.tab = estado.tab ?? 'competition';
                }
            } catch (e) { /* modo privado, sin memoria */ }

            this.$watch('open', () => this.remember());
            this.$watch('tab', () => this.remember());
        },

        remember() {
            try {
                localStorage.setItem(@js($llave), JSON.stringify({ open: this.open, tab: this.tab }));
            } catch (e) {}
        },
    }"
    class="shrink-0 border-b border-slate-800 bg-slate-950/60">

    {{-- ============ LA BARRA ============ --}}

    <div class="flex flex-wrap items-center gap-2 px-4 py-2">

        <button type="button" @click="open = !open"
            class="flex items-center gap-1.5 text-[10px] font-black uppercase tracking-[0.18em] text-amber-400 transition hover:text-amber-300">
            <span x-text="open ? '▾' : '▸'"></span>
            🏅 Ranking
        </button>

        {{-- Las dos preguntas --}}

        <div class="flex rounded-lg border border-slate-800 bg-slate-950 p-0.5">

            @foreach ([
                'competition' => ['Esta competición', $enJuego->count()],
                'universe' => ['Universo', $rankingUniverso->count()],
            ] as $clave => [$etiqueta, $cuantos])
                <button type="button" @click="tab = '{{ $clave }}'; open = true"
                    class="flex items-center gap-1.5 rounded-md px-2.5 py-1 text-[10px] font-black transition"
                    :class="tab === '{{ $clave }}'
                        ? 'bg-amber-500 text-slate-950'
                        : 'text-slate-500 hover:text-slate-200'">
                    {{ $etiqueta }}
                    <span class="font-mono opacity-60">{{ $cuantos }}</span>
                </button>
            @endforeach

        </div>

        <span class="text-[10px] text-slate-600" x-show="tab === 'competition'">
            · por puesto final; los que aún juegan salen sin número
        </span>

        <span class="text-[10px] text-slate-600" x-show="tab === 'universe'" x-cloak>
            · todo el historial del universo · los de esta competición van marcados
        </span>

        <a href="{{ route('universes.ranking', $universe) }}"
            class="ml-auto rounded-lg border border-slate-800 px-2.5 py-1 text-[10px] font-black text-slate-500 transition hover:border-amber-500 hover:text-amber-300">
            Ranking completo →
        </a>

    </div>


    {{-- ============ EN FILA ============ --}}

    <div x-show="open" x-cloak class="arena-scroll overflow-x-auto px-4 pb-3">

        {{-- ---------------- ESTA COMPETICIÓN ---------------- --}}

        <div x-show="tab === 'competition'" class="flex min-w-max items-stretch gap-2">

            @forelse ($enJuego as $fila)
                @php
                    $entidad = $fila->universeEntity;
                    $puesto = $fila->placement;
                    $enUniverso = $entidad ? ($ranking[$entidad->id] ?? null) : null;
                @endphp

                <div @class([
                    'flex w-[132px] shrink-0 flex-col overflow-hidden rounded-xl border bg-slate-900/60',
                    'border-amber-400/60 bg-amber-500/10' => $puesto === 1,
                    'border-slate-700' => $puesto === 2 || $puesto === 3,
                    'border-slate-800' => $puesto === null || $puesto > 3,
                ])>

                    <div class="relative aspect-square overflow-hidden bg-slate-950">

                        @if ($entidad?->image_url)
                            <img src="{{ $entidad->image_url }}" alt="{{ $fila->name }}" loading="lazy"
                                class="h-full w-full object-cover">
                        @else
                            <div class="flex h-full w-full items-center justify-center text-2xl opacity-25">✦</div>
                        @endif

                        {{-- El puesto, que es lo que se viene a leer --}}
                        <span @class([
                            'absolute left-1.5 top-1.5 flex h-6 min-w-6 items-center justify-center rounded-lg px-1 font-mono text-[11px] font-black',
                            'bg-amber-400 text-slate-950' => $puesto === 1,
                            'bg-slate-300 text-slate-950' => $puesto === 2,
                            'bg-orange-600 text-white' => $puesto === 3,
                            'bg-slate-950/85 text-slate-400' => $puesto === null || $puesto > 3,
                        ])>
                            {{ $puesto ?? '—' }}
                        </span>

                        @if ($fila->outcome === 'CHAMPION')
                            <span class="absolute right-1.5 top-1.5 text-sm">🏆</span>
                        @endif

                        <span class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-slate-950 via-slate-950/85 to-transparent px-1.5 pb-1 pt-5">
                            <span class="block truncate text-[10px] font-black text-white">{{ $fila->name }}</span>
                        </span>

                    </div>

                    {{-- Sus cifras de HOY --}}
                    <div class="flex items-center justify-between gap-1 px-1.5 py-1">

                        <span class="font-mono text-[9px] text-slate-500">
                            <span class="text-emerald-400">{{ $fila->wins }}</span>·<span
                                class="text-slate-600">{{ $fila->draws }}</span>·<span
                                class="text-rose-400">{{ $fila->losses }}</span>
                        </span>

                        <span class="font-mono text-[10px] font-black text-violet-300">{{ $fila->points }}</span>
                    </div>

                    {{--
                        Y dónde está en el universo. Es el dato que hace hablar
                        a la tira: el campeón de hoy puede ser el 18.º de todos.
                    --}}
                    @if ($enUniverso)
                        <div class="border-t border-slate-800/70 px-1.5 py-1">
                            <span class="text-[8px] font-black uppercase tracking-wider text-slate-600">
                                universo
                            </span>
                            <span class="ml-1 font-mono text-[10px] font-black text-cyan-300">
                                #{{ $enUniverso->position }}
                            </span>
                        </div>
                    @endif

                </div>
            @empty
                <p class="py-3 text-[11px] text-slate-600">
                    Todavía no hay clasificación: aparece en cuanto se juegue la primera batalla.
                </p>
            @endforelse

        </div>


        {{-- ---------------- EL UNIVERSO ---------------- --}}

        <div x-show="tab === 'universe'" x-cloak class="flex min-w-max items-stretch gap-2">

            @forelse ($rankingUniverso as $fila)
                @php $compite = $enEstaCompeticion->has((int) $fila->universe_entity_id); @endphp

                <div @class([
                    'flex w-[132px] shrink-0 flex-col overflow-hidden rounded-xl border',
                    'border-cyan-400/50 bg-cyan-500/5' => $compite,
                    'border-slate-800 bg-slate-900/40 opacity-60' => ! $compite,
                ])>

                    <div class="relative aspect-square overflow-hidden bg-slate-950">

                        @if ($fila->entity?->image_url)
                            <img src="{{ $fila->entity->image_url }}" alt="" loading="lazy"
                                class="h-full w-full object-cover">
                        @else
                            <div class="flex h-full w-full items-center justify-center text-2xl opacity-25">✦</div>
                        @endif

                        <span @class([
                            'absolute left-1.5 top-1.5 flex h-6 min-w-6 items-center justify-center rounded-lg px-1 font-mono text-[11px] font-black',
                            'bg-amber-400 text-slate-950' => $fila->position === 1,
                            'bg-slate-300 text-slate-950' => $fila->position === 2,
                            'bg-orange-600 text-white' => $fila->position === 3,
                            'bg-slate-950/85 text-slate-400' => $fila->position > 3,
                        ])>
                            {{ $fila->position }}
                        </span>

                        {{-- Títulos: la única cifra que resume una carrera --}}
                        @if ($fila->titles > 0)
                            <span class="absolute right-1.5 top-1.5 rounded-lg bg-slate-950/85 px-1.5 py-0.5 text-[9px] font-black text-amber-300">
                                🏆 {{ $fila->titles }}
                            </span>
                        @endif

                        <span class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-slate-950 via-slate-950/85 to-transparent px-1.5 pb-1 pt-5">
                            <span class="block truncate text-[10px] font-black text-white">
                                {{ $fila->entity?->display_label ?? '—' }}
                            </span>
                        </span>

                        @if ($compite)
                            <span class="absolute inset-x-0 top-0 h-0.5 bg-cyan-400"></span>
                        @endif

                    </div>

                    <div class="flex items-center justify-between gap-1 px-1.5 py-1">

                        <span class="font-mono text-[9px] text-slate-500">
                            <span class="text-emerald-400">{{ $fila->wins }}</span>·<span
                                class="text-slate-600">{{ $fila->draws }}</span>·<span
                                class="text-rose-400">{{ $fila->losses }}</span>
                        </span>

                        <span class="font-mono text-[10px] font-black text-violet-300">{{ $fila->points }}</span>
                    </div>

                    <div class="flex items-center justify-between gap-1 border-t border-slate-800/70 px-1.5 py-1">

                        <span class="text-[8px] font-black uppercase tracking-wider text-slate-600">
                            {{ $fila->tournaments }} {{ $fila->tournaments === 1 ? 'torneo' : 'torneos' }}
                        </span>

                        <span class="font-mono text-[9px] font-black text-slate-400">
                            {{ round($fila->win_rate) }}%
                        </span>
                    </div>

                    @if ($compite)
                        <div class="bg-cyan-500/10 px-1.5 py-0.5 text-center text-[8px] font-black uppercase tracking-wider text-cyan-300">
                            compite aquí
                        </div>
                    @endif

                </div>
            @empty
                <p class="py-3 text-[11px] text-slate-600">
                    Este universo todavía no tiene ranking: se construye con lo jugado.
                </p>
            @endforelse

        </div>

    </div>

</div>
