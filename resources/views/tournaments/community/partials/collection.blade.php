@php
    /*
     * Un conjunto de piezas de la comunidad, en los cuatro modos.
     *
     * El mismo archivo dibuja torneos y fases porque en esta pantalla se
     * comparan entre sí: si cada tipo tuviera su propio renderizador, un día
     * enseñarían cosas distintas en sitios equivalentes y dejarían de poder
     * compararse. Lo que cambia entre uno y otro —qué cifras tiene, qué se ve
     * en detalle, a qué ruta se va— está aislado en `$esTorneo`.
     */

    $esTorneo = $kind === 'tournament';

    $rutaFicha = $esTorneo ? 'tournaments.community.tournament' : 'tournaments.community.phase';

    $rutaCopia = $esTorneo ? 'tournaments.templates.duplicate' : 'tournaments.phase-templates.duplicate';
@endphp


{{-- ============ CUADRÍCULA Y DETALLE ============ --}}

<div x-show="view === 'grid' || view === 'detail'" class="grid gap-4" :class="columns">

    @foreach ($items as $pieza)
        @php
            $tono = $tonos[$pieza->accent] ?? $tonos['slate'];

            $puedeCopiarse = auth()->user()?->can('duplicate', $pieza) ?? false;

            $esMia = $pieza->user_id === auth()->id();
        @endphp

        <article
            class="group flex flex-col overflow-hidden rounded-2xl border {{ $tono['borde'] }} bg-slate-900/50 transition hover:bg-slate-900">

            {{-- La cara --}}

            <a href="{{ route($rutaFicha, $pieza) }}"
                class="relative block aspect-[16/9] overflow-hidden bg-slate-950">

                @if ($pieza->image_url)
                    <img src="{{ $pieza->image_url }}" alt="{{ $pieza->name }}" loading="lazy"
                        class="h-full w-full object-cover transition duration-300 group-hover:scale-105">
                @else
                    <span class="flex h-full w-full items-center justify-center text-5xl opacity-20">
                        {{ $pieza->display_icon }}
                    </span>
                @endif

                <span
                    class="absolute left-2 top-2 flex items-center gap-1.5 rounded-lg border {{ $tono['borde'] }} bg-slate-950/85 px-2 py-1">
                    <span class="text-[11px]">{{ $pieza->display_icon }}</span>
                    <span class="text-[9px] font-black uppercase tracking-wider {{ $tono['texto'] }}">
                        {{ $esTorneo ? $pieza->category_label ?? 'Torneo' : $pieza->type_label }}
                    </span>
                </span>

                {{-- Visitas y copias: la única señal honesta que hay aquí --}}
                <span class="absolute right-2 top-2 flex flex-col items-end gap-1">
                    @if ($pieza->clones_count > 0)
                        <span class="rounded bg-emerald-500/85 px-1.5 py-0.5 text-[8px] font-black uppercase tracking-wider text-slate-950">
                            {{ $pieza->clones_count }} copias
                        </span>
                    @endif

                    @if (!$pieza->allow_cloning)
                        <span class="rounded bg-slate-950/85 px-1.5 py-0.5 text-[8px] font-black uppercase tracking-wider text-slate-500">
                            solo mirar
                        </span>
                    @endif
                </span>

                @if ($esMia)
                    <span class="absolute bottom-2 left-2 rounded-lg bg-amber-500/90 px-2 py-0.5 text-[9px] font-black text-slate-950">
                        tuya
                    </span>
                @endif

            </a>


            {{-- Quién es --}}

            <div class="flex-1 p-3">

                <a href="{{ route($rutaFicha, $pieza) }}"
                    class="block truncate text-[13px] font-black text-white transition hover:text-violet-300">
                    {{ $pieza->name }}
                </a>

                {{-- De quién es. En una comunidad, esto no es un detalle --}}
                <a href="{{ route('tournaments.community.index', ['creator' => $pieza->user_id]) }}"
                    class="mt-1 flex items-center gap-2 text-[11px] text-slate-500 transition hover:text-violet-300">
                    <x-user-avatar :user="$pieza->user" size="xs" />
                    <span class="truncate">{{ $pieza->user?->name ?? 'Alguien' }}</span>
                    <span class="font-mono text-[9px] text-slate-700">{{ $pieza->code }}</span>
                </a>

                <p class="mt-1.5 line-clamp-2 text-[11px] leading-relaxed text-slate-500">
                    {{ $pieza->summary ?? $pieza->description ?? 'Sin descripción.' }}
                </p>


                {{-- Sus cifras --}}

                <div class="mt-2.5 grid grid-cols-4 gap-1.5">
                    @php
                        $cifras = $esTorneo
                            ? [
                                ['Entra', $pieza->graph_starts_count, 'text-cyan-300'],
                                ['Fases', $pieza->graph_nodes_count, 'text-amber-300'],
                                ['Enlaces', $pieza->graph_connections_count, 'text-slate-300'],
                                ['Finales', $pieza->graph_terminals_count, 'text-violet-300'],
                            ]
                            : [
                                ['Entradas', $pieza->input_gates_count, 'text-cyan-300'],
                                ['Salidas', $pieza->exits_count, 'text-violet-300'],
                                ['En uso', $pieza->tournament_phase_nodes_count, 'text-amber-300'],
                                ['Vistas', $pieza->views_count, 'text-slate-300'],
                            ];
                    @endphp

                    @foreach ($cifras as [$etiqueta, $valor, $color])
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
                        {{ $esTorneo ? $pieza->participant_range_label : $pieza->participant_mode_label }}
                    </span>

                    @if ($esTorneo)
                        @foreach ($pieza->tags as $etiqueta)
                            <span class="rounded-lg border border-slate-800 bg-slate-950 px-2 py-1 font-bold text-slate-500">
                                #{{ $etiqueta }}
                            </span>
                        @endforeach
                    @endif
                </p>


                {{-- ============ QUÉ HACE POR DENTRO, EN MODO DETALLE ============ --}}

                {{--
                    Copiar algo sin saber qué hace por dentro es copiar un
                    problema. Este bloque es la razón de que exista el modo
                    detalle en esta pantalla.
                --}}

                <div x-show="view === 'detail'" x-cloak
                    class="mt-3 space-y-2 rounded-xl border border-slate-800 bg-slate-950/60 p-2.5">

                    @if ($esTorneo)

                        <div>
                            <p class="text-[8px] font-black uppercase tracking-wider text-cyan-400">⇥ entra por</p>

                            @if ($pieza->graphStarts->isEmpty())
                                <p class="mt-0.5 text-[10px] text-slate-600">Sin entradas.</p>
                            @else
                                <div class="mt-1 flex flex-wrap gap-1">
                                    @foreach ($pieza->graphStarts as $entrada)
                                        <span class="rounded border border-cyan-500/25 bg-cyan-500/5 px-1.5 py-0.5 text-[9px] font-bold text-slate-300">
                                            {{ $entrada->name }}
                                            @if ($entrada->expected_participants)
                                                <span class="font-mono text-cyan-400">·{{ $entrada->expected_participants }}</span>
                                            @endif
                                        </span>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        <div>
                            <p class="text-[8px] font-black uppercase tracking-wider text-amber-400">▦ atraviesa</p>

                            @if ($pieza->graphNodes->isEmpty())
                                <p class="mt-0.5 text-[10px] text-slate-600">Sin fases.</p>
                            @else
                                <ol class="mt-1 space-y-1">
                                    @foreach ($pieza->graphNodes as $indice => $nodo)
                                        <li class="flex items-center gap-2 rounded border border-slate-800 bg-slate-900/60 px-1.5 py-1">
                                            <span class="font-mono text-[9px] font-black text-slate-600">
                                                {{ str_pad($indice + 1, 2, '0', STR_PAD_LEFT) }}
                                            </span>

                                            <span class="text-[11px]">{{ $nodo->phaseTemplate?->display_icon ?? '◇' }}</span>

                                            <span class="min-w-0 flex-1">
                                                <span class="block truncate text-[10px] font-bold text-slate-200">
                                                    {{ $nodo->name }}
                                                </span>
                                                <span class="block truncate text-[9px] text-slate-600">
                                                    {{ $nodo->phaseTemplate?->type_label ?? 'Fase sin plantilla' }}
                                                </span>
                                            </span>
                                        </li>
                                    @endforeach
                                </ol>
                            @endif
                        </div>

                        <div>
                            <p class="text-[8px] font-black uppercase tracking-wider text-violet-400">▲ acaba en</p>

                            @if ($pieza->graphTerminals->isEmpty())
                                <p class="mt-0.5 text-[10px] text-slate-600">Sin finales.</p>
                            @else
                                <div class="mt-1 flex flex-wrap gap-1">
                                    @foreach ($pieza->graphTerminals as $final)
                                        <span class="rounded border px-1.5 py-0.5 text-[9px] font-bold {{ $finalTono[$final->terminal_type] ?? 'border-slate-700 bg-slate-900 text-slate-400' }}">
                                            {{ $final->name }}
                                        </span>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                    @else

                        <div>
                            <p class="text-[8px] font-black uppercase tracking-wider text-cyan-400">⇥ entra por</p>

                            @if ($pieza->inputGates->isEmpty())
                                <p class="mt-0.5 text-[10px] text-slate-600">Sin puertas de entrada.</p>
                            @else
                                <div class="mt-1 flex flex-wrap gap-1">
                                    @foreach ($pieza->inputGates as $puerta)
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
                            <span class="text-[9px] font-black {{ $tono['texto'] }}">{{ $pieza->type_label }}</span>
                            <span class="h-px flex-1 bg-slate-800"></span>
                        </div>

                        <div>
                            <p class="text-[8px] font-black uppercase tracking-wider text-violet-400">▲ sale por</p>

                            @if ($pieza->exits->isEmpty())
                                <p class="mt-0.5 text-[10px] text-slate-600">Sin salidas: nadie avanza desde aquí.</p>
                            @else
                                <div class="mt-1 flex flex-wrap gap-1">
                                    @foreach ($pieza->exits as $salida)
                                        <span class="rounded border border-violet-500/25 bg-violet-500/5 px-1.5 py-0.5 text-[9px] font-bold text-slate-300">
                                            {{ $salida->name }}
                                        </span>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                    @endif

                </div>

            </div>


            {{-- Llevársela --}}

            <div class="flex items-center gap-1 border-t border-slate-800 px-2 py-1.5">

                <a href="{{ route($rutaFicha, $pieza) }}"
                    class="rounded-lg px-2 py-1 text-[10px] font-black text-slate-400 transition hover:text-white">
                    Ver por dentro
                </a>

                @if ($puedeCopiarse)
                    <form method="POST" action="{{ route($rutaCopia, $pieza) }}" class="ml-auto">
                        @csrf
                        <button type="submit"
                            class="rounded-lg bg-violet-500/15 px-2.5 py-1 text-[10px] font-black text-violet-300 transition hover:bg-violet-500 hover:text-white">
                            {{ $esMia ? '⧉ Duplicar' : '↓ Llevármela' }}
                        </button>
                    </form>
                @else
                    <span class="ml-auto px-2 py-1 text-[10px] font-black text-slate-700">
                        No se puede copiar
                    </span>
                @endif

            </div>

        </article>
    @endforeach

