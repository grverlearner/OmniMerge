{{--
    Lo que más se copia. Es la señal honesta de que algo le sirvió a alguien:
    mirar es gratis, llevárselo es una decisión.
--}}

@php
    $maximo = max(1, (int) $copiados->max('copias'));
@endphp

<section class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

    <div class="flex items-center gap-2 border-b border-slate-800 px-3 py-2.5">
        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-pink-500/15 text-pink-300">
            <x-omni-icon name="medalla" size="h-4 w-4" />
        </span>
        <div>
            <h2 class="text-[14px] font-black text-white">Lo que más se copia</h2>
            <p class="text-[10px] text-slate-500">Lo que la gente se ha llevado a su cuenta</p>
        </div>
    </div>

    @if ($copiados->isEmpty())
        <p class="px-4 py-8 text-center text-[11px] leading-relaxed text-slate-500">
            Nadie ha copiado nada todavía. Cuando alguien se lleve una pieza, aparecerá aquí.
        </p>
    @else
        <ol class="divide-y divide-slate-800/70">
            @foreach ($copiados as $indice => $pieza)
                <li class="flex items-center gap-2.5 px-3 py-2">
                    <span class="w-4 shrink-0 text-center font-mono text-[11px] font-black {{ $indice < 3 ? 'text-amber-300' : 'text-slate-600' }}">{{ $indice + 1 }}</span>

                    <a href="{{ $pieza['url'] }}" class="h-9 w-9 shrink-0 overflow-hidden rounded-lg border bg-slate-950" style="border-color: {{ $pieza['tono'] }}55">
                        @if ($pieza['img'])
                            <img src="{{ $pieza['img'] }}" alt="" loading="lazy" class="h-full w-full object-cover">
                        @else
                            <span class="flex h-full w-full items-center justify-center" style="color: {{ $pieza['tono'] }}88">
                                <x-omni-icon :name="$pieza['icono']" size="h-4 w-4" />
                            </span>
                        @endif
                    </a>

                    <div class="min-w-0 flex-1">
                        <a href="{{ $pieza['url'] }}" class="block truncate text-[12px] font-black text-slate-200 transition hover:text-emerald-300">{{ $pieza['nombre'] }}</a>
                        <span class="mt-1 block h-1 overflow-hidden rounded-full bg-slate-800">
                            <span class="block h-full rounded-full" style="width: {{ round($pieza['copias'] / $maximo * 100) }}%; background-color: {{ $pieza['tono'] }}"></span>
                        </span>
                    </div>

                    <span class="shrink-0 text-right">
                        <span class="block font-mono text-[12px] font-black text-emerald-300">{{ $pieza['copias'] }}×</span>
                        <span class="block text-[8px] font-black uppercase tracking-wider" style="color: {{ $pieza['tono'] }}">{{ $pieza['etiqueta'] }}</span>
                    </span>
                </li>
            @endforeach
        </ol>
    @endif
</section>
