@php
    /*
     * El formulario de un valor de catálogo, compartido entre crear y editar.
     *
     * Lo que hacía antes: cinco secciones numeradas en blanco, un desplegable
     * para el padre y una vista previa lateral. Correcto y mudo sobre las tres
     * cosas que de verdad se preguntan al rellenarlo:
     *
     *   · ¿ya existe algo así?      → nada lo decía hasta guardar
     *   · ¿de qué cuelga?           → un desplegable de nombres, sin caras
     *   · ¿cómo se verá al elegirlo? → la vista previa era otra cosa distinta
     *
     * Ahora se contestan las tres: aviso de nombre repetido mientras se escribe,
     * el padre se elige por su cara con un dibujo de dónde quedará el valor, y
     * la vista previa es literalmente la ficha que verá quien lo elija.
     */

    $editando = isset($attributeOption) && $attributeOption->exists;

    $catalogo = $editando ? $attributeOption->attribute : $selectedAttribute;

    $acento = $catalogo?->color ?: '#6366f1';

    $nombreActual = old('name', $attributeOption->name ?? '');
    $iconoActual = old('icon', $attributeOption->icon ?? '');
    $colorActual = old('color', $attributeOption->color ?? $acento);
    $descripcionActual = old('description', $attributeOption->description ?? '');
    $numeroActual = old('numeric_value', $attributeOption->numeric_value ?? '');
    $estadoActual = old('status', $attributeOption->status ?? 'ACTIVE');

    /* `selectedParentId` solo existe al crear: al editar no lo manda nadie. */
    $padreActual = old(
        'parent_option_id',
        $attributeOption->parent_option_id ?? ($selectedParentId ?? null),
    );

    /* Los que ya están dentro, en el formato que consume el motor. */
    $existentes = ($existingOptions ?? collect())
        ->map(
            fn($valor) => [
                'id' => (string) $valor->id,
                'name' => $valor->name,
                'normalizado' => mb_strtolower(trim($valor->name)),
                'image' => $valor->image_url,
                'icon' => $valor->icon ?: '',
                'color' => $valor->color ?: $acento,
                'status' => $valor->status,
                'parent' => $valor->parent_option_id ? (string) $valor->parent_option_id : '',
            ],
        )
        ->values();

    $candidatosPadre = $parentOptions
        ->map(
            fn($valor) => [
                'id' => (string) $valor->id,
                'name' => $valor->name,
                'image' => $valor->image_url,
                'icon' => $valor->icon ?: '',
                'color' => $valor->color ?: $acento,
            ],
        )
        ->values();

    $tonoEstado = [
        'ACTIVE' => 'bg-emerald-500/15 text-emerald-300',
        'INACTIVE' => 'bg-amber-500/15 text-amber-300',
        'ARCHIVED' => 'bg-slate-800 text-slate-500',
    ];
@endphp

