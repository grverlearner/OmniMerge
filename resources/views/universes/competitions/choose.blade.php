@php
    /*
     * Nueva competición: ¿de qué torneo?
     *
     * Una competición es una edición de un torneo, así que antes de diseñarla
     * hay que saber de cuál. Cada tarjeta dice lo necesario para elegir:
     * cuántos pueden entrar, cuántas ediciones lleva y cuál fue la última,
     * con la opción de copiarla.
     */

    $estados = [
        'DRAFT' => ['Borrador', '#94a3b8'],
        'READY' => ['Lista', '#38bdf8'],
        'RUNNING' => ['En juego', '#34d399'],
        'PAUSED' => ['Pausada', '#fbbf24'],
        'COMPLETED' => ['Terminada', '#a78bfa'],
        'CANCELLED' => ['Cancelada', '#f43f5e'],
    ];
@endphp

<x-universe-layout :universe="$universe" surface="dark">

    <x-slot name="header">Nueva competición</x-slot>

    <div x-data="{ q: '' }" class="space-y-3">

        <header class="flex flex-wrap items-end gap-3">
            <a href="{{ route('universes.competitions.index', $universe) }}" title="Volver a las competiciones"
                class="flex h-9 w-9 items-center justify-center rounded-xl border border-slate-800 text-slate-400 transition hover:border-slate-600 hover:text-white">
                <x-omni-icon name="flecha-izquierda" size="h-4 w-4" />
            </a>

            <div class="min-w-0 flex-1">
                <p class="text-[9px] font-black uppercase tracking-[0.2em] text-amber-400">{{ $universe->name }} · Nueva competición</p>
                <h1 class="text-xl font-black text-white">¿De qué torneo es esta edición?</h1>
                <p class="mt-0.5 max-w-2xl text-[11px] leading-relaxed text-slate-500">
                    Cada competición es una edición de un torneo: hereda su forma, su juego, sus premios y quién puede entrar.
                    Elige el torneo y después podrás cambiar lo que quieras solo para esta edición.
                </p>
            </div>

            <label class="relative w-full sm:w-64">
                <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-600">
                    <x-omni-icon name="brujula" size="h-3.5 w-3.5" />
                </span>
                <input type="search" x-model="q" placeholder="Buscar un torneo…"
                    class="w-full rounded-xl border-slate-800 bg-slate-950 py-2 pl-9 text-[12px] text-slate-200 placeholder:text-slate-600 focus:border-amber-500 focus:ring-amber-500">
            </label>
        </header>

        @if ($noExiste)
            <p class="flex items-center gap-2 rounded-xl border border-amber-500/40 bg-amber-500/10 px-3 py-2 text-[11px] text-amber-200">
                <x-omni-icon name="aviso" size="h-4 w-4" />
                Ese torneo ya no existe en este universo. Elige otro.
            </p>
        @endif

        <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ($torneos as $torneo)
                @php
                    $ultima = $ultimas[$torneo->id] ?? null;
                    $puedenEntrar = $permitidos[$torneo->id] ?? 0;
                    $activo = $torneo->status === 'ACTIVE';
                    $conSala = ! empty($torneo->eligibility['rules'] ?? null) || ! empty($torneo->eligibility['groups'] ?? null) || ! empty($torneo->eligibility['doors'] ?? null);
                @endphp

                <article x-show="! q.trim() || @js(mb_strtolower($torneo->name)).includes(q.trim().toLowerCase())"
                    class="group flex flex-col overflow-hidden rounded-2xl border bg-slate-900/50 transition hover:-translate-y-0.5 {{ $activo ? 'border-amber-500/30 hover:border-amber-400/60' : 'border-slate-800 opacity-70' }}">

                    <div class="relative h-28 overflow-hidden bg-slate-950">
                        @if ($torneo->image_url)
                            <img src="{{ $torneo->image_url }}" alt="" class="h-full w-full object-cover opacity-60 transition duration-500 group-hover:scale-105 group-hover:opacity-80">
                        @else
                            <span class="flex h-full w-full items-center justify-center bg-gradient-to-br from-amber-500/15 to-slate-950 text-amber-400/40">
                                <x-omni-icon name="trofeo" size="h-10 w-10" />
                            </span>
                        @endif

                        <span class="absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-950/40 to-transparent"></span>

                        <span class="absolute right-2 top-2 rounded-md px-1.5 py-0.5 text-[9px] font-black uppercase tracking-wider backdrop-blur {{ $activo ? 'bg-emerald-500/20 text-emerald-300' : 'bg-slate-700/60 text-slate-300' }}">
                            {{ $activo ? 'Activo' : 'Inactivo' }}
                        </span>

                        <div class="absolute inset-x-0 bottom-0 p-3">
                            <h2 class="truncate text-[15px] font-black text-white">{{ $torneo->name }}</h2>
                            <p class="truncate text-[10px] text-slate-400">Forma: {{ $torneo->tournamentTemplate?->name ?? 'sin plantilla' }}</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-px border-y border-slate-800 bg-slate-800">
                        <span class="bg-slate-900 px-2 py-2 text-center">
                            <span class="block font-mono text-[15px] font-black {{ $puedenEntrar ? 'text-rose-300' : 'text-slate-600' }}">{{ $puedenEntrar }}</span>
                            <span class="block text-[8px] font-black uppercase tracking-wider text-slate-600">de {{ $habitantes }} entran</span>
                        </span>
                        <span class="bg-slate-900 px-2 py-2 text-center">
                            <span class="block font-mono text-[15px] font-black {{ $torneo->instances_count ? 'text-amber-300' : 'text-slate-600' }}">{{ $torneo->instances_count }}</span>
                            <span class="block text-[8px] font-black uppercase tracking-wider text-slate-600">ediciones</span>
                        </span>
                        <span class="bg-slate-900 px-2 py-2 text-center">
                            <span class="block text-[11px] font-black {{ $conSala ? 'text-violet-300' : 'text-slate-500' }}">{{ $conSala ? 'Propios' : 'Abierto' }}</span>
                            <span class="block text-[8px] font-black uppercase tracking-wider text-slate-600">participantes</span>
                        </span>
                    </div>

                    <div class="flex-1 px-3 py-2">
                        @if ($ultima)
                            @php [$textoEstado, $tonoEstado] = $estados[$ultima->status] ?? [$ultima->status, '#94a3b8']; @endphp
                            <p class="text-[9px] font-black uppercase tracking-wider text-slate-600">Última edición</p>
                            <a href="{{ route('universes.competitions.show', [$universe, $ultima->id]) }}"
                                class="mt-0.5 flex items-center gap-2 text-[11px] text-slate-300 transition hover:text-white">
                                <span class="min-w-0 flex-1 truncate font-bold">{{ $ultima->name }}</span>
                                <span class="shrink-0 rounded px-1.5 py-0.5 text-[9px] font-black" style="color: {{ $tonoEstado }}; background-color: {{ $tonoEstado }}1f">{{ $textoEstado }}</span>
                            </a>
                            <p class="text-[10px] text-slate-600">{{ $ultima->participant_count }} competidores · {{ $ultima->created_at?->diffForHumans() }}</p>
                        @else
                            <p class="text-[11px] text-slate-500">Todavía no se ha jugado ninguna edición.</p>
                        @endif

                        @if ($puedenEntrar === 0)
                            <p class="mt-2 flex items-center gap-1.5 text-[10px] font-bold text-rose-300">
                                <x-omni-icon name="aviso" size="h-3.5 w-3.5" />
                                Con sus reglas no entra nadie.
                            </p>
                        @endif
                    </div>

                    <div class="flex flex-wrap items-center gap-1.5 border-t border-slate-800 p-2">
                        <a href="{{ route('universes.competitions.create', ['universe' => $universe, 'universe_tournament_id' => $torneo->id]) }}"
                            class="flex flex-1 items-center justify-center gap-1.5 rounded-xl bg-amber-500 px-3 py-2 text-[11px] font-black text-slate-950 transition hover:bg-amber-400">
                            <x-omni-icon name="mas" size="h-3.5 w-3.5" />
                            Nueva edición
                        </a>

                        @if ($ultima)
                            <a href="{{ route('universes.competitions.create', ['universe' => $universe, 'universe_tournament_id' => $torneo->id, 'copy' => $ultima->id]) }}"
                                title="Parte de la última edición: su forma, juego, premios y participantes"
                                class="rounded-xl border border-slate-700 px-2.5 py-2 text-[11px] font-black text-slate-300 transition hover:border-amber-400 hover:text-amber-200">
                                Copiar la última
                            </a>
                        @endif

                        <a href="{{ route('universes.tournaments.participants', [$universe, $torneo]) }}" title="Sala de participantes del torneo"
                            class="rounded-xl border border-slate-700 p-2 text-slate-400 transition hover:border-rose-400 hover:text-rose-300">
                            <x-omni-icon name="usuario" size="h-4 w-4" />
                        </a>
                    </div>
                </article>
            @endforeach
        </section>

        <p class="text-[10px] text-slate-600">
            ¿Buscas un torneo que no está?
            <a href="{{ route('universes.tournaments.create', $universe) }}" class="font-black text-amber-300 underline hover:text-amber-200">Crea uno nuevo</a>
            y después su primera edición.
        </p>
    </div>

</x-universe-layout>
