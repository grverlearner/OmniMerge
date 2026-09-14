@php
    /*
     * Un valor de catálogo, en formato ficha.
     *
     * Lo que hace falta saber de un valor sin abrirlo: de qué catálogo es, si
     * cuelga de otro, cuántos cuelgan de él, y —lo que de verdad importa— si lo
     * lleva alguna entidad. Un valor con cero usos no es un error, pero saber
     * cuáles son es la diferencia entre un catálogo trabajado y uno abandonado.
     *
     * Su color es un dato suyo, así que viaja en `style`: una clase de Tailwind
     * compuesta a partir de la base de datos no existiría en el CSS generado.
     */

    $acento = $valor->color ?: ($valor->attribute?->color ?: '#6366f1');

    $sinCara = ! $valor->image_url;
@endphp

<article
    class="group relative flex flex-col overflow-hidden rounded-2xl border bg-slate-900/50 transition duration-300 hover:-translate-y-0.5"
    style="border-color: {{ $sinCara ? '#f43f5e40' : $acento . '40' }}">

    {{-- ============ SELECCIÓN ============ --}}

    <label class="absolute left-2 top-2 z-10 flex h-6 w-6 cursor-pointer items-center justify-center rounded-lg border border-slate-700 bg-slate-950/85 transition hover:border-violet-500"
        :class="seleccionadas.includes({{ $valor->id }}) ? 'border-violet-500 bg-violet-500' : ''"
        title="Seleccionar para cambiar su estado en lote">
        <input type="checkbox" value="{{ $valor->id }}" x-model.number="seleccionadas" class="sr-only">
        <span class="text-[11px] font-black text-white"
            x-show="seleccionadas.includes({{ $valor->id }})">✓</span>
    </label>


    {{-- ============ SU CARA ============ --}}

    <a href="{{ route('attribute-options.show', $valor) }}"
        class="relative block aspect-square overflow-hidden bg-slate-950">

        @if ($valor->image_url)
            <img src="{{ $valor->image_url }}" alt="{{ $valor->name }}" loading="lazy"
                class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
        @else
            <span class="flex h-full w-full items-center justify-center text-3xl"
                style="color: {{ $acento }}66; background: radial-gradient(120% 90% at 50% 0%, {{ $acento }}22, transparent 70%)">
                {{ $valor->icon ?: '◇' }}
            </span>
        @endif

        <span class="absolute right-2 top-2 flex flex-col items-end gap-1">
            @if ($valor->status !== 'ACTIVE')
                <span class="rounded px-1.5 py-0.5 text-[9px] font-black uppercase tracking-wider {{ $tonoEstado[$valor->status] ?? 'bg-slate-800 text-slate-500' }}">
                    {{ $estadoEtiqueta[$valor->status] ?? $valor->status }}
                </span>
            @endif

            @if ($valor->children_count > 0)
                <span class="rounded bg-slate-950/85 px-1.5 py-0.5 font-mono text-[9px] font-black text-slate-300"
                    title="{{ $valor->children_count }} valores cuelgan de este">
                    ⌄{{ $valor->children_count }}
                </span>
            @endif
        </span>

        @if ($sinCara)
            <span class="absolute inset-x-0 bottom-0 bg-rose-500/85 py-0.5 text-center text-[9px] font-black text-white">
                sin imagen
            </span>
        @endif
    </a>


    {{-- ============ QUÉ ES ============ --}}

    <div class="flex-1 p-2.5">

        <a href="{{ route('attribute-options.show', $valor) }}"
            class="block truncate text-[12px] font-black text-white">
            {{ $valor->name }}
        </a>

        <a href="{{ route('attribute-options.index', ['attribute' => $valor->attribute_id]) }}"
            class="mt-0.5 flex items-center gap-1 truncate text-[10px] font-bold transition hover:underline"
            style="color: {{ $acento }}">
            {{ $valor->attribute?->name ?? 'Sin catálogo' }}
        </a>

        @if ($valor->parent)
            <p class="mt-0.5 truncate text-[9px] text-slate-600">
                dentro de {{ $valor->parent->name }}
            </p>
        @endif

        <div class="mt-2 flex items-center gap-1.5">
            <span class="flex-1 rounded-lg border border-slate-800 bg-slate-950 px-2 py-1"
                title="Entidades que llevan este valor">
                <span class="block font-mono text-[13px] font-black"
                    style="color: {{ $valor->values_count > 0 ? $acento : '#475569' }}">
                    {{ $valor->values_count }}
                </span>
                <span class="block text-[8px] font-black uppercase tracking-wider text-slate-600">
                    {{ $valor->values_count === 1 ? 'uso' : 'usos' }}
                </span>
            </span>

            <span class="font-mono text-[9px] text-slate-700">{{ $valor->code }}</span>
        </div>

    </div>


    {{-- ============ QUÉ SE PUEDE HACER ============ --}}

    <div class="flex items-center gap-0.5 border-t border-slate-800 px-1.5 py-1">

        <a href="{{ route('attribute-options.show', $valor) }}"
            class="rounded-lg px-1.5 py-1 text-[10px] font-black text-slate-400 transition hover:text-white">
            Ver
        </a>

        @can('update', $valor)
            <a href="{{ route('attribute-options.edit', $valor) }}"
                class="rounded-lg px-1.5 py-1 text-[10px] font-black text-slate-400 transition hover:text-amber-300">
                ✎
            </a>

            <button type="button"
                @click="cambiarEstado({{ $valor->id }}, '{{ $valor->status === 'ACTIVE' ? 'ARCHIVED' : 'ACTIVE' }}')"
                class="ml-auto rounded-lg px-1.5 py-1 text-[10px] font-black transition {{ $valor->status === 'ACTIVE' ? 'text-slate-600 hover:text-amber-300' : 'text-emerald-400 hover:text-emerald-300' }}"
                title="{{ $valor->status === 'ACTIVE' ? 'Archivar: deja de poder elegirse' : 'Reactivar: vuelve a poder elegirse' }}">
                {{ $valor->status === 'ACTIVE' ? 'Archivar' : 'Reactivar' }}
            </button>
        @endcan

    </div>

</article>
