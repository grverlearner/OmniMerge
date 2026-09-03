@php
    /*
     * La cabecera y los filtros de la gestión masiva.
     *
     * Los filtros los resuelve el SERVIDOR: lo que llega ya está filtrado, y
     * es sobre eso sobre lo que se marca. Filtrar en el cliente daría la
     * impresión de estar eligiendo entre todas cuando solo se estaría
     * eligiendo entre las de esta página.
     *
     * El filtro por característica y valor —el más potente que hay— se envía
     * como `attribute_filters[]` con su índice, igual que antes.
     */

    $estados = [
        '' => 'Cualquier estado',
        'ACTIVE' => 'Activa',
        'INACTIVE' => 'Inactiva',
        'ARCHIVED' => 'Archivada',
    ];

    $visibilidades = [
        '' => 'Cualquier visibilidad',
        'PRIVATE' => 'Privada',
        'PUBLIC' => 'Pública',
        'UNLISTED' => 'No listada',
    ];

    $conImagen = [
        '' => 'Con o sin imagen',
        'yes' => 'Solo con imagen',
        'no' => 'Solo sin imagen',
    ];

    $conAtributos = [
        '' => 'Con o sin características',
        'yes' => 'Solo con características',
        'no' => 'Solo sin características',
    ];

    $ordenes = [
        'newest' => 'Más recientes',
        'oldest' => 'Más antiguas',
        'name_asc' => 'Nombre A → Z',
        'name_desc' => 'Nombre Z → A',
    ];

    $filtrando = $search !== '' || $status || $visibility || $type || $image || $attributesState || $collectionId;
@endphp


{{-- ===================================================== --}}
{{-- CABECERA --}}
{{-- ===================================================== --}}

<header
    class="relative overflow-hidden rounded-2xl border border-slate-800 bg-gradient-to-br from-slate-900 via-slate-900 to-cyan-950/40">

    <span class="pointer-events-none absolute -right-24 -top-28 h-64 w-64 rounded-full bg-cyan-500/10 blur-3xl"></span>

    <div class="relative flex flex-wrap items-end gap-4 px-5 py-5">

        <div class="min-w-0 flex-1">
            <a href="{{ route('entities.index') }}"
                class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-600 transition hover:text-cyan-400">
                ← Entidades
            </a>

            <h1 class="mt-1.5 text-2xl font-black tracking-tight text-white">
                Gestión masiva
            </h1>

            <p class="mt-1 max-w-2xl text-[12px] leading-relaxed text-slate-400">
                Cambiar muchas entidades a la vez. Primero se filtra, después se marca sobre
                quién actuar, y cada acción enseña a quién va a afectar antes de aplicarse.
            </p>
        </div>

        <div class="flex flex-wrap gap-2">
            @foreach ([['En la biblioteca', $stats['total'] ?? count($entityPayload), 'text-white'], ['Tras los filtros', $matchedCount, 'text-cyan-300']] as [$etiqueta, $valor, $color])
                <span class="flex items-baseline gap-2 rounded-xl border border-slate-800 bg-slate-950/60 px-3 py-2">
                    <span class="font-mono text-lg font-black {{ $color }}">{{ $valor }}</span>
                    <span class="text-[9px] font-black uppercase tracking-wider text-slate-600">{{ $etiqueta }}</span>
                </span>
            @endforeach

            <span class="flex items-baseline gap-2 rounded-xl border border-cyan-500/30 bg-cyan-500/10 px-3 py-2">
                <span class="font-mono text-lg font-black text-cyan-300" x-text="selectedCount"></span>
                <span class="text-[9px] font-black uppercase tracking-wider text-slate-500">Marcadas</span>
            </span>
        </div>

    </div>

</header>


{{-- ===================================================== --}}
{{-- LO QUE PASÓ LA ÚLTIMA VEZ --}}
{{-- ===================================================== --}}

@if (session('success'))
    <div class="rounded-2xl border border-emerald-500/40 bg-emerald-500/10 px-4 py-3 text-xs font-black text-emerald-300"
        role="status">
        ✓ {{ session('success') }}
    </div>
@endif

@if (session('warning'))
    <div class="rounded-2xl border border-amber-500/40 bg-amber-500/10 px-4 py-3 text-xs font-black text-amber-300"
        role="status">
        {{ session('warning') }}
    </div>
@endif

