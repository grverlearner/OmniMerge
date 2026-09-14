@php
    /*
     * Una competición, en formato ficha.
     *
     * Lo que hace falta saber de un vistazo: de qué torneo sale, en qué
     * temporada se juega, en qué estado está —y, si está a mitad, si está
     * esperando algo de ti—, cuántos compiten, y quién ganó si ya terminó.
     *
     * La cara es la suya propia si la tiene; si no, la del torneo del que sale,
     * que es de donde viene su identidad.
     */

    $torneo = $competicion->universeTournament;

    $cara = $competicion->image_url ?: $torneo?->image_url;

    $campeon = $competicion->relationLoaded('participants')
        ? $competicion->participants->first()
        : null;

    $tonoEstado = [
        'DRAFT' => ['#fbbf24', 'bg-amber-500/15 text-amber-300'],
        'RUNNING' => ['#34d399', 'bg-emerald-500/15 text-emerald-300'],
        'PAUSED' => ['#fb7185', 'bg-rose-500/15 text-rose-300'],
        'COMPLETED' => ['#22d3ee', 'bg-cyan-500/15 text-cyan-300'],
        'CANCELLED' => ['#475569', 'bg-slate-800 text-slate-500'],
    ];

    [$tono, $claseEstado] = $tonoEstado[$competicion->status] ?? ['#94a3b8', 'bg-slate-800 text-slate-400'];

    /* Lo que de verdad pide atención */
    $teEspera = in_array($competicion->runtime_status, ['AWAITING_DECISION', 'BLOCKED'], true);

    $definicionJuego = $juegos[$competicion->game_key] ?? null;
@endphp

