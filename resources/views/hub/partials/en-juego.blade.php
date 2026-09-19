@php
    /*
     * Lo que se está jugando en tus mundos y quién ganó lo último.
     *
     * Cada competición con su portada —la suya, o la de su mundo—, cuánto
     * lleva jugado y un botón para seguir jugando. Las paradas van primero:
     * sin ti no avanzan.
     */

    $estado = function ($c) {
        if (in_array($c->runtime_status, ['BLOCKED', 'AWAITING_DECISION'], true)) {
            return ['Parada', '#fb7185', 'aviso'];
        }

        return match ($c->status) {
            'RUNNING' => ['En curso', '#34d399', 'reproducir'],
            'PAUSED' => ['Pausada', '#fbbf24', 'pausa'],
            default => ['Preparada', '#60a5fa', 'calendario'],
        };
    };
@endphp

@if ($enJuego->isNotEmpty() || $campeones->isNotEmpty())
    <section class="grid gap-5 xl:grid-cols-[1fr_360px]">

        {{-- ===================================================== --}}
        {{-- EN JUEGO --}}
        {{-- ===================================================== --}}

        <div>
            <div class="mb-3 flex items-end justify-between gap-3">
                <div>
                    <h2 class="flex items-center gap-2 text-lg font-black text-white">
                        <span class="relative flex h-2.5 w-2.5">
                            <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-60"></span>
                            <span class="relative inline-flex h-2.5 w-2.5 rounded-full bg-emerald-400"></span>
                        </span>
                        En juego ahora
                    </h2>
                    <p class="text-xs text-slate-500">Las competiciones abiertas de tus mundos. Las paradas, primero.</p>
                </div>
                <a href="{{ route('universes.dashboard') }}" class="text-xs font-black text-violet-300 hover:text-violet-200">Ver en Universos</a>
            </div>

            @if ($enJuego->isEmpty())
                <div class="rounded-2xl border border-dashed border-white/10 p-8 text-center">
                    <x-omni-icon name="espadas" size="h-8 w-8" class="mx-auto text-slate-700" />
                    <p class="mt-2 text-sm font-bold text-slate-400">No hay nada jugándose ahora mismo.</p>
                    <p class="text-xs text-slate-500">Prepara una edición desde un torneo de cualquiera de tus mundos.</p>
                </div>
            @else
                <div class="grid gap-3 sm:grid-cols-2 2xl:grid-cols-3">
                    @foreach ($enJuego as $c)
                        @php
                            [$texto, $color, $icono] = $estado($c);
                            $portada = $c->image_url ?? $c->universe?->image_url;
                            $avance = $c->matches_count ? round($c->jugados_count / $c->matches_count * 100) : 0;
                        @endphp

                        <article class="group overflow-hidden rounded-2xl border bg-slate-900/60 transition hover:-translate-y-0.5"
                            style="border-color: {{ $color }}44;">
                            <a href="{{ route('universes.competitions.show', [$c->universe_id, $c]) }}" class="relative block aspect-[16/8] overflow-hidden bg-slate-900">
                                @if ($portada)
                                    <img src="{{ $portada }}" alt="" loading="lazy" class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
                                @else
                                    <div class="flex h-full items-center justify-center text-slate-700"><x-omni-icon name="espadas" size="h-10 w-10" /></div>
                                @endif
                                <div class="absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-950/30 to-transparent"></div>

                                <span class="absolute left-3 top-3 inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-black backdrop-blur"
                                    style="color: {{ $color }}; background-color: #020617cc; border: 1px solid {{ $color }}66;">
                                    <x-omni-icon :name="$icono" size="h-3 w-3" /> {{ $texto }}
                                </span>

                                <span class="absolute inset-x-3 bottom-2">
                                    <span class="block truncate text-[10px] font-bold uppercase tracking-wider text-violet-300">{{ $c->universe?->name }}</span>
                                    <span class="block truncate text-base font-black text-white">{{ $c->name }}</span>
                                </span>
                            </a>

                            <div class="space-y-2.5 p-3">
                                <div>
                                    <div class="flex justify-between text-[10px] font-bold text-slate-500">
                                        <span>{{ $c->jugados_count }} de {{ $c->matches_count }} enfrentamientos</span>
                                        <span>{{ $avance }}%</span>
                                    </div>
                                    <div class="mt-1 h-1.5 overflow-hidden rounded-full bg-slate-800">
                                        <div class="h-full rounded-full" style="width: {{ $avance }}%; background-color: {{ $color }}"></div>
                                    </div>
                                </div>

                                <div class="flex gap-2">
                                    <a href="{{ route('universes.competitions.play', [$c->universe_id, $c]) }}"
                                        class="flex flex-1 items-center justify-center gap-1.5 rounded-xl py-2 text-xs font-black text-slate-950 transition hover:brightness-110"
                                        style="background-color: {{ $color }}">
                                        <x-omni-icon :name="$c->status === 'DRAFT' ? 'reproducir' : 'espadas'" size="h-3.5 w-3.5" />
                                        {{ $c->status === 'DRAFT' ? 'Empezar' : 'Jugar' }}
                                    </a>
                                    <a href="{{ route('universes.competitions.show', [$c->universe_id, $c]) }}"
                                        class="flex items-center justify-center rounded-xl border border-white/10 px-3 text-xs font-black text-slate-300 hover:text-white">Ficha</a>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif
        </div>


        {{-- ===================================================== --}}
        {{-- ÚLTIMOS CAMPEONES --}}
        {{-- ===================================================== --}}

        <aside>
            <div class="mb-3">
                <h2 class="flex items-center gap-2 text-lg font-black text-white">
                    <x-omni-icon name="trofeo" size="h-5 w-5" class="text-amber-300" /> Últimos campeones
                </h2>
                <p class="text-xs text-slate-500">Quién ganó las últimas competiciones terminadas.</p>
            </div>

            <div class="space-y-2.5">
                @forelse ($campeones as $i => $fila)
                    @php $c = $fila['instancia']; @endphp
                    <a href="{{ route('universes.competitions.show', [$c->universe_id, $c]) }}"
                        class="group flex items-center gap-3 overflow-hidden rounded-2xl border bg-slate-900/60 p-2.5 transition hover:bg-slate-900 {{ $i === 0 ? 'border-amber-500/50' : 'border-white/10' }}">
                        <span class="relative h-16 w-16 shrink-0 overflow-hidden rounded-xl border {{ $i === 0 ? 'border-amber-400/70' : 'border-white/10' }} bg-slate-950">
                            @if ($fila['cara'])
                                <img src="{{ $fila['cara'] }}" alt="" loading="lazy" class="h-full w-full object-cover">
                            @else
                                <span class="flex h-full w-full items-center justify-center text-amber-300/60"><x-omni-icon name="medalla" size="h-7 w-7" /></span>
                            @endif
                            <span class="absolute bottom-0 right-0 rounded-tl-lg bg-amber-400 px-1 text-amber-950"><x-omni-icon name="trofeo" size="h-3 w-3" /></span>
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-sm font-black text-white">{{ $fila['nombre'] ?? 'Sin campeón registrado' }}</span>
                            <span class="block truncate text-[11px] text-slate-400">{{ $c->name }}</span>
                            <span class="block truncate text-[10px] text-slate-600">
                                {{ $c->universe?->name }} · {{ $c->completed_at?->diffForHumans() }}
                                @if ($fila['victorias'] !== null) · {{ $fila['victorias'] }} victorias @endif
                            </span>
                        </span>
                    </a>
                @empty
                    <div class="rounded-2xl border border-dashed border-white/10 p-6 text-center text-xs text-slate-500">
                        Cuando termine una competición, su campeón aparecerá aquí.
                    </div>
                @endforelse
            </div>
        </aside>
    </section>
@endif
