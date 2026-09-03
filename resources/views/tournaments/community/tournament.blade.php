@php
    /*
     * La ficha pública de un torneo.
     *
     * Antes de llevarse una plantilla hay que poder ver qué hace por dentro:
     * qué fases encadena, de quién son esas fases y en qué finales acaba.
     * Una ficha que solo enseñara nombre e imagen obligaría a copiarla para
     * descubrirlo, y una copia no se deshace sola.
     */

    $tono = [
        'amber' => ['borde' => 'border-amber-500/40', 'texto' => 'text-amber-300', 'fondo' => 'bg-amber-500/10'],
        'violet' => ['borde' => 'border-violet-500/40', 'texto' => 'text-violet-300', 'fondo' => 'bg-violet-500/10'],
        'cyan' => ['borde' => 'border-cyan-500/40', 'texto' => 'text-cyan-300', 'fondo' => 'bg-cyan-500/10'],
        'emerald' => ['borde' => 'border-emerald-500/40', 'texto' => 'text-emerald-300', 'fondo' => 'bg-emerald-500/10'],
        'rose' => ['borde' => 'border-rose-500/40', 'texto' => 'text-rose-300', 'fondo' => 'bg-rose-500/10'],
        'sky' => ['borde' => 'border-sky-500/40', 'texto' => 'text-sky-300', 'fondo' => 'bg-sky-500/10'],
        'slate' => ['borde' => 'border-slate-700', 'texto' => 'text-slate-300', 'fondo' => 'bg-slate-800/60'],
    ][$template->accent];

    $finalTono = [
        'CHAMPION' => 'border-amber-500/30 bg-amber-500/5 text-amber-300',
        'QUALIFIED' => 'border-emerald-500/30 bg-emerald-500/5 text-emerald-300',
        'PLACEMENT' => 'border-sky-500/30 bg-sky-500/5 text-sky-300',
        'ELIMINATED' => 'border-slate-700 bg-slate-900 text-slate-500',
    ];

    $puedeCopiarse = auth()->user()?->can('duplicate', $template) ?? false;

    $esMia = $template->user_id === auth()->id();
@endphp