<div x-data="editorDeValor({
    nombre: @js($nombreActual),
    icono: @js($iconoActual),
    color: @js($colorActual),
    padre: @js($padreActual ? (string) $padreActual : ''),
    imagenActual: @js($editando ? $attributeOption->image_url : null),
    existentes: @js($existentes),
    padres: @js($candidatosPadre),
    catalogo: @js($catalogo?->name ?? ''),
})" class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_320px]">


    {{-- ===================================================== --}}
    {{-- LO QUE SE RELLENA --}}
    {{-- ===================================================== --}}

    <div class="space-y-4">

        {{-- ---------- 1 · A QUÉ CATÁLOGO VA ---------- --}}

        <section class="overflow-hidden rounded-2xl border bg-slate-900/50"
            style="border-color: {{ $acento }}40">

            <div class="flex items-center gap-3 border-b border-slate-800 px-4 py-2.5">
                <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-lg font-mono text-[11px] font-black"
                    style="background-color: {{ $acento }}22; color: {{ $acento }}">1</span>
                <h3 class="min-w-0 flex-1 text-[12px] font-black text-white">A qué catálogo pertenece</h3>
                <span class="shrink-0 rounded-lg bg-slate-800 px-2 py-0.5 text-[9px] font-black uppercase tracking-wider text-slate-400">
                    No se puede cambiar después
                </span>
            </div>

            <div class="flex flex-wrap items-center gap-3 p-4">

                @include('attributes.partials.cara', [
                    'cosa' => $catalogo,
                    'tamano' => 'h-12 w-12',
                    'respaldo' => '◫',
                    'tono' => $acento,
                ])

                <div class="min-w-0 flex-1">
                    <p class="truncate text-[14px] font-black text-white">{{ $catalogo?->name ?? 'Sin catálogo' }}</p>
                    <p class="font-mono text-[10px] text-slate-600">{{ $catalogo?->code }}</p>
                </div>

                <div class="shrink-0 text-right">
                    <p class="font-mono text-lg font-black" style="color: {{ $acento }}">
                        {{ $existentes->count() + ($editando ? 1 : 0) }}
                    </p>
                    <p class="text-[9px] font-black uppercase tracking-wider text-slate-600">
                        {{ $existentes->count() === 0 && ! $editando ? 'está vacío' : 'ya dentro' }}
                    </p>
                </div>

                @if (! $editando)
                    <a href="{{ route('attribute-options.create') }}"
                        class="shrink-0 rounded-xl border border-slate-800 px-2.5 py-1.5 text-[10px] font-black text-slate-400 transition hover:border-violet-500 hover:text-violet-300">
                        Cambiar
                    </a>
                @endif
            </div>

            {{-- De qué está hecho ya, para no repetirse --}}
            @if ($existentes->isNotEmpty())
                <div class="border-t border-slate-800 px-4 py-2.5">
                    <p class="mb-1.5 text-[9px] font-black uppercase tracking-wider text-slate-600">
                        Lo que ya hay dentro
                    </p>

                    <div class="flex flex-wrap gap-1">
                        @foreach ($existingOptions->take(24) as $existente)
                            <span class="flex items-center gap-1 rounded-lg border border-slate-800 bg-slate-950 py-0.5 pl-0.5 pr-1.5"
                                title="{{ $existente->name }}">
                                <span class="h-4 w-4 shrink-0 overflow-hidden rounded-sm">
                                    @if ($existente->image_url)
                                        <img src="{{ $existente->image_url }}" alt="" loading="lazy"
                                            class="h-full w-full object-cover">
                                    @else
                                        <span class="flex h-full w-full items-center justify-center text-[8px] text-slate-700">◇</span>
                                    @endif
                                </span>
                                <span class="max-w-[110px] truncate text-[9px] text-slate-400">{{ $existente->name }}</span>
                            </span>
                        @endforeach

                        @if ($existingOptions->count() > 24)
                            <span class="rounded-lg border border-slate-800 px-1.5 py-0.5 font-mono text-[9px] font-black text-slate-500">
                                +{{ $existingOptions->count() - 24 }}
                            </span>
                        @endif
                    </div>
                </div>
            @endif
        </section>


        {{-- ---------- 2 · CÓMO SE LLAMA Y QUÉ CARA TIENE ---------- --}}

        <section class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

            <div class="flex items-center gap-3 border-b border-slate-800 px-4 py-2.5">
                <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-lg bg-violet-500/15 font-mono text-[11px] font-black text-violet-300">2</span>
                <h3 class="min-w-0 flex-1 text-[12px] font-black text-white">Cómo se llama y qué cara tiene</h3>
            </div>

            <div class="space-y-4 p-4">

                <label class="block">
                    <span class="mb-1 block text-[10px] font-black uppercase tracking-wider text-slate-600">
                        Nombre
                    </span>

                    <input type="text" name="name" x-model="nombre" required maxlength="150"
                        placeholder="«Uzumaki», «Konoha», «Fuego»…"
                        class="w-full rounded-xl border-slate-800 bg-slate-950 text-sm font-bold text-white placeholder:font-normal placeholder:text-slate-600 focus:border-violet-500 focus:ring-violet-500">

                    @error('name')
                        <span class="mt-1 block text-[11px] font-bold text-rose-300">{{ $message }}</span>
                    @enderror

                    {{--
                        El aviso que faltaba. Un catálogo con «Uzumaki» y
                        «uzumaki» no da error en ningún sitio: simplemente queda
                        mal para siempre. La condición se escribe entera en la
                        expresión para que Alpine la vuelva a evaluar al teclear.
                    --}}

                    <span x-show="nombre.trim() !== '' && repetido"
                        x-cloak
                        class="mt-1.5 flex items-center gap-2 rounded-xl border border-amber-500/30 bg-amber-500/10 px-2.5 py-1.5">
                        <span class="text-[11px] text-amber-300">⚠</span>
                        <span class="text-[10px] leading-4 text-amber-200/90">
                            Ya hay un valor llamado
                            <strong class="text-amber-200" x-text="repetido?.name"></strong>
                            en este catálogo. Puedes guardarlo igual, pero al elegirlo nadie sabrá cuál
                            es cuál.
                        </span>
                    </span>

                    <span x-show="nombre.trim() !== '' && ! repetido && parecidos.length > 0"
                        x-cloak
                        class="mt-1.5 block text-[10px] leading-4 text-slate-500">
                        Parecidos que ya existen:
                        <template x-for="(p, i) in parecidos" :key="p.id">
                            <span>
                                <span class="text-slate-300" x-text="p.name"></span><span
                                    x-show="i < parecidos.length - 1">, </span>
                            </span>
                        </template>
                    </span>
                </label>


                <div class="grid gap-4 sm:grid-cols-[minmax(0,180px)_minmax(0,1fr)]">

                    <div>
                        <span class="mb-1 block text-[10px] font-black uppercase tracking-wider text-slate-600">
                            Su imagen
                        </span>

                        <div class="overflow-hidden rounded-xl border border-slate-800 bg-slate-950">
                            <div class="relative aspect-square overflow-hidden bg-slate-900">
                                <template x-if="imagen">
                                    <img :src="imagen" alt="" class="h-full w-full object-cover">
                                </template>

                                <template x-if="! imagen">
                                    <span class="flex h-full w-full flex-col items-center justify-center gap-1">
                                        <span class="text-3xl" :style="`color: ${color}66`"
                                            x-text="icono || '◇'"></span>
                                        <span class="text-[9px] font-black uppercase tracking-wider text-rose-400">
                                            sin imagen
                                        </span>
                                    </span>
                                </template>
                            </div>
                        </div>

                        <div class="mt-2 flex items-center gap-1.5">
                            <label class="flex-1 cursor-pointer rounded-xl border border-dashed border-slate-700 px-2 py-1.5 text-center transition hover:border-violet-500">
                                <input type="file" name="image" accept=".jpg,.jpeg,.png,.webp"
                                    x-ref="campoImagen" @change="verImagen($event)" class="sr-only">
                                <span class="text-[10px] font-black text-slate-300">Elegir</span>
                            </label>

                            <button type="button" x-show="imagen" x-cloak @click="quitarImagen()"
                                class="rounded-xl border border-slate-800 px-2 py-1.5 text-[10px] font-black text-slate-500 transition hover:border-rose-500 hover:text-rose-300">
                                Quitar
                            </button>
                        </div>

                        @if ($editando)
                            <input type="hidden" name="remove_image" :value="quitarLaExistente ? 1 : 0">
                        @endif

                        @error('image')
                            <span class="mt-1 block text-[11px] font-bold text-rose-300">{{ $message }}</span>
                        @enderror

                        <p class="mt-1.5 text-[9px] leading-3 text-slate-600">
                            JPG, PNG o WEBP, hasta 4 MB. Cuadrada se ve mejor.
                        </p>
                    </div>


                    <div class="space-y-3">

                        <div class="rounded-xl border border-rose-500/25 bg-rose-500/5 px-3 py-2"
                            x-show="! imagen" x-cloak>
                            <p class="text-[10px] leading-4 text-rose-200/80">
                                <strong class="text-rose-200">Sin imagen no se le reconoce.</strong>
                                Todas las pantallas donde se elige un valor —el constructor de reglas, las
                                dependencias entre catálogos, el formulario de una entidad— lo enseñan por
                                su cara. Se puede guardar así, pero aparecerá como un cuadro vacío.
                            </p>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <label class="block">
                                <span class="mb-1 block text-[10px] font-black uppercase tracking-wider text-slate-600">
                                    Símbolo
                                </span>
                                <input type="text" name="icon" x-model="icono" maxlength="100" placeholder="◆"
                                    class="w-full rounded-xl border-slate-800 bg-slate-950 text-center text-sm text-slate-200 placeholder:text-slate-600 focus:border-violet-500 focus:ring-violet-500">
                                <span class="mt-1 block text-[9px] leading-3 text-slate-600">
                                    El respaldo si no hay imagen.
                                </span>
                            </label>

                            <label class="block">
                                <span class="mb-1 block text-[10px] font-black uppercase tracking-wider text-slate-600">
                                    Color
                                </span>
                                <div class="flex items-center gap-1.5">
                                    <input type="color" name="color" x-model="color"
                                        class="h-[38px] w-12 shrink-0 cursor-pointer rounded-xl border border-slate-800 bg-slate-950 p-1">
                                    <input type="text" :value="color" readonly
                                        class="w-full min-w-0 rounded-xl border-slate-800 bg-slate-950 font-mono text-[11px] text-slate-400 focus:border-violet-500 focus:ring-violet-500">
                                </div>
                                <span class="mt-1 block text-[9px] leading-3 text-slate-600">
                                    Su acento en listas y bordes.
                                </span>
                            </label>
                        </div>

                        <label class="block">
                            <span class="mb-1 block text-[10px] font-black uppercase tracking-wider text-slate-600">
                                Qué representa
                            </span>
                            <textarea name="description" rows="3" maxlength="3000"
                                placeholder="Opcional. Una línea explicando qué es, para acordarte dentro de un año."
                                class="w-full rounded-xl border-slate-800 bg-slate-950 text-xs text-slate-200 placeholder:text-slate-600 focus:border-violet-500 focus:ring-violet-500">{{ $descripcionActual }}</textarea>
                            @error('description')
                                <span class="mt-1 block text-[11px] font-bold text-rose-300">{{ $message }}</span>
                            @enderror
                        </label>
                    </div>
                </div>
            </div>
        </section>


        {{-- ---------- 3 · DE QUÉ CUELGA ---------- --}}

        <section class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

            <div class="flex items-center gap-3 border-b border-slate-800 px-4 py-2.5">
                <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-lg bg-cyan-500/15 font-mono text-[11px] font-black text-cyan-300">3</span>
                <h3 class="min-w-0 flex-1 text-[12px] font-black text-white">De qué cuelga</h3>
                <span class="shrink-0 text-[10px] font-bold text-slate-600">Opcional</span>
            </div>

            <div class="p-4">

                {{--
                    Va fuera del bloque de candidatos a propósito: si el catálogo
                    no tiene ninguno, el campo tiene que viajar igual para no
                    dejar sin decir de qué cuelga un valor que ya colgaba.
                --}}
                <input type="hidden" name="parent_option_id" :value="padre">

                <div class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_260px]">

                    <div>
                        <p class="text-[11px] leading-relaxed text-slate-500">
                            Solo si este valor está <strong class="text-slate-300">dentro</strong> de otro del
                            mismo catálogo: Konoha dentro del País del Fuego. Si no, déjalo suelto —la mayoría
                            lo están—.
                        </p>

                        @if ($candidatosPadre->isEmpty())
                            <p class="mt-3 rounded-xl border border-dashed border-slate-800 py-6 text-center text-[10px] text-slate-600">
                                No hay ningún otro valor activo del que pueda colgar.
                            </p>
                        @else
                            <div class="mt-3">
                                <input type="search" x-model="buscarPadre" placeholder="Buscar entre los que ya hay…"
                                    class="w-full rounded-xl border-slate-800 bg-slate-950 text-xs text-slate-200 placeholder:text-slate-600 focus:border-violet-500 focus:ring-violet-500">
                            </div>

                            <div class="mt-2 flex max-h-56 flex-wrap gap-1.5 overflow-y-auto rounded-xl border border-slate-800 bg-slate-950 p-2">

                                <button type="button" @click="padre = ''"
                                    :class="padre === '' ? 'border-cyan-500 bg-cyan-500/15 text-cyan-200' : 'border-slate-800 text-slate-400'"
                                    class="flex items-center gap-1.5 rounded-lg border px-2 py-1.5 text-[10px] font-black transition">
                                    <span class="text-[11px]">⊘</span>
                                    De ninguno
                                </button>

                                <template x-for="candidato in padres" :key="candidato.id">
                                    <button type="button" @click="padre = candidato.id"
                                        x-show="! buscarPadre || candidato.name.toLowerCase().includes(buscarPadre.toLowerCase())"
                                        :class="padre === candidato.id ? 'border-cyan-500 bg-cyan-500/15 text-white' : 'border-slate-800 text-slate-400'"
                                        class="flex items-center gap-1.5 rounded-lg border py-0.5 pl-0.5 pr-2 text-[10px] font-black transition hover:border-slate-600">
                                        <span class="h-6 w-6 shrink-0 overflow-hidden rounded-md border border-slate-800 bg-slate-900">
                                            <template x-if="candidato.image">
                                                <img :src="candidato.image" alt="" class="h-full w-full object-cover">
                                            </template>
                                            <template x-if="! candidato.image">
                                                <span class="flex h-full w-full items-center justify-center text-[9px]"
                                                    :style="`color: ${candidato.color}`"
                                                    x-text="candidato.icon || '◇'"></span>
                                            </template>
                                        </span>
                                        <span class="max-w-[130px] truncate" x-text="candidato.name"></span>
                                    </button>
                                </template>
                            </div>

                            @error('parent_option_id')
                                <span class="mt-1 block text-[11px] font-bold text-rose-300">{{ $message }}</span>
                            @enderror
                        @endif
                    </div>


                    {{-- Dónde va a quedar --}}
                    <div class="rounded-xl border border-slate-800 bg-slate-950 p-3">
                        <p class="text-[9px] font-black uppercase tracking-wider text-slate-600">
                            Dónde quedará
                        </p>

                        <div class="mt-2 space-y-1.5">

                            <div class="flex items-center gap-2 rounded-lg border border-slate-800 px-2 py-1.5">
                                @include('attributes.partials.cara', [
                                    'cosa' => $catalogo,
                                    'tamano' => 'h-6 w-6',
                                    'respaldo' => '◫',
                                    'tono' => $acento,
                                ])
                                <span class="min-w-0 flex-1 truncate text-[10px] font-black text-slate-300">
                                    {{ $catalogo?->name }}
                                </span>
                            </div>

                            {{--
                                `padre` se lee dentro de la propia expresión a
                                propósito: si la condición fuera solo el captador,
                                Alpine no registraría de qué depende y el dibujo
                                se quedaría congelado al cambiar de padre.
                            --}}
                            <template x-if="padre && padreElegido">
                                <div class="ml-3 flex items-center gap-2 rounded-lg border border-cyan-500/40 px-2 py-1.5">
                                    <span class="font-mono text-[10px] text-slate-700">└</span>
                                    <span class="h-6 w-6 shrink-0 overflow-hidden rounded-md border border-slate-800 bg-slate-900">
                                        <template x-if="padreElegido.image">
                                            <img :src="padreElegido.image" alt="" class="h-full w-full object-cover">
                                        </template>
                                        <template x-if="! padreElegido.image">
                                            <span class="flex h-full w-full items-center justify-center text-[9px]"
                                                :style="`color: ${padreElegido.color}`"
                                                x-text="padreElegido.icon || '◇'"></span>
                                        </template>
                                    </span>
                                    <span class="min-w-0 flex-1 truncate text-[10px] font-black text-slate-300"
                                        x-text="padreElegido.name"></span>
                                </div>
                            </template>

                            <div class="flex items-center gap-2 rounded-lg border px-2 py-1.5"
                                :class="padre ? 'ml-6 border-violet-500/60' : 'ml-3 border-violet-500/60'">
                                <span class="font-mono text-[10px] text-slate-700">└</span>
                                <span class="h-6 w-6 shrink-0 overflow-hidden rounded-md border border-slate-800 bg-slate-900">
                                    <template x-if="imagen">
                                        <img :src="imagen" alt="" class="h-full w-full object-cover">
                                    </template>
                                    <template x-if="! imagen">
                                        <span class="flex h-full w-full items-center justify-center text-[9px]"
                                            :style="`color: ${color}`" x-text="icono || '◇'"></span>
                                    </template>
                                </span>
                                <span class="min-w-0 flex-1 truncate text-[10px] font-black text-white"
                                    x-text="nombre || 'este valor'"></span>
                            </div>
                        </div>

                        @if ($editando && $attributeOption->children_count > 0)
                            <p class="mt-2 border-t border-slate-800 pt-2 text-[9px] leading-3 text-amber-300/80">
                                Cuidado: {{ $attributeOption->children_count }}
                                {{ $attributeOption->children_count === 1 ? 'valor cuelga' : 'valores cuelgan' }}
                                de este, y se {{ $attributeOption->children_count === 1 ? 'moverá' : 'moverán' }}
                                con él.
                            </p>
                        @endif
                    </div>
                </div>
            </div>
        </section>


        {{-- ---------- 4 · LO DEMÁS ---------- --}}

        <section x-data="{ abierto: {{ $errors->any() || $numeroActual !== '' || $estadoActual !== 'ACTIVE' ? 'true' : 'false' }} }"
            class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

            <button type="button" @click="abierto = !abierto"
                class="flex w-full items-center gap-3 px-4 py-2.5 text-left transition hover:bg-slate-950/40">

                <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-lg bg-slate-800 font-mono text-[11px] font-black text-slate-400">4</span>

                <span class="min-w-0 flex-1">
                    <span class="block text-[12px] font-black text-white">Lo demás</span>
                    <span class="block text-[10px] text-slate-500">
                        Un número de referencia y si se puede elegir. Casi nunca hace falta tocarlo.
                    </span>
                </span>

                <span class="shrink-0 text-slate-500 transition" :class="abierto ? 'rotate-90' : ''">
                    <x-omni-icon name="chevron-derecha" size="h-4 w-4" />
                </span>
            </button>

            <div x-show="abierto" x-cloak x-collapse class="border-t border-slate-800 p-4">

                <div class="grid gap-4 sm:grid-cols-2">

                    <label class="block">
                        <span class="mb-1 block text-[10px] font-black uppercase tracking-wider text-slate-600">
                            Valor numérico
                        </span>
                        <input type="number" step="any" name="numeric_value" value="{{ $numeroActual }}"
                            placeholder="Vacío"
                            class="w-full rounded-xl border-slate-800 bg-slate-950 font-mono text-sm text-slate-200 placeholder:font-sans placeholder:text-slate-600 focus:border-violet-500 focus:ring-violet-500">
                        <span class="mt-1 block text-[10px] leading-4 text-slate-600">
                            Un número asociado a este valor, por si más adelante hace falta ordenarlos o
                            compararlos por él: el rango de un ninja, la potencia de un elemento. No se
                            enseña en ninguna parte todavía.
                        </span>
                        @error('numeric_value')
                            <span class="mt-1 block text-[11px] font-bold text-rose-300">{{ $message }}</span>
                        @enderror
                    </label>

                    <div>
                        <span class="mb-1 block text-[10px] font-black uppercase tracking-wider text-slate-600">
                            Se puede elegir
                        </span>

                        <div class="space-y-1.5">
                            @foreach ([['ACTIVE', 'Sí, activo', 'Aparece en los selectores y se puede asignar.'], ['INACTIVE', 'De momento no', 'Se oculta de los selectores, pero sigue ahí.'], ['ARCHIVED', 'Archivado', 'Fuera de circulación. Quien ya lo llevaba lo conserva.']] as [$valor, $etiqueta, $ayuda])
                                <label class="group flex cursor-pointer items-start gap-2 rounded-xl border border-slate-800 bg-slate-950 px-2.5 py-1.5 transition has-[:checked]:border-violet-500 has-[:checked]:bg-violet-500/10">
                                    <input type="radio" name="status" value="{{ $valor }}"
                                        @checked($estadoActual === $valor)
                                        class="mt-0.5 border-slate-700 bg-slate-900 text-violet-500 focus:ring-violet-500">
                                    <span class="min-w-0">
                                        <span class="block text-[11px] font-black text-slate-200">{{ $etiqueta }}</span>
                                        <span class="block text-[9px] leading-3 text-slate-600">{{ $ayuda }}</span>
                                    </span>
                                </label>
                            @endforeach
                        </div>

                        @error('status')
                            <span class="mt-1 block text-[11px] font-bold text-rose-300">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
            </div>
        </section>

    </div>


    {{-- ===================================================== --}}
    {{-- LO QUE SE VERÁ --}}
    {{-- ===================================================== --}}

    <aside class="space-y-3 xl:sticky xl:top-2 xl:self-start">

        <section class="overflow-hidden rounded-2xl border bg-slate-900/50"
            style="border-color: {{ $acento }}40">

            <div class="border-b border-slate-800 px-3 py-2">
                <p class="text-[10px] font-black uppercase tracking-wider text-slate-600">
                    Así se verá al elegirlo
                </p>
            </div>

            <div class="p-3">

                {{--
                    No es un adorno: es la misma ficha que sale en la galería del
                    catálogo y en el selector de una entidad. Lo que se vea aquí
                    es exactamente lo que verá quien tenga que reconocerlo.
                --}}

                <article class="overflow-hidden rounded-xl border bg-slate-950"
                    :style="`border-color: ${imagen ? color + '55' : '#f43f5e55'}`">

                    <div class="relative aspect-square overflow-hidden bg-slate-900">
                        <template x-if="imagen">
                            <img :src="imagen" alt="" class="h-full w-full object-cover">
                        </template>
                        <template x-if="! imagen">
                            <span class="flex h-full w-full items-center justify-center text-4xl"
                                :style="`color: ${color}66`" x-text="icono || '◇'"></span>
                        </template>

                        <template x-if="! imagen">
                            <span class="absolute inset-x-0 bottom-0 bg-rose-500/85 py-0.5 text-center text-[9px] font-black text-white">
                                sin imagen
                            </span>
                        </template>
                    </div>

                    <div class="p-2">
                        <p class="truncate text-[12px] font-black text-white" x-text="nombre || 'Sin nombre'"></p>
                        <p class="truncate text-[10px] font-bold" style="color: {{ $acento }}">
                            {{ $catalogo?->name }}
                        </p>
                    </div>
                </article>


                {{-- Y así, en una fila --}}
                <p class="mt-3 text-[9px] font-black uppercase tracking-wider text-slate-600">
                    Y así en una lista
                </p>

                <div class="mt-1 flex items-center gap-2 rounded-xl border border-slate-800 bg-slate-950 p-1.5">
                    <span class="h-8 w-8 shrink-0 overflow-hidden rounded-lg border border-slate-800 bg-slate-900">
                        <template x-if="imagen">
                            <img :src="imagen" alt="" class="h-full w-full object-cover">
                        </template>
                        <template x-if="! imagen">
                            <span class="flex h-full w-full items-center justify-center text-[11px]"
                                :style="`color: ${color}`" x-text="icono || '◇'"></span>
                        </template>
                    </span>
                    <span class="min-w-0 flex-1 truncate text-[11px] font-black text-white"
                        x-text="nombre || 'Sin nombre'"></span>
                </div>
            </div>


            {{-- Qué falta --}}
            <div class="border-t border-slate-800 px-3 py-2.5">
                <p class="mb-1.5 text-[9px] font-black uppercase tracking-wider text-slate-600">
                    Qué le falta
                </p>

                <ul class="space-y-1">
                    <li class="flex items-center gap-1.5 text-[10px]">
                        <span x-text="nombre.trim() ? '✓' : '·'"
                            :class="nombre.trim() ? 'text-emerald-400' : 'text-slate-700'"
                            class="w-3 text-center font-black"></span>
                        <span :class="nombre.trim() ? 'text-slate-400' : 'text-slate-600'">Un nombre</span>
                    </li>

                    <li class="flex items-center gap-1.5 text-[10px]">
                        <span x-text="imagen ? '✓' : '!'"
                            :class="imagen ? 'text-emerald-400' : 'text-rose-400'"
                            class="w-3 text-center font-black"></span>
                        <span :class="imagen ? 'text-slate-400' : 'text-rose-300'">Una imagen</span>
                    </li>

                    <li class="flex items-center gap-1.5 text-[10px]">
                        <span class="w-3 text-center font-black text-slate-700">·</span>
                        <span class="text-slate-600">Descripción y jerarquía, si aplican</span>
                    </li>
                </ul>
            </div>
        </section>


        {{-- ---------- GUARDAR ---------- --}}

        <section class="space-y-2 rounded-2xl border border-slate-800 bg-slate-900/50 p-3">

            @if (! $editando)
                <label class="flex cursor-pointer items-start gap-2 rounded-xl border border-slate-800 bg-slate-950 px-2.5 py-2 transition has-[:checked]:border-violet-500 has-[:checked]:bg-violet-500/10">
                    <input type="checkbox" name="keep_adding" value="1" @checked(old('keep_adding'))
                        class="mt-0.5 rounded border-slate-700 bg-slate-900 text-violet-500 focus:ring-violet-500">
                    <span class="min-w-0">
                        <span class="block text-[11px] font-black text-slate-200">Seguir añadiendo</span>
                        <span class="block text-[9px] leading-3 text-slate-600">
                            Al guardar, vuelve aquí con el catálogo ya elegido. Para llenar un catálogo de
                            cincuenta sin dar cincuenta vueltas.
                        </span>
                    </span>
                </label>
            @endif

            <button type="submit" :disabled="! nombre.trim()"
                class="w-full rounded-xl px-4 py-2.5 text-[12px] font-black text-slate-950 transition disabled:cursor-not-allowed disabled:opacity-40"
                style="background-color: {{ $acento }}">
                {{ $editando ? 'Guardar los cambios' : 'Crear el valor' }}
            </button>

            <a href="{{ $editando ? route('attribute-options.show', $attributeOption) : route('attribute-options.index') }}"
                class="block rounded-xl border border-slate-800 px-4 py-2 text-center text-[11px] font-black text-slate-400 transition hover:border-slate-700 hover:text-white">
                Cancelar
            </a>
        </section>

    </aside>