<article class="group flex flex-col overflow-hidden rounded-2xl border bg-slate-900/50 transition duration-300 hover:-translate-y-0.5"
    style="border-color: {{ $teEspera ? '#fb7185' : $tono . '40' }}">

    {{-- ============ SU CARA ============ --}}

    <a href="{{ route('universes.competitions.show', [$universe, $competicion]) }}"
        class="relative block aspect-[16/9] overflow-hidden bg-slate-950">

        @if ($cara)
            <img src="{{ $cara }}" alt="{{ $competicion->name }}" loading="lazy"
                class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
            <span class="absolute inset-x-0 bottom-0 h-3/5 bg-gradient-to-t from-slate-950 to-transparent"></span>
        @else
            <span class="flex h-full w-full items-center justify-center text-4xl"
                style="color: {{ $tono }}66; background: radial-gradient(120% 90% at 50% 0%, {{ $tono }}22, transparent 70%)">
                {{ $definicionJuego['icon'] ?? '🏆' }}
            </span>
        @endif

        {{-- La temporada, que es la etiqueta que pidió el encargo --}}
        @if ($competicion->season)
            <span class="absolute left-2 top-2 flex items-center gap-1 rounded-lg border border-violet-500/50 px-1.5 py-0.5 text-[9px] font-black uppercase tracking-wider text-violet-200"
                style="background-color: #020617d9">
                <span class="font-mono">T{{ $competicion->season->number }}</span>
                <span class="max-w-[90px] truncate font-sans normal-case">{{ $competicion->season->name }}</span>
            </span>
        @else
            <span class="absolute left-2 top-2 rounded-lg border border-slate-700 px-1.5 py-0.5 text-[9px] font-black uppercase tracking-wider text-slate-500"
                style="background-color: #020617d9">
                sin temporada
            </span>
        @endif

        <span class="absolute right-2 top-2 rounded px-1.5 py-0.5 text-[9px] font-black uppercase tracking-wider {{ $claseEstado }}">
            {{ $competicion->status_label }}
        </span>

        <span class="absolute inset-x-0 bottom-0 p-2">
            <span class="block truncate text-[13px] font-black text-white">{{ $competicion->name }}</span>
        </span>
    </a>


    {{-- ============ DE DÓNDE SALE ============ --}}

    <div class="flex-1 space-y-2 p-2.5">

        @if ($torneo)
            <a href="{{ route('universes.tournaments.show', [$universe, $torneo]) }}"
                class="flex items-center gap-1.5 rounded-lg border border-slate-800 bg-slate-950 py-0.5 pl-0.5 pr-2 transition hover:border-slate-600">

                <span class="h-6 w-6 shrink-0 overflow-hidden rounded-md border border-slate-800 bg-slate-900">
                    @if ($torneo->image_url)
                        <img src="{{ $torneo->image_url }}" alt="" loading="lazy" class="h-full w-full object-cover">
                    @else
                        <span class="flex h-full w-full items-center justify-center text-[10px]">🏆</span>
                    @endif
                </span>

                <span class="min-w-0 flex-1">
                    <span class="block text-[8px] font-black uppercase tracking-wider text-slate-600">del torneo</span>
                    <span class="block truncate text-[10px] font-black text-slate-300">{{ $torneo->name }}</span>
                </span>
            </a>
        @else
            <p class="rounded-lg border border-dashed border-slate-800 px-2 py-1 text-center text-[9px] text-slate-600">
                Suelta: no sale de ningún torneo.
            </p>
        @endif

        <div class="flex flex-wrap items-center gap-1">
            <span class="rounded border border-slate-800 px-1.5 py-0.5 font-mono text-[9px] text-slate-400"
                title="Participantes">
                {{ $competicion->participant_count }} comp.
            </span>

            @if ($definicionJuego)
                <span class="rounded border border-slate-800 px-1.5 py-0.5 text-[9px] font-bold text-slate-500"
                    title="Juego con el que se resuelven las batallas">
                    {{ $definicionJuego['icon'] }} {{ $definicionJuego['name'] }}
                </span>
            @endif

            @if ($competicion->started_at)
                <span class="rounded border border-slate-800 px-1.5 py-0.5 font-mono text-[9px] text-slate-600"
                    title="Cuándo empezó">
                    {{ $competicion->started_at->diffForHumans(null, true) }}
                </span>
            @endif
        </div>


        {{-- Lo que está pasando ahora mismo con ella --}}
        @if ($teEspera)
            <p class="rounded-xl border border-rose-500/40 bg-rose-500/10 px-2 py-1.5 text-[10px] font-bold leading-4 text-rose-200">
                ⏸ {{ $competicion->runtime_status_label }} — no avanza sola.
            </p>
        @elseif ($campeon)
            @php $cara2 = $campeon->universeEntity; @endphp

            <div class="flex items-center gap-2 rounded-xl border p-1.5"
                style="border-color: {{ $tono }}40; background-color: {{ $tono }}10">
                <span class="shrink-0 text-sm">🏆</span>

                <span class="h-7 w-7 shrink-0 overflow-hidden rounded-lg border border-slate-800 bg-slate-950">
                    @if ($cara2?->image_url)
                        <img src="{{ $cara2->image_url }}" alt="" loading="lazy" class="h-full w-full object-cover">
                    @else
                        <span class="flex h-full w-full items-center justify-center text-slate-700">◍</span>
                    @endif
                </span>

                <span class="min-w-0 flex-1 truncate text-[11px] font-black" style="color: {{ $tono }}">
                    {{ $cara2?->display_label ?? $campeon->display_name ?? $campeon->name ?? 'Campeón' }}
                </span>
            </div>
        @elseif ($competicion->status === 'DRAFT')
            <p class="rounded-xl border border-dashed border-amber-500/30 px-2 py-1.5 text-center text-[10px] text-amber-300/70">
                Preparada. Falta darle al play.
            </p>
        @elseif ($competicion->runtime_status_label)
            <p class="rounded-xl border border-slate-800 px-2 py-1.5 text-center text-[10px] text-slate-500">
                {{ $competicion->runtime_status_label }}
            </p>
        @endif
    </div>


    {{-- ============ QUÉ SE PUEDE HACER ============ --}}

    <div class="flex items-center gap-1 border-t border-slate-800 px-2 py-1.5">

        <a href="{{ route('universes.competitions.show', [$universe, $competicion]) }}"
            class="rounded-lg px-2 py-1 text-[10px] font-black text-slate-400 transition hover:text-white">
            Ver
        </a>

        @if (in_array($competicion->status, ['DRAFT', 'RUNNING', 'PAUSED'], true))
            <a href="{{ route('universes.competitions.play', [$universe, $competicion]) }}"
                class="rounded-lg px-2 py-1 text-[10px] font-black transition"
                style="color: {{ $teEspera ? '#fb7185' : $tono }}">
                {{ $competicion->status === 'DRAFT' ? '▶ Empezar' : ($teEspera ? '⏸ Atender' : '▶ Seguir') }}
            </a>
        @endif

        <span class="ml-auto font-mono text-[9px] text-slate-700">{{ $competicion->code }}</span>
    </div>

</article>