</div>


{{-- ============ LISTA ============ --}}

<div x-show="view === 'list'" x-cloak class="space-y-2">

    @foreach ($items as $pieza)
        @php
            $tono = $tonos[$pieza->accent] ?? $tonos['slate'];
            $puedeCopiarse = auth()->user()?->can('duplicate', $pieza) ?? false;
        @endphp

        <div class="flex flex-wrap items-center gap-3 rounded-xl border {{ $tono['borde'] }} bg-slate-900/40 px-3 py-2 transition hover:bg-slate-900">

            <a href="{{ route($rutaFicha, $pieza) }}"
                class="h-11 w-11 shrink-0 overflow-hidden rounded-xl border border-slate-800 bg-slate-950">
                @if ($pieza->image_url)
                    <img src="{{ $pieza->image_url }}" alt="" loading="lazy" class="h-full w-full object-cover">
                @else
                    <span class="flex h-full w-full items-center justify-center text-lg opacity-40">
                        {{ $pieza->display_icon }}
                    </span>
                @endif
            </a>

            <div class="min-w-[200px] flex-1">
                <div class="flex flex-wrap items-center gap-1.5">
                    <a href="{{ route($rutaFicha, $pieza) }}"
                        class="truncate text-[13px] font-black text-white transition hover:text-violet-300">
                        {{ $pieza->name }}
                    </a>

                    <span class="rounded px-1.5 py-0.5 text-[8px] font-black uppercase tracking-wider {{ $tono['fondo'] }} {{ $tono['texto'] }}">
                        {{ $esTorneo ? 'torneo' : 'fase' }}
                    </span>
                </div>

                <p class="mt-0.5 flex flex-wrap items-center gap-1.5 text-[10px]">
                    <span class="text-slate-500">{{ $pieza->user?->name }}</span>
                    <span class="text-slate-800">·</span>
                    <span class="font-bold {{ $tono['texto'] }}">
                        {{ $esTorneo ? $pieza->category_label ?? 'Torneo' : $pieza->type_label }}
                    </span>
                    <span class="text-slate-800">·</span>
                    <span class="text-slate-500">
                        {{ $esTorneo ? $pieza->participant_range_label : $pieza->participant_mode_label }}
                    </span>
                </p>
            </div>

            <div class="flex min-w-[180px] flex-1 flex-wrap items-center gap-1.5 text-[9px]">
                @foreach ($esTorneo ? [['fases', $pieza->graph_nodes_count], ['finales', $pieza->graph_terminals_count]] : [['entradas', $pieza->input_gates_count], ['salidas', $pieza->exits_count]] as [$etiqueta, $valor])
                    <span class="rounded border border-slate-800 bg-slate-950 px-1.5 py-0.5 font-bold text-slate-400">
                        <span class="font-mono text-slate-200">{{ $valor }}</span> {{ $etiqueta }}
                    </span>
                @endforeach

                <span class="rounded border border-slate-800 bg-slate-950 px-1.5 py-0.5 font-bold text-slate-500">
                    <span class="font-mono text-emerald-300">{{ $pieza->clones_count }}</span> copias
                </span>
            </div>

            <div class="flex shrink-0 items-center gap-1">
                <a href="{{ route($rutaFicha, $pieza) }}"
                    class="rounded-lg border border-slate-800 px-2.5 py-1 text-[10px] font-black text-slate-300 transition hover:border-slate-600">
                    Ver →
                </a>

                @if ($puedeCopiarse)
                    <form method="POST" action="{{ route($rutaCopia, $pieza) }}">
                        @csrf
                        <button type="submit"
                            class="rounded-lg bg-violet-500/15 px-2.5 py-1 text-[10px] font-black text-violet-300 transition hover:bg-violet-500 hover:text-white">
                            ↓
                        </button>
                    </form>
                @endif
            </div>

        </div>
    @endforeach