@if ($errors->any())
    <div class="rounded-2xl border border-rose-500/40 bg-rose-500/10 p-4" role="alert">
        <p class="text-xs font-black uppercase tracking-wider text-rose-300">No se pudo aplicar</p>

        <ul class="mt-2 list-disc space-y-1 pl-5 text-[11px] font-bold text-rose-200/80">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif


{{-- ===================================================== --}}
{{-- FILTROS --}}
{{-- ===================================================== --}}

<div class="rounded-2xl border border-slate-800 bg-slate-950/95">

    <form method="GET" action="{{ route('entities.bulk-edit.index') }}" class="space-y-2 px-4 py-3">

        <div class="flex flex-wrap items-center gap-2">

            <label class="relative min-w-[200px] flex-1">
                <span class="sr-only">Buscar entidad</span>

                <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-600">
                    <x-omni-icon name="brujula" size="h-3.5 w-3.5" />
                </span>

                <input type="search" name="search" value="{{ $search }}"
                    placeholder="Buscar por nombre, código o descripción..."
                    class="w-full rounded-xl border-slate-800 bg-slate-900 pl-9 text-xs text-slate-200 placeholder:text-slate-600 focus:border-cyan-500 focus:ring-cyan-500">
            </label>

            <select name="type" onchange="this.form.submit()"
                class="rounded-xl border-slate-800 bg-slate-900 py-2 text-[11px] font-bold text-slate-300 focus:border-cyan-500 focus:ring-cyan-500">
                <option value="">Cualquier tipo</option>
                <option value="none" @selected($type === 'none')>Sin tipo</option>

                @foreach ($entityTypes as $tipoEntidad)
                    <option value="{{ $tipoEntidad->id }}" @selected((string) $type === (string) $tipoEntidad->id)>
                        {{ $tipoEntidad->icon }} {{ $tipoEntidad->name }}
                    </option>
                @endforeach
            </select>

            <select name="collection" onchange="this.form.submit()"
                class="rounded-xl border-slate-800 bg-slate-900 py-2 text-[11px] font-bold text-slate-300 focus:border-cyan-500 focus:ring-cyan-500">
                <option value="">Cualquier colección</option>

                @foreach ($collections as $coleccion)
                    <option value="{{ $coleccion->id }}" @selected((int) $collectionId === $coleccion->id)>
                        {{ $coleccion->name }}
                    </option>
                @endforeach
            </select>

            @foreach ([['status', $estados, $status], ['visibility', $visibilidades, $visibility], ['sort', $ordenes, $sort]] as [$campo, $opciones, $actual])
                <select name="{{ $campo }}" onchange="this.form.submit()"
                    class="rounded-xl border-slate-800 bg-slate-900 py-2 text-[11px] font-bold text-slate-300 focus:border-cyan-500 focus:ring-cyan-500">
                    @foreach ($opciones as $valor => $etiqueta)
                        <option value="{{ $valor }}" @selected((string) $actual === (string) $valor)>{{ $etiqueta }}</option>
                    @endforeach
                </select>
            @endforeach

            <button type="submit"
                class="rounded-xl border border-slate-800 bg-slate-900 px-3 py-2 text-[11px] font-black text-slate-300 transition hover:border-cyan-500 hover:text-cyan-300">
                Filtrar
            </button>
        </div>

        <div class="flex flex-wrap items-center gap-2 border-t border-slate-800/70 pt-2">

            @foreach ([['image', $conImagen, $image], ['attributes_state', $conAtributos, $attributesState]] as [$campo, $opciones, $actual])
                <select name="{{ $campo }}" onchange="this.form.submit()"
                    class="rounded-xl border-slate-800 bg-slate-900 py-1.5 text-[10px] font-bold text-slate-400 focus:border-cyan-500 focus:ring-cyan-500">
                    @foreach ($opciones as $valor => $etiqueta)
                        <option value="{{ $valor }}" @selected((string) $actual === (string) $valor)>{{ $etiqueta }}</option>
                    @endforeach
                </select>
            @endforeach

            @if ($filtrando)
                <a href="{{ route('entities.bulk-edit.index') }}"
                    class="rounded-xl border border-rose-500/30 px-3 py-1.5 text-[10px] font-black text-rose-300 transition hover:bg-rose-500/10">
                    Quitar filtros
                </a>
            @endif

            <span class="ml-auto font-mono text-[10px] text-slate-600">
                {{ $matchedCount }} {{ $matchedCount === 1 ? 'entidad' : 'entidades' }}
                @if ($filtrando)
                    <span class="text-cyan-400">tras los filtros</span>
                @endif
            </span>

        </div>

    </form>

</div>
