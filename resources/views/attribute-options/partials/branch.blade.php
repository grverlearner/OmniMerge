@php
    /*
     * Una rama del árbol de un catálogo.
     *
     * Se llama a sí misma para bajar por los hijos. La colección `$todos` viaja
     * entera en cada nivel a propósito: así el árbol se monta sin una consulta
     * por nodo, que es lo que pasaría recorriendo `->children` a pelo.
     *
     * El uso llega en `$usos` —un mapa de id a número— porque estos valores se
     * piden en versión ligera, sin el `values_count` que sí trae la página.
     */

    $hijos = $todos->where('parent_option_id', $nodo->id);

    $color = $nodo->color ?: $acento;

    $usado = (int) ($usos[$nodo->id] ?? 0);

    /* Más de tres niveles y la sangría se come el ancho en un móvil. */
    $sangria = min($nivel, 3) * 20;
@endphp

<div style="margin-left: {{ $sangria }}px">

    <div class="flex items-center gap-2 rounded-xl border border-slate-800 bg-slate-950 p-1.5"
        @if ($nivel > 0) style="border-left: 2px solid {{ $color }}55" @endif>

        @if ($nivel > 0)
            <span class="shrink-0 font-mono text-[10px] text-slate-700">└</span>
        @endif

        <a href="{{ route('attribute-options.show', $nodo->id) }}"
            class="h-8 w-8 shrink-0 overflow-hidden rounded-lg border border-slate-800 bg-slate-900">
            @if ($nodo->image_url)
                <img src="{{ $nodo->image_url }}" alt="" loading="lazy" class="h-full w-full object-cover">
            @else
                <span class="flex h-full w-full items-center justify-center text-[11px]"
                    style="color: {{ $color }}">{{ $nodo->icon ?: '◇' }}</span>
            @endif
        </a>

        <a href="{{ route('attribute-options.show', $nodo->id) }}"
            class="min-w-0 flex-1 truncate text-[11px] font-black {{ $nodo->status === 'ACTIVE' ? 'text-white' : 'text-slate-500' }}">
            {{ $nodo->name }}
        </a>

        @if ($nodo->status !== 'ACTIVE')
            <span class="shrink-0 rounded px-1.5 py-0.5 text-[8px] font-black uppercase tracking-wider text-slate-500">
                {{ $nodo->status === 'INACTIVE' ? 'Inactivo' : 'Archivado' }}
            </span>
        @endif

        @if ($hijos->isNotEmpty())
            <span class="shrink-0 rounded-lg border border-slate-800 px-1.5 py-0.5 font-mono text-[9px] font-black text-slate-400"
                title="{{ $hijos->count() }} valores cuelgan de este">
                ⌄{{ $hijos->count() }}
            </span>
        @endif

        <span class="shrink-0 rounded-lg border px-1.5 py-0.5 font-mono text-[9px] font-black"
            style="border-color: {{ $color }}40; color: {{ $usado > 0 ? $color : '#475569' }}"
            title="Entidades que llevan este valor">
            {{ $usado }}
        </span>
    </div>

    @if ($hijos->isNotEmpty())
        <div class="mt-1.5 space-y-1.5">
            @foreach ($hijos as $hijo)
                @include('attribute-options.partials.branch', [
                    'nodo' => $hijo,
                    'nivel' => $nivel + 1,
                    'todos' => $todos,
                    'acento' => $acento,
                    'usos' => $usos,
                ])
            @endforeach
        </div>
    @endif

</div>
