@php
    /*
     * Aplicar un molde a muchas entidades de una vez.
     *
     * La pantalla vieja era una carrera de obstáculos: una lista plana de hasta
     * doscientas fichas, un campo de archivos «en masa» que emparejaba por
     * nombre **sin decir qué había emparejado**, y una regla dura escondida
     * —cada versión necesita imagen— que rechazaba el envío entero al final,
     * cuando ya habías elegido cincuenta.
     *
     * Lo que cambia:
     *
     *   · se dice desde el principio que cada versión necesita una cara, y se
     *     dan las TRES maneras de dársela, con su dibujo
     *   · el emparejamiento por nombre se ve ANTES de enviar: qué archivo va a
     *     qué entidad, y cuáles se han quedado sin pareja
     *   · «copiar la imagen de la entidad» es una de las tres maneras, así que
     *     el caso normal ya no falla nunca
     *   · elegir a quién se le aplica se puede hacer por la cara, en lista o en
     *     tabla, y hay un botón que selecciona exactamente a las que cumplen
     *     las reglas de catálogo del molde
     *   · el botón de enviar dice por qué no se puede enviar, en vez de dejar
     *     que el servidor lo diga después
     */

    $entidadesParaAlpine = $entities
        ->map(
            fn($entidad) => [
                'id' => $entidad->id,
                'name' => $entidad->name,
                /* El mismo slug que usa el servidor para emparejar. */
                'slug' => Str::slug($entidad->name),
                'type' => $entidad->entityType?->name,
                'image_url' => $entidad->image_url,
                'tiene_imagen' => (bool) $entidad->image_url,
                'encaja' => $eligibleIds->contains($entidad->id),
            ],
        )
        ->values();

    $cuantasEncajan = $entities->filter(fn($e) => $eligibleIds->contains($e->id))->count();

    $sinImagenPropia = $entities->filter(fn($e) => !$e->image_url)->count();
@endphp

