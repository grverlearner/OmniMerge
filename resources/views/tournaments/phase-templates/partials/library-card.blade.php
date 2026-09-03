@php
    /*
     * Una fase en la biblioteca, en formato ficha.
     *
     * La misma ficha sirve para los dos modos de rejilla: en «cuadrícula»
     * enseña lo esencial y en «estructura» abre además el recorrido —por
     * dónde entra la gente y por dónde sale, con sus nombres—. Son dos
     * profundidades de la misma cosa, no dos componentes: duplicarla
     * garantizaría que un día enseñen datos distintos.
     */

    $tono = $tonos[$fase->accent];

    $entradas = $fase->inputGates;
    $salidas = $fase->exits;
@endphp

<article class="group flex flex-col overflow-hidden rounded-2xl border {{ $tono['borde'] }} bg-slate-900/50 transition hover:bg-slate-900">

    {{-- ============ LA CARA ============ --}}

    <a href="{{ route('tournaments.phase-templates.show', $fase) }}"
        class="relative block aspect-[16/9] overflow-hidden bg-slate-950">

        @if ($fase->image_url)
            <img src="{{ $fase->image_url }}" alt="{{ $fase->name }}" loading="lazy"
                class="h-full w-full object-cover transition duration-300 group-hover:scale-105">
        @else
            <span class="flex h-full w-full items-center justify-center text-5xl opacity-20">
                {{ $fase->display_icon }}
            </span>
        @endif

        {{-- El motor, que es lo primero que se busca --}}
        <span class="absolute left-2 top-2 flex items-center gap-1.5 rounded-lg border {{ $tono['borde'] }} bg-slate-950/85 px-2 py-1">
            <span class="text-[11px]">{{ $fase->display_icon }}</span>
            <span class="text-[9px] font-black uppercase tracking-wider {{ $tono['texto'] }}">
                {{ $fase->type_label }}
            </span>
        </span>

        {{-- Estado y visibilidad --}}
        <span class="absolute right-2 top-2 flex flex-col items-end gap-1">
            <span class="rounded px-1.5 py-0.5 text-[8px] font-black uppercase tracking-wider {{ $estadoTono[$fase->status] ?? 'bg-slate-800 text-slate-500' }}">
                {{ $fase->status }}
            </span>

            @if ($fase->visibility === 'PUBLIC')
                <span class="rounded bg-sky-500/20 px-1.5 py-0.5 text-[8px] font-black uppercase tracking-wider text-sky-300">
                    pública
                </span>
            @endif
        </span>

        {{-- Y si ya la está usando algún torneo --}}
        @if ($fase->tournament_phase_nodes_count > 0)
            <span class="absolute bottom-2 left-2 rounded-lg bg-amber-500/90 px-2 py-0.5 text-[9px] font-black text-slate-950">
                en uso · {{ $fase->tournament_phase_nodes_count }}
            </span>
        @endif

    </a>


    {{-- ============ QUIÉN ES ============ --}}

    <div class="flex-1 p-3">

        <div class="flex items-start gap-2">

            <div class="min-w-0 flex-1">
                <a href="{{ route('tournaments.phase-templates.show', $fase) }}"
                    class="block truncate text-[13px] font-black text-white transition hover:text-amber-300">
                    {{ $fase->name }}
                </a>

                <p class="font-mono text-[9px] text-slate-600">{{ $fase->code }}</p>
            </div>
        </div>

        {{-- La frase de la fase, si la tiene; si no, su descripción --}}
        <p class="mt-1.5 line-clamp-2 text-[11px] leading-relaxed text-slate-500">
            {{ $fase->summary ?? $fase->description ?? 'Sin descripción.' }}
        </p>


        {{-- ============ SU FORMA ============ --}}

        <div class="mt-2.5 flex flex-wrap gap-1.5">

            <span class="rounded-lg border border-slate-800 bg-slate-950 px-2 py-1 text-[9px] font-bold text-slate-400">
                {{ $fase->participant_mode_label }}
            </span>

            <span class="flex items-center gap-1 rounded-lg border border-slate-800 bg-slate-950 px-2 py-1">
                <span class="text-[9px] font-black text-cyan-300">{{ $fase->input_gates_count }}</span>
                <span class="text-[9px] text-slate-600">entradas</span>
            </span>

            <span class="flex items-center gap-1 rounded-lg border border-slate-800 bg-slate-950 px-2 py-1">
                <span class="text-[9px] font-black text-violet-300">{{ $fase->exits_count }}</span>
                <span class="text-[9px] text-slate-600">salidas</span>
            </span>

        </div>


        {{-- ============ EL RECORRIDO, EN MODO ESTRUCTURA ============ --}}

        {{--
            x-show y no x-if: cambiar de modo veinte veces no debería
            reconstruir el DOM veinte veces, y aquí no hay campos de
            formulario que puedan estorbar estando ocultos.
        --}}

        <div x-show="view === 'structure'" x-cloak
            class="mt-2.5 space-y-1.5 rounded-xl border border-slate-800 bg-slate-950/60 p-2">

            {{-- Por dónde entra --}}
            <div>
                <p class="text-[8px] font-black uppercase tracking-wider text-cyan-400">⇥ entra por</p>

                @if ($entradas->isEmpty())
                    <p class="mt-0.5 text-[10px] text-slate-600">Sin puertas de entrada.</p>
                @else
                    <div class="mt-1 flex flex-wrap gap-1">
                        @foreach ($entradas as $puerta)
                            <span class="rounded border border-cyan-500/25 bg-cyan-500/5 px-1.5 py-0.5 text-[9px] font-bold text-slate-300">
                                {{ $puerta->name }}
                                @if ($puerta->exact_participants)
                                    <span class="font-mono text-cyan-400">·{{ $puerta->exact_participants }}</span>
                                @endif
                            </span>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="flex items-center gap-1.5">
                <span class="h-px flex-1 bg-slate-800"></span>
                <span class="text-[9px] font-black {{ $tono['texto'] }}">{{ $fase->type_label }}</span>
                <span class="h-px flex-1 bg-slate-800"></span>
            </div>

            {{-- Por dónde sale --}}
            <div>
                <p class="text-[8px] font-black uppercase tracking-wider text-violet-400">▲ sale por</p>

                @if ($salidas->isEmpty())
                    <p class="mt-0.5 text-[10px] text-slate-600">Sin salidas: nadie avanza desde aquí.</p>
                @else
                    <div class="mt-1 flex flex-wrap gap-1">
                        @foreach ($salidas as $salida)
                            <span class="rounded border border-violet-500/25 bg-violet-500/5 px-1.5 py-0.5 text-[9px] font-bold text-slate-300">
                                {{ $salida->name }}
                            </span>
                        @endforeach
                    </div>
                @endif
            </div>

        </div>

    </div>


    {{-- ============ QUÉ SE PUEDE HACER ============ --}}

    <div class="flex items-center gap-1 border-t border-slate-800 px-2 py-1.5">

        <a href="{{ route('tournaments.phase-templates.show', $fase) }}"
            class="rounded-lg px-2 py-1 text-[10px] font-black text-slate-400 transition hover:text-white">
            Ver
        </a>

        @can('update', $fase)
            <a href="{{ route('tournaments.phase-templates.edit', $fase) }}"
                class="rounded-lg px-2 py-1 text-[10px] font-black text-slate-400 transition hover:text-amber-300">
                ✎ Definición
            </a>

            <a href="{{ route('tournaments.phase-templates.super.show', $fase) }}"
                class="rounded-lg px-2 py-1 text-[10px] font-black text-slate-400 transition hover:text-violet-300">
                ⚙ Super Edición
            </a>
        @endcan

        @can('duplicate', $fase)
            <form method="POST" action="{{ route('tournaments.phase-templates.duplicate', $fase) }}"
                class="ml-auto">
                @csrf
                <button type="submit" title="Duplicar esta fase"
                    class="rounded-lg px-2 py-1 text-[10px] font-black text-slate-500 transition hover:text-slate-100">
                    ⧉
                </button>
            </form>
        @endcan

    </div>

</article>