</div>


{{-- ============ TABLA ============ --}}

<div x-show="view === 'table'" x-cloak class="overflow-x-auto rounded-2xl border border-slate-800 bg-slate-900/40">

    <table class="w-full min-w-[900px]">

        <thead class="border-b border-slate-800 text-left">
            <tr class="text-[9px] font-black uppercase tracking-wider text-slate-600">
                <th class="px-3 py-2.5">{{ $esTorneo ? 'Torneo' : 'Fase' }}</th>
                <th class="px-3 py-2.5">Creador</th>
                <th class="px-3 py-2.5">{{ $esTorneo ? 'Tipo' : 'Motor' }}</th>
                <th class="px-3 py-2.5 text-right">Participantes</th>
                <th class="px-3 py-2.5 text-right">{{ $esTorneo ? 'Fases' : 'Entradas' }}</th>
                <th class="px-3 py-2.5 text-right">{{ $esTorneo ? 'Finales' : 'Salidas' }}</th>
                <th class="px-3 py-2.5 text-right">Vistas</th>
                <th class="px-3 py-2.5 text-right">Copias</th>
                <th class="px-3 py-2.5"></th>
            </tr>
        </thead>

        <tbody class="divide-y divide-slate-800/70">
            @foreach ($items as $pieza)
                @php $puedeCopiarse = auth()->user()?->can('duplicate', $pieza) ?? false; @endphp

                <tr class="transition hover:bg-slate-900/60">

                    <td class="px-3 py-2.5">
                        <a href="{{ route($rutaFicha, $pieza) }}" class="flex items-center gap-2">
                            <span class="flex h-7 w-7 shrink-0 items-center justify-center overflow-hidden rounded-lg border border-slate-800 bg-slate-950 text-[11px]">
                                @if ($pieza->image_url)
                                    <img src="{{ $pieza->image_url }}" alt="" loading="lazy" class="h-full w-full object-cover">
                                @else
                                    {{ $pieza->display_icon }}
                                @endif
                            </span>

                            <span class="min-w-0">
                                <span class="block truncate text-[12px] font-black text-white">{{ $pieza->name }}</span>
                                <span class="block font-mono text-[9px] text-slate-600">{{ $pieza->code }}</span>
                            </span>
                        </a>
                    </td>

                    <td class="px-3 py-2.5">
                        <a href="{{ route('tournaments.community.index', ['creator' => $pieza->user_id]) }}"
                            class="text-[11px] text-slate-400 transition hover:text-violet-300">
                            {{ $pieza->user?->name }}
                        </a>
                    </td>

                    <td class="px-3 py-2.5 text-[11px] font-bold {{ ($tonos[$pieza->accent] ?? $tonos['slate'])['texto'] }}">
                        {{ $esTorneo ? $pieza->category_label ?? '—' : $pieza->type_label }}
                    </td>

                    <td class="px-3 py-2.5 text-right font-mono text-[11px] text-slate-300">
                        {{ $esTorneo ? $pieza->participant_range_label : $pieza->participant_mode_label }}
                    </td>

                    <td class="px-3 py-2.5 text-right font-mono text-[11px] text-amber-300">
                        {{ $esTorneo ? $pieza->graph_nodes_count : $pieza->input_gates_count }}
                    </td>

                    <td class="px-3 py-2.5 text-right font-mono text-[11px] text-violet-300">
                        {{ $esTorneo ? $pieza->graph_terminals_count : $pieza->exits_count }}
                    </td>

                    <td class="px-3 py-2.5 text-right font-mono text-[11px] text-slate-400">
                        {{ $pieza->views_count }}
                    </td>

                    <td class="px-3 py-2.5 text-right font-mono text-[11px] {{ $pieza->clones_count > 0 ? 'text-emerald-300' : 'text-slate-700' }}">
                        {{ $pieza->clones_count }}
                    </td>

                    <td class="px-3 py-2.5 text-right">
                        @if ($puedeCopiarse)
                            <form method="POST" action="{{ route($rutaCopia, $pieza) }}" class="inline">
                                @csrf
                                <button type="submit"
                                    class="rounded-lg bg-violet-500/15 px-2 py-1 text-[10px] font-black text-violet-300 transition hover:bg-violet-500 hover:text-white">
                                    ↓ Llevármela
                                </button>
                            </form>
                        @else
                            <span class="text-[10px] text-slate-700">—</span>
                        @endif
                    </td>

                </tr>
            @endforeach
        </tbody>

    </table>

</div>
