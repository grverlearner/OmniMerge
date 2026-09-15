@php
    /*
     * Lo que espera por ti.
     *
     * La parte que no existia. El universo ya sabia que una competicion estaba
     * bloqueada, que otra termino sin repartir premios y que un torneo lleva
     * meses definido sin jugarse nunca; para enterarte habia que entrar panel
     * por panel.
     *
     * Regla: cada punto lleva a donde se resuelve. Un aviso que no se puede
     * atender desde el aviso no es un aviso, es una queja. Y el de los premios
     * sin repartir se resuelve AQUI MISMO, con su boton, porque la accion ya
     * existia y estaba escondida dentro de la ficha de cada edicion.
     */

    $urgentes = $atencion->where('urgente', true)->count();
@endphp

@if ($atencion->isEmpty())

    <section class="flex flex-wrap items-center gap-3 rounded-2xl border border-emerald-500/25 bg-emerald-500/5 px-4 py-3">

        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-emerald-500/15 text-emerald-300">
            <x-omni-icon name="chispa" size="h-4 w-4" />
        </span>

        <div class="min-w-0 flex-1">
            <p class="text-[13px] font-black text-white">Nada espera por ti</p>
            <p class="text-[10px] text-emerald-200/60">
                No hay competiciones paradas, ni premios sin repartir, ni torneos sin estrenar.
                El mundo está al día.
            </p>
        </div>
    </section>

@else

    <section class="overflow-hidden rounded-2xl border bg-slate-900/50"
        style="border-color: {{ $urgentes > 0 ? '#fb718544' : '#33415580' }}">

        <header class="flex flex-wrap items-center gap-2 border-b border-slate-800 px-4 py-2.5">

            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg"
                style="background-color: {{ $urgentes > 0 ? '#fb718526' : '#33415566' }};
                       color: {{ $urgentes > 0 ? '#fb7185' : '#94a3b8' }}">
                <x-omni-icon name="chispa" size="h-4 w-4" />
            </span>

            <div class="min-w-0 flex-1">
                <h2 class="text-[13px] font-black text-white">Lo que espera por ti</h2>
                <p class="text-[10px] text-slate-500">
                    {{ $atencion->count() }} {{ $atencion->count() === 1 ? 'cosa' : 'cosas' }} que
                    este mundo tiene a medias.
                    @if ($urgentes > 0)
                        <strong class="text-rose-300">{{ $urgentes }}</strong>
                        {{ $urgentes === 1 ? 'frena' : 'frenan' }} lo que se está jugando.
                    @endif
                </p>
            </div>
        </header>

        <div class="divide-y divide-slate-800/70">

            @foreach ($atencion as $indice => $punto)

                <div class="flex flex-wrap items-center gap-3 px-4 py-2.5 transition hover:bg-slate-950/40">

                    <span class="h-8 w-1 shrink-0 rounded-full" style="background-color: {{ $punto['tono'] }}"></span>

                    @if ($punto['cuantos'] > 0)
                        <span class="w-7 shrink-0 text-center font-mono text-[15px] font-black"
                            style="color: {{ $punto['tono'] }}">{{ $punto['cuantos'] }}</span>
                    @else
                        <span class="w-7 shrink-0 text-center" style="color: {{ $punto['tono'] }}">
                            <x-omni-icon name="brujula" size="h-4 w-4" />
                        </span>
                    @endif

                    <div class="min-w-0 flex-1">
                        <p class="text-[12px] font-black leading-tight text-slate-100">{{ $punto['titulo'] }}</p>
                        <p class="text-[10px] leading-3 text-slate-500">{{ $punto['texto'] }}</p>
                    </div>

                    @if ($punto['urgente'])
                        <span class="shrink-0 rounded-lg bg-rose-500/15 px-2 py-0.5 text-[9px] font-black uppercase tracking-wider text-rose-300">
                            frena el juego
                        </span>
                    @endif

                    @if ($punto['accion'])
                        <a href="{{ $punto['url'] }}"
                            class="shrink-0 rounded-xl border px-2.5 py-1.5 text-[11px] font-black transition"
                            style="border-color: {{ $punto['tono'] }}55; color: {{ $punto['tono'] }}"
                            onmouseover="this.style.backgroundColor='{{ $punto['tono'] }}22'"
                            onmouseout="this.style.backgroundColor=''">
                            {{ $punto['accion'] }}
                        </a>
                    @endif
                </div>

                {{--
                    Los premios sin repartir se arreglan desde aqui. El
                    procesador es idempotente -solo aplica lo que falte-, pero
                    aun asi se pide confirmacion: concede trofeos de verdad.
                --}}
                @if (isset($punto['premios']) && $puedeEditar)
                    <div class="space-y-1.5 bg-slate-950/50 px-4 py-2.5">
                        @foreach ($punto['premios'] as $edicion)
                            <div class="flex flex-wrap items-center gap-2 rounded-xl border border-slate-800 bg-slate-900/60 px-2.5 py-1.5">

                                <span class="h-8 w-8 shrink-0 overflow-hidden rounded-lg border border-slate-800 bg-slate-950">
                                    @if ($edicion->image_url)
                                        <img src="{{ $edicion->image_url }}" alt="" loading="lazy" class="h-full w-full object-cover">
                                    @else
                                        <span class="flex h-full w-full items-center justify-center text-slate-700">
                                            <x-omni-icon name="trofeo" size="h-3.5 w-3.5" />
                                        </span>
                                    @endif
                                </span>

                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-[11px] font-black text-slate-200">
                                        {{ $edicion->name ?: 'Competición sin nombre' }}
                                    </p>
                                    <p class="truncate font-mono text-[9px] text-slate-600">
                                        {{ $edicion->universeTournament?->name }}
                                        @if ($edicion->completed_at)
                                            · {{ $edicion->completed_at->format('d/m/Y') }}
                                        @endif
                                    </p>
                                </div>

                                <form method="POST"
                                    action="{{ route('universes.tournaments.rewards.reprocess', [$universe, $edicion->universeTournament, $edicion]) }}"
                                    class="shrink-0">
                                    @csrf
                                    @method('PUT')

                                    <button type="submit"
                                        x-show="confirmando !== {{ $edicion->id }}"
                                        @click.prevent="confirmando = {{ $edicion->id }}"
                                        class="rounded-lg border border-amber-500/40 bg-amber-500/10 px-2.5 py-1.5 text-[10px] font-black text-amber-300 transition hover:bg-amber-500 hover:text-slate-950">
                                        Repartir sus premios
                                    </button>

                                    <span x-show="confirmando === {{ $edicion->id }}" x-cloak
                                        class="flex items-center gap-1.5">
                                        <button type="submit"
                                            class="rounded-lg bg-amber-400 px-2.5 py-1.5 text-[10px] font-black text-slate-950 transition hover:bg-amber-300">
                                            Sí, repartir
                                        </button>
                                        <button type="button" @click="confirmando = null"
                                            class="rounded-lg px-1.5 py-1.5 text-[10px] font-black text-slate-500 transition hover:text-slate-200">
                                            No
                                        </button>
                                    </span>
                                </form>
                            </div>
                        @endforeach
                    </div>
                @endif
            @endforeach
        </div>
    </section>
@endif
