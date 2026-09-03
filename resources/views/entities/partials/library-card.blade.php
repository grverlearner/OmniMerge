@php
    /*
     * Una entidad en la biblioteca, en formato ficha.
     *
     * La misma ficha sirve para «cuadrícula» y para «a fondo»: en cuadrícula
     * enseña la cara y lo esencial, y a fondo abre además sus características
     * con su valor, sus colecciones y sus versiones. Son dos profundidades de
     * la misma cosa, no dos componentes.
     *
     * La imagen usa `base_display_image_url`, que es la de la Base activa si
     * la hay y la del original si no: es la cara que la entidad tiene HOY, no
     * la que se le puso el primer día.
     *
     * Los colores del tipo y de las colecciones son datos del usuario —hex,
     * elegidos por él— y por eso van en `style` y no en clases de Tailwind:
     * no son tokens del diseño, son contenido.
     */

    $tipo = $entidad->entityType;

    $colorTipo = $tipo?->color ?: '#6366f1';

    $baseActiva = $entidad->baseVersionSetting?->entityVersion;

    /* Las características que tienen algún valor puesto van primero */
    $caracteristicas = $entidad->entityAttributes
        ->sortByDesc(fn($ea) => $ea->values->count())
        ->values();
@endphp

<article
    class="group flex flex-col overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50 transition hover:border-slate-700 hover:bg-slate-900">

    {{-- ============ LA CARA ============ --}}

    <a href="{{ route('entities.show', $entidad) }}"
        class="relative block aspect-[4/5] overflow-hidden bg-slate-950">

        @if ($entidad->base_display_image_url)
            <img src="{{ $entidad->base_display_image_url }}" alt="{{ $entidad->name }}" loading="lazy"
                class="h-full w-full object-cover transition duration-300 group-hover:scale-105">

            <span class="absolute inset-x-0 bottom-0 h-2/5 bg-gradient-to-t from-slate-950 to-transparent"></span>
        @else
            <span class="flex h-full w-full items-center justify-center text-4xl font-black opacity-25"
                style="color: {{ $colorTipo }}">
                {{ $tipo?->icon ?: mb_strtoupper(mb_substr($entidad->name, 0, 1)) }}
            </span>
        @endif

        {{-- De qué tipo es, con su color --}}
        @if ($tipo)
            <span class="absolute left-2 top-2 flex items-center gap-1.5 rounded-lg border bg-slate-950/85 px-2 py-1"
                style="border-color: {{ $colorTipo }}66">
                <span class="text-[11px]">{{ $tipo->icon }}</span>
                <span class="text-[9px] font-black uppercase tracking-wider" style="color: {{ $colorTipo }}">
                    {{ $tipo->name }}
                </span>
            </span>
        @else
            <span class="absolute left-2 top-2 rounded-lg bg-amber-500/85 px-2 py-1 text-[8px] font-black uppercase tracking-wider text-slate-950">
                sin tipo
            </span>
        @endif

        {{-- Estado y visibilidad --}}
        <span class="absolute right-2 top-2 flex flex-col items-end gap-1">
            @if ($entidad->status !== 'ACTIVE')
                <span class="rounded px-1.5 py-0.5 text-[8px] font-black uppercase tracking-wider {{ $estadoTono[$entidad->status] ?? 'bg-slate-800 text-slate-500' }}">
                    {{ $entidad->status_label }}
                </span>
            @endif

            @if ($entidad->visibility === 'PUBLIC')
                <span class="rounded bg-sky-500/80 px-1.5 py-0.5 text-[8px] font-black uppercase tracking-wider text-slate-950">
                    pública
                </span>
            @endif
        </span>

        {{--
            Si la cara que se ve viene de una Base activa y no del original,
            se dice: es la diferencia entre «así es» y «así es ahora mismo».
        --}}
        @if ($baseActiva && $baseActiva->image_url)
            <span class="absolute bottom-2 left-2 rounded bg-violet-500/85 px-1.5 py-0.5 text-[8px] font-black uppercase tracking-wider text-white">
                ★ {{ $baseActiva->name }}
            </span>
        @endif

    </a>


    {{-- ============ QUIÉN ES ============ --}}

    <div class="flex-1 p-2.5">

        <a href="{{ route('entities.show', $entidad) }}"
            class="block truncate text-[12px] font-black text-white transition hover:text-indigo-300">
            {{ $entidad->name }}
        </a>

        <p class="font-mono text-[9px] text-slate-600">{{ $entidad->code }}</p>

        <p class="mt-1 line-clamp-2 text-[10px] leading-relaxed text-slate-500">
            {{ $entidad->description ?: 'Sin descripción.' }}
        </p>


        {{-- ============ LO QUE LLEVA ENCIMA ============ --}}

        <div class="mt-2 grid grid-cols-3 gap-1">

            @foreach ([['Rasgos', $entidad->entity_attributes_count, 'text-violet-300'], ['Colecc.', $entidad->collections_count, 'text-cyan-300'], ['Versión', $entidad->entity_versions_count, 'text-amber-300']] as [$etiqueta, $valor, $color])
                <span class="rounded-lg border border-slate-800 bg-slate-950 px-1.5 py-1 text-center">
                    <span class="block font-mono text-[12px] font-black {{ $valor > 0 ? $color : 'text-slate-700' }}">
                        {{ $valor }}
                    </span>
                    <span class="block text-[7px] font-black uppercase tracking-wider text-slate-600">
                        {{ $etiqueta }}
                    </span>
                </span>
            @endforeach

        </div>


        {{-- ============ A FONDO ============ --}}

        {{--
            x-show y no x-if: cambiar de modo veinte veces no debería
            reconstruir el DOM veinte veces.
        --}}

        <div x-show="view === 'detail'" x-cloak
            class="mt-2 space-y-2 rounded-xl border border-slate-800 bg-slate-950/60 p-2">

            {{-- Sus características, con su valor --}}
            <div>
                <p class="text-[8px] font-black uppercase tracking-wider text-violet-400">Características</p>

                @if ($caracteristicas->isEmpty())
                    <p class="mt-0.5 text-[10px] text-slate-600">Ninguna todavía.</p>
                @else
                    <ul class="mt-1 space-y-0.5">
                        @foreach ($caracteristicas as $caracteristica)
                            @php $valores = $caracteristica->values->map->displayValue()->filter(); @endphp

                            <li class="flex items-baseline justify-between gap-2 text-[10px]">
                                <span class="flex min-w-0 items-center gap-1 text-slate-500">
                                    @if ($caracteristica->attribute?->icon)
                                        <span>{{ $caracteristica->attribute->icon }}</span>
                                    @endif
                                    <span class="truncate">{{ $caracteristica->attribute?->name ?? 'Característica' }}</span>
                                </span>

                                <span class="shrink-0 truncate font-bold {{ $valores->isEmpty() ? 'text-slate-700' : 'text-slate-200' }}">
                                    {{ $valores->isEmpty() ? 'sin valor' : $valores->implode(', ') }}
                                </span>
                            </li>
                        @endforeach
                    </ul>

                    @if ($entidad->entity_attributes_count > $caracteristicas->count())
                        <p class="mt-0.5 text-[9px] text-slate-600">
                            +{{ $entidad->entity_attributes_count - $caracteristicas->count() }} más
                        </p>
                    @endif
                @endif
            </div>

            {{-- Dónde vive --}}
            <div>
                <p class="text-[8px] font-black uppercase tracking-wider text-cyan-400">Colecciones</p>

                @if ($entidad->collections->isEmpty())
                    <p class="mt-0.5 text-[10px] text-slate-600">En ninguna.</p>
                @else
                    <div class="mt-1 flex flex-wrap gap-1">
                        @foreach ($entidad->collections as $coleccion)
                            <a href="{{ route('entities.index', ['collection' => $coleccion->id]) }}"
                                class="flex items-center gap-1 rounded border bg-slate-900 px-1.5 py-0.5 text-[9px] font-bold text-slate-300 transition hover:bg-slate-800"
                                style="border-color: {{ $coleccion->color ?: '#334155' }}66">

                                @if ($coleccion->image)
                                    <img src="{{ asset('storage/' . $coleccion->image) }}" alt="" loading="lazy"
                                        class="h-3 w-3 rounded-sm object-cover">
                                @else
                                    <span class="h-2 w-2 rounded-full"
                                        style="background-color: {{ $coleccion->color ?: '#64748b' }}"></span>
                                @endif

                                {{ $coleccion->name }}
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Qué cara enseña --}}
            <div>
                <p class="text-[8px] font-black uppercase tracking-wider text-amber-400">Base activa</p>

                @if ($baseActiva)
                    <p class="mt-0.5 flex items-center gap-1.5 text-[10px] text-slate-300">
                        <span class="text-amber-400">★</span>
                        <span class="truncate font-bold">{{ $baseActiva->name }}</span>
                    </p>
                @else
                    <p class="mt-0.5 text-[10px] text-slate-600">
                        Ninguna: se enseña la original.
                    </p>
                @endif
            </div>

        </div>

    </div>


    {{-- ============ QUÉ SE PUEDE HACER ============ --}}

    <div class="flex items-center gap-0.5 border-t border-slate-800 px-1.5 py-1">

        <a href="{{ route('entities.show', $entidad) }}"
            class="rounded-lg px-1.5 py-1 text-[10px] font-black text-slate-400 transition hover:text-white">
            Ver
        </a>

        @can('update', $entidad)
            <a href="{{ route('entities.edit', $entidad) }}"
                class="rounded-lg px-1.5 py-1 text-[10px] font-black text-slate-400 transition hover:text-indigo-300">
                ✎
            </a>

            <a href="{{ route('entities.attributes.edit', $entidad) }}" title="Características"
                class="rounded-lg px-1.5 py-1 text-[10px] font-black text-slate-400 transition hover:text-violet-300">
                ☷
            </a>

            <a href="{{ route('entity-versions.index', $entidad) }}" title="Versiones"
                class="ml-auto rounded-lg px-1.5 py-1 text-[10px] font-black text-slate-400 transition hover:text-amber-300">
                ★
            </a>
        @endcan

    </div>

</article>