<x-tournament-layout surface="dark">

    <x-slot name="header">{{ $template->name }}</x-slot>

    <div class="space-y-4">

        {{-- ===================================================== --}}
        {{-- LA PORTADA --}}
        {{-- ===================================================== --}}

        <header class="overflow-hidden rounded-2xl border {{ $tono['borde'] }} bg-slate-900/50">

            <div class="relative aspect-[21/7] overflow-hidden bg-slate-950">
                @if ($template->image_url)
                    <img src="{{ $template->image_url }}" alt="{{ $template->name }}"
                        class="h-full w-full object-cover">
                    <span class="absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-950/40 to-transparent"></span>
                @else
                    <span class="flex h-full w-full items-center justify-center text-7xl opacity-10">
                        {{ $template->display_icon }}
                    </span>
                @endif

                <a href="{{ route('tournaments.community.index') }}"
                    class="absolute left-4 top-4 rounded-lg border border-slate-700 bg-slate-950/80 px-3 py-1.5 text-[10px] font-black text-slate-300 backdrop-blur transition hover:border-violet-500 hover:text-violet-300">
                    ← Comunidad
                </a>
            </div>

            <div class="flex flex-wrap items-end gap-5 p-5">

                <div class="min-w-0 flex-1">

                    <div class="flex flex-wrap items-center gap-2">
                        <span
                            class="flex items-center gap-1.5 rounded-lg border {{ $tono['borde'] }} {{ $tono['fondo'] }} px-2 py-1">
                            <span class="text-[11px]">{{ $template->display_icon }}</span>
                            <span class="text-[9px] font-black uppercase tracking-wider {{ $tono['texto'] }}">
                                {{ $template->category_label ?? 'Torneo' }}
                            </span>
                        </span>

                        <span class="font-mono text-[10px] text-slate-600">{{ $template->code }}</span>

                        @if ($esMia)
                            <span class="rounded bg-amber-500/20 px-1.5 py-0.5 text-[8px] font-black uppercase tracking-wider text-amber-300">
                                es tuya
                            </span>
                        @endif
                    </div>

                    <h1 class="mt-2 text-2xl font-black tracking-tight text-white">
                        {{ $template->name }}
                    </h1>

                    @if ($template->summary)
                        <p class="mt-1 text-[13px] text-slate-400">{{ $template->summary }}</p>
                    @endif

                    <a href="{{ route('tournaments.community.creator', $template->user) }}"
                        class="mt-3 inline-flex items-center gap-2.5 rounded-xl border border-slate-800 bg-slate-950 px-3 py-2 transition hover:border-violet-500/50">
                        <x-user-avatar :user="$template->user" size="sm" />

                        <span>
                            <span class="block text-[12px] font-black text-slate-200">{{ $template->user->name }}</span>
                            <span class="block text-[10px] text-slate-500">
                                {{ $template->user->headline ?: '@' . $template->user->username }}
                            </span>
                        </span>
                    </a>

                </div>


                {{-- Llevársela --}}

                <div class="w-full max-w-xs shrink-0 space-y-2">

                    @if ($puedeCopiarse)
                        <form method="POST" action="{{ route('tournaments.templates.duplicate', $template) }}">
                            @csrf
                            <button type="submit"
                                class="flex w-full items-center justify-center gap-2 rounded-xl bg-violet-500 px-4 py-3 text-xs font-black text-white shadow-lg shadow-violet-950/40 transition hover:bg-violet-400">
                                <x-omni-icon name="capas" size="h-4 w-4" />
                                {{ $esMia ? 'Duplicar en mi espacio' : 'Llevármela a mi espacio' }}
                            </button>
                        </form>

                        <p class="text-center text-[10px] leading-4 text-slate-600">
                            Entra como borrador privado, con su estructura entera. El original no se
                            entera de lo que hagas después.
                        </p>
                    @else
                        <div class="rounded-xl border border-slate-800 bg-slate-950 px-4 py-3 text-center">
                            <p class="text-xs font-black text-slate-400">Solo para mirar</p>
                            <p class="mt-1 text-[10px] leading-4 text-slate-600">
                                Quien la publicó no ha permitido que se copie.
                            </p>
                        </div>
                    @endif

                    <div class="grid grid-cols-2 gap-2">
                        <span class="rounded-xl border border-slate-800 bg-slate-950 px-3 py-2 text-center">
                            <span class="block font-mono text-lg font-black text-slate-300">{{ $template->views_count }}</span>
                            <span class="block text-[8px] font-black uppercase tracking-wider text-slate-600">Vistas</span>
                        </span>

                        <span class="rounded-xl border border-slate-800 bg-slate-950 px-3 py-2 text-center">
                            <span class="block font-mono text-lg font-black text-emerald-300">{{ $template->clones_count }}</span>
                            <span class="block text-[8px] font-black uppercase tracking-wider text-slate-600">Copias</span>
                        </span>
                    </div>

                </div>

            </div>

        </header>


        <div class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_320px] xl:items-start">

            <div class="space-y-4">

                {{-- ===================================================== --}}
                {{-- EL RECORRIDO --}}
                {{-- ===================================================== --}}

                <section class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

                    <header class="flex items-center gap-3 border-b border-slate-800 px-5 py-3">
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-amber-500/15 text-amber-300">
                            <x-omni-icon name="grafo" size="h-4 w-4" />
                        </span>

                        <div>
                            <h2 class="text-sm font-black text-white">Cómo funciona por dentro</h2>
                            <p class="text-[10px] text-slate-500">
                                Por dónde entra la gente, qué atraviesa y dónde acaba.
                            </p>
                        </div>
                    </header>

                    <div class="space-y-4 p-5">

                        {{-- Entradas --}}
                        <div>
                            <p class="text-[9px] font-black uppercase tracking-wider text-cyan-400">⇥ Entra por</p>

                            @if ($template->graphStarts->isEmpty())
                                <p class="mt-1 text-[11px] text-slate-600">Sin entradas definidas.</p>
                            @else
                                <div class="mt-1.5 flex flex-wrap gap-1.5">
                                    @foreach ($template->graphStarts as $entrada)
                                        <span class="rounded-lg border border-cyan-500/25 bg-cyan-500/5 px-2.5 py-1.5 text-[11px] font-bold text-slate-300">
                                            {{ $entrada->name }}
                                            @if ($entrada->expected_participants)
                                                <span class="ml-1 font-mono text-cyan-400">{{ $entrada->expected_participants }}</span>
                                            @endif
                                        </span>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        {{-- Fases --}}
                        <div>
                            <p class="text-[9px] font-black uppercase tracking-wider text-amber-400">▦ Atraviesa</p>

                            @if ($template->graphNodes->isEmpty())
                                <p class="mt-1 text-[11px] text-slate-600">Sin fases: no hay nada que jugar.</p>
                            @else
                                <ol class="mt-1.5 space-y-1.5">
                                    @foreach ($template->graphNodes as $indice => $nodo)
                                        <li class="flex items-center gap-3 rounded-xl border border-slate-800 bg-slate-950/60 px-3 py-2">

                                            <span class="font-mono text-[11px] font-black text-slate-600">
                                                {{ str_pad($indice + 1, 2, '0', STR_PAD_LEFT) }}
                                            </span>

                                            <span class="text-lg">{{ $nodo->phaseTemplate?->display_icon ?? '◇' }}</span>

                                            <span class="min-w-0 flex-1">
                                                <span class="block truncate text-[12px] font-black text-slate-200">
                                                    {{ $nodo->name }}
                                                </span>
                                                <span class="block truncate text-[10px] text-slate-500">
                                                    {{ $nodo->phaseTemplate?->type_label ?? 'Fase sin plantilla' }}
                                                    @if ($nodo->phaseTemplate?->user && $nodo->phaseTemplate->user_id !== $template->user_id)
                                                        · fase de {{ $nodo->phaseTemplate->user->name }}
                                                    @endif
                                                </span>
                                            </span>

                                            {{--
                                                Si la fase que usa este torneo también está
                                                publicada, se puede ir a ella: a veces lo que
                                                uno quiere no es la copa entera sino una de
                                                sus piezas.
                                            --}}
                                            @if ($nodo->phaseTemplate?->isPublished())
                                                <a href="{{ route('tournaments.community.phase', $nodo->phaseTemplate) }}"
                                                    class="shrink-0 rounded-lg border border-slate-800 px-2 py-1 text-[10px] font-black text-slate-400 transition hover:border-cyan-500 hover:text-cyan-300">
                                                    Ver la fase →
                                                </a>
                                            @endif

                                        </li>
                                    @endforeach
                                </ol>
                            @endif
                        </div>

                        {{-- Finales --}}
                        <div>
                            <p class="text-[9px] font-black uppercase tracking-wider text-violet-400">▲ Acaba en</p>

                            @if ($template->graphTerminals->isEmpty())
                                <p class="mt-1 text-[11px] text-slate-600">Sin finales: nadie llega a ninguna parte.</p>
                            @else
                                <div class="mt-1.5 flex flex-wrap gap-1.5">
                                    @foreach ($template->graphTerminals as $final)
                                        <span class="rounded-lg border px-2.5 py-1.5 text-[11px] font-bold {{ $finalTono[$final->terminal_type] ?? 'border-slate-700 bg-slate-900 text-slate-400' }}">
                                            {{ $final->name }}
                                            <span class="ml-1 text-[9px] uppercase opacity-70">
                                                {{ $final->terminal_type_label }}
                                            </span>
                                        </span>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                    </div>

                </section>


                {{-- ===================================================== --}}
                {{-- LA DESCRIPCIÓN --}}
                {{-- ===================================================== --}}

                @if ($template->description)
                    <section class="rounded-2xl border border-slate-800 bg-slate-900/50 p-5">
                        <p class="text-[9px] font-black uppercase tracking-wider text-slate-500">
                            Lo que cuenta quien lo montó
                        </p>

                        <p class="mt-2 whitespace-pre-line text-[12px] leading-relaxed text-slate-400">
                            {{ $template->description }}
                        </p>
                    </section>
                @endif

            </div>


            <aside class="space-y-4">

                {{-- Sus cifras --}}

                <section class="rounded-2xl border border-slate-800 bg-slate-900/50 p-4">

                    <p class="text-[9px] font-black uppercase tracking-wider text-slate-500">De un vistazo</p>

                    <dl class="mt-2.5 space-y-2 text-[11px]">
                        @foreach ([['Participantes', $template->participant_range_label], ['Entradas', $template->graph_starts_count], ['Fases', $template->graph_nodes_count], ['Enlaces', $template->graph_connections_count], ['Finales', $template->graph_terminals_count], ['BYE', $template->allow_byes ? 'Permitido' : 'No'], ['Publicada', $template->published_at?->locale('es')->diffForHumans() ?? '—']] as [$etiqueta, $valor])
                            <div class="flex items-baseline justify-between gap-3">
                                <dt class="text-slate-600">{{ $etiqueta }}</dt>
                                <dd class="font-black text-slate-200">{{ $valor }}</dd>
                            </div>
                        @endforeach
                    </dl>

                    @if ($template->tags)
                        <div class="mt-3 flex flex-wrap gap-1 border-t border-slate-800 pt-3">
                            @foreach ($template->tags as $etiqueta)
                                <span class="rounded-lg border border-slate-800 bg-slate-950 px-2 py-1 text-[10px] font-bold text-slate-500">
                                    #{{ $etiqueta }}
                                </span>
                            @endforeach
                        </div>
                    @endif

                </section>


                {{-- Más de esta persona --}}

                @if ($more->isNotEmpty())
                    <section class="rounded-2xl border border-slate-800 bg-slate-900/50 p-4">

                        <p class="text-[9px] font-black uppercase tracking-wider text-slate-500">
                            Más de {{ $template->user->name }}
                        </p>

                        <ul class="mt-2.5 space-y-1">
                            @foreach ($more as $otra)
                                <li>
                                    <a href="{{ route('tournaments.community.tournament', $otra) }}"
                                        class="flex items-center gap-2 rounded-lg border border-slate-800 bg-slate-950/60 px-2 py-1.5 transition hover:border-slate-700 hover:bg-slate-900">

                                        <span class="text-[13px]">{{ $otra->display_icon }}</span>

                                        <span class="min-w-0 flex-1">
                                            <span class="block truncate text-[11px] font-black text-slate-200">
                                                {{ $otra->name }}
                                            </span>
                                            <span class="block truncate text-[9px] text-slate-600">
                                                {{ $otra->category_label ?? 'Torneo' }} · {{ $otra->clones_count }} copias
                                            </span>
                                        </span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>

                        <a href="{{ route('tournaments.community.creator', $template->user) }}"
                            class="mt-2.5 block rounded-xl border border-slate-800 px-3 py-2 text-center text-[11px] font-black text-slate-400 transition hover:border-violet-500 hover:text-violet-300">
                            Ver todo lo suyo →
                        </a>

                    </section>
                @endif

            </aside>

        </div>

    </div>

</x-tournament-layout>
