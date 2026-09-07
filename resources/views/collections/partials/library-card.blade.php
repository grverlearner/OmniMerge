@php
    /*
     * Una colección, en formato ficha.
     *
     * Dos cosas la definen y las dos se enseñan aquí: su **color**, que es un
     * dato suyo y no una decisión de diseño —por eso va en `style`, porque una
     * clase de Tailwind compuesta no existe en el CSS—, y **lo que hay
     * dentro**, que es lo único que de verdad la distingue de otra.
     */

    $acento = $coleccion->color ?: '#8b5cf6';

    $vacia = $coleccion->entities_count === 0;

    $miembros = $coleccion->entities->take(5);
@endphp

<article
    class="group relative flex flex-col rounded-2xl border bg-slate-900/50 transition duration-300 hover:-translate-y-0.5 {{ $vacia ? 'border-slate-800' : 'border-[color:var(--acento)]/30 hover:shadow-[0_16px_38px_-14px_var(--halo)]' }}"
    style="--acento: {{ $acento }}; --halo: {{ $acento }}55">

    {{-- ============ SU CARA ============ --}}

    <a href="{{ route('collections.show', $coleccion) }}"
        class="relative block aspect-[16/9] overflow-hidden rounded-t-2xl bg-slate-950">

        @if ($coleccion->image_url)
            <img src="{{ $coleccion->image_url }}" alt="{{ $coleccion->name }}" loading="lazy"
                class="h-full w-full object-cover transition duration-500 group-hover:scale-105">

            <span class="absolute inset-x-0 bottom-0 h-2/5 bg-gradient-to-t from-slate-950 to-transparent"></span>
        @else
            <span class="flex h-full w-full items-center justify-center text-3xl"
                style="color: {{ $acento }}55; background: radial-gradient(120% 90% at 50% 0%, {{ $acento }}22, transparent 70%)">
                {{ $coleccion->icon ?: '◫' }}
            </span>
        @endif

        <span class="absolute left-2 top-2 flex items-center gap-1 rounded-lg bg-slate-950/85 px-2 py-1 text-[9px] font-black uppercase tracking-wider"
            style="color: {{ $acento }}">
            {{ $coleccion->visibility_label }}
        </span>

        @if ($coleccion->status !== 'ACTIVE')
            <span class="absolute right-2 top-2 rounded px-1.5 py-0.5 text-[9px] font-black uppercase tracking-wider {{ $estadoTono[$coleccion->status] ?? 'bg-slate-800 text-slate-500' }}">
                {{ $estadoEtiqueta[$coleccion->status] ?? $coleccion->status }}
            </span>
        @endif
    </a>


    {{-- ============ QUÉ ES ============ --}}

    <div class="flex-1 p-3">

        <a href="{{ route('collections.show', $coleccion) }}"
            class="block truncate text-[13px] font-black text-white transition"
            onmouseover="this.style.color='{{ $acento }}'" onmouseout="this.style.color=''">
            {{ $coleccion->name }}
        </a>

        <p class="font-mono text-[9px] text-slate-600">{{ $coleccion->code }}</p>

        <p class="mt-1.5 line-clamp-2 text-[10px] leading-relaxed text-slate-500">
            {{ $coleccion->description ?: 'Sin descripción.' }}
        </p>


        {{-- Lo que hay dentro: sus caras --}}
        <div class="mt-2.5">
            @if ($vacia)
                <p class="rounded-xl border border-dashed border-slate-800 px-3 py-2.5 text-center text-[10px] leading-4 text-slate-600">
                    Vacía: no agrupa ninguna entidad todavía.
                </p>
            @else
                <a href="{{ route('collections.show', $coleccion) }}"
                    class="flex items-center gap-2 rounded-xl border border-slate-800 bg-slate-950 p-1.5 transition hover:border-slate-700">

                    <span class="flex -space-x-2">
                        @foreach ($miembros as $miembro)
                            <span class="h-8 w-8 shrink-0 overflow-hidden rounded-lg border-2 border-slate-950 bg-slate-900"
                                title="{{ $miembro->name }}">
                                @if ($miembro->image_url)
                                    <img src="{{ $miembro->image_url }}" alt="" loading="lazy" class="h-full w-full object-cover">
                                @else
                                    <span class="flex h-full w-full items-center justify-center text-[10px] text-slate-700">◍</span>
                                @endif
                            </span>
                        @endforeach
                    </span>

                    <span class="min-w-0 flex-1 text-[10px] leading-3">
                        <span class="block font-mono text-sm font-black" style="color: {{ $acento }}">
                            {{ $coleccion->entities_count }}
                        </span>
                        <span class="block text-slate-500">
                            {{ $coleccion->entities_count === 1 ? 'entidad dentro' : 'entidades dentro' }}
                        </span>
                    </span>
                </a>
            @endif
        </div>


        {{-- Sus etiquetas --}}
        <div class="mt-2 flex flex-wrap gap-1 text-[9px]">
            @if ($coleccion->allow_cloning)
                <span class="rounded-lg border border-emerald-500/25 bg-emerald-500/5 px-1.5 py-0.5 font-bold text-emerald-300"
                    title="Otros pueden copiarla a su biblioteca">Copiable</span>
            @endif

            @if ($coleccion->source_collection_id)
                <span class="rounded-lg border border-cyan-500/25 bg-cyan-500/5 px-1.5 py-0.5 font-bold text-cyan-300"
                    title="Copiada de {{ $coleccion->sourceCollection?->name ?? 'otra colección' }}">Clonada</span>
            @endif

            @if ($coleccion->views_count > 0)
                <span class="rounded-lg border border-slate-800 px-1.5 py-0.5 font-bold text-slate-400">
                    {{ $coleccion->views_count }} vistas
                </span>
            @endif

            @if ($coleccion->clones_count > 0)
                <span class="rounded-lg border border-slate-800 px-1.5 py-0.5 font-bold text-slate-400">
                    {{ $coleccion->clones_count }} copias
                </span>
            @endif
        </div>

    </div>


    {{-- ============ QUÉ SE PUEDE HACER ============ --}}

    <div class="flex items-center gap-1 border-t border-slate-800 px-2 py-1.5">

        <a href="{{ route('collections.show', $coleccion) }}"
            class="rounded-lg px-2 py-1 text-[10px] font-black text-slate-400 transition hover:text-white">
            Ver
        </a>

        @can('update', $coleccion)
            <a href="{{ route('collections.edit', $coleccion) }}"
                class="rounded-lg px-2 py-1 text-[10px] font-black text-slate-400 transition hover:text-amber-300">
                ✎ Editar
            </a>

            @include('collections.partials.quick-visibility', ['coleccion' => $coleccion])
        @endcan

    </div>

</article>