</div>


<script>
    function editorDeValor(config) {

        return {

            nombre: config.nombre ?? '',
            icono: config.icono ?? '',
            color: config.color ?? '#6366f1',
            padre: config.padre ?? '',

            imagen: config.imagenActual ?? null,
            quitarLaExistente: false,

            buscarPadre: '',

            existentes: config.existentes ?? [],
            padres: config.padres ?? [],

            /*
             * Un nombre exactamente igual al de otro valor del mismo catálogo.
             * Se compara en minúsculas y sin espacios sobrantes porque
             * «Uzumaki» y «uzumaki » son el mismo clan para cualquiera menos
             * para la base de datos.
             */
            get repetido() {
                const buscado = this.nombre.trim().toLowerCase();

                if (! buscado) {
                    return null;
                }

                return this.existentes.find(v => v.normalizado === buscado) ?? null;
            },

            /*
             * Los que empiezan igual. No es un aviso, es un recordatorio: sirve
             * para darse cuenta de que «Uchiha» ya existe antes de crear
             * «Uchiha (clan)».
             */
            get parecidos() {
                const buscado = this.nombre.trim().toLowerCase();

                if (buscado.length < 3) {
                    return [];
                }

                return this.existentes
                    .filter(v => v.normalizado !== buscado
                        && (v.normalizado.includes(buscado) || buscado.includes(v.normalizado)))
                    .slice(0, 5);
            },

            get padreElegido() {
                return this.padres.find(p => p.id === this.padre) ?? null;
            },

            verImagen(evento) {
                const archivo = evento.target.files?.[0];

                if (! archivo) {
                    return;
                }

                this.quitarLaExistente = false;

                const lector = new FileReader();

                lector.onload = (e) => {
                    this.imagen = e.target.result;
                };

                lector.readAsDataURL(archivo);
            },

            quitarImagen() {
                this.imagen = null;
                this.quitarLaExistente = true;

                if (this.$refs.campoImagen) {
                    this.$refs.campoImagen.value = '';
                }
            },
        };
    }
</script>
