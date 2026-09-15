@php
    /*
     * La temporada que corre.
     *
     * Lo que esta pantalla no sabia decir: si la temporada va por la mitad o
     * esta sin empezar. Sale de cruzar la recurrencia de cada torneo -«cada dos
     * temporadas», «solo en la primera»- con lo que ya se ha creado.
     */
@endphp

<section class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

    <header class="flex flex-wrap items-center gap-2 border-b border-slate-800 px-4 py-2.5">

        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-violet-500/15 text-violet-300">
            <x-omni-icon name="calendario" size="h-4 w-4" />
        </span>

        <div class="min-w-0 flex-1">
            <h2 class="text-[13px] font-black text-white">
                @if ($activeSeason)
                    Temporada {{ $activeSeason->number }} · {{ $activeSeason->name }}
                @else
                    La temporada
                @endif
            </h2>

            <p class="text-[10px] text-slate-500">
                @if ($activeSeason)
                    {{ $activeSeason->period_label }}
                @else
                    Nada corre ahora mismo.
                @endif
            </p>
        </div>

        <a href="{{ route('universes.seasons.index', $universe) }}"
            class="shrink-0 text-[11px] font-black text-violet-300 underline transition hover:text-white">
            Temporadas
        </a>
    </header>


    @if (! $activeSeason)

        <div class="px-4 py-6 text-center">
            <p class="text-[12px] font-black text-slate-300">Ninguna temporada está en marcha</p>
            <p class="mx-auto mt-1 max-w-md text-[11px] leading-relaxed text-slate-500">
                La temporada es el reloj del universo: sin una en curso, los torneos que se
                repiten «cada N temporadas» no saben si les toca.
            </p>
        </div>

    @else

        <div class="p-4">

            {{-- El avance de la temporada, dibujado --}}
            <div class="flex flex-wrap items-center gap-3">

                <div class="min-w-[180px] flex-1">
                    <div class="flex items-baseline justify-between">
                        <span class="text-[10px] font-black uppercase tracking-wider text-slate-600">
                            Lo que le tocaba
                        </span>
                        <span class="font-mono text-[11px] font-black text-slate-400">
                            {{ $temporada['jugados'] }}/{{ $temporada['tocan'] }}
                        </span>
                    </div>

                    <div class="mt-1 h-2.5 overflow-hidden rounded-full bg-slate-950">
                        <div class="h-full rounded-full transition-all duration-500"
                            style="width: {{ $temporada['avance'] }}%;
                                   background: linear-gradient(90deg, #a78bfa, #22d3ee)"></div>
                    </div>

                    <p class="mt-1 text-[10px] text-slate-500">
                        @if ($temporada['tocan'] === 0)
                            A esta temporada no le toca ningún torneo con la recurrencia que
                            tienen configurada.
                        @elseif ($temporada['faltan'] === 0)
                            Todo lo que le tocaba ya se ha creado.
                        @else
                            Faltan <strong class="text-slate-300">{{ $temporada['faltan'] }}</strong>
                            por crear.
                        @endif
                    </p>
                </div>

                <div class="flex shrink-0 gap-2">
                    @foreach ([['Competiciones', $temporada['competiciones'], '#34d399'], ['Le tocan', $temporada['tocan'], '#a78bfa'], ['Faltan', $temporada['faltan'], '#fbbf24']] as [$etiqueta, $valor, $tono])
                        <span class="rounded-xl border border-slate-800 bg-slate-950 px-3 py-1.5 text-center">
                            <span class="block font-mono text-[17px] font-black"
                                style="color: {{ $valor > 0 ? $tono : '#475569' }}">{{ $valor }}</span>
                            <span class="block text-[8px] font-black uppercase tracking-wider text-slate-600">
                                {{ $etiqueta }}
                            </span>
                        </span>
                    @endforeach
                </div>
            </div>


            {{-- ---------- LO QUE TOCA JUGAR ---------- --}}

            @if ($upcoming->isNotEmpty())

                <p class="mt-4 text-[9px] font-black uppercase tracking-wider text-slate-600">
                    Toca jugar y todavía no se ha creado
                </p>

                <div class="mt-2 grid gap-2 sm:grid-cols-2">
                    @foreach ($upcoming as $torneo)
                        <div class="flex items-center gap-2.5 rounded-xl border border-slate-800 bg-slate-950 p-2.5">

                            <span class="h-11 w-11 shrink-0 overflow-hidden rounded-lg border border-slate-800 bg-slate-900">
                                @if ($torneo->image_url)
                                    <img src="{{ $torneo->image_url }}" alt="" loading="lazy" class="h-full w-full object-cover">
                                @else
                                    <span class="flex h-full w-full items-center justify-center text-slate-700">
                                        <x-omni-icon name="trofeo" size="h-4 w-4" />
                                    </span>
                                @endif
                            </span>

                            <div class="min-w-0 flex-1">
                                <p class="truncate text-[12px] font-black text-white">{{ $torneo->name }}</p>
                                <p class="truncate text-[10px] text-slate-500">
                                    {{ $torneo->recurrence_label ?? 'Le toca esta temporada' }}
                                </p>
                            </div>

                            <a href="{{ route('universes.tournaments.show', [$universe, $torneo]) }}"
                                class="shrink-0 rounded-lg border border-violet-500/40 bg-violet-500/10 px-2.5 py-1.5 text-[10px] font-black text-violet-300 transition hover:bg-violet-500 hover:text-white">
                                Crear edición
                            </a>
                        </div>
                    @endforeach
                </div>

            @elseif ($temporada['tocan'] > 0)

                <p class="mt-4 rounded-xl border border-emerald-500/25 bg-emerald-500/5 px-3 py-2 text-[11px] text-emerald-200/80">
                    Todos los torneos que le tocaban a esta temporada ya tienen su edición creada.
                </p>
            @endif
        </div>
    @endif
</section>
