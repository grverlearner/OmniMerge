@php
    /*
     * Una colección de la comunidad.
     *
     * Una colección es un montón de entidades, así que la ficha enseña las caras
     * de las que tiene dentro: es lo único que dice si vale la pena copiarla.
     */

    $tono = $coleccion->color ?: '#6366f1';

    $miCopia = $coleccion->clones->first();

    $esMio = $coleccion->user_id === auth()->id();

    $dentro = $coleccion->relationLoaded('entities') ? $coleccion->entities->take(6) : collect();
@endphp

<article class="group flex flex-col overflow-hidden rounded-2xl border bg-slate-900/50 transition duration-300 hover:-translate-y-0.5"
    style="border-color: {{ $tono }}40">

    <a href="{{ route('community.collections.show', $coleccion) }}"
        class="relative block aspect-[16/9] overflow-hidden bg-slate-950">

        @if ($coleccion->image_url)
            <img src="{{ $coleccion->image_url }}" alt="{{ $coleccion->name }}" loading="lazy"
                class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
            <span class="absolute inset-x-0 bottom-0 h-2/5 bg-gradient-to-t from-slate-950 to-transparent"></span>
        @else
            <span class="flex h-full w-full items-center justify-center text-3xl"
                style="color: {{ $tono }}66; background: radial-gradient(120% 90% at 50% 0%, {{ $tono }}22, transparent 70%)">
                {{ $coleccion->icon ?: '❒' }}
            </span>
        @endif

        <span class="absolute left-2 top-2 rounded-lg border px-1.5 py-0.5 font-mono text-[10px] font-black"
            style="border-color: {{ $tono }}55; background-color: #020617d9; color: {{ $tono }}">
            {{ $coleccion->entities_count }}
        </span>

        @if ($miCopia)
            <span class="absolute right-2 top-2 rounded-lg bg-emerald-500 px-1.5 py-0.5 text-[9px] font-black text-emerald-950">
                ✓ tuya
            </span>
        @elseif ($coleccion->clones_count > 0)
            <span class="absolute right-2 top-2 rounded-lg bg-slate-950/85 px-1.5 py-0.5 font-mono text-[9px] font-black text-violet-300"
                title="Copiada {{ $coleccion->clones_count }} veces">
                ↺{{ $coleccion->clones_count }}
            </span>
        @endif

        <span class="absolute inset-x-0 bottom-0 p-2">
            <x-content-badges type="collection" :id="$coleccion->id" size="xs" wrap="mb-1 flex flex-wrap gap-1" />
            <span class="block truncate text-[13px] font-black text-white">{{ $coleccion->name }}</span>
        </span>
    </a>

    <div class="flex-1 space-y-1.5 p-2.5">

        @include('community.partials.atribucion', [
            'autor' => $coleccion->creator,
            'origen' => $coleccion->sourceCollection?->creator,
        ])

        <p class="line-clamp-2 text-[10px] leading-relaxed text-slate-500">
            {{ $coleccion->description ?: 'Sin descripción.' }}
        </p>

        @if ($dentro->isNotEmpty())
            <div class="flex -space-x-2">
                @foreach ($dentro as $miembro)
                    <span class="h-7 w-7 shrink-0 overflow-hidden rounded-lg border-2 border-slate-900 bg-slate-950"
                        title="{{ $miembro->name }}">
                        @if ($miembro->public_image_url ?: $miembro->image_url)
                            <img src="{{ $miembro->public_image_url ?: $miembro->image_url }}" alt=""
                                loading="lazy" class="h-full w-full object-cover">
                        @else
                            <span class="flex h-full w-full items-center justify-center text-[9px] text-slate-700">◍</span>
                        @endif
                    </span>
                @endforeach

                @if ($coleccion->entities_count > 6)
                    <span class="flex h-7 shrink-0 items-center rounded-lg border-2 border-slate-900 bg-slate-950 px-1.5 font-mono text-[9px] font-black"
                        style="color: {{ $tono }}">
                        +{{ $coleccion->entities_count - 6 }}
                    </span>
                @endif
            </div>
        @elseif ($coleccion->entities_count === 0)
            <p class="rounded-lg border border-dashed border-slate-800 px-2 py-1 text-center text-[9px] text-slate-600">
                Está vacía.
            </p>
        @endif
    </div>

    <div class="flex items-center gap-1 border-t border-slate-800 px-2 py-1.5">

        <a href="{{ route('community.collections.show', $coleccion) }}"
            class="rounded-lg px-2 py-1 text-[10px] font-black text-slate-400 transition hover:text-white">
            Ver
        </a>

        <span class="ml-auto flex items-center gap-1">
            @include('community.partials.copiar', [
                'ruta' => route('community.collections.clone', $coleccion),
                'esMio' => $esMio,
                'miCopia' => $miCopia,
                'seDeja' => (bool) $coleccion->allow_cloning,
                'rutaMia' => $miCopia ? route('collections.show', $miCopia) : '#',
                'compacto' => true,
            ])
        </span>
    </div>

</article>
