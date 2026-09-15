@php
    /*
     * El pulso de los ultimos doce meses, sumando todos los mundos.
     *
     * Es lo unico que contesta si esto se usa o se uso. Las cifras de arriba
     * dicen cuanto hay; esto dice cuando pasó.
     *
     * Sale de las competiciones ya cargadas: ninguna consulta nueva.
     */

    $techo = max(1, $pulso->max('cuantas'));
    $total = $pulso->sum('cuantas');
@endphp

<section class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

    <header class="flex flex-wrap items-center gap-2 border-b border-slate-800 px-4 py-2.5">

        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-cyan-500/15 text-cyan-300">
            <x-omni-icon name="barras" size="h-4 w-4" />
        </span>

        <div class="min-w-0 flex-1">
            <h2 class="text-[13px] font-black text-white">El último año</h2>
            <p class="text-[10px] text-slate-500">
                Cuántas competiciones empezaron cada mes, en todos tus mundos juntos.
            </p>
        </div>

        <span class="shrink-0 font-mono text-[11px] font-black"
            style="color: {{ $total > 0 ? '#22d3ee' : '#475569' }}">
            {{ $total }} en 12 meses
        </span>
    </header>

    <div class="overflow-x-auto p-4">

        <div class="flex min-w-max items-end gap-1.5">

            @foreach ($pulso as $mes)
                @php
                    $alto = max(4, (int) round($mes['cuantas'] / $techo * 100));
                    $esElMayor = $mes['cuantas'] === $techo && $techo > 0;
                @endphp

                <span class="flex w-12 flex-col items-center"
                    title="{{ $meses[$mes['mes']] }} {{ $mes['anio'] }} — {{ $mes['cuantas'] }} {{ $mes['cuantas'] === 1 ? 'competición' : 'competiciones' }}">

                    <span class="mb-1 font-mono text-[10px] font-black"
                        style="color: {{ $mes['cuantas'] > 0 ? ($esElMayor ? '#22d3ee' : '#64748b') : '#334155' }}">
                        {{ $mes['cuantas'] }}
                    </span>

                    <span class="block w-full rounded-t-md transition"
                        style="height: {{ $alto }}px;
                               background: {{ $mes['cuantas'] > 0
                                   ? 'linear-gradient(to top, #0e7490, #22d3ee)'
                                   : '#1e293b' }}"></span>

                    <span class="mt-1 block text-[9px] font-black uppercase tracking-wider"
                        style="color: {{ $mes['cuantas'] > 0 ? '#64748b' : '#334155' }}">
                        {{ $meses[$mes['mes']] }}
                    </span>
                </span>
            @endforeach
        </div>

        @if ($total === 0)
            <p class="mt-3 text-[10px] leading-4 text-slate-600">
                No se ha empezado ninguna competición en los últimos doce meses. Las barras se
                llenan solas en cuanto se juegue algo, en cualquiera de tus mundos.
            </p>
        @endif
    </div>
</section>
