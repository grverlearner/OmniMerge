@php
    /*
     * Una fase en una línea.
     *
     * El modo lista es para recorrer muchas de arriba abajo: cabe todo lo
     * que la ficha dice, pero en horizontal y sin la cara grande. Las
     * entradas y salidas se enseñan por su NOMBRE, que es lo que distingue
     * dos fases que por cifras parecen iguales.
     */

    $tono = $tonos[$fase->accent];
@endphp

<div class="flex flex-wrap items-center gap-3 rounded-xl border {{ $tono['borde'] }} bg-slate-900/40 px-3 py-2 transition hover:bg-slate-900">

    {{-- La cara, pequeña --}}
    <a href="{{ route('tournaments.phase-templates.show', $fase) }}"
        class="h-11 w-11 shrink-0 overflow-hidden rounded-xl border border-slate-800 bg-slate-950">
        @if ($fase->image_url)
            <img src="{{ $fase->image_url }}" alt="" loading="lazy" class="h-full w-full object-cover">
        @else
            <span class="flex h-full w-full items-center justify-center text-lg opacity-40">{{ $fase->display_icon }}</span>
        @endif
    </a>

    {{-- Quién es --}}
    <div class="min-w-[180px] flex-1">

        <div class="flex flex-wrap items-center gap-1.5">
            <a href="{{ route('tournaments.phase-templates.show', $fase) }}"
                class="truncate text-[13px] font-black text-white transition hover:text-amber-300">
                {{ $fase->name }}
            </a>

            <span class="rounded px-1.5 py-0.5 text-[8px] font-black uppercase tracking-wider {{ $estadoTono[$fase->status] ?? 'bg-slate-800 text-slate-500' }}">
                {{ $fase->status }}
            </span>

            @if ($fase->tournament_phase_nodes_count > 0)
                <span class="rounded bg-amber-500/20 px-1.5 py-0.5 text-[8px] font-black uppercase tracking-wider text-amber-300">
                    en uso · {{ $fase->tournament_phase_nodes_count }}
                </span>
            @endif
        </div>

        <p class="mt-0.5 flex flex-wrap items-center gap-1.5 text-[10px]">
            <span class="font-mono text-slate-600">{{ $fase->code }}</span>
            <span class="text-slate-800">·</span>
            <span class="font-bold {{ $tono['texto'] }}">{{ $fase->type_label }}</span>
            <span class="text-slate-800">·</span>
            <span class="text-slate-500">{{ $fase->participant_mode_label }}</span>
        </p>

    </div>

    {{-- Por dónde entra y por dónde sale, con nombres --}}
    <div class="flex min-w-[220px] flex-1 flex-wrap items-center gap-1">

        @forelse ($fase->inputGates->take(3) as $puerta)
            <span class="rounded border border-cyan-500/25 bg-cyan-500/5 px-1.5 py-0.5 text-[9px] font-bold text-slate-300">
                ⇥ {{ $puerta->name }}
            </span>
        @empty
            <span class="text-[9px] text-slate-700">sin entradas</span>
        @endforelse

        @if ($fase->input_gates_count > 3)
            <span class="text-[9px] text-slate-600">+{{ $fase->input_gates_count - 3 }}</span>
        @endif

        <span class="px-0.5 text-slate-700">→</span>

        @forelse ($fase->exits->take(3) as $salida)
            <span class="rounded border border-violet-500/25 bg-violet-500/5 px-1.5 py-0.5 text-[9px] font-bold text-slate-300">
                {{ $salida->name }} ▲
            </span>
        @empty
            <span class="text-[9px] text-slate-700">sin salidas</span>
        @endforelse

        @if ($fase->exits_count > 3)
            <span class="text-[9px] text-slate-600">+{{ $fase->exits_count - 3 }}</span>
        @endif

    </div>

    {{-- Qué se puede hacer --}}
    <div class="flex shrink-0 items-center gap-1">

        @can('update', $fase)
            <a href="{{ route('tournaments.phase-templates.edit', $fase) }}"
                class="rounded-lg border border-slate-800 px-2 py-1 text-[10px] font-black text-slate-400 transition hover:border-amber-500 hover:text-amber-300">
                ✎
            </a>

            <a href="{{ route('tournaments.phase-templates.super.show', $fase) }}"
                class="rounded-lg border border-slate-800 px-2 py-1 text-[10px] font-black text-slate-400 transition hover:border-violet-500 hover:text-violet-300">
                ⚙
            </a>
        @endcan

        <a href="{{ route('tournaments.phase-templates.show', $fase) }}"
            class="rounded-lg border border-slate-800 px-2.5 py-1 text-[10px] font-black text-slate-300 transition hover:border-slate-600">
            Ver →
        </a>

    </div>

</div>
