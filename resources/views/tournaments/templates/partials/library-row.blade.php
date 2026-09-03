@php
    /*
     * Una plantilla de torneo en una línea.
     *
     * El modo lista es para recorrer muchas de arriba abajo: cabe lo que la
     * ficha dice, pero en horizontal y sin la cara grande. Las fases se
     * enseñan por su NOMBRE, que es lo que distingue dos plantillas que por
     * cifras parecen iguales.
     */

    $tono = $tonos[$plantilla->accent];
@endphp

<div
    class="flex flex-wrap items-center gap-3 rounded-xl border {{ $tono['borde'] }} bg-slate-900/40 px-3 py-2 transition hover:bg-slate-900">

    {{-- La cara, pequeña --}}
    <a href="{{ route('tournaments.templates.show', $plantilla) }}"
        class="h-11 w-11 shrink-0 overflow-hidden rounded-xl border border-slate-800 bg-slate-950">
        @if ($plantilla->image_url)
            <img src="{{ $plantilla->image_url }}" alt="" loading="lazy" class="h-full w-full object-cover">
        @else
            <span class="flex h-full w-full items-center justify-center text-lg opacity-40">
                {{ $plantilla->display_icon }}
            </span>
        @endif
    </a>

    {{-- Quién es --}}
    <div class="min-w-[200px] flex-1">

        <div class="flex flex-wrap items-center gap-1.5">
            <a href="{{ route('tournaments.templates.show', $plantilla) }}"
                class="truncate text-[13px] font-black text-white transition hover:text-amber-300">
                {{ $plantilla->name }}
            </a>

            <span
                class="rounded px-1.5 py-0.5 text-[8px] font-black uppercase tracking-wider {{ $estadoTono[$plantilla->status] ?? 'bg-slate-800 text-slate-500' }}">
                {{ $plantilla->status_label }}
            </span>

            @if ($plantilla->universe_tournaments_count > 0)
                <span
                    class="rounded bg-emerald-500/20 px-1.5 py-0.5 text-[8px] font-black uppercase tracking-wider text-emerald-300">
                    en uso · {{ $plantilla->universe_tournaments_count }}
                </span>
            @endif
        </div>

        <p class="mt-0.5 flex flex-wrap items-center gap-1.5 text-[10px]">
            <span class="font-mono text-slate-600">{{ $plantilla->code }}</span>
            <span class="text-slate-800">·</span>
            <span class="font-bold {{ $tono['texto'] }}">{{ $plantilla->category_label ?? 'Torneo' }}</span>
            <span class="text-slate-800">·</span>
            <span class="text-slate-500">{{ $plantilla->participant_range_label }}</span>
        </p>

    </div>

    {{-- Su recorrido, resumido --}}
    <div class="flex min-w-[260px] flex-1 flex-wrap items-center gap-1">

        @forelse ($plantilla->graphStarts->take(2) as $entrada)
            <span class="rounded border border-cyan-500/25 bg-cyan-500/5 px-1.5 py-0.5 text-[9px] font-bold text-slate-300">
                ⇥ {{ $entrada->name }}
            </span>
        @empty
            <span class="text-[9px] text-slate-700">sin entradas</span>
        @endforelse

        <span class="px-0.5 text-slate-700">→</span>

        @forelse ($plantilla->graphNodes->take(3) as $fase)
            <span class="rounded border border-amber-500/25 bg-amber-500/5 px-1.5 py-0.5 text-[9px] font-bold text-slate-300">
                {{ $fase->name }}
            </span>
        @empty
            <span class="text-[9px] text-slate-700">sin fases</span>
        @endforelse

        @if ($plantilla->graph_nodes_count > 3)
            <span class="text-[9px] text-slate-600">+{{ $plantilla->graph_nodes_count - 3 }}</span>
        @endif

        <span class="px-0.5 text-slate-700">→</span>

        <span class="rounded border border-violet-500/25 bg-violet-500/5 px-1.5 py-0.5 text-[9px] font-bold text-slate-300">
            {{ $plantilla->graph_terminals_count }} finales
        </span>

    </div>

    {{-- Qué se puede hacer --}}
    <div class="flex shrink-0 items-center gap-1">

        @can('update', $plantilla)
            <a href="{{ route('tournaments.templates.edit', $plantilla) }}" title="Definición"
                class="rounded-lg border border-slate-800 px-2 py-1 text-[10px] font-black text-slate-400 transition hover:border-amber-500 hover:text-amber-300">
                ✎
            </a>

            <a href="{{ route('tournaments.super.show', $plantilla) }}" title="Super Edición"
                class="rounded-lg border border-slate-800 px-2 py-1 text-[10px] font-black text-slate-400 transition hover:border-violet-500 hover:text-violet-300">
                ⚙
            </a>
        @endcan

        <a href="{{ route('tournaments.templates.show', $plantilla) }}"
            class="rounded-lg border border-slate-800 px-2.5 py-1 text-[10px] font-black text-slate-300 transition hover:border-slate-600">
            Ver →
        </a>

    </div>

</div>