<x-app-layout :title="'Aplicar ' . $version->name" surface="dark">

    <x-slot name="header">Versiones</x-slot>

    <form method="POST" enctype="multipart/form-data"
        action="{{ route('versions.entities.bulk.store', $version) }}"
        x-data="aplicadorEnLote(@js($entidadesParaAlpine))" class="space-y-4">

        @csrf

        {{--
            Los ids van una sola vez, aquí. Los tres modos de vista conviven en
            el DOM —x-show oculta, no desmonta—, así que un name por modo
            enviaría cada entidad tres veces.
        --}}
        <template x-for="id in seleccionadas" :key="'sel' + id">
            <input type="hidden" name="entity_ids[]" :value="id">
        </template>


        {{-- ===================================================== --}}
        {{-- CABECERA --}}
        {{-- ===================================================== --}}

        <header class="flex flex-wrap items-center gap-3">

            <a href="{{ route('versions.show', $version) }}"
                class="h-12 w-12 shrink-0 overflow-hidden rounded-xl border border-slate-800 bg-slate-950">
                @if ($version->image_url)
                    <img src="{{ $version->image_url }}" alt="" class="h-full w-full object-cover">
                @else
                    <span class="flex h-full w-full items-center justify-center text-violet-400">◈</span>
                @endif
            </a>

            <div class="min-w-0 flex-1">
                <a href="{{ route('versions.show', $version) }}"
                    class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-600 transition hover:text-violet-400">
                    ← {{ $version->name }}
                </a>

                <h1 class="mt-0.5 truncate text-xl font-black tracking-tight text-white">
                    Aplicar «{{ $version->name }}» a varias entidades
                </h1>

                <p class="text-[10px] text-slate-500">
                    Se creará <strong class="text-slate-400">una versión por entidad</strong>.
                    Ya la llevan {{ $yaAplicada }};
                    quedan {{ $totalDisponibles }} sin ella.
                </p>
            </div>

            <a href="{{ route('versions.show', $version) }}"
                class="rounded-xl border border-slate-800 bg-slate-900/50 px-3 py-2 text-[11px] font-black text-slate-400 transition hover:border-slate-700 hover:text-white">
                Cancelar
            </a>
        </header>


        @include('versions.partials.workspace-navigation')


        @if ($errors->any())
            <div class="rounded-2xl border border-rose-500/30 bg-rose-500/10 px-4 py-3">
                <p class="text-[12px] font-black text-rose-200">No se ha podido aplicar:</p>
                <ul class="mt-1 space-y-0.5 text-[11px] leading-relaxed text-rose-200/80">
                    @foreach ($errors->all() as $error)
                        <li>· {{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif


        {{-- ===================================================== --}}
        {{-- CÓMO FUNCIONA --}}
        {{-- ===================================================== --}}

        <section x-data="{ abierto: false }"
            class="overflow-hidden rounded-2xl border border-violet-500/25 bg-violet-500/5">

            <button type="button" @click="abierto = !abierto"
                class="flex w-full items-center gap-3 px-4 py-2.5 text-left transition hover:bg-violet-500/5">

                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-violet-500/15 text-violet-300">
                    <x-omni-icon name="grafo" size="h-3.5 w-3.5" />
                </span>

                <span class="min-w-0 flex-1 text-[12px] font-black text-white">
                    Cada versión necesita una cara
                    <span class="font-bold text-slate-500">— las tres maneras de dársela</span>
                </span>

                <span class="shrink-0 text-slate-500 transition" :class="abierto ? 'rotate-90' : ''">
                    <x-omni-icon name="chevron-derecha" size="h-4 w-4" />
                </span>
            </button>

            <div x-show="abierto" x-cloak x-collapse class="grid gap-3 border-t border-violet-500/20 p-4 lg:grid-cols-3">

                @foreach ([['Subir en masa', 'text-fuchsia-400', 'Eliges muchos archivos de golpe y cada uno se empareja con la entidad que se llama igual: «Naruto Uzumaki.jpg» → Naruto Uzumaki. Aquí abajo verás qué ha emparejado antes de enviar.'], ['Copiar la de la entidad', 'text-indigo-400', 'La versión nace con la misma cara que la entidad. Es lo normal cuando todavía no tienes una imagen propia del nuevo estado; se cambia después.'], ['Una a una', 'text-violet-400', 'Dentro de cada entidad elegida hay un campo para subirle su archivo. Manda sobre las otras dos.']] as $i => [$titulo, $tono, $texto])
                    <div class="rounded-xl border border-slate-800 bg-slate-950 p-3">

                        <svg viewBox="0 0 120 44" class="h-11 w-full {{ $tono }}" fill="none"
                            stroke="currentColor" stroke-width="1.4" stroke-linecap="round" aria-hidden="true">

                            @if ($i === 0)
                                <rect x="4" y="4" width="20" height="14" rx="2" />
                                <rect x="4" y="24" width="20" height="14" rx="2" />
                                <path d="M28 11h16M44 11l-4-3M44 11l-4 3" opacity=".7" />
                                <path d="M28 31h16M44 31l-4-3M44 31l-4 3" opacity=".7" />
                                <rect x="50" y="4" width="26" height="14" rx="2" opacity=".85" />
                                <rect x="50" y="24" width="26" height="14" rx="2" opacity=".85" />
                                <text x="98" y="16" text-anchor="middle" fill="currentColor" stroke="none"
                                    font-size="8" font-weight="700">.jpg</text>
                                <text x="98" y="36" text-anchor="middle" fill="currentColor" stroke="none"
                                    font-size="8" font-weight="700">= nombre</text>
                            @elseif ($i === 1)
                                <rect x="6" y="10" width="30" height="24" rx="3" />
                                <circle cx="21" cy="22" r="7" opacity=".7" />
                                <path d="M42 22h16M58 22l-5-3M58 22l-5 3" opacity=".7" />
                                <rect x="64" y="10" width="30" height="24" rx="3" stroke-dasharray="3 2" />
                                <circle cx="79" cy="22" r="7" opacity=".45" />
                                <text x="108" y="26" text-anchor="middle" fill="currentColor" stroke="none"
                                    font-size="9" font-weight="700">≡</text>
                            @else
                                <rect x="6" y="8" width="34" height="28" rx="3" />
                                <circle cx="23" cy="22" r="8" opacity=".7" />
                                <path d="M46 22h14M60 22l-4-3M60 22l-4 3" opacity=".7" />
                                <rect x="66" y="8" width="34" height="28" rx="3" stroke-dasharray="3 2" />
                                <path d="M74 30l8-9 5 5 4-4" opacity=".7" />
                                <circle cx="106" cy="14" r="5" opacity=".8" />
                                <path d="M104 14h4M106 12v4" opacity=".9" />
                            @endif
                        </svg>

                        <p class="mt-2 text-[11px] font-black text-white">{{ $titulo }}</p>
                        <p class="mt-0.5 text-[10px] leading-4 text-slate-500">{{ $texto }}</p>
                    </div>
                @endforeach

            </div>
        </section>


        {{-- ===================================================== --}}
        {{-- PASO 1 · A QUIÉNES --}}
        {{-- ===================================================== --}}

        <section class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

            <div class="flex flex-wrap items-center gap-3 border-b border-slate-800 px-4 py-3">
                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-violet-500 font-mono text-[11px] font-black text-white">1</span>

                <div class="min-w-0 flex-1">
                    <h2 class="text-[13px] font-black text-white">¿A quiénes se le aplica?</h2>
                    <p class="text-[10px] text-slate-500">
                        Solo salen las que <strong class="text-slate-400">todavía no</strong> llevan este molde.
                    </p>
                </div>

                <span class="shrink-0 rounded-xl border border-violet-500/30 bg-violet-500/10 px-3 py-1.5 font-mono text-[11px] font-black text-violet-300"
                    x-text="seleccionadas.length + ' elegidas'"></span>
            </div>


            {{-- Filtros del servidor --}}
            <div class="flex flex-wrap items-center gap-2 border-b border-slate-800 bg-slate-950/40 px-4 py-3">

                <label class="relative min-w-[170px] flex-1">
                    <span class="sr-only">Filtrar aquí</span>
                    <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-600">
                        <x-omni-icon name="brujula" size="h-3.5 w-3.5" />
                    </span>
                    <input type="search" x-model="buscar" placeholder="Filtrar entre las que ya están cargadas…"
                        class="w-full rounded-xl border-slate-800 bg-slate-900 pl-9 text-xs text-slate-200 placeholder:text-slate-600 focus:border-violet-500 focus:ring-violet-500">
                </label>

                <span class="flex flex-wrap items-center gap-1">
                    <button type="button" @click="todas()"
                        class="rounded-lg border border-slate-800 px-2.5 py-2 text-[10px] font-black text-slate-400 transition hover:border-violet-500 hover:text-violet-300">
                        Todas las visibles
                    </button>

                    <button type="button" @click="ninguna()"
                        class="rounded-lg border border-slate-800 px-2.5 py-2 text-[10px] font-black text-slate-400 transition hover:border-slate-600 hover:text-white">
                        Ninguna
                    </button>

                    <button type="button" @click="invertir()"
                        class="rounded-lg border border-slate-800 px-2.5 py-2 text-[10px] font-black text-slate-400 transition hover:border-slate-600 hover:text-white">
                        Invertir
                    </button>

                    @if ($cuantasEncajan > 0)
                        <button type="button" @click="soloLasQueEncajan()"
                            title="Las que cumplen las reglas de catálogo de este molde"
                            class="rounded-lg border border-cyan-500/30 bg-cyan-500/10 px-2.5 py-2 text-[10px] font-black text-cyan-300 transition hover:bg-cyan-500 hover:text-slate-950">
                            Las {{ $cuantasEncajan }} que encajan
                        </button>
                    @endif
                </span>

                <span class="flex items-center gap-1 rounded-xl border border-slate-800 bg-slate-900 p-1">
                    @foreach ([['grid', 'cuadricula', 'Cuadrícula: elegir por la cara'], ['list', 'capas', 'Lista: una línea por entidad'], ['table', 'controles', 'Tabla: para repasar el estado de todas']] as [$modo, $icono, $ayuda])
                        <button type="button" @click="vista = '{{ $modo }}'" title="{{ $ayuda }}"
                            :class="vista === '{{ $modo }}' ? 'bg-violet-500 text-white' : 'text-slate-500 hover:text-slate-200'"
                            class="rounded-lg px-2 py-1.5 transition">
                            <x-omni-icon :name="$icono" size="h-4 w-4" />
                        </button>
                    @endforeach
                </span>

                <span x-show="vista === 'grid'" x-cloak
                    class="flex items-center gap-1 rounded-xl border border-slate-800 bg-slate-900 p-1">
                    <button type="button" @click="tamano = Math.max(3, tamano - 1)" :disabled="tamano === 3"
                        class="rounded-lg px-2 py-1.5 text-slate-500 transition hover:text-slate-200 disabled:opacity-30">
                        <x-omni-icon name="chevron-izquierda" size="h-3.5 w-3.5" />
                    </button>
                    <span class="w-3 text-center font-mono text-[10px] font-black text-slate-500" x-text="tamano"></span>
                    <button type="button" @click="tamano = Math.min(8, tamano + 1)" :disabled="tamano === 8"
                        class="rounded-lg px-2 py-1.5 text-slate-500 transition hover:text-slate-200 disabled:opacity-30">
                        <x-omni-icon name="chevron-derecha" size="h-3.5 w-3.5" />
                    </button>
                </span>
            </div>


            {{-- Buscar en el servidor: el filtro que sí trae más --}}
            <div class="flex flex-wrap items-center gap-2 border-b border-slate-800 px-4 py-2.5">
                <p class="min-w-0 flex-1 text-[10px] leading-relaxed text-slate-500">
                    Se cargan {{ $entities->count() }} de {{ $totalDisponibles }}.
                    @if ($totalDisponibles > $entities->count())
                        Para llegar a las demás, <strong class="text-slate-400">busca por nombre o filtra por
                        tipo</strong> aquí abajo: eso vuelve a preguntar al servidor.
                    @endif
                </p>

                <a href="{{ route('versions.entities.bulk.create', $version) }}"
                    class="text-[10px] font-black text-slate-600 underline transition hover:text-slate-300">
                    Quitar filtros
                </a>
            </div>

            <div class="flex flex-wrap items-center gap-2 border-b border-slate-800 bg-slate-950/40 px-4 py-2.5">
                <span class="text-[10px] font-black uppercase tracking-wider text-slate-600">Traer del servidor</span>

                <input type="search" form="filtro-lote" name="search" value="{{ $search }}"
                    placeholder="Buscar por nombre o código…"
                    class="min-w-[170px] flex-1 rounded-xl border-slate-800 bg-slate-900 text-xs text-slate-200 placeholder:text-slate-600 focus:border-violet-500 focus:ring-violet-500">

                <select form="filtro-lote" name="type"
                    class="rounded-xl border-slate-800 bg-slate-900 py-2 text-[11px] font-bold text-slate-300 focus:border-violet-500 focus:ring-violet-500">
                    <option value="">Cualquier tipo</option>
                    @foreach ($entityTypes as $tipo)
                        <option value="{{ $tipo->id }}" @selected($typeId === $tipo->id)>{{ $tipo->name }}</option>
                    @endforeach
                </select>

                <button type="submit" form="filtro-lote"
                    class="rounded-xl border border-slate-800 bg-slate-900 px-3 py-2 text-[11px] font-black text-slate-300 transition hover:border-violet-500 hover:text-violet-300">
                    Buscar
                </button>
            </div>


            {{-- ---------- LAS ENTIDADES ---------- --}}

            @if ($entities->isEmpty())

                <div class="p-10 text-center">
                    <span class="inline-flex text-slate-700"><x-omni-icon name="usuario" size="h-9 w-9" /></span>

                    <p class="mt-2 text-[13px] font-black text-white">
                        {{ $search || $typeId ? 'Ninguna encaja con ese filtro' : 'Todas la llevan ya' }}
                    </p>

                    <p class="mx-auto mt-1 max-w-md text-[11px] leading-relaxed text-slate-500">
                        {{ $search || $typeId
                            ? 'Prueba con otro nombre o sin filtrar por tipo.'
                            : 'Las ' . $yaAplicada . ' entidades de tu biblioteca ya tienen aplicado este molde.' }}
                    </p>
                </div>

            @else

                {{-- CUADRÍCULA --}}
                <div x-show="vista === 'grid'" class="grid gap-2 p-4" :class="columnas">
                    <template x-for="e in visibles" :key="e.id">
                        <label class="group relative cursor-pointer overflow-hidden rounded-xl border bg-slate-950 transition"
                            :class="seleccionadas.includes(e.id)
                                ? 'border-violet-500 ring-1 ring-violet-500'
                                : 'border-slate-800 hover:border-slate-600'">

                            <input type="checkbox" class="sr-only"
                                :checked="seleccionadas.includes(e.id)" @change="alternar(e.id)">

                            <span class="relative block aspect-square overflow-hidden bg-slate-900">
                                <template x-if="e.image_url">
                                    <img :src="e.image_url" alt="" loading="lazy"
                                        class="h-full w-full object-cover transition duration-300 group-hover:scale-105">
                                </template>
                                <template x-if="! e.image_url">
                                    <span class="flex h-full w-full items-center justify-center text-2xl text-slate-800">◍</span>
                                </template>

                                <template x-if="e.encaja">
                                    <span class="absolute left-1 top-1 rounded bg-cyan-500 px-1 text-[8px] font-black text-slate-950"
                                        title="Cumple las reglas de catálogo del molde">✓ ENCAJA</span>
                                </template>

                                {{-- El estado de su imagen, siempre visible cuando está elegida --}}
                                <template x-if="seleccionadas.includes(e.id)">
                                    <span class="absolute bottom-1 right-1 rounded px-1 text-[8px] font-black uppercase tracking-wider"
                                        :class="tonoEstadoImagen(e)"
                                        x-text="etiquetaEstadoImagen(e)"></span>
                                </template>

                                <span x-show="seleccionadas.includes(e.id)" x-cloak
                                    class="absolute inset-0 flex items-center justify-center bg-violet-500/25 text-xl font-black text-white">✓</span>
                            </span>

                            <span class="block px-1.5 py-1">
                                <span class="block truncate text-[10px] font-black text-white" x-text="e.name"></span>
                                <span class="block truncate text-[9px] text-slate-600" x-text="e.type ?? 'Sin tipo'"></span>
                            </span>
                        </label>
                    </template>
                </div>

                {{-- LISTA --}}
                <div x-show="vista === 'list'" x-cloak class="divide-y divide-slate-800/70">
                    <template x-for="e in visibles" :key="e.id">
                        <label class="flex cursor-pointer items-center gap-3 px-4 py-2 transition hover:bg-slate-950/50"
                            :class="seleccionadas.includes(e.id) ? 'bg-violet-500/5' : ''">

                            <input type="checkbox"
                                :checked="seleccionadas.includes(e.id)" @change="alternar(e.id)"
                                class="rounded border-slate-700 bg-slate-900 text-violet-500">

                            <span class="h-10 w-10 shrink-0 overflow-hidden rounded-lg border border-slate-800 bg-slate-950">
                                <template x-if="e.image_url">
                                    <img :src="e.image_url" alt="" loading="lazy" class="h-full w-full object-cover">
                                </template>
                                <template x-if="! e.image_url">
                                    <span class="flex h-full w-full items-center justify-center text-slate-700">◍</span>
                                </template>
                            </span>

                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-[12px] font-black text-white" x-text="e.name"></span>
                                <span class="block truncate text-[10px] text-slate-500" x-text="e.type ?? 'Sin tipo'"></span>
                            </span>

                            <template x-if="e.encaja">
                                <span class="shrink-0 rounded bg-cyan-500/20 px-1.5 py-0.5 text-[9px] font-black text-cyan-300">ENCAJA</span>
                            </template>

                            <template x-if="seleccionadas.includes(e.id)">
                                <span class="shrink-0 rounded px-1.5 py-0.5 text-[9px] font-black uppercase tracking-wider"
                                    :class="tonoEstadoImagen(e)" x-text="etiquetaEstadoImagen(e)"></span>
                            </template>
                        </label>
                    </template>
                </div>

                {{-- TABLA --}}
                <div x-show="vista === 'table'" x-cloak class="overflow-x-auto">
                    <table class="w-full min-w-[640px]">
                        <thead class="border-b border-slate-800 text-left">
                            <tr class="text-[9px] font-black uppercase tracking-wider text-slate-600">
                                <th class="px-4 py-2.5">Entidad</th>
                                <th class="px-3 py-2.5">Tipo</th>
                                <th class="px-3 py-2.5 text-center">Encaja</th>
                                <th class="px-3 py-2.5">Imagen</th>
                                <th class="px-3 py-2.5">Se llamará</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-800/70">
                            <template x-for="e in visibles" :key="e.id">
                                <tr class="transition hover:bg-slate-950/50"
                                    :class="seleccionadas.includes(e.id) ? 'bg-violet-500/5' : ''">

                                    <td class="px-4 py-2">
                                        <label class="flex cursor-pointer items-center gap-2">
                                            <input type="checkbox"
                                                :checked="seleccionadas.includes(e.id)" @change="alternar(e.id)"
                                                class="rounded border-slate-700 bg-slate-900 text-violet-500">

                                            <span class="h-8 w-8 shrink-0 overflow-hidden rounded-lg border border-slate-800 bg-slate-950">
                                                <template x-if="e.image_url">
                                                    <img :src="e.image_url" alt="" loading="lazy" class="h-full w-full object-cover">
                                                </template>
                                            </span>

                                            <span class="truncate text-[12px] font-black text-white" x-text="e.name"></span>
                                        </label>
                                    </td>

                                    <td class="px-3 py-2 text-[11px] text-slate-500" x-text="e.type ?? '—'"></td>

                                    <td class="px-3 py-2 text-center">
                                        <span x-text="e.encaja ? '✓' : '·'"
                                            :class="e.encaja ? 'text-cyan-400' : 'text-slate-800'"></span>
                                    </td>

                                    <td class="px-3 py-2">
                                        <span x-show="seleccionadas.includes(e.id)"
                                            class="rounded px-1.5 py-0.5 text-[9px] font-black uppercase tracking-wider"
                                            :class="tonoEstadoImagen(e)" x-text="etiquetaEstadoImagen(e)"></span>
                                        <span x-show="! seleccionadas.includes(e.id)" class="text-[10px] text-slate-700">—</span>
                                    </td>

                                    <td class="px-3 py-2 text-[11px] text-slate-400"
                                        x-text="seleccionadas.includes(e.id) ? nombreFinal(e) : '—'"></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <p x-show="visibles.length === 0" x-cloak class="p-8 text-center text-[11px] text-slate-600">
                    Ninguna de las cargadas encaja con «<span x-text="buscar"></span>».
                </p>

            @endif

        </section>


        {{-- ===================================================== --}}
        {{-- PASO 2 · LAS CARAS --}}
        {{-- ===================================================== --}}

        <section x-show="seleccionadas.length > 0" x-cloak x-collapse
            class="overflow-hidden rounded-2xl border border-fuchsia-500/25 bg-slate-900/50">

            <div class="flex flex-wrap items-center gap-3 border-b border-slate-800 px-4 py-3">
                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-fuchsia-500 font-mono text-[11px] font-black text-white">2</span>

                <div class="min-w-0 flex-1">
                    <h2 class="text-[13px] font-black text-white">La cara de cada una</h2>
                    <p class="text-[10px] text-slate-500">
                        Ninguna versión puede crearse sin imagen. Aquí se ve, entidad por entidad, de dónde
                        va a salir la suya.
                    </p>
                </div>

                <span class="shrink-0 rounded-xl border px-3 py-1.5 font-mono text-[11px] font-black"
                    :class="sinImagen.length === 0
                        ? 'border-emerald-500/30 bg-emerald-500/10 text-emerald-300'
                        : 'border-rose-500/30 bg-rose-500/10 text-rose-300'"
                    x-text="sinImagen.length === 0
                        ? 'Todas resueltas'
                        : sinImagen.length + ' sin imagen'"></span>
            </div>


            {{-- Copiar la de la entidad --}}
            <div class="flex flex-wrap items-center gap-3 border-b border-slate-800 bg-slate-950/40 px-4 py-3">

                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-indigo-500/15 text-indigo-300">
                    <x-omni-icon name="usuario" size="h-4 w-4" />
                </span>

                <div class="min-w-0 flex-1">
                    <p class="text-[12px] font-black text-white">Copiar la imagen de cada entidad</p>
                    <p class="text-[10px] leading-relaxed text-slate-500">
                        La manera rápida: cada versión nace con la cara de su entidad y la cambias después.
                        @if ($sinImagenPropia > 0)
                            <strong class="text-amber-300">{{ $sinImagenPropia }}</strong>
                            de las cargadas no tienen imagen propia, así que a esas habrá que subírsela.
                        @endif
                    </p>
                </div>

                <button type="button" @click="copiarTodas()"
                    class="shrink-0 rounded-xl bg-indigo-500/15 px-3 py-2 text-[11px] font-black text-indigo-300 transition hover:bg-indigo-500 hover:text-white">
                    Usarla en las <span x-text="seleccionadas.length"></span> elegidas
                </button>

                <button type="button" @click="copiarNinguna()" x-show="copiarDeEntidad.length > 0" x-cloak
                    class="shrink-0 rounded-xl border border-slate-800 px-2.5 py-2 text-[10px] font-black text-slate-400 transition hover:border-rose-500 hover:text-rose-300">
                    Quitar
                </button>

                <template x-for="id in copiarDeEntidad" :key="id">
                    <input type="hidden" name="use_entity_image[]" :value="id">
                </template>
            </div>


            {{-- Subir en masa, con el emparejamiento a la vista --}}
            <div class="border-b border-slate-800 p-4">

                <div class="flex flex-wrap items-center gap-3">
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-fuchsia-500/15 text-fuchsia-300">
                        <x-omni-icon name="galeria" size="h-4 w-4" />
                    </span>

                    <div class="min-w-0 flex-1">
                        <p class="text-[12px] font-black text-white">Subir muchas de golpe</p>
                        <p class="text-[10px] leading-relaxed text-slate-500">
                            Cada archivo va a la entidad que se llame igual:
                            <strong class="text-slate-300">Naruto Uzumaki.jpg</strong> → Naruto Uzumaki.
                            Da igual la extensión y las mayúsculas.
                        </p>
                    </div>

                    <label class="shrink-0 cursor-pointer rounded-xl bg-fuchsia-500/15 px-3 py-2 text-[11px] font-black text-fuchsia-300 transition hover:bg-fuchsia-500 hover:text-white">
                        <input type="file" name="bulk_images[]" multiple accept=".jpg,.jpeg,.png,.webp"
                            @change="leerMasivas($event)" class="sr-only">
                        Elegir archivos
                    </label>
                </div>

                {{-- El emparejamiento, antes de enviar --}}
                <div x-show="archivosMasivos.length > 0" x-cloak x-collapse class="mt-3">

                    <div class="flex flex-wrap items-center gap-2 rounded-xl border border-slate-800 bg-slate-950 px-3 py-2">
                        <span class="font-mono text-[11px] font-black text-slate-300"
                            x-text="archivosMasivos.length + ' archivos'"></span>

                        <span class="rounded-lg border border-emerald-500/30 bg-emerald-500/10 px-2 py-0.5 font-mono text-[10px] font-black text-emerald-300"
                            x-text="Object.keys(emparejados).length + ' emparejados'"></span>

                        <span x-show="huerfanos.length > 0"
                            class="rounded-lg border border-amber-500/30 bg-amber-500/10 px-2 py-0.5 font-mono text-[10px] font-black text-amber-300"
                            x-text="huerfanos.length + ' sin pareja'"></span>
                    </div>

                    <div x-show="huerfanos.length > 0" x-cloak
                        class="mt-2 rounded-xl border border-amber-500/25 bg-amber-500/5 px-3 py-2">
                        <p class="text-[10px] font-black text-amber-200">
                            Estos archivos no se van a usar: ninguna entidad elegida se llama así.
                        </p>
                        <p class="mt-1 text-[10px] leading-relaxed text-amber-200/70" x-text="huerfanos.join(' · ')"></p>
                    </div>

                    <p class="mt-2 text-[10px] text-slate-600">
                        El emparejamiento se calcula igual que en el servidor, con el nombre del archivo sin
                        extensión. Una imagen individual manda sobre esta.
                    </p>
                </div>
            </div>


            {{-- Las elegidas, una a una --}}
            <div class="p-4">

                <div class="mb-2.5 flex flex-wrap items-center gap-2">
                    <p class="min-w-0 flex-1 text-[11px] font-black text-slate-400">
                        Las <span x-text="seleccionadas.length"></span> elegidas
                    </p>

                    <label class="flex cursor-pointer items-center gap-2 rounded-xl border border-slate-800 bg-slate-950 px-3 py-1.5">
                        <input type="checkbox" x-model="soloSinResolver"
                            class="rounded border-slate-700 bg-slate-900 text-rose-500">
                        <span class="text-[10px] font-black text-slate-300">Solo las que faltan</span>
                    </label>
                </div>

                <div class="space-y-1.5">
                    <template x-for="e in elegidas" :key="'d' + e.id">
                        <article x-show="! soloSinResolver || estadoImagen(e) === 'falta'"
                            class="overflow-hidden rounded-xl border bg-slate-950"
                            :class="estadoImagen(e) === 'falta' ? 'border-rose-500/40' : 'border-slate-800'">

                            <div class="flex flex-wrap items-center gap-2.5 p-2.5">

                                <span class="h-10 w-10 shrink-0 overflow-hidden rounded-lg border border-slate-800 bg-slate-900">
                                    <template x-if="e.image_url">
                                        <img :src="e.image_url" alt="" loading="lazy" class="h-full w-full object-cover">
                                    </template>
                                    <template x-if="! e.image_url">
                                        <span class="flex h-full w-full items-center justify-center text-slate-700">◍</span>
                                    </template>
                                </span>

                                <span class="min-w-0 flex-1">
                                    <span class="block truncate text-[12px] font-black text-white" x-text="e.name"></span>
                                    <span class="block truncate text-[10px] text-slate-500" x-text="explicacionImagen(e)"></span>
                                </span>

                                <span class="shrink-0 rounded px-1.5 py-0.5 text-[9px] font-black uppercase tracking-wider"
                                    :class="tonoEstadoImagen(e)" x-text="etiquetaEstadoImagen(e)"></span>

                                <label class="shrink-0 cursor-pointer rounded-lg border border-slate-800 px-2.5 py-1.5 text-[10px] font-black text-slate-400 transition hover:border-violet-500 hover:text-violet-300">
                                    <input type="file" :name="'images[' + e.id + ']'" accept=".jpg,.jpeg,.png,.webp"
                                        @change="archivoPropio(e.id, $event)" class="sr-only">
                                    <span x-text="archivosPropios[e.id] ? 'Cambiar' : 'Subir la suya'"></span>
                                </label>

                                <button type="button" @click="alternarDetalle(e.id)"
                                    class="shrink-0 rounded-lg px-2 py-1.5 text-[10px] font-black text-slate-500 transition hover:text-white">
                                    <span x-text="detalles.includes(e.id) ? '−' : '✎'"></span>
                                </button>

                                <button type="button" @click="alternar(e.id)" title="Quitarla de la selección"
                                    class="shrink-0 rounded-lg px-2 py-1.5 text-[10px] font-black text-slate-600 transition hover:text-rose-300">✕</button>
                            </div>

                            {{-- Nombre y descripción --}}
                            <div x-show="detalles.includes(e.id)" x-cloak x-collapse
                                class="grid gap-2 border-t border-slate-800 p-2.5 sm:grid-cols-2">

                                <label class="block">
                                    <span class="mb-1 block text-[9px] font-black uppercase tracking-wider text-slate-600">
                                        Cómo se llamará
                                    </span>
                                    <input type="text" :name="'names[' + e.id + ']'" x-model="nombres[e.id]"
                                        :placeholder="e.name + ' — {{ $version->name }}'" maxlength="150"
                                        class="w-full rounded-lg border-slate-800 bg-slate-900 text-[11px] text-slate-200 placeholder:text-slate-600 focus:border-violet-500 focus:ring-violet-500">
                                </label>

                                <label class="block">
                                    <span class="mb-1 block text-[9px] font-black uppercase tracking-wider text-slate-600">
                                        Qué cambia en ella
                                    </span>
                                    <input type="text" :name="'descriptions[' + e.id + ']'"
                                        placeholder="Opcional" maxlength="500"
                                        class="w-full rounded-lg border-slate-800 bg-slate-900 text-[11px] text-slate-200 placeholder:text-slate-600 focus:border-violet-500 focus:ring-violet-500">
                                </label>
                            </div>
                        </article>
                    </template>
                </div>

                <p x-show="soloSinResolver && sinImagen.length === 0" x-cloak
                    class="rounded-xl border border-emerald-500/25 bg-emerald-500/5 px-3 py-3 text-center text-[11px] font-black text-emerald-300">
                    Todas las elegidas tienen resuelta su imagen.
                </p>

            </div>

        </section>


        {{-- ===================================================== --}}
        {{-- ENVIAR --}}
        {{-- ===================================================== --}}

        <div class="sticky bottom-4 z-20 flex flex-wrap items-center gap-3 rounded-2xl border border-slate-800 bg-slate-950/95 px-4 py-3 backdrop-blur">

            <p class="min-w-0 flex-1 text-[11px] leading-relaxed text-slate-400">
                <span x-show="seleccionadas.length === 0">
                    Elige al menos una entidad ahí arriba.
                </span>

                <span x-show="seleccionadas.length > 0 && sinImagen.length > 0" x-cloak>
                    <strong class="text-rose-300" x-text="sinImagen.length"></strong>
                    de las <span x-text="seleccionadas.length"></span> elegidas
                    <strong class="text-rose-300">siguen sin imagen</strong>. Súbeles un archivo, o pulsa
                    «copiar la imagen de cada entidad».
                </span>

                <span x-show="seleccionadas.length > 0 && sinImagen.length === 0" x-cloak>
                    Se crearán <strong class="font-mono text-base text-violet-300" x-text="seleccionadas.length"></strong>
                    versiones de «{{ $version->name }}», todas con su cara resuelta.
                </span>
            </p>

            <a href="{{ route('versions.show', $version) }}"
                class="rounded-xl border border-slate-800 px-4 py-2 text-[11px] font-black text-slate-400 transition hover:text-white">
                Cancelar
            </a>

            <button type="submit" :disabled="seleccionadas.length === 0 || sinImagen.length > 0"
                class="rounded-xl bg-violet-500 px-5 py-2.5 text-[11px] font-black text-white transition hover:bg-violet-400 disabled:cursor-not-allowed disabled:opacity-40">
                Aplicar a las <span x-text="seleccionadas.length"></span>
            </button>
        </div>

    </form>


    {{-- El formulario de filtros vive fuera para no anidarse en el de envío --}}
    <form method="GET" id="filtro-lote" action="{{ route('versions.entities.bulk.create', $version) }}"></form>


    <script>
        /*
         * El aplicador en lote.
         *
         * Lo único con enjundia aquí es el emparejamiento por nombre: se
         * calcula EN EL NAVEGADOR con la misma regla que el servidor —el slug
         * del nombre del archivo sin extensión contra el slug del nombre de la
         * entidad— para poder enseñarlo antes de enviar. Los slugs de las
         * entidades vienen ya calculados desde PHP con Str::slug, así que lo
         * único que se aproxima aquí es el del nombre del archivo.
         *
         * Y se respeta el mismo orden de prioridad del servidor:
         *
         *   1. la imagen individual de esa entidad
         *   2. el archivo emparejado por nombre
         *   3. la imagen de la propia entidad, si se ha pedido copiarla
         */
        function aplicadorEnLote(entidades) {

            return {

                entidades: entidades ?? [],

                seleccionadas: [],
                copiarDeEntidad: [],

                archivosPropios: {},
                archivosMasivos: [],
                nombres: {},

                detalles: [],

                buscar: '',
                soloSinResolver: false,

                vista: 'grid',
                tamano: 6,


                init() {
                    try {
                        const g = JSON.parse(localStorage.getItem('omnimerge.bulkVersion.view') ?? '{}');
                        if (['grid', 'list', 'table'].includes(g.vista)) this.vista = g.vista;
                        if (g.tamano >= 3 && g.tamano <= 8) this.tamano = g.tamano;
                    } catch (e) {}

                    this.$watch('vista', () => this.recordar());
                    this.$watch('tamano', () => this.recordar());
                },

                recordar() {
                    try {
                        localStorage.setItem('omnimerge.bulkVersion.view',
                            JSON.stringify({ vista: this.vista, tamano: this.tamano }));
                    } catch (e) {}
                },


                get columnas() {
                    return {
                        3: 'grid-cols-2 sm:grid-cols-3',
                        4: 'grid-cols-2 sm:grid-cols-4',
                        5: 'grid-cols-3 sm:grid-cols-5',
                        6: 'grid-cols-3 sm:grid-cols-4 lg:grid-cols-6',
                        7: 'grid-cols-4 sm:grid-cols-5 lg:grid-cols-7',
                        8: 'grid-cols-4 sm:grid-cols-6 lg:grid-cols-8',
                    }[this.tamano];
                },


                get visibles() {
                    const q = this.buscar.trim().toLowerCase();

                    if (! q) return this.entidades;

                    return this.entidades.filter((e) => e.name.toLowerCase().includes(q));
                },


                get elegidas() {
                    return this.entidades.filter((e) => this.seleccionadas.includes(e.id));
                },


                /*
                 * El emparejamiento, con la misma prioridad que el servidor: un
                 * archivo solo se ofrece a las entidades elegidas que no tengan
                 * ya una imagen individual, y cada archivo se gasta una vez.
                 */
                get emparejados() {
                    const resultado = {};
                    const disponibles = {};

                    this.elegidas.forEach((e) => {
                        if (this.archivosPropios[e.id]) return;

                        if (! disponibles[e.slug]) disponibles[e.slug] = [];
                        disponibles[e.slug].push(e.id);
                    });

                    this.archivosMasivos.forEach((nombre) => {
                        const base = this.slug(nombre.replace(/\.[^.]+$/, ''));

                        if (disponibles[base] && disponibles[base].length > 0) {
                            resultado[disponibles[base].shift()] = nombre;
                        }
                    });

                    return resultado;
                },


                get huerfanos() {
                    const usados = Object.values(this.emparejados);

                    return this.archivosMasivos.filter((n) => ! usados.includes(n));
                },


                get sinImagen() {
                    return this.elegidas.filter((e) => this.estadoImagen(e) === 'falta');
                },


                slug(texto) {
                    return String(texto)
                        .normalize('NFD')
                        .replace(/[\u0300-\u036f]/g, '')
                        .toLowerCase()
                        .replace(/[^a-z0-9]+/g, '-')
                        .replace(/^-+|-+$/g, '');
                },


                estadoImagen(e) {
                    if (this.archivosPropios[e.id]) return 'propia';
                    if (this.emparejados[e.id]) return 'masa';
                    if (this.copiarDeEntidad.includes(e.id) && e.tiene_imagen) return 'entidad';

                    return 'falta';
                },


                etiquetaEstadoImagen(e) {
                    return {
                        propia: '✓ subida',
                        masa: '✓ archivo',
                        entidad: '✦ entidad',
                        falta: '✕ falta',
                    }[this.estadoImagen(e)];
                },


                tonoEstadoImagen(e) {
                    return {
                        propia: 'bg-violet-500 text-white',
                        masa: 'bg-fuchsia-500 text-white',
                        entidad: 'bg-indigo-500 text-white',
                        falta: 'bg-rose-500 text-white',
                    }[this.estadoImagen(e)];
                },


                explicacionImagen(e) {
                    const estado = this.estadoImagen(e);

                    if (estado === 'propia') return 'Usará el archivo que le subiste: ' + this.archivosPropios[e.id];
                    if (estado === 'masa') return 'Emparejada por nombre con ' + this.emparejados[e.id];
                    if (estado === 'entidad') return 'Copiará la imagen de la entidad';

                    return e.tiene_imagen
                        ? 'Sin imagen todavía: súbele una, o copia la de la entidad'
                        : 'Sin imagen, y la entidad tampoco tiene: hay que subírsela';
                },


                nombreFinal(e) {
                    const puesto = (this.nombres[e.id] ?? '').trim();

                    return puesto !== '' ? puesto : e.name + ' — ' + @js($version->name);
                },


                alternar(id) {
                    const i = this.seleccionadas.indexOf(id);

                    if (i === -1) {
                        this.seleccionadas.push(id);
                    } else {
                        this.seleccionadas.splice(i, 1);
                        this.detalles = this.detalles.filter((x) => x !== id);
                        this.copiarDeEntidad = this.copiarDeEntidad.filter((x) => x !== id);
                    }
                },


                alternarDetalle(id) {
                    const i = this.detalles.indexOf(id);

                    if (i === -1) this.detalles.push(id); else this.detalles.splice(i, 1);
                },


                todas() {
                    const ids = this.visibles.map((e) => e.id);

                    this.seleccionadas = [...new Set([...this.seleccionadas, ...ids])];
                },


                ninguna() {
                    this.seleccionadas = [];
                    this.detalles = [];
                    this.copiarDeEntidad = [];
                },


                invertir() {
                    const ids = this.visibles.map((e) => e.id);

                    this.seleccionadas = ids.filter((id) => ! this.seleccionadas.includes(id));
                    this.detalles = [];
                },


                soloLasQueEncajan() {
                    this.seleccionadas = this.entidades.filter((e) => e.encaja).map((e) => e.id);
                },


                copiarTodas() {
                    this.copiarDeEntidad = [...this.seleccionadas];
                },


                copiarNinguna() {
                    this.copiarDeEntidad = [];
                },


                archivoPropio(id, evento) {
                    const archivo = evento.target.files?.[0];

                    if (archivo) {
                        this.archivosPropios[id] = archivo.name;
                    } else {
                        delete this.archivosPropios[id];
                    }
                },


                leerMasivas(evento) {
                    this.archivosMasivos = [...(evento.target.files ?? [])].map((f) => f.name);
                },
            };
        }
    </script>

</x-app-layout>
