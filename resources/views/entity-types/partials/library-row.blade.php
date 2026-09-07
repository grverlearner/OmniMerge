@php
    /*
     * Un tipo de entidad en una línea.
     *
     * Lo mismo que la ficha, en horizontal: la cara del tipo, su descripción,
     * las caras de sus entidades y su cuenta. Es el modo para recorrer muchos
     * de arriba abajo.
     */

    $color = $tipo->color ?: '#6366f1';

    $vacio = $tipo->entities_count === 0;
@endphp

<div class="flex flex-wrap items-center gap-3 rounded-xl border bg-slate-900/40 px-3 py-2 transition hover:bg-slate-900"
    style="border-color: {{ $vacio ? '#1e293b' : $color . '3a' }}">

    <a href="{{ route('entity-types.show', $tipo) }}"
        class="h-11 w-11 shrink-0 overflow-hidden rounded-xl border bg-slate-950"
        style="border-color: {{ $color }}44">
        @if ($tipo->image_url)
            <img src="{{ $tipo->image_url }}" alt="" loading="lazy" class="h-full w-full object-cover">
        @else
            <span class="flex h-full w-full items-center justify-center text-lg"
                style="color: {{ $color }}">{{ $tipo->icon ?: '◇' }}</span>
        @endif
    </a>

    <div class="min-w-[170px] flex-1">
        <div class="flex flex-wrap items-center gap-1.5">
            <a href="{{ route('entity-types.show', $tipo) }}"
                class="truncate text-[13px] font-black text-white transition hover:opacity-80">
                {{ $tipo->name }}
            </a>

            @if ($tipo->status !== 'ACTIVE')
                <span class="rounded px-1.5 py-0.5 text-[8px] font-black uppercase tracking-wider {{ $estadoTono[$tipo->status] ?? 'bg-slate-800 text-slate-500' }}">
                    {{ $tipo->status }}
                </span>
            @endif
        </div>

        <p class="mt-0.5 flex flex-wrap items-center gap-1.5 text-[10px]">
            <span class="font-mono text-slate-600">{{ $tipo->code }}</span>
            <span class="text-slate-800">·</span>
            <span class="flex items-center gap-1" style="color: {{ $color }}">
                <span class="h-1.5 w-1.5 rounded-full" style="background-color: {{ $color }}"></span>
                {{ $color }}
            </span>
        </p>
    </div>

    {{-- Las caras de lo que lo lleva --}}
    <div class="flex min-w-[160px] flex-1 items-center gap-1">
        @forelse ($tipo->entities as $entidad)
            <a href="{{ route('entities.show', $entidad) }}" title="{{ $entidad->name }}"
                class="h-8 w-8 shrink-0 overflow-hidden rounded-lg border border-slate-800 bg-slate-950 transition hover:border-slate-600">
                @if ($entidad->image_url)
                    <img src="{{ $entidad->image_url }}" alt="" loading="lazy" class="h-full w-full object-cover">
                @else
                    <span class="flex h-full w-full items-center justify-center text-[10px] font-black text-slate-600">
                        {{ mb_strtoupper(mb_substr($entidad->name, 0, 1)) }}
                    </span>
                @endif
            </a>
        @empty
            <span class="text-[9px] text-slate-700">todavía no lo lleva nada</span>
        @endforelse

        @if ($tipo->entities_count > $tipo->entities->count())
            <span class="font-mono text-[10px] text-slate-600">
                +{{ $tipo->entities_count - $tipo->entities->count() }}
            </span>
        @endif
    </div>

    <span class="shrink-0 rounded-lg border px-2 py-1 font-mono text-[11px] font-black"
        style="border-color: {{ $color }}33; background-color: {{ $color }}14; color: {{ $vacio ? '#475569' : $color }}">
        {{ $tipo->entities_count }}
    </span>

    <div class="flex shrink-0 items-center gap-1">
        @can('update', $tipo)
            <a href="{{ route('entity-types.edit', $tipo) }}" title="Editar"
                class="rounded-lg border border-slate-800 px-2 py-1 text-[10px] font-black text-slate-400 transition hover:border-amber-500 hover:text-amber-300">
                ✎
            </a>
        @endcan

        <a href="{{ route('entities.index', ['type' => $tipo->id]) }}" title="Ver sus entidades en la biblioteca"
            class="rounded-lg border border-slate-800 px-2 py-1 text-[10px] font-black text-slate-400 transition hover:border-indigo-500 hover:text-indigo-300">
            ▦
        </a>

        <a href="{{ route('entity-types.show', $tipo) }}"
            class="rounded-lg border border-slate-800 px-2.5 py-1 text-[10px] font-black text-slate-300 transition hover:border-slate-600">
            Ver →
        </a>
    </div>

</div>
