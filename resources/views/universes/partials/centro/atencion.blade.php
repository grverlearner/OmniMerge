@php
    /*
     * Lo que espera por ti, en todos los mundos a la vez.
     *
     * Mismos criterios que el Resumen de cada universo -si la portada del
     * modulo dijese una cosa y el mundo otra, uno de los dos mentiria-, pero
     * juntos y con el mundo al lado, porque la pregunta que se hace aqui no es
     * «que le pasa a este universo» sino «donde tengo algo parado».
     */
@endphp

@if ($atencion->isEmpty())

    <section class="flex flex-wrap items-center gap-3 rounded-2xl border border-emerald-500/25 bg-emerald-500/5 px-4 py-3">

        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-emerald-500/15 text-emerald-300">
            <x-omni-icon name="chispa" size="h-4 w-4" />
        </span>

        <div class="min-w-0 flex-1">
            <p class="text-[13px] font-black text-white">Ninguno de tus mundos te está esperando</p>
            <p class="text-[10px] text-emerald-200/60">
                Ni competiciones paradas, ni premios sin repartir, ni mundos vacíos o sin
                calendario. Todo está al día.
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
                    {{ $atencion->count() }} {{ $atencion->count() === 1 ? 'cosa' : 'cosas' }} a
                    medias, repartidas en
                    {{ $atencion->pluck('mundo.id')->unique()->count() }}
                    {{ $atencion->pluck('mundo.id')->unique()->count() === 1 ? 'mundo' : 'mundos' }}.
                    @if ($urgentes > 0)
                        <strong class="text-rose-300">{{ $urgentes }}</strong>
                        {{ $urgentes === 1 ? 'frena' : 'frenan' }} lo que se está jugando.
                    @endif
                </p>
            </div>
        </header>

        <div class="divide-y divide-slate-800/70">

            @foreach ($atencion as $punto)
                @php
                    $suMundo = $punto['mundo'];
                    [$tonoM] = $tonosEstado[$suMundo->status] ?? ['#94a3b8'];
                @endphp

                <div class="flex flex-wrap items-center gap-3 px-4 py-2.5 transition hover:bg-slate-950/40">

                    <span class="h-9 w-1 shrink-0 rounded-full" style="background-color: {{ $punto['tono'] }}"></span>

                    {{-- De qué mundo es: la cara, que es como se reconoce --}}
                    <a href="{{ $suMundo->home_url }}"
                        class="flex shrink-0 items-center gap-1.5"
                        title="{{ $suMundo->name }}">

                        <span class="h-9 w-9 overflow-hidden rounded-lg border bg-slate-950"
                            style="border-color: {{ $tonoM }}55">
                            @if ($suMundo->image_url)
                                <img src="{{ $suMundo->image_url }}" alt="" loading="lazy" class="h-full w-full object-cover">
                            @else
                                <span class="flex h-full w-full items-center justify-center text-slate-700">
                                    <x-omni-icon name="globo" size="h-3.5 w-3.5" />
                                </span>
                            @endif
                        </span>

                        <span class="hidden max-w-[110px] truncate text-[10px] font-black text-slate-400 sm:block">
                            {{ $suMundo->name }}
                        </span>
                    </a>

                    @if ($punto['cuantos'] > 0)
                        <span class="w-6 shrink-0 text-center font-mono text-[15px] font-black"
                            style="color: {{ $punto['tono'] }}">{{ $punto['cuantos'] }}</span>
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

                    <a href="{{ $punto['url'] }}"
                        class="shrink-0 rounded-xl border px-2.5 py-1.5 text-[11px] font-black transition"
                        style="border-color: {{ $punto['tono'] }}55; color: {{ $punto['tono'] }}"
                        onmouseover="this.style.backgroundColor='{{ $punto['tono'] }}22'"
                        onmouseout="this.style.backgroundColor=''">
                        Ir a resolverlo
                    </a>
                </div>
            @endforeach
        </div>
    </section>
@endif
