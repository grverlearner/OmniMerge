@php
    /*
     * Una entidad en una línea.
     *
     * El modo lista es para recorrer muchas de arriba abajo. Cabe lo que la
     * ficha dice, pero en horizontal: la cara sigue estando —pequeña, pero
     * está—, y las características se enseñan por su NOMBRE Y SU VALOR, que
     * es lo que distingue dos entidades que por cifras parecen iguales.
     */

    $tipo = $entidad->entityType;

    $colorTipo = $tipo?->color ?: '#6366f1';

    $conValor = $entidad->entityAttributes
        ->filter(fn($ea) => $ea->values->isNotEmpty())
        ->take(4);
@endphp

<div
    class="flex flex-wrap items-center gap-3 rounded-xl border border-slate-800 bg-slate-900/40 px-3 py-2 transition hover:border-slate-700 hover:bg-slate-900">

    {{-- La cara, pequeña --}}
    <a href="{{ route('entities.show', $entidad) }}"
        class="h-12 w-12 shrink-0 overflow-hidden rounded-xl border border-slate-800 bg-slate-950">
        @if ($entidad->base_display_image_url)
            <img src="{{ $entidad->base_display_image_url }}" alt="" loading="lazy"
                class="h-full w-full object-cover">
        @else
            <span class="flex h-full w-full items-center justify-center text-lg font-black opacity-40"
                style="color: {{ $colorTipo }}">
                {{ $tipo?->icon ?: mb_strtoupper(mb_substr($entidad->name, 0, 1)) }}
            </span>
        @endif
    </a>

    {{-- Quién es --}}
    <div class="min-w-[180px] flex-1">

        <div class="flex flex-wrap items-center gap-1.5">
            <a href="{{ route('entities.show', $entidad) }}"
                class="truncate text-[13px] font-black text-white transition hover:text-indigo-300">
                {{ $entidad->name }}
            </a>

            @if ($entidad->status !== 'ACTIVE')
                <span class="rounded px-1.5 py-0.5 text-[8px] font-black uppercase tracking-wider {{ $estadoTono[$entidad->status] ?? 'bg-slate-800 text-slate-500' }}">
                    {{ $entidad->status_label }}
                </span>
            @endif

            @if ($entidad->visibility === 'PUBLIC')
                <span class="rounded bg-sky-500/20 px-1.5 py-0.5 text-[8px] font-black uppercase tracking-wider text-sky-300">
                    pública
                </span>
            @endif
        </div>

        <p class="mt-0.5 flex flex-wrap items-center gap-1.5 text-[10px]">
            <span class="font-mono text-slate-600">{{ $entidad->code }}</span>

            <span class="text-slate-800">·</span>

            @if ($tipo)
                <span class="flex items-center gap-1 font-bold" style="color: {{ $colorTipo }}">
                    <span class="h-1.5 w-1.5 rounded-full" style="background-color: {{ $colorTipo }}"></span>
                    {{ $tipo->name }}
                </span>
            @else
                <span class="text-amber-300">Sin tipo</span>
            @endif
        </p>

    </div>

    {{-- Lo que lleva encima --}}
    <div class="flex min-w-[240px] flex-1 flex-wrap items-center gap-1">

        @forelse ($conValor as $caracteristica)
            <span class="rounded border border-violet-500/25 bg-violet-500/5 px-1.5 py-0.5 text-[9px] text-slate-400">
                {{ $caracteristica->attribute?->name }}
                <span class="font-bold text-slate-200">
                    {{ $caracteristica->values->map->displayValue()->filter()->implode(', ') }}
                </span>
            </span>
        @empty
            <span class="text-[9px] text-slate-700">sin características con valor</span>
        @endforelse

        @if ($entidad->entity_attributes_count > $conValor->count())
            <span class="text-[9px] text-slate-600">+{{ $entidad->entity_attributes_count - $conValor->count() }}</span>
        @endif

        @foreach ($entidad->collections->take(2) as $coleccion)
            <span class="flex items-center gap-1 rounded border bg-slate-950 px-1.5 py-0.5 text-[9px] font-bold text-slate-400"
                style="border-color: {{ $coleccion->color ?: '#334155' }}66">
                <span class="h-1.5 w-1.5 rounded-full" style="background-color: {{ $coleccion->color ?: '#64748b' }}"></span>
                {{ $coleccion->name }}
            </span>
        @endforeach

    </div>

    {{-- Sus cifras --}}
    <div class="flex shrink-0 items-center gap-2 font-mono text-[10px]">
        <span class="text-violet-300" title="Características">{{ $entidad->entity_attributes_count }}☷</span>
        <span class="text-cyan-300" title="Colecciones">{{ $entidad->collections_count }}◈</span>
        <span class="text-amber-300" title="Versiones">{{ $entidad->entity_versions_count }}★</span>
    </div>

    {{-- Qué se puede hacer --}}
    <div class="flex shrink-0 items-center gap-1">

        @can('update', $entidad)
            <a href="{{ route('entities.edit', $entidad) }}" title="Editar"
                class="rounded-lg border border-slate-800 px-2 py-1 text-[10px] font-black text-slate-400 transition hover:border-indigo-500 hover:text-indigo-300">
                ✎
            </a>

            <a href="{{ route('entities.attributes.edit', $entidad) }}" title="Características"
                class="rounded-lg border border-slate-800 px-2 py-1 text-[10px] font-black text-slate-400 transition hover:border-violet-500 hover:text-violet-300">
                ☷
            </a>
        @endcan

        <a href="{{ route('entities.show', $entidad) }}"
            class="rounded-lg border border-slate-800 px-2.5 py-1 text-[10px] font-black text-slate-300 transition hover:border-slate-600">
            Ver →
        </a>

    </div>

</div>
