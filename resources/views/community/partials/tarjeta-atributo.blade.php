@php
    /*
     * Un atributo de la comunidad.
     *
     * Lo que decide si copiarlo: de qué tipo es y, si es catálogo, qué valores
     * trae dentro. Un atributo de catálogo vacío no sirve de nada, y eso hay que
     * verlo antes de copiarlo, no después.
     */

    $tono = $atributo->color ?: '#8b5cf6';

    $miCopia = $atributo->clones->first();

    $esMio = $atributo->user_id === auth()->id();

    $esCatalogo = $atributo->data_type === 'OPTION';

    $valores = $atributo->relationLoaded('options') ? $atributo->options->take(6) : collect();
@endphp

<article class="group flex flex-col overflow-hidden rounded-2xl border bg-slate-900/50 transition duration-300 hover:-translate-y-0.5"
    style="border-color: {{ $esCatalogo && $atributo->options_count === 0 ? '#f43f5e40' : $tono . '40' }}">

    <a href="{{ route('community.attributes.show', $atributo) }}"
        class="relative block aspect-[16/9] overflow-hidden bg-slate-950">

        @if ($atributo->image_url)
            <img src="{{ $atributo->image_url }}" alt="{{ $atributo->name }}" loading="lazy"
                class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
            <span class="absolute inset-x-0 bottom-0 h-2/5 bg-gradient-to-t from-slate-950 to-transparent"></span>
        @else
            <span class="flex h-full w-full items-center justify-center text-3xl"
                style="color: {{ $tono }}66; background: radial-gradient(120% 90% at 50% 0%, {{ $tono }}22, transparent 70%)">
                {{ $atributo->icon ?: $atributo->data_type_icon }}
            </span>
        @endif

        <span class="absolute left-2 top-2 rounded-lg border px-1.5 py-0.5 text-[9px] font-black uppercase tracking-wider"
            style="border-color: {{ $tono }}55; background-color: #020617d9; color: {{ $tono }}">
            {{ $atributo->data_type_label }}
        </span>

        @if ($miCopia)
            <span class="absolute right-2 top-2 rounded-lg bg-emerald-500 px-1.5 py-0.5 text-[9px] font-black text-emerald-950">
                ✓ tuyo
            </span>
        @elseif ($atributo->clones_count > 0)
            <span class="absolute right-2 top-2 rounded-lg bg-slate-950/85 px-1.5 py-0.5 font-mono text-[9px] font-black text-violet-300"
                title="Copiado {{ $atributo->clones_count }} veces">
                ↺{{ $atributo->clones_count }}
            </span>
        @endif

        <span class="absolute inset-x-0 bottom-0 p-2">
            <span class="block truncate text-[13px] font-black text-white">{{ $atributo->name }}</span>
        </span>
    </a>

    <div class="flex-1 space-y-1.5 p-2.5">

        @include('community.partials.atribucion', [
            'autor' => $atributo->creator,
            'origen' => $atributo->sourceAttribute?->creator,
        ])

        <p class="line-clamp-2 text-[10px] leading-relaxed text-slate-500">
            {{ $atributo->description ?: 'Sin descripción.' }}
        </p>

        @if ($esCatalogo)
            @if ($valores->isNotEmpty())
                <div class="flex -space-x-2">
                    @foreach ($valores as $valor)
                        <span class="h-7 w-7 shrink-0 overflow-hidden rounded-lg border-2 border-slate-900 bg-slate-950"
                            title="{{ $valor->name }}">
                            @if ($valor->image_url)
                                <img src="{{ $valor->image_url }}" alt="" loading="lazy" class="h-full w-full object-cover">
                            @else
                                <span class="flex h-full w-full items-center justify-center text-[9px] text-slate-700">◇</span>
                            @endif
                        </span>
                    @endforeach

                    @if ($atributo->options_count > 6)
                        <span class="flex h-7 shrink-0 items-center rounded-lg border-2 border-slate-900 bg-slate-950 px-1.5 font-mono text-[9px] font-black"
                            style="color: {{ $tono }}">
                            +{{ $atributo->options_count - 6 }}
                        </span>
                    @endif
                </div>
            @else
                <p class="rounded-lg border border-dashed border-rose-500/30 px-2 py-1 text-center text-[9px] text-rose-300/70">
                    Catálogo vacío: copiarlo no trae ningún valor.
                </p>
            @endif
        @else
            <span class="inline-block rounded border border-slate-800 px-1.5 py-0.5 font-mono text-[9px] text-slate-500">
                {{ $atributo->unit ?: 'se escribe a mano' }}
            </span>
        @endif
    </div>

    <div class="flex items-center gap-1 border-t border-slate-800 px-2 py-1.5">

        <a href="{{ route('community.attributes.show', $atributo) }}"
            class="rounded-lg px-2 py-1 text-[10px] font-black text-slate-400 transition hover:text-white">
            Ver
        </a>

        <span class="ml-auto flex items-center gap-1">
            @include('community.partials.copiar', [
                'ruta' => route('community.attributes.clone', $atributo),
                'esMio' => $esMio,
                'miCopia' => $miCopia,
                'seDeja' => (bool) $atributo->allow_cloning,
                'rutaMia' => $miCopia ? route('attributes.show', $miCopia) : '#',
                'compacto' => true,
            ])
        </span>
    </div>

</article>
