@php
    /*
     * Lo que ha soltado del lado de los torneos.
     *
     * Es la mitad que el perfil de biblioteca no enseñaba nunca. Una plantilla
     * de torneo no tiene cara, asi que lo que la describe son sus numeros: de
     * cuantas fases se compone y cuantas salidas tiene.
     */
@endphp

@if ($cifras['torneos'] + $cifras['fases'] > 0)

    <section class="overflow-hidden rounded-2xl border border-amber-500/25 bg-slate-900/50">

        <header class="flex flex-wrap items-center gap-2 border-b border-amber-500/15 px-4 py-2.5">

            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-amber-500/15 text-amber-300">
                <x-omni-icon name="trofeo" size="h-4 w-4" />
            </span>

            <div class="min-w-0 flex-1">
                <h2 class="text-[13px] font-black text-white">Lo que ha diseñado para competir</h2>
                <p class="text-[10px] text-slate-500">
                    Plantillas de torneo y fases publicadas.
                </p>
            </div>

            <a href="{{ route('tournaments.community.creator', $creador) }}"
                class="shrink-0 text-[11px] font-black text-amber-300 underline transition hover:text-white">
                Verlas todas
            </a>
        </header>


        {{-- ---------- TORNEOS ---------- --}}

        @if ($torneos->isNotEmpty())
            <div class="border-b border-slate-800/70 p-3">

                <p class="mb-2 flex items-baseline gap-1.5 text-[9px] font-black uppercase tracking-wider text-slate-600">
                    Torneos
                    <span class="font-mono text-amber-400">{{ $cifras['torneos'] }}</span>
                </p>

                <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($torneos as $torneo)
                        <a href="{{ route('tournaments.community.tournament', $torneo) }}"
                            class="group overflow-hidden rounded-xl border border-slate-800 bg-slate-950 transition hover:-translate-y-0.5 hover:border-amber-500/50">

                            <span class="block h-20 overflow-hidden bg-slate-900">
                                @if ($torneo->image_url)
                                    <img src="{{ $torneo->image_url }}" alt="" loading="lazy"
                                        class="h-full w-full object-cover transition duration-300 group-hover:scale-105">
                                @else
                                    <span class="flex h-full w-full items-center justify-center text-amber-500/25">
                                        <x-omni-icon name="trofeo" size="h-6 w-6" />
                                    </span>
                                @endif
                            </span>

                            <span class="block p-2">
                                <span class="block truncate text-[12px] font-black text-slate-200">{{ $torneo->name }}</span>

                                <span class="mt-0.5 flex flex-wrap items-center gap-x-2 font-mono text-[9px] text-slate-600">
                                    <span>{{ $torneo->graph_nodes_count }} fases</span>
                                    <span>{{ $torneo->graph_terminals_count }} salidas</span>
                                    @if ($torneo->clones_count > 0)
                                        <span class="text-amber-500">{{ $torneo->clones_count }} copias</span>
                                    @endif
                                </span>
                            </span>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif


        {{-- ---------- FASES ---------- --}}

        @if ($fases->isNotEmpty())
            <div class="p-3">

                <p class="mb-2 flex items-baseline gap-1.5 text-[9px] font-black uppercase tracking-wider text-slate-600">
                    Fases
                    <span class="font-mono text-pink-400">{{ $cifras['fases'] }}</span>
                </p>

                <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($fases as $fase)
                        <a href="{{ route('tournaments.community.phase', $fase) }}"
                            class="flex items-center gap-2.5 rounded-xl border border-slate-800 bg-slate-950 p-2 transition hover:border-pink-500/50">

                            <span class="h-10 w-10 shrink-0 overflow-hidden rounded-lg bg-slate-900">
                                @if ($fase->image_url)
                                    <img src="{{ $fase->image_url }}" alt="" loading="lazy" class="h-full w-full object-cover">
                                @else
                                    <span class="flex h-full w-full items-center justify-center text-pink-500/40">
                                        <x-omni-icon name="grafo" size="h-4 w-4" />
                                    </span>
                                @endif
                            </span>

                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-[12px] font-black text-slate-200">{{ $fase->name }}</span>
                                <span class="block font-mono text-[9px] text-slate-600">
                                    {{ $fase->type_label ?? '' }}
                                    · {{ $fase->exits_count }} salidas
                                </span>
                            </span>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
    </section>
@endif
