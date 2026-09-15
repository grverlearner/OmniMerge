@php
    /*
     * Quien manda, en corto.
     *
     * Los cinco primeros con su barra comparada con el lider: es lo que
     * convierte «126 puntos» en «el doble que el quinto» sin leer numeros.
     */

    $techoPuntos = $ranking->max('points') ?: 1;

    $medallas = ['#fbbf24', '#cbd5e1', '#f59e0b'];
@endphp

<section class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

    <header class="flex flex-wrap items-center gap-2 border-b border-slate-800 px-4 py-2.5">

        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-violet-500/15 text-violet-300">
            <x-omni-icon name="barras" size="h-4 w-4" />
        </span>

        <div class="min-w-0 flex-1">
            <h2 class="text-[13px] font-black text-white">Quién manda</h2>
            <p class="text-[10px] text-slate-500">Se calcula sola con lo jugado aquí.</p>
        </div>

        <a href="{{ route('universes.ranking', $universe) }}"
            class="shrink-0 text-[11px] font-black text-violet-300 underline transition hover:text-white">
            Ver todo
        </a>
    </header>


    @if ($ranking->isEmpty())

        <div class="px-4 py-8 text-center">
            <p class="text-[12px] font-black text-slate-300">Aún no hay clasificación</p>
            <p class="mx-auto mt-1 max-w-xs text-[11px] leading-relaxed text-slate-500">
                Aparecerá sola en cuanto termine la primera competición.
            </p>
        </div>

    @else

        <div class="divide-y divide-slate-800/70">

            @foreach ($ranking as $fila)
                @php
                    $competidor = $fila->entity;
                    $tono = $medallas[$fila->position - 1] ?? '#64748b';
                    $ancho = max((int) round($fila->points / max($techoPuntos, 1) * 100), 2);
                @endphp

                <a href="{{ route('universes.entities.show', [$universe, $competidor]) }}"
                    class="flex items-center gap-2.5 px-3 py-2 transition hover:bg-slate-950/50">

                    <span class="w-4 shrink-0 text-center font-mono text-[12px] font-black"
                        style="color: {{ $tono }}">{{ $fila->position }}</span>

                    <span class="h-9 w-9 shrink-0 overflow-hidden rounded-lg border bg-slate-950"
                        style="border-color: {{ $fila->position <= 3 ? $tono . '66' : '#1e293b' }}">
                        @if ($competidor?->image_url)
                            <img src="{{ $competidor->image_url }}" alt="" loading="lazy" class="h-full w-full object-cover">
                        @else
                            <span class="flex h-full w-full items-center justify-center text-slate-700">◍</span>
                        @endif
                    </span>

                    <span class="min-w-0 flex-1">
                        <span class="flex items-center gap-1.5">
                            <span class="truncate text-[12px] font-black text-white">
                                {{ $competidor?->display_label ?? 'Sin nombre' }}
                            </span>

                            @if ($fila->titles > 0)
                                <span class="shrink-0 rounded bg-amber-500/15 px-1 font-mono text-[9px] font-black text-amber-300"
                                    title="Títulos ganados">{{ $fila->titles }}</span>
                            @endif
                        </span>

                        <span class="mt-0.5 block h-1.5 overflow-hidden rounded-full bg-slate-950">
                            <span class="block h-full rounded-full"
                                style="width: {{ $ancho }}%; background-color: {{ $fila->position <= 3 ? $tono : '#8b5cf6' }}"></span>
                        </span>
                    </span>

                    <span class="w-10 shrink-0 text-right font-mono text-[13px] font-black text-violet-300">
                        {{ $fila->points }}
                    </span>
                </a>
            @endforeach
        </div>
    @endif
</section>
