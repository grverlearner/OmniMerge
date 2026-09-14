@php
    /*
     * Un valor de catálogo, en una línea.
     *
     * Vive en su propia pieza porque la lista se pinta dos veces —seguida y
     * separada por catálogo— y duplicar sesenta líneas de marcado para eso es
     * la forma segura de que las dos dejen de parecerse.
     */

    $tonoValor = $valor->color ?: ($valor->attribute?->color ?: '#6366f1');
@endphp

<div class="flex items-center gap-3 border-b border-slate-800/70 px-4 py-2 transition hover:bg-slate-950/50">

    <label class="flex h-5 w-5 shrink-0 cursor-pointer items-center justify-center rounded border border-slate-700 transition hover:border-violet-500"
        :class="seleccionadas.includes({{ $valor->id }}) ? 'border-violet-500 bg-violet-500' : ''">
        <input type="checkbox" value="{{ $valor->id }}" x-model.number="seleccionadas" class="sr-only">
        <span class="text-[10px] font-black text-white" x-show="seleccionadas.includes({{ $valor->id }})">✓</span>
    </label>

    <a href="{{ route('attribute-options.show', $valor) }}"
        class="h-10 w-10 shrink-0 overflow-hidden rounded-lg border bg-slate-950"
        style="border-color: {{ $valor->image_url ? '#1e293b' : '#f43f5e55' }}">
        @if ($valor->image_url)
            <img src="{{ $valor->image_url }}" alt="" loading="lazy" class="h-full w-full object-cover">
        @else
            <span class="flex h-full w-full items-center justify-center text-rose-500/50">
                {{ $valor->icon ?: '◇' }}
            </span>
        @endif
    </a>

    <div class="min-w-0 flex-1">
        <div class="flex flex-wrap items-center gap-1.5">
            <a href="{{ route('attribute-options.show', $valor) }}"
                class="truncate text-[12px] font-black text-white">{{ $valor->name }}</a>

            @if ($valor->status !== 'ACTIVE')
                <span class="rounded px-1.5 py-0.5 text-[8px] font-black uppercase tracking-wider {{ $tonoEstado[$valor->status] ?? 'bg-slate-800 text-slate-500' }}">
                    {{ $estadoEtiqueta[$valor->status] ?? $valor->status }}
                </span>
            @endif

            @if (! $valor->image_url)
                <span class="rounded bg-rose-500/15 px-1.5 py-0.5 text-[8px] font-black uppercase tracking-wider text-rose-300">
                    sin imagen
                </span>
            @endif
        </div>

        <p class="truncate text-[10px] text-slate-500">
            <span style="color: {{ $tonoValor }}">{{ $valor->attribute?->name ?? 'Sin catálogo' }}</span>
            @if ($valor->parent)
                <span class="text-slate-700">·</span> dentro de {{ $valor->parent->name }}
            @elseif ($valor->children_count > 0)
                <span class="text-slate-700">·</span> {{ $valor->children_count }} cuelgan de él
            @endif
        </p>
    </div>

    <span class="shrink-0 rounded-lg border px-2 py-1 font-mono text-[10px] font-black"
        style="border-color: {{ $tonoValor }}40; color: {{ $valor->values_count > 0 ? $tonoValor : '#475569' }}"
        title="Entidades que llevan este valor">
        {{ $valor->values_count }}
    </span>

    @can('update', $valor)
        <a href="{{ route('attribute-options.edit', $valor) }}"
            class="shrink-0 rounded-lg px-2 py-1 text-[10px] font-black text-slate-500 transition hover:text-amber-300">✎</a>

        <button type="button"
            @click="cambiarEstado({{ $valor->id }}, '{{ $valor->status === 'ACTIVE' ? 'ARCHIVED' : 'ACTIVE' }}')"
            class="shrink-0 rounded-lg px-2 py-1 text-[10px] font-black transition {{ $valor->status === 'ACTIVE' ? 'text-slate-600 hover:text-amber-300' : 'text-emerald-400 hover:text-emerald-300' }}"
            title="{{ $valor->status === 'ACTIVE' ? 'Archivar: deja de poder elegirse' : 'Reactivar: vuelve a poder elegirse' }}">
            {{ $valor->status === 'ACTIVE' ? 'Archivar' : 'Reactivar' }}
        </button>
    @endcan
</div>
