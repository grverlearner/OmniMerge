@php
    /*
     * Un atributo, en formato ficha.
     *
     * Lo que hace falta saber de un atributo sin abrirlo: de qué tipo es, si lo
     * usa alguien, y qué interruptores tiene puestos —porque son los que
     * deciden si aparece en los filtros, en las comparaciones y en las
     * búsquedas de toda la aplicación—.
     *
     * Su color es un dato suyo, así que viaja en `style`: una clase de Tailwind
     * compuesta a partir de la base de datos no existiría en el CSS.
     */

    $acento = $atributo->color ?: ($tonoTipo[$atributo->data_type][2] ?? '#8b5cf6');

    $sinUsar = $atributo->entity_attributes_count === 0;

    $catalogoVacio = $atributo->data_type === 'OPTION' && $atributo->options_count === 0;

    $interruptores = [
        ['Varios', $atributo->allows_multiple, 'Admite varios valores a la vez'],
        ['Filtrable', $atributo->is_filterable, 'Aparece en los filtros de las listas'],
        ['Comparable', $atributo->is_comparable, 'Se puede usar para comparar entidades'],
        ['Buscable', $atributo->is_searchable, 'Su valor entra en las búsquedas'],
    ];
@endphp

<article
    class="group flex flex-col overflow-hidden rounded-2xl border bg-slate-900/50 transition duration-300 hover:-translate-y-0.5"
    style="border-color: {{ $catalogoVacio ? '#f43f5e55' : $acento . '40' }}">

    {{-- ============ SU CARA ============ --}}

    <a href="{{ route('attributes.show', $atributo) }}"
        class="relative block aspect-[16/9] overflow-hidden bg-slate-950">

        @if ($atributo->image_url)
            <img src="{{ $atributo->image_url }}" alt="{{ $atributo->name }}" loading="lazy"
                class="h-full w-full object-cover transition duration-500 group-hover:scale-105">

            <span class="absolute inset-x-0 bottom-0 h-2/5 bg-gradient-to-t from-slate-950 to-transparent"></span>
        @else
            <span class="flex h-full w-full items-center justify-center text-3xl"
                style="color: {{ $acento }}66; background: radial-gradient(120% 90% at 50% 0%, {{ $acento }}22, transparent 70%)">
                {{ $atributo->icon ?: $atributo->data_type_icon }}
            </span>
        @endif

        <span class="absolute left-2 top-2 rounded-lg border px-1.5 py-0.5 text-[9px] font-black uppercase tracking-wider {{ $tonoTipo[$atributo->data_type][1] ?? 'border-slate-700 bg-slate-950/85 text-slate-300' }}">
            {{ $atributo->data_type_label }}
        </span>

        <span class="absolute right-2 top-2 flex flex-col items-end gap-1">
            @if ($atributo->is_featured)
                <span class="rounded bg-amber-400 px-1.5 py-0.5 text-[9px] font-black text-amber-950" title="Destacado">★</span>
            @endif

            @if ($atributo->status !== 'ACTIVE')
                <span class="rounded px-1.5 py-0.5 text-[9px] font-black uppercase tracking-wider {{ $tonoEstado[$atributo->status] ?? 'bg-slate-800 text-slate-500' }}">
                    {{ $estadoEtiqueta[$atributo->status] ?? $atributo->status }}
                </span>
            @endif
        </span>
    </a>


    {{-- ============ QUÉ ES ============ --}}

    <div class="flex-1 p-3">

        <a href="{{ route('attributes.show', $atributo) }}"
            class="block truncate text-[13px] font-black text-white">
            {{ $atributo->name }}
        </a>

        <p class="font-mono text-[9px] text-slate-600">{{ $atributo->code }}</p>

        <p class="mt-1.5 line-clamp-2 text-[10px] leading-relaxed text-slate-500">
            {{ $atributo->description ?: 'Sin descripción.' }}
        </p>


        {{-- Cuánto se usa, y con qué --}}
        <div class="mt-2.5 grid grid-cols-2 gap-1.5">

            <a href="{{ route('attributes.show', $atributo) }}"
                class="rounded-xl border border-slate-800 bg-slate-950 px-2 py-1.5 transition hover:border-slate-700">
                <span class="block font-mono text-sm font-black"
                    style="color: {{ $atributo->entity_attributes_count > 0 ? $acento : '#475569' }}">
                    {{ $atributo->entity_attributes_count }}
                </span>
                <span class="block text-[9px] font-black uppercase tracking-wider text-slate-600">
                    {{ $atributo->entity_attributes_count === 1 ? 'entidad' : 'entidades' }}
                </span>
            </a>

            @if ($atributo->data_type === 'OPTION')
                <a href="{{ route('attributes.show', $atributo) }}"
                    class="rounded-xl border px-2 py-1.5 transition {{ $catalogoVacio ? 'border-rose-500/40 bg-rose-500/5' : 'border-slate-800 bg-slate-950 hover:border-slate-700' }}">
                    <span class="block font-mono text-sm font-black {{ $catalogoVacio ? 'text-rose-300' : '' }}"
                        style="{{ $catalogoVacio ? '' : 'color: ' . $acento }}">
                        {{ $atributo->options_count }}
                    </span>
                    <span class="block text-[9px] font-black uppercase tracking-wider text-slate-600">valores</span>
                </a>
            @else
                <span class="rounded-xl border border-slate-800 bg-slate-950 px-2 py-1.5">
                    <span class="block truncate font-mono text-sm font-black text-slate-500">
                        {{ $atributo->unit ?: '—' }}
                    </span>
                    <span class="block text-[9px] font-black uppercase tracking-wider text-slate-600">unidad</span>
                </span>
            @endif
        </div>


        {{-- Los valores, si es catálogo --}}
        @if ($atributo->data_type === 'OPTION' && $atributo->options->isNotEmpty())
            <div class="mt-2 flex -space-x-2">
                @foreach ($atributo->options->take(6) as $valor)
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
                        style="color: {{ $acento }}">
                        +{{ $atributo->options_count - 6 }}
                    </span>
                @endif
            </div>
        @endif


        {{-- Los interruptores que sí cambian algo --}}
        <div class="mt-2 flex flex-wrap gap-1">
            @foreach ($interruptores as [$etiqueta, $puesto, $ayuda])
                <span title="{{ $ayuda }}"
                    class="rounded-lg border px-1.5 py-0.5 text-[9px] font-bold {{ $puesto ? 'border-slate-700 bg-slate-800/60 text-slate-300' : 'border-slate-800/60 text-slate-700' }}">
                    {{ $etiqueta }}
                </span>
            @endforeach
        </div>


        @if ($sinUsar)
            <p class="mt-2 rounded-lg border border-dashed border-slate-800 px-2 py-1 text-center text-[9px] text-slate-600">
                Ninguna entidad lo usa todavía.
            </p>
        @endif

    </div>


    {{-- ============ QUÉ SE PUEDE HACER ============ --}}

    <div class="flex items-center gap-1 border-t border-slate-800 px-2 py-1.5">

        <a href="{{ route('attributes.show', $atributo) }}"
            class="rounded-lg px-2 py-1 text-[10px] font-black text-slate-400 transition hover:text-white">
            Ver
        </a>

        @can('update', $atributo)
            <a href="{{ route('attributes.edit', $atributo) }}"
                class="rounded-lg px-2 py-1 text-[10px] font-black text-slate-400 transition hover:text-amber-300">
                ✎ Editar
            </a>
        @endcan

        <a href="{{ route('attributes.index', ['data_type' => $atributo->data_type]) }}"
            class="ml-auto rounded-lg px-2 py-1 text-[10px] font-black text-slate-600 transition hover:text-slate-300"
            title="Ver todos los del mismo tipo">
            {{ $atributo->data_type_label }} →
        </a>

    </div>

</article>
