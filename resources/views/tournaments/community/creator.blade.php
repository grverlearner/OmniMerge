@php
    /*
     * El perfil de un creador, visto desde el taller.
     *
     * Deliberadamente parcial: aquí solo está lo que esta persona ha
     * publicado de torneos y de fases. Su biblioteca de entidades vive en la
     * otra comunidad y se enlaza, no se copia: mezclarlas convertiría esta
     * pantalla en un perfil general y dejaría de responder a la pregunta que
     * se hace aquí —«¿me sirve algo de lo que monta?»—.
     */

    $esYo = auth()->user()?->is($creator) ?? false;
@endphp

<x-tournament-layout surface="dark">

    <x-slot name="header">{{ $creator->name }}</x-slot>

    <div class="space-y-4">

        {{-- ===================================================== --}}
        {{-- QUIÉN ES --}}
        {{-- ===================================================== --}}

        <header
            class="relative overflow-hidden rounded-2xl border border-slate-800 bg-gradient-to-br from-slate-900 via-slate-900 to-violet-950/40">

            <span class="pointer-events-none absolute -right-24 -top-28 h-72 w-72 rounded-full bg-violet-500/10 blur-3xl"></span>

            <div class="relative p-5">

                <a href="{{ route('tournaments.community.index') }}"
                    class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-600 transition hover:text-violet-400">
                    ← Comunidad
                </a>

                <div class="mt-3 flex flex-wrap items-start gap-5">

                    <x-user-avatar :user="$creator" size="xl" ring />

                    <div class="min-w-0 flex-1">

                        <h1 class="text-2xl font-black tracking-tight text-white">{{ $creator->name }}</h1>

                        <p class="text-[12px] text-slate-500">{{ '@' . $creator->username }}</p>

                        @if ($creator->headline)
                            <p class="mt-2 text-[13px] font-bold text-violet-300">{{ $creator->headline }}</p>
                        @endif

                        @if ($creator->bio)
                            <p class="mt-2 max-w-2xl whitespace-pre-line text-[12px] leading-relaxed text-slate-400">
                                {{ $creator->bio }}
                            </p>
                        @endif

                        <div class="mt-3 flex flex-wrap items-center gap-3 text-[11px] text-slate-500">
                            @if ($creator->location)
                                <span class="flex items-center gap-1.5">
                                    <x-omni-icon name="brujula" size="h-3.5 w-3.5" />
                                    {{ $creator->location }}
                                </span>
                            @endif

                            @if ($creator->website)
                                <a href="{{ $creator->website }}" target="_blank" rel="noopener noreferrer"
                                    class="flex items-center gap-1.5 transition hover:text-violet-300">
                                    <x-omni-icon name="globo" size="h-3.5 w-3.5" />
                                    {{ preg_replace('#^https?://#', '', $creator->website) }}
                                </a>
                            @endif

                            <span class="flex items-center gap-1.5">
                                <x-omni-icon name="calendario" size="h-3.5 w-3.5" />
                                Aquí desde {{ $creator->created_at?->locale('es')->isoFormat('MMMM [de] YYYY') }}
                            </span>
                        </div>

                    </div>

                    <div class="flex flex-wrap gap-2">
                        @foreach ([['Torneos', $tournaments->count(), 'text-amber-300'], ['Fases', $phases->count(), 'text-cyan-300'], ['Motores', $totals['engines'], 'text-emerald-300'], ['Copias', $totals['clones'], 'text-violet-300'], ['Vistas', $totals['views'], 'text-slate-300']] as [$etiqueta, $valor, $color])
                            <span class="flex items-baseline gap-2 rounded-xl border border-slate-800 bg-slate-950/60 px-3 py-2">
                                <span class="font-mono text-lg font-black {{ $color }}">{{ $valor }}</span>
                                <span class="text-[9px] font-black uppercase tracking-wider text-slate-600">
                                    {{ $etiqueta }}
                                </span>
                            </span>
                        @endforeach
                    </div>

                </div>

                <div class="mt-4 flex flex-wrap gap-2 border-t border-slate-800 pt-4">

                    <a href="{{ route('tournaments.community.index', ['creator' => $creator->id]) }}"
                        class="rounded-xl bg-violet-500 px-4 py-2.5 text-[11px] font-black text-white transition hover:bg-violet-400">
                        Explorar todo lo suyo con filtros
                    </a>

                    {{-- Su biblioteca de entidades vive en la otra comunidad --}}
                    <a href="{{ route('community.creators.show', $creator) }}"
                        class="rounded-xl border border-slate-800 px-4 py-2.5 text-[11px] font-black text-slate-400 transition hover:border-slate-600 hover:text-slate-200">
                        Su perfil en la Biblioteca →
                    </a>

                    @if ($esYo)
                        <a href="{{ route('tournaments.creator.show') }}"
                            class="ml-auto rounded-xl border border-amber-500/40 bg-amber-500/10 px-4 py-2.5 text-[11px] font-black text-amber-300 transition hover:bg-amber-500/20">
                            Así te ven · editar mi perfil
                        </a>
                    @endif

                </div>

            </div>

        </header>


        {{-- ===================================================== --}}
        {{-- LO QUE HA PUBLICADO --}}
        {{-- ===================================================== --}}

        @if ($tournaments->isEmpty() && $phases->isEmpty())

            <div class="rounded-2xl border border-dashed border-slate-800 py-16 text-center">
                <span class="inline-flex text-slate-700">
                    <x-omni-icon name="trofeo" size="h-10 w-10" />
                </span>

                <h2 class="mt-3 text-lg font-black text-white">Todavía no ha publicado nada</h2>

                <p class="mx-auto mt-1.5 max-w-md text-xs leading-relaxed text-slate-500">
                    Puede que tenga cosas montadas, pero solo aparecen aquí las que están activas,
                    son públicas y tienen fecha de publicación.
                </p>
            </div>

        @else

            @foreach ([['Torneos completos', $tournaments, 'tournament', 'trofeo', 'amber'], ['Fases sueltas', $phases, 'phase', 'grafo', 'cyan']] as [$titulo, $coleccion, $tipo, $icono, $color])
                @if ($coleccion->isNotEmpty())
                    <section>

                        <div class="mb-2.5 flex items-center gap-2">
                            <span
                                class="flex h-7 w-7 items-center justify-center rounded-lg {{ $color === 'amber' ? 'bg-amber-500/15 text-amber-300' : 'bg-cyan-500/15 text-cyan-300' }}">
                                <x-omni-icon :name="$icono" size="h-3.5 w-3.5" />
                            </span>

                            <h2 class="text-sm font-black text-white">{{ $titulo }}</h2>

                            <span class="font-mono text-[11px] text-slate-600">{{ $coleccion->count() }}</span>
                        </div>

                        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                            @foreach ($coleccion as $pieza)
                                @php
                                    $puedeCopiarse = auth()->user()?->can('duplicate', $pieza) ?? false;

                                    $ruta = $tipo === 'tournament'
                                        ? 'tournaments.community.tournament'
                                        : 'tournaments.community.phase';

                                    $rutaCopia = $tipo === 'tournament'
                                        ? 'tournaments.templates.duplicate'
                                        : 'tournaments.phase-templates.duplicate';
                                @endphp

                                <article
                                    class="group flex flex-col overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50 transition hover:bg-slate-900">

                                    <a href="{{ route($ruta, $pieza) }}"
                                        class="relative block aspect-[16/9] overflow-hidden bg-slate-950">
                                        @if ($pieza->image_url)
                                            <img src="{{ $pieza->image_url }}" alt="" loading="lazy"
                                                class="h-full w-full object-cover transition duration-300 group-hover:scale-105">
                                        @else
                                            <span class="flex h-full w-full items-center justify-center text-4xl opacity-20">
                                                {{ $pieza->display_icon }}
                                            </span>
                                        @endif

                                        @if ($pieza->clones_count > 0)
                                            <span class="absolute right-2 top-2 rounded bg-emerald-500/85 px-1.5 py-0.5 text-[8px] font-black uppercase tracking-wider text-slate-950">
                                                {{ $pieza->clones_count }} copias
                                            </span>
                                        @endif
                                    </a>

                                    <div class="flex-1 p-3">
                                        <a href="{{ route($ruta, $pieza) }}"
                                            class="block truncate text-[12px] font-black text-white transition hover:text-violet-300">
                                            {{ $pieza->name }}
                                        </a>

                                        <p class="mt-0.5 text-[10px] text-slate-500">
                                            {{ $tipo === 'tournament'
                                                ? ($pieza->category_label ?? 'Torneo') . ' · ' . $pieza->graph_nodes_count . ' fases'
                                                : $pieza->type_label . ' · ' . $pieza->exits_count . ' salidas' }}
                                        </p>

                                        <p class="mt-1.5 line-clamp-2 text-[10px] leading-relaxed text-slate-600">
                                            {{ $pieza->summary ?? $pieza->description ?? 'Sin descripción.' }}
                                        </p>
                                    </div>

                                    <div class="flex items-center gap-1 border-t border-slate-800 px-2 py-1.5">
                                        <a href="{{ route($ruta, $pieza) }}"
                                            class="rounded-lg px-2 py-1 text-[10px] font-black text-slate-400 transition hover:text-white">
                                            Ver por dentro
                                        </a>

                                        @if ($puedeCopiarse)
                                            <form method="POST" action="{{ route($rutaCopia, $pieza) }}" class="ml-auto">
                                                @csrf
                                                <button type="submit"
                                                    class="rounded-lg bg-violet-500/15 px-2.5 py-1 text-[10px] font-black text-violet-300 transition hover:bg-violet-500 hover:text-white">
                                                    ↓ Llevármela
                                                </button>
                                            </form>
                                        @endif
                                    </div>

                                </article>
                            @endforeach
                        </div>

                    </section>
                @endif
            @endforeach

        @endif

    </div>

</x-tournament-layout>
