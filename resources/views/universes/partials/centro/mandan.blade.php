@php
    /*
     * Quien manda en cada mundo.
     *
     * Lo que hace interesante juntarlos: la clasificacion es de CADA universo,
     * asi que el numero uno de uno puede ser el dieciocho de otro. Hasta ahora
     * eso no se veia en ninguna parte, porque ninguna pantalla ponia dos mundos
     * al lado.
     */
@endphp

@if ($mandan->isNotEmpty())

    <section class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

        <header class="flex flex-wrap items-center gap-2 border-b border-slate-800 px-4 py-2.5">

            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-violet-500/15 text-violet-300">
                <x-omni-icon name="barras" size="h-4 w-4" />
            </span>

            <div class="min-w-0 flex-1">
                <h2 class="text-[13px] font-black text-white">Quién manda en cada mundo</h2>
                <p class="text-[10px] leading-3 text-slate-500">
                    Cada clasificación es de su universo: el primero de uno puede ser el último
                    de otro.
                </p>
            </div>
        </header>

        <div class="divide-y divide-slate-800/70">

            @foreach ($mandan as $fila)
                @php
                    $suMundo = $fila['mundo'];
                    $quien = $fila['quien'];
                    [$tonoM] = $tonosEstado[$suMundo->status] ?? ['#94a3b8'];
                @endphp

                <div class="flex items-center gap-2.5 px-3 py-2 transition hover:bg-slate-950/50">

                    {{-- El mundo --}}
                    <a href="{{ route('universes.ranking', $suMundo) }}"
                        class="h-8 w-8 shrink-0 overflow-hidden rounded-lg border bg-slate-950"
                        style="border-color: {{ $tonoM }}55"
                        title="Clasificación de {{ $suMundo->name }}">
                        @if ($suMundo->image_url)
                            <img src="{{ $suMundo->image_url }}" alt="" loading="lazy" class="h-full w-full object-cover">
                        @else
                            <span class="flex h-full w-full items-center justify-center text-slate-700">
                                <x-omni-icon name="globo" size="h-3 w-3" />
                            </span>
                        @endif
                    </a>

                    {{-- Quien manda --}}
                    <a href="{{ route('universes.entities.show', [$suMundo, $quien]) }}"
                        class="h-10 w-10 shrink-0 overflow-hidden rounded-lg border border-amber-500/50 bg-slate-950">
                        @if ($quien->image_url)
                            <img src="{{ $quien->image_url }}" alt="" loading="lazy" class="h-full w-full object-cover">
                        @else
                            <span class="flex h-full w-full items-center justify-center text-slate-700">◍</span>
                        @endif
                    </a>

                    <div class="min-w-0 flex-1">
                        <p class="truncate text-[12px] font-black text-white">
                            {{ $quien->display_label }}
                        </p>

                        <p class="truncate text-[9px] text-slate-600">
                            manda en <span class="font-black text-slate-500">{{ $suMundo->name }}</span>
                            · 1º de {{ $fila['de_cuantos'] }}
                        </p>
                    </div>

                    <span class="shrink-0 text-right">
                        <span class="block font-mono text-[14px] font-black text-violet-300">
                            {{ $fila['puntos'] }}
                        </span>

                        @if ($fila['titulos'] > 0)
                            <span class="block font-mono text-[9px] font-black text-amber-400">
                                {{ $fila['titulos'] }}
                                {{ $fila['titulos'] === 1 ? 'título' : 'títulos' }}
                            </span>
                        @else
                            <span class="block text-[9px] text-slate-700">sin título</span>
                        @endif
                    </span>
                </div>
            @endforeach
        </div>
    </section>
@endif
