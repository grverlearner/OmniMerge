@php
    /*
     * Una característica de una versión.
     *
     * Lo que la ficha tenía que decir y no decía: **de dónde sale**. Una
     * característica heredada viene de la entidad y cambiará cuando la entidad
     * cambie; una propia solo existe en esta versión. Son cosas distintas y
     * ahora se distinguen por color y por etiqueta.
     *
     * Y si el valor es de catálogo, se enseña con su cara: un clan, una aldea o
     * un anime se reconocen antes por la imagen que por el nombre.
     */

    $esPropia = $rasgo['source'] === 'VERSION';

    $opciones = $rasgo['options'] ?? collect();

    $etiqueta = $rasgo['custom_label'] ?: $rasgo['attribute']->name;
@endphp

<article class="overflow-hidden rounded-xl border bg-slate-950 {{ $esPropia ? 'border-violet-500/30' : 'border-slate-800' }}">

    <div class="flex items-center gap-2 border-b px-2.5 py-1.5 {{ $esPropia ? 'border-violet-500/20 bg-violet-500/5' : 'border-slate-800/70' }}">

        <span class="h-6 w-6 shrink-0 overflow-hidden rounded border border-slate-800 bg-slate-900">
            @if ($rasgo['attribute']->image_url)
                <img src="{{ $rasgo['attribute']->image_url }}" alt="" loading="lazy" class="h-full w-full object-cover">
            @else
                <span class="flex h-full w-full items-center justify-center text-[10px] text-slate-700">◱</span>
            @endif
        </span>

        <span class="min-w-0 flex-1 truncate text-[10px] font-black uppercase tracking-wider {{ $esPropia ? 'text-violet-300' : 'text-slate-500' }}">
            {{ $etiqueta }}
        </span>

        @if ($rasgo['is_featured'])
            <span class="shrink-0 text-amber-400" title="Destacada">★</span>
        @endif

        <span class="shrink-0 rounded px-1 py-0.5 text-[8px] font-black uppercase tracking-wider {{ $esPropia ? 'bg-violet-500/20 text-violet-300' : 'bg-slate-800 text-slate-500' }}"
            title="{{ $esPropia
                ? 'Valor propio de esta versión: no cambia aunque cambie la entidad'
                : 'Heredada: viene de ' . $rasgo['source_name'] . ' y cambiará si aquello cambia' }}">
            {{ $esPropia ? 'Propia' : 'Heredada' }}
        </span>
    </div>

    <div class="p-2.5">

        @if ($opciones->isNotEmpty())

            {{-- Valores de catálogo: con su cara --}}
            <div class="flex flex-wrap gap-1.5">
                @foreach ($opciones as $opcion)
                    <span class="flex items-center gap-1.5 rounded-lg border border-slate-800 bg-slate-900 py-1 pl-1 pr-2">
                        <span class="h-6 w-6 shrink-0 overflow-hidden rounded border border-slate-800 bg-slate-950">
                            @if ($opcion->image_url)
                                <img src="{{ $opcion->image_url }}" alt="" loading="lazy" class="h-full w-full object-cover">
                            @else
                                <span class="flex h-full w-full items-center justify-center text-[9px] text-slate-700">◇</span>
                            @endif
                        </span>

                        <span class="truncate text-[11px] font-bold text-slate-200">{{ $opcion->name }}</span>
                    </span>
                @endforeach
            </div>

        @elseif (filled($rasgo['display']))

            <p class="text-[12px] font-bold leading-relaxed text-slate-200">{{ $rasgo['display'] }}</p>

        @else

            <p class="text-[11px] text-slate-700">Sin valor</p>

        @endif

        @unless ($esPropia)
            <p class="mt-1.5 truncate text-[9px] text-slate-600">
                de {{ $rasgo['source_name'] }}
            </p>
        @endunless

    </div>

</article>
