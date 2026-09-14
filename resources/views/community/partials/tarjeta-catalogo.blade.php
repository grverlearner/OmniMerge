@php
    /*
     * Un valor de catálogo de la comunidad.
     *
     * Es la pieza más pequeña que se puede copiar. Lo que hace falta saber: a
     * qué catálogo pertenece —porque un valor suelto no significa nada— y si
     * cuelga de otro.
     */

    $tono = $valor->color ?: ($valor->attribute?->color ?: '#6366f1');

    $miCopia = $valor->clones->first();

    $esMio = $valor->user_id === auth()->id();
@endphp

<article class="group flex flex-col overflow-hidden rounded-2xl border bg-slate-900/50 transition duration-300 hover:-translate-y-0.5"
    style="border-color: {{ $valor->image_url ? $tono . '40' : '#f43f5e40' }}">

    <a href="{{ route('community.catalogs.show', $valor) }}"
        class="relative block aspect-square overflow-hidden bg-slate-950">

        @if ($valor->image_url)
            <img src="{{ $valor->image_url }}" alt="{{ $valor->name }}" loading="lazy"
                class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
        @else
            <span class="flex h-full w-full items-center justify-center text-3xl"
                style="color: {{ $tono }}66; background: radial-gradient(120% 90% at 50% 0%, {{ $tono }}22, transparent 70%)">
                {{ $valor->icon ?: '◇' }}
            </span>
        @endif

        @if ($miCopia)
            <span class="absolute right-1.5 top-1.5 rounded bg-emerald-500 px-1 py-0.5 text-[9px] font-black text-emerald-950">✓</span>
        @endif

        @if ($valor->children_count > 0)
            <span class="absolute left-1.5 top-1.5 rounded bg-slate-950/85 px-1 font-mono text-[9px] font-black text-slate-300"
                title="{{ $valor->children_count }} valores cuelgan de este">
                ⌄{{ $valor->children_count }}
            </span>
        @endif
    </a>

    <div class="flex-1 space-y-1 p-2">

        <a href="{{ route('community.catalogs.show', $valor) }}"
            class="block truncate text-[12px] font-black text-white">{{ $valor->name }}</a>

        @if ($valor->attribute)
            <a href="{{ route('community.attributes.show', $valor->attribute) }}"
                class="block truncate text-[10px] font-bold transition hover:underline"
                style="color: {{ $valor->attribute->color ?: '#a78bfa' }}">
                {{ $valor->attribute->name }}
            </a>
        @endif

        @if ($valor->parent)
            <p class="truncate text-[9px] text-slate-600">dentro de {{ $valor->parent->name }}</p>
        @endif

        @include('community.partials.atribucion', [
            'autor' => $valor->user,
            'origen' => $valor->sourceOption?->user,
        ])
    </div>

    <div class="flex items-center gap-1 border-t border-slate-800 px-1.5 py-1.5">

        <span class="font-mono text-[9px] text-slate-600" title="Entidades que lo llevan">
            {{ $valor->values_count }}
        </span>

        <span class="ml-auto flex items-center gap-1">
            @include('community.partials.copiar', [
                'ruta' => route('community.catalogs.clone', $valor),
                'esMio' => $esMio,
                'miCopia' => $miCopia,
                'seDeja' => true,
                'rutaMia' => $miCopia ? route('attribute-options.show', $miCopia) : '#',
                'compacto' => true,
            ])
        </span>
    </div>

</article>
