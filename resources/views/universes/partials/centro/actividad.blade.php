@php
    /*
     * Que ha pasado, en todos los mundos mezclados.
     *
     * Cada linea lleva su universo, porque una entidad importada no dice nada
     * sin saber a donde llego. El filtro por tipo se aplica en el sitio: son
     * catorce lineas, no hace falta volver al servidor.
     */

    $tiposPresentes = $actividad->pluck('type')->unique()->values();
@endphp

<section class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

    <header class="flex flex-wrap items-center gap-2 border-b border-slate-800 px-4 py-2.5">

        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-slate-800 text-slate-300">
            <x-omni-icon name="historial" size="h-4 w-4" />
        </span>

        <div class="min-w-0 flex-1">
            <h2 class="text-[13px] font-black text-white">Qué ha pasado</h2>
            <p class="text-[10px] text-slate-500">Lo último de todos tus mundos, mezclado.</p>
        </div>
    </header>

    @if ($actividad->isEmpty())

        <div class="px-4 py-8 text-center">
            <p class="text-[12px] font-black text-slate-300">Todavía no ha pasado nada</p>
            <p class="mx-auto mt-1 max-w-sm text-[11px] leading-relaxed text-slate-500">
                Este registro se escribe solo: cuando empiece una temporada, se juegue una
                competición o lleguen competidores nuevos, aparecerá aquí.
            </p>
        </div>

    @else

        @if ($tiposPresentes->count() > 1)
            <div class="flex flex-wrap items-center gap-1.5 border-b border-slate-800/70 px-4 py-2">

                <button type="button" @click="tipoActividad = ''"
                    class="rounded-lg border px-2 py-1 text-[10px] font-black transition"
                    :class="tipoActividad === '' ? 'border-slate-600 bg-slate-800 text-slate-200' : 'border-slate-800 text-slate-500 hover:text-slate-300'">
                    Todo
                </button>

                @foreach ($tiposPresentes as $tipo)
                    @php [$tonoA, $textoA] = $tonoTipoActividad[$tipo] ?? ['#94a3b8', $tipo]; @endphp

                    <button type="button" @click="tipoActividad = (tipoActividad === '{{ $tipo }}' ? '' : '{{ $tipo }}')"
                        class="flex items-center gap-1.5 rounded-lg border px-2 py-1 text-[10px] font-black transition"
                        :style="tipoActividad === '{{ $tipo }}'
                            ? 'border-color: {{ $tonoA }}; background-color: {{ $tonoA }}22; color: {{ $tonoA }}'
                            : 'border-color: #1e293b; color: #64748b'">
                        <span class="h-2 w-2 rounded-sm" style="background-color: {{ $tonoA }}"></span>
                        {{ $textoA }}
                    </button>
                @endforeach
            </div>
        @endif

        <div class="divide-y divide-slate-800/70">

            @foreach ($actividad as $linea)
                @php
                    [$tonoA, $textoA] = $tonoTipoActividad[$linea->type] ?? ['#94a3b8', $linea->type];
                    $suMundo = $mundos[$linea->universe_id] ?? null;
                    $suEntidad = $linea->universeEntity;
                @endphp

                <div x-show="tipoActividad === '' || tipoActividad === '{{ $linea->type }}'"
                    class="flex items-center gap-2.5 px-4 py-2 transition hover:bg-slate-950/40">

                    <span class="h-6 w-1 shrink-0 rounded-full" style="background-color: {{ $tonoA }}"></span>

                    @if ($suEntidad && $suMundo)
                        <a href="{{ route('universes.entities.show', [$suMundo, $suEntidad]) }}"
                            class="h-8 w-8 shrink-0 overflow-hidden rounded-lg border border-slate-800 bg-slate-950">
                            @if ($suEntidad->image_url)
                                <img src="{{ $suEntidad->image_url }}" alt="" loading="lazy" class="h-full w-full object-cover">
                            @else
                                <span class="flex h-full w-full items-center justify-center text-slate-700">◍</span>
                            @endif
                        </a>
                    @else
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border border-slate-800 bg-slate-950"
                            style="color: {{ $tonoA }}">
                            <x-omni-icon name="chispa" size="h-3.5 w-3.5" />
                        </span>
                    @endif

                    <div class="min-w-0 flex-1">
                        <p class="truncate text-[11px] font-bold text-slate-200">
                            {{ $linea->message ?: $textoA }}
                        </p>

                        <p class="flex items-center gap-1.5 text-[9px]">
                            @if ($suMundo)
                                <a href="{{ $suMundo->home_url }}"
                                    class="truncate font-black text-slate-500 transition hover:text-violet-300">
                                    {{ $suMundo->name }}
                                </a>
                            @endif

                            <span class="font-black uppercase tracking-wider"
                                style="color: {{ $tonoA }}99">{{ $textoA }}</span>
                        </p>
                    </div>

                    <span class="shrink-0 text-right font-mono text-[9px] text-slate-600">
                        @if ($linea->occurred_at)
                            {{ $linea->occurred_at->format('d') }}
                            {{ $meses[(int) $linea->occurred_at->format('n')] }}
                            <span class="block">{{ $linea->occurred_at->format('H:i') }}</span>
                        @else
                            —
                        @endif
                    </span>
                </div>
            @endforeach
        </div>
    @endif
</section>
