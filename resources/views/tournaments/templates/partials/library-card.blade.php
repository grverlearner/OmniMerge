@php
    /*
     * Una plantilla de torneo en la biblioteca, en formato ficha.
     *
     * La misma ficha sirve para «cuadrícula» y para «detalle»: en cuadrícula
     * enseña la cara y las cifras, y en detalle despliega además el
     * recorrido —quién entra, qué fases atraviesa, dónde acaba—. Son dos
     * profundidades de la misma cosa, no dos componentes: duplicarla
     * garantizaría que un día enseñaran datos distintos.
     */

    $tono = $tonos[$plantilla->accent];

    $entradas = $plantilla->graphStarts;
    $fases = $plantilla->graphNodes;
    $finales = $plantilla->graphTerminals;

    /*
     * Un torneo sin fases o sin finales no es un borrador a medio hacer: es
     * un torneo que no se puede jugar. Decirlo aquí ahorra abrirlo para
     * descubrirlo.
     */
    $incompleta = $plantilla->graph_nodes_count === 0 || $plantilla->graph_terminals_count === 0;
@endphp

<article
    class="group flex flex-col overflow-hidden rounded-2xl border {{ $tono['borde'] }} bg-slate-900/50 transition hover:bg-slate-900">

    {{-- ============ LA CARA ============ --}}

    <a href="{{ route('tournaments.templates.show', $plantilla) }}"
        class="relative block aspect-[16/9] overflow-hidden bg-slate-950">

        @if ($plantilla->image_url)
            <img src="{{ $plantilla->image_url }}" alt="{{ $plantilla->name }}" loading="lazy"
                class="h-full w-full object-cover transition duration-300 group-hover:scale-105">
        @else
            <span class="flex h-full w-full items-center justify-center text-5xl opacity-20">
                {{ $plantilla->display_icon }}
            </span>
        @endif

        {{-- Qué clase de torneo es --}}
        <span
            class="absolute left-2 top-2 flex items-center gap-1.5 rounded-lg border {{ $tono['borde'] }} bg-slate-950/85 px-2 py-1">
            <span class="text-[11px]">{{ $plantilla->display_icon }}</span>
            <span class="text-[9px] font-black uppercase tracking-wider {{ $tono['texto'] }}">
                {{ $plantilla->category_label ?? 'Torneo' }}
            </span>
        </span>

        {{-- Estado y visibilidad --}}
        <span class="absolute right-2 top-2 flex flex-col items-end gap-1">
            <span
                class="rounded px-1.5 py-0.5 text-[8px] font-black uppercase tracking-wider {{ $estadoTono[$plantilla->status] ?? 'bg-slate-800 text-slate-500' }}">
                {{ $plantilla->status_label }}
            </span>

            @if ($plantilla->visibility === 'PUBLIC')
                <span class="rounded bg-sky-500/20 px-1.5 py-0.5 text-[8px] font-black uppercase tracking-wider text-sky-300">
                    pública
                </span>
            @endif
        </span>

        {{-- Y si ya sostiene torneos de verdad --}}
        <span class="absolute bottom-2 left-2 flex flex-wrap gap-1">
            @if ($plantilla->universe_tournaments_count > 0)
                <span class="rounded-lg bg-emerald-500/90 px-2 py-0.5 text-[9px] font-black text-slate-950">
                    en uso · {{ $plantilla->universe_tournaments_count }}
                </span>
            @endif

            @if ($incompleta)
                <span class="rounded-lg bg-rose-500/90 px-2 py-0.5 text-[9px] font-black text-slate-950">
                    sin terminar
                </span>
            @endif
        </span>

    </a>


    {{-- ============ QUIÉN ES ============ --}}

    <div class="flex-1 p-3">

        <a href="{{ route('tournaments.templates.show', $plantilla) }}"
            class="block truncate text-[13px] font-black text-white transition hover:text-amber-300">
            {{ $plantilla->name }}
        </a>

        <p class="font-mono text-[9px] text-slate-600">{{ $plantilla->code }}</p>

        <p class="mt-1.5 line-clamp-2 text-[11px] leading-relaxed text-slate-500">
            {{ $plantilla->summary ?? $plantilla->description ?? 'Sin descripción.' }}
        </p>


        {{-- ============ SUS CIFRAS ============ --}}

        <div class="mt-2.5 grid grid-cols-4 gap-1.5">

            @foreach ([['Entra', $plantilla->graph_starts_count, 'text-cyan-300'], ['Fases', $plantilla->graph_nodes_count, 'text-amber-300'], ['Enlaces', $plantilla->graph_connections_count, 'text-slate-300'], ['Finales', $plantilla->graph_terminals_count, 'text-violet-300']] as [$etiqueta, $valor, $color])
                <span class="rounded-lg border border-slate-800 bg-slate-950 px-2 py-1.5 text-center">
                    <span class="block font-mono text-[13px] font-black {{ $color }}">{{ $valor }}</span>
                    <span class="block text-[8px] font-black uppercase tracking-wider text-slate-600">
                        {{ $etiqueta }}
                    </span>
                </span>
            @endforeach

        </div>

        <p class="mt-2 flex flex-wrap items-center gap-1.5 text-[10px]">
            <span class="rounded-lg border border-slate-800 bg-slate-950 px-2 py-1 font-bold text-slate-400">
                {{ $plantilla->participant_range_label }}
            </span>

            @if ($plantilla->allow_byes)
                <span class="rounded-lg border border-amber-500/25 bg-amber-500/5 px-2 py-1 font-bold text-amber-300">
                    BYE
                </span>
            @endif

            @foreach ($plantilla->tags as $etiqueta)
                <span class="rounded-lg border border-slate-800 bg-slate-950 px-2 py-1 font-bold text-slate-500">
                    #{{ $etiqueta }}
                </span>
            @endforeach
        </p>


        {{-- ============ EL RECORRIDO, EN MODO DETALLE ============ --}}

        {{--
            x-show y no x-if: cambiar de modo veinte veces no debería
            reconstruir el DOM veinte veces, y aquí no hay campos de
            formulario que puedan estorbar estando ocultos.
        --}}

        <div x-show="view === 'detail'" x-cloak
            class="mt-3 space-y-2 rounded-xl border border-slate-800 bg-slate-950/60 p-2.5">

            {{-- Por dónde entra --}}
            <div>
                <p class="text-[8px] font-black uppercase tracking-wider text-cyan-400">⇥ entra por</p>

                @if ($entradas->isEmpty())
                    <p class="mt-0.5 text-[10px] text-slate-600">Sin entradas: nadie puede empezar.</p>
                @else
                    <div class="mt-1 flex flex-wrap gap-1">
                        @foreach ($entradas as $entrada)
                            <span
                                class="rounded border border-cyan-500/25 bg-cyan-500/5 px-1.5 py-0.5 text-[9px] font-bold text-slate-300">
                                {{ $entrada->name }}
                                @if ($entrada->expected_participants)
                                    <span class="font-mono text-cyan-400">·{{ $entrada->expected_participants }}</span>
                                @endif
                            </span>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Qué atraviesa --}}
            <div>
                <p class="text-[8px] font-black uppercase tracking-wider text-amber-400">▦ atraviesa</p>

                @if ($fases->isEmpty())
                    <p class="mt-0.5 text-[10px] text-slate-600">Sin fases: no hay nada que jugar.</p>
                @else
                    <ol class="mt-1 space-y-1">
                        @foreach ($fases as $indice => $fase)
                            <li class="flex items-center gap-2 rounded border border-slate-800 bg-slate-900/60 px-1.5 py-1">
                                <span class="font-mono text-[9px] font-black text-slate-600">
                                    {{ str_pad($indice + 1, 2, '0', STR_PAD_LEFT) }}
                                </span>

                                <span class="text-[11px]">
                                    {{ $fase->phaseTemplate?->display_icon ?? '◇' }}
                                </span>

                                <span class="min-w-0 flex-1">
                                    <span class="block truncate text-[10px] font-bold text-slate-200">
                                        {{ $fase->name }}
                                    </span>

                                    <span class="block truncate text-[9px] text-slate-600">
                                        {{ $fase->phaseTemplate?->type_label ?? 'Fase sin plantilla' }}
                                    </span>
                                </span>
                            </li>
                        @endforeach
                    </ol>
                @endif
            </div>

            {{-- Dónde acaba --}}
            <div>
                <p class="text-[8px] font-black uppercase tracking-wider text-violet-400">▲ acaba en</p>

                @if ($finales->isEmpty())
                    <p class="mt-0.5 text-[10px] text-slate-600">Sin finales: nadie llega a ninguna parte.</p>
                @else
                    <div class="mt-1 flex flex-wrap gap-1">
                        @foreach ($finales as $final)
                            <span
                                class="rounded border px-1.5 py-0.5 text-[9px] font-bold {{ $finalTono[$final->terminal_type] ?? 'border-slate-700 bg-slate-900 text-slate-400' }}">
                                {{ $final->name }}
                                @if ($final->expected_participants)
                                    <span class="font-mono opacity-70">·{{ $final->expected_participants }}</span>
                                @endif
                            </span>
                        @endforeach
                    </div>
                @endif
            </div>

        </div>

    </div>


    {{-- ============ QUÉ SE PUEDE HACER ============ --}}

    <div class="flex items-center gap-1 border-t border-slate-800 px-2 py-1.5">

        <a href="{{ route('tournaments.templates.show', $plantilla) }}"
            class="rounded-lg px-2 py-1 text-[10px] font-black text-slate-400 transition hover:text-white">
            Ver
        </a>

        @can('update', $plantilla)
            <a href="{{ route('tournaments.templates.edit', $plantilla) }}"
                class="rounded-lg px-2 py-1 text-[10px] font-black text-slate-400 transition hover:text-amber-300">
                ✎ Definición
            </a>

            <a href="{{ route('tournaments.super.show', $plantilla) }}"
                class="rounded-lg px-2 py-1 text-[10px] font-black text-slate-400 transition hover:text-violet-300">
                ⚙ Super Edición
            </a>
        @endcan

    </div>

</article>
