@php
    /*
     * Una competición, en una línea.
     *
     * Vive en su propia pieza porque se pinta en tres sitios —la lista, la
     * agrupación por torneo y la agrupación por temporada— y duplicarla tres
     * veces es la forma segura de que las tres dejen de parecerse.
     */

    $suTorneo = $competicion->universeTournament;

    [$tono, $clase, $etiqueta] = $tonoEstado[$competicion->status]
        ?? ['#94a3b8', 'bg-slate-800 text-slate-400', $competicion->status];

    $teEspera = in_array($competicion->runtime_status, ['AWAITING_DECISION', 'BLOCKED'], true);

    $cara = $competicion->image_url ?: $suTorneo?->image_url;
@endphp

<div class="flex flex-wrap items-center gap-3 px-4 py-2 transition hover:bg-slate-950/50">

    <a href="{{ route('universes.competitions.show', [$universe, $competicion]) }}"
        class="h-10 w-10 shrink-0 overflow-hidden rounded-lg border bg-slate-950"
        style="border-color: {{ $teEspera ? '#fb718555' : $tono . '40' }}">
        @if ($cara)
            <img src="{{ $cara }}" alt="" loading="lazy" class="h-full w-full object-cover">
        @else
            <span class="flex h-full w-full items-center justify-center text-sm">🏆</span>
        @endif
    </a>

    <div class="min-w-0 flex-1">
        <div class="flex flex-wrap items-center gap-1.5">
            <a href="{{ route('universes.competitions.show', [$universe, $competicion]) }}"
                class="truncate text-[12px] font-black text-white">{{ $competicion->name }}</a>

            <span class="rounded px-1.5 py-0.5 text-[8px] font-black uppercase tracking-wider {{ $clase }}">
                {{ $etiqueta }}
            </span>

            @if ($teEspera)
                <span class="rounded bg-rose-500/15 px-1.5 py-0.5 text-[8px] font-black uppercase tracking-wider text-rose-300">
                    te espera
                </span>
            @endif
        </div>

        <p class="truncate text-[10px] text-slate-500">
            @if ($competicion->season)
                <span class="font-mono text-violet-300">T{{ $competicion->season->number }}</span>
                <span class="text-slate-600">{{ $competicion->season->name }}</span>
                <span class="text-slate-700">·</span>
            @endif
            {{ $suTorneo?->name ?? 'sin torneo' }}
            @if ($juegos[$competicion->game_key] ?? null)
                <span class="text-slate-700">·</span>
                {{ $juegos[$competicion->game_key]['name'] }}
            @endif
        </p>
    </div>

    <span class="hidden shrink-0 rounded-lg border border-slate-800 px-2 py-1 font-mono text-[10px] text-slate-400 sm:block"
        title="Participantes">
        {{ $competicion->participant_count }}
    </span>

    <span class="hidden shrink-0 font-mono text-[9px] text-slate-600 lg:block">
        {{ $competicion->started_at?->diffForHumans(null, true) ?? 'sin empezar' }}
    </span>

    @if (in_array($competicion->status, ['DRAFT', 'RUNNING', 'PAUSED'], true))
        <a href="{{ route('universes.competitions.play', [$universe, $competicion]) }}"
            class="shrink-0 rounded-lg px-2 py-1 text-[10px] font-black transition"
            style="color: {{ $teEspera ? '#fb7185' : $tono }}">
            {{ $competicion->status === 'DRAFT' ? '▶ Empezar' : ($teEspera ? '⏸ Atender' : '▶ Seguir') }}
        </a>
    @else
        <a href="{{ route('universes.competitions.show', [$universe, $competicion]) }}"
            class="shrink-0 rounded-lg px-2 py-1 text-[10px] font-black text-slate-400 transition hover:text-white">
            Ver →
        </a>
    @endif
</div>
