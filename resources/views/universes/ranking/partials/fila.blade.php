@php
    /*
     * Un competidor en la clasificación, en una línea.
     *
     * La barra es su puntuación comparada con la del primero: es lo que
     * convierte «340 puntos» en «la mitad que el líder».
     */

    $competidor = $fila->entity;

    $ancho = max((int) round($fila->points / max($maximoPuntos, 1) * 100), 2);
@endphp

<div class="flex flex-wrap items-center gap-3 border-b border-slate-800/70 px-4 py-2 transition hover:bg-slate-950/50">

    <span class="w-6 shrink-0 text-center font-mono text-[12px] font-black text-slate-600">
        {{ $fila->position }}
    </span>

    <a href="{{ route('universes.entities.show', [$universe, $competidor]) }}"
        class="h-9 w-9 shrink-0 overflow-hidden rounded-lg border border-slate-800 bg-slate-950">
        @if ($competidor?->image_url)
            <img src="{{ $competidor->image_url }}" alt="" loading="lazy" class="h-full w-full object-cover">
        @else
            <span class="flex h-full w-full items-center justify-center text-slate-700">◍</span>
        @endif
    </a>

    <div class="min-w-0 flex-1">
        <div class="flex flex-wrap items-center gap-1.5">
            <a href="{{ route('universes.entities.show', [$universe, $competidor]) }}"
                class="truncate text-[12px] font-black text-white">
                {{ $competidor?->display_label ?? 'Sin nombre' }}
            </a>

            @if ($fila->titles > 0)
                <span class="rounded bg-amber-500/15 px-1.5 py-0.5 font-mono text-[9px] font-black text-amber-300"
                    title="Títulos ganados">
                    🏆{{ $fila->titles }}
                </span>
            @endif
        </div>

        <div class="mt-0.5 h-1.5 overflow-hidden rounded-full bg-slate-950">
            <div class="h-full rounded-full bg-violet-500" style="width: {{ $ancho }}%"></div>
        </div>
    </div>

    <span class="hidden shrink-0 font-mono text-[10px] text-slate-600 sm:block">
        {{ $fila->wins }}G · {{ $fila->draws }}E · {{ $fila->losses }}P
    </span>

    <span class="hidden w-12 shrink-0 text-right font-mono text-[10px] text-slate-500 lg:block"
        title="Porcentaje de victorias">
        {{ $fila->win_rate === null ? '—' : $fila->win_rate . '%' }}
    </span>

    <span class="w-12 shrink-0 text-right font-mono text-[14px] font-black text-violet-300">
        {{ $fila->points }}
    </span>
</div>
