@php
    /*
     * El formulario de un atributo.
     *
     * La decisión que manda aquí es **de qué tipo es**, porque cambia todo lo
     * demás —qué campos tienen sentido, cómo se rellenará después, y si hace
     * falta un catálogo de valores— y en la edición ya no se puede cambiar.
     * Aun así se elegía en un desplegable de ocho palabras.
     *
     * Ahora cada tipo es una tarjeta con **el dibujo de cómo se rellenará**, y
     * debajo hay una vista previa en vivo: el campo real, tal como lo verá
     * quien rellene una entidad. Eso es lo que contesta la pregunta que nadie
     * podía contestar mirando «DECIMAL».
     *
     * El resto se pliega por tipo: los límites numéricos no aparecen si has
     * elegido «Sí / No», y los ajustes de catálogo solo si has elegido
     * catálogo.
     *
     * Sobre la jerarquía: la tienen los **valores** de un catálogo —una aldea
     * puede colgar de un país—, no los atributos entre sí. Se monta al añadir
     * los valores, en la ficha del atributo, y aquí se dice para que nadie la
     * busque en esta pantalla.
     */

    $editing = isset($attribute) && $attribute->exists;

    $typeLocked = $typeLocked ?? false;

    $currentDataType = old('data_type', $attribute->data_type ?? 'OPTION');

    $currentMultiple = (bool) old('allows_multiple', $attribute->allows_multiple ?? true);

    /* Los ocho tipos, con lo que hace falta saber de cada uno. */
    $tipos = [
        'OPTION' => [
            'Catálogo',
            '#8b5cf6',
            'Una lista de valores que tú defines: clanes, aldeas, elementos.',
            'Se elige de una lista, no se escribe. Los valores se añaden después, con su imagen, y pueden colgar unos de otros.',
        ],
        'BOOLEAN' => [
            'Sí / No',
            '#10b981',
            'Solo dos estados: lo tiene o no lo tiene.',
            '¿Está vivo? ¿Es jugable? Un interruptor y ya está.',
        ],
        'TEXT' => [
            'Texto corto',
            '#64748b',
            'Una línea escrita a mano.',
            'Un apodo, un lema. Si se repite mucho, mejor un catálogo.',
        ],
        'LONG_TEXT' => [
            'Texto largo',
            '#64748b',
            'Varios párrafos.',
            'Una biografía, una historia. No sirve para filtrar.',
        ],
        'INTEGER' => [
            'Número entero',
            '#06b6d4',
            'Una cantidad sin decimales.',
            'Edad, nivel, victorias. Se puede comparar y ordenar.',
        ],
        'DECIMAL' => [
            'Número decimal',
            '#06b6d4',
            'Una cantidad con decimales.',
            'Altura, peso, porcentaje. Admite unidad.',
        ],
        'DATE' => [
            'Fecha',
            '#f59e0b',
            'Un día concreto.',
            'Fecha de nacimiento, de estreno. Se puede ordenar.',
        ],
        'COLOR' => [
            'Color',
            '#d946ef',
            'Un color exacto.',
            'El color de un clan, de un uniforme.',
        ],
    ];

    $paleta = [
        '#6366f1' => 'Índigo',
        '#8b5cf6' => 'Violeta',
        '#06b6d4' => 'Cian',
        '#10b981' => 'Verde',
        '#f59e0b' => 'Ámbar',
        '#ee8420' => 'Naranja',
        '#f43f5e' => 'Rosa',
        '#d946ef' => 'Fucsia',
        '#64748b' => 'Pizarra',
    ];

    $iconos = ['◆', '◇', '★', '✦', '❖', '⬢', '◉', '¶', '#', '☯', '⚔', '☾'];

    $interruptores = [
        'is_filterable' => ['Filtrable', 'Aparece como filtro en los listados de entidades.', true],
        'is_comparable' => ['Comparable', 'Se puede usar al comparar dos entidades una al lado de otra.', true],
        'is_searchable' => ['Buscable', 'Su valor entra en la caja de búsqueda.', true],
        'is_visible' => ['Visible', 'Se enseña en la ficha de la entidad.', true],
        'is_featured' => ['Destacado', 'Sale antes que los demás y con más peso visual.', false],
    ];

    $visibilidades = [
        'PUBLIC' => ['Público', 'globo', 'Aparece en Comunidad y otros pueden verlo.', 'border-emerald-500 bg-emerald-500/10', 'text-emerald-400'],
        'UNLISTED' => ['No listado', 'brujula', 'Solo quien tenga el enlace.', 'border-amber-500 bg-amber-500/10', 'text-amber-400'],
        'PRIVATE' => ['Privado', 'usuario', 'Solo tú.', 'border-slate-500 bg-slate-500/10', 'text-slate-400'],
    ];
@endphp

<div x-data="constructorDeAtributo({
        nombre: @js(old('name', $attribute->name ?? '')),
        tipo: @js($currentDataType),
        varios: @js($currentMultiple),
        icono: @js(old('icon', $attribute->icon ?? '◆')),
        color: @js(old('color', $attribute->color ?? '#6366f1')),
        unidad: @js(old('unit', $attribute->unit ?? '')),
        imagen: @js($editing ? $attribute->image_url : null),
        bloqueado: @js($typeLocked),
    })" class="space-y-4">


    {{-- ===================================================== --}}
    {{-- PASO 1 · DE QUÉ TIPO ES --}}
    {{-- ===================================================== --}}

    <section class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

        <div class="flex flex-wrap items-center gap-3 border-b border-slate-800 px-4 py-3">
            <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-violet-500 font-mono text-[11px] font-black text-white">1</span>

            <div class="min-w-0 flex-1">
                <h2 class="text-[13px] font-black text-white">¿Qué clase de dato es?</h2>
                <p class="text-[10px] leading-relaxed text-slate-500">
                    @if ($typeLocked)
                        <strong class="text-amber-300">No se puede cambiar</strong> una vez creado: los valores
                        que ya se hayan guardado son de este tipo. Si te equivocaste, crea otro atributo.
                    @else
                        Es la decisión que manda: cambia qué campos tienen sentido y cómo se rellenará
                        después. <strong class="text-amber-300">No se puede cambiar</strong> una vez creado.
                    @endif
                </p>
            </div>
        </div>

        <input type="hidden" name="data_type" :value="tipo">

        <div class="grid gap-2 p-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($tipos as $valor => [$etiqueta, $tono, $resumen, $ejemplo])
                <button type="button" @disabled($typeLocked)
                    @if (! $typeLocked) @click="elegirTipo(@js($valor))" @endif
                    :class="tipo === @js($valor) ? 'ring-1' : 'hover:border-slate-600'"
                    :style="tipo === @js($valor)
                        ? 'border-color: {{ $tono }}; background-color: {{ $tono }}14; --tw-ring-color: {{ $tono }}'
                        : ''"
                    class="rounded-xl border border-slate-800 bg-slate-950 p-3 text-left transition disabled:cursor-not-allowed disabled:opacity-40">

                    {{-- El dibujo de cómo se rellena --}}
                    <svg viewBox="0 0 120 34" class="h-9 w-full" fill="none" stroke="{{ $tono }}"
                        stroke-width="1.4" stroke-linecap="round" aria-hidden="true">

                        @if ($valor === 'OPTION')
                            <rect x="4" y="4" width="76" height="12" rx="3" />
                            <path d="M72 8l3 3 3-3" />
                            <rect x="4" y="20" width="34" height="10" rx="2" opacity=".55" />
                            <rect x="42" y="20" width="34" height="10" rx="2" opacity=".35" />
                            <circle cx="98" cy="12" r="7" opacity=".5" />
                            <circle cx="106" cy="24" r="5" opacity=".3" />
                        @elseif ($valor === 'BOOLEAN')
                            <rect x="10" y="8" width="40" height="18" rx="9" />
                            <circle cx="41" cy="17" r="6" fill="{{ $tono }}" stroke="none" />
                            <rect x="62" y="8" width="40" height="18" rx="9" opacity=".4" />
                            <circle cx="71" cy="17" r="6" opacity=".4" />
                        @elseif ($valor === 'TEXT')
                            <rect x="4" y="9" width="112" height="16" rx="3" />
                            <path d="M12 17h44" opacity=".6" />
                            <path d="M60 12v10" opacity=".9" />
                        @elseif ($valor === 'LONG_TEXT')
                            <rect x="4" y="3" width="112" height="28" rx="3" />
                            <path d="M12 11h96M12 18h84M12 25h52" opacity=".5" />
                        @elseif ($valor === 'INTEGER')
                            <rect x="4" y="9" width="70" height="16" rx="3" />
                            <text x="14" y="22" fill="{{ $tono }}" stroke="none" font-size="11" font-weight="700">42</text>
                            <path d="M62 14l4-3 4 3M62 20l4 3 4-3" opacity=".7" />
                            <path d="M84 17h28" opacity=".3" />
                        @elseif ($valor === 'DECIMAL')
                            <rect x="4" y="9" width="70" height="16" rx="3" />
                            <text x="12" y="22" fill="{{ $tono }}" stroke="none" font-size="11" font-weight="700">1,80</text>
                            <text x="92" y="22" fill="{{ $tono }}" stroke="none" font-size="9" font-weight="700" opacity=".6">m</text>
                        @elseif ($valor === 'DATE')
                            <rect x="20" y="6" width="80" height="24" rx="3" />
                            <path d="M20 14h80M34 3v6M86 3v6" opacity=".7" />
                            <rect x="46" y="19" width="8" height="6" rx="1" fill="{{ $tono }}" stroke="none" opacity=".8" />
                        @else
                            <circle cx="26" cy="17" r="11" fill="{{ $tono }}" stroke="none" opacity=".85" />
                            <rect x="46" y="9" width="60" height="16" rx="3" />
                            <text x="56" y="21" fill="{{ $tono }}" stroke="none" font-size="9" font-weight="700">#8b5cf6</text>
                        @endif
                    </svg>

                    <p class="mt-1.5 text-[12px] font-black text-white">{{ $etiqueta }}</p>
                    <p class="mt-0.5 text-[10px] leading-4 text-slate-500">{{ $resumen }}</p>
                </button>
            @endforeach
        </div>


        {{-- ---------- CÓMO SE RELLENARÁ ---------- --}}

        {{--
            El campo real, no una descripción del campo. Es lo que verá quien
            rellene una entidad con este atributo.
        --}}

        <div class="border-t border-slate-800 bg-slate-950/40 p-4">

            <div class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_minmax(0,300px)]">

                <div>
                    <p class="text-[10px] font-black uppercase tracking-wider text-slate-600">
                        Así se rellenará
                    </p>

                    <div class="mt-2 rounded-xl border border-slate-800 bg-slate-900 p-3">

                        <p class="mb-1.5 text-[11px] font-black text-slate-300"
                            x-text="nombre || 'Nombre del atributo'"
                            :class="nombre ? '' : 'text-slate-600'"></p>

                        {{-- Catálogo --}}
                        <template x-if="tipo === 'OPTION' && ! varios">
                            <div class="flex items-center justify-between rounded-lg border border-slate-800 bg-slate-950 px-3 py-2">
                                <span class="text-[11px] text-slate-500">Elige un valor…</span>
                                <span class="text-[10px] text-slate-600">▾</span>
                            </div>
                        </template>

                        <template x-if="tipo === 'OPTION' && varios">
                            <div class="flex flex-wrap gap-1.5">
                                <template x-for="n in 3" :key="'e' + n">
                                    <span class="flex items-center gap-1.5 rounded-lg border border-slate-800 bg-slate-950 py-1 pl-1 pr-2">
                                        <span class="h-5 w-5 rounded" :style="'background-color: ' + color + '33'"></span>
                                        <span class="text-[10px] text-slate-500">Valor <span x-text="n"></span></span>
                                    </span>
                                </template>
                                <span class="rounded-lg border border-dashed border-slate-800 px-2 py-1 text-[10px] text-slate-700">+ añadir</span>
                            </div>
                        </template>

                        {{-- Sí / No --}}
                        <template x-if="tipo === 'BOOLEAN'">
                            <div class="flex items-center gap-2">
                                <span class="flex h-5 w-9 items-center rounded-full p-0.5"
                                    :style="'background-color: ' + color">
                                    <span class="ml-auto h-4 w-4 rounded-full bg-white"></span>
                                </span>
                                <span class="text-[11px] text-slate-400">Sí</span>
                            </div>
                        </template>

                        {{-- Texto corto --}}
                        <template x-if="tipo === 'TEXT'">
                            <div class="rounded-lg border border-slate-800 bg-slate-950 px-3 py-2">
                                <span class="text-[11px] text-slate-600">Escribe aquí…</span>
                            </div>
                        </template>

                        {{-- Texto largo --}}
                        <template x-if="tipo === 'LONG_TEXT'">
                            <div class="rounded-lg border border-slate-800 bg-slate-950 px-3 py-2">
                                <span class="block text-[11px] text-slate-600">Escribe aquí…</span>
                                <span class="mt-3 block h-px w-full bg-slate-800/60"></span>
                                <span class="mt-2 block h-px w-2/3 bg-slate-800/40"></span>
                            </div>
                        </template>

                        {{-- Números --}}
                        <template x-if="tipo === 'INTEGER' || tipo === 'DECIMAL'">
                            <div class="flex items-center gap-2">
                                <span class="flex-1 rounded-lg border border-slate-800 bg-slate-950 px-3 py-2 font-mono text-[12px]"
                                    :style="'color: ' + color"
                                    x-text="tipo === 'DECIMAL' ? '1,80' : '42'"></span>

                                <span x-show="unidad" x-cloak
                                    class="rounded-lg border border-slate-800 px-2 py-2 text-[11px] font-black text-slate-400"
                                    x-text="unidad"></span>
                            </div>
                        </template>

                        {{-- Fecha --}}
                        <template x-if="tipo === 'DATE'">
                            <div class="flex items-center justify-between rounded-lg border border-slate-800 bg-slate-950 px-3 py-2">
                                <span class="font-mono text-[11px] text-slate-400">10 / 10 / 1993</span>
                                <span class="text-[11px]" :style="'color: ' + color">◫</span>
                            </div>
                        </template>

                        {{-- Color --}}
                        <template x-if="tipo === 'COLOR'">
                            <div class="flex items-center gap-2">
                                <span class="h-8 w-8 rounded-lg border border-slate-800" :style="'background-color: ' + color"></span>
                                <span class="rounded-lg border border-slate-800 bg-slate-950 px-3 py-2 font-mono text-[11px] text-slate-400"
                                    x-text="color"></span>
                            </div>
                        </template>

                    </div>
                </div>


                <div class="space-y-2 text-[11px] leading-relaxed text-slate-400">
                    <p class="font-black text-white" x-text="etiquetaTipo"></p>
                    <p x-text="ejemploTipo"></p>

                    <template x-if="tipo === 'OPTION'">
                        <p class="rounded-xl border border-violet-500/25 bg-violet-500/5 px-3 py-2 text-[10px] text-violet-200">
                            Los <strong>valores</strong> del catálogo se añaden después, en la ficha del
                            atributo: con su imagen, y pudiendo colgar unos de otros —una aldea dentro de un
                            país—. Aquí solo se crea el catálogo vacío.
                        </p>
                    </template>
                </div>

            </div>
        </div>

    </section>


    {{-- ===================================================== --}}
    {{-- PASO 2 · CÓMO SE LLAMA Y SE VE --}}
    {{-- ===================================================== --}}

    <section class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_300px]">

        <div class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

            <div class="flex items-center gap-3 border-b border-slate-800 px-4 py-3">
                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-violet-500 font-mono text-[11px] font-black text-white">2</span>

                <div class="min-w-0 flex-1">
                    <h2 class="text-[13px] font-black text-white">¿Cómo se llama?</h2>
                    <p class="text-[10px] text-slate-500">
                        El nombre que verá quien rellene una entidad.
                    </p>
                </div>

                <span class="shrink-0 rounded-lg border border-slate-800 px-2 py-1 font-mono text-[10px] text-slate-500">
                    {{ $previewCode ?? $attribute->code }}
                </span>
            </div>

            <div class="space-y-3 p-4">

                <label class="block">
                    <span class="mb-1 block text-[10px] font-black uppercase tracking-wider text-slate-600">Nombre</span>
                    <input type="text" name="name" x-model="nombre" required maxlength="150"
                        placeholder="«Clan», «Edad», «Aldea de origen»…"
                        class="w-full rounded-xl border-slate-800 bg-slate-900 text-sm font-bold text-white placeholder:font-normal placeholder:text-slate-600 focus:border-violet-500 focus:ring-violet-500">
                    @error('name')
                        <span class="mt-1 block text-[10px] font-bold text-rose-300">{{ $message }}</span>
                    @enderror
                </label>

                <div class="grid gap-3 sm:grid-cols-2">
                    <label class="block">
                        <span class="mb-1 block text-[10px] font-black uppercase tracking-wider text-slate-600">
                            De qué va
                        </span>
                        <textarea name="description" rows="2" maxlength="5000"
                            placeholder="Para ti, dentro de un año."
                            class="w-full rounded-xl border-slate-800 bg-slate-900 text-xs text-slate-200 placeholder:text-slate-600 focus:border-violet-500 focus:ring-violet-500">{{ old('description', $attribute->description ?? '') }}</textarea>
                    </label>

                    <label class="block">
                        <span class="mb-1 block text-[10px] font-black uppercase tracking-wider text-slate-600">
                            Ayuda al rellenar
                        </span>
                        <textarea name="help_text" rows="2" maxlength="500"
                            placeholder="Sale debajo del campo, para quien lo rellene."
                            class="w-full rounded-xl border-slate-800 bg-slate-900 text-xs text-slate-200 placeholder:text-slate-600 focus:border-violet-500 focus:ring-violet-500">{{ old('help_text', $attribute->help_text ?? '') }}</textarea>
                    </label>
                </div>

                <label class="block" x-show="tipo !== 'OPTION' && tipo !== 'BOOLEAN'" x-cloak>
                    <span class="mb-1 block text-[10px] font-black uppercase tracking-wider text-slate-600">
                        Texto de ejemplo dentro del campo
                    </span>
                    <input type="text" name="placeholder" value="{{ old('placeholder', $attribute->placeholder ?? '') }}"
                        maxlength="150" placeholder="Lo que se ve en gris antes de escribir."
                        class="w-full rounded-xl border-slate-800 bg-slate-900 text-xs text-slate-200 placeholder:text-slate-600 focus:border-violet-500 focus:ring-violet-500">
                </label>


                {{-- Color --}}
                <div>
                    <span class="mb-1.5 block text-[10px] font-black uppercase tracking-wider text-slate-600">Su color</span>

                    <input type="hidden" name="color" :value="color">

                    <div class="flex flex-wrap items-center gap-1.5">
                        @foreach ($paleta as $hex => $nombreColor)
                            <button type="button" @click="color = @js($hex)" title="{{ $nombreColor }}"
                                :class="color.toLowerCase() === @js($hex) ? 'ring-2 ring-offset-2 ring-offset-slate-900' : 'opacity-60 hover:opacity-100'"
                                class="h-8 w-8 rounded-lg border border-slate-700 transition"
                                style="background-color: {{ $hex }}; --tw-ring-color: {{ $hex }}"></button>
                        @endforeach

                        <label class="flex cursor-pointer items-center gap-1.5 rounded-lg border border-slate-800 bg-slate-950 px-2 py-1.5 text-[10px] font-black text-slate-400 transition hover:border-slate-600">
                            <input type="color" x-model="color" class="h-5 w-5 cursor-pointer rounded border-0 bg-transparent p-0">
                            Otro
                        </label>
                    </div>
                </div>


                {{-- Icono --}}
                <div>
                    <span class="mb-1.5 block text-[10px] font-black uppercase tracking-wider text-slate-600">
                        Su símbolo
                        <span class="font-bold normal-case tracking-normal text-slate-700">— se usa si no le pones imagen</span>
                    </span>

                    <input type="hidden" name="icon" :value="icono">

                    <div class="flex flex-wrap items-center gap-1">
                        @foreach ($iconos as $simbolo)
                            <button type="button" @click="icono = @js($simbolo)"
                                :class="icono === @js($simbolo) ? 'border-violet-500 bg-violet-500/15' : 'border-slate-800 bg-slate-950 hover:border-slate-600'"
                                class="flex h-9 w-9 items-center justify-center rounded-lg border text-base transition"
                                :style="icono === @js($simbolo) ? 'color: ' + color : ''">
                                {{ $simbolo }}
                            </button>
                        @endforeach
                    </div>
                </div>


                {{-- Imagen --}}
                <div>
                    <span class="mb-1.5 block text-[10px] font-black uppercase tracking-wider text-slate-600">Su imagen</span>

                    <label class="block cursor-pointer rounded-xl border border-dashed border-slate-700 p-3 text-center transition hover:border-violet-500">
                        <input type="file" name="image" accept="image/jpeg,image/png,image/webp"
                            x-ref="imageInput" @change="verImagen($event)" class="sr-only">
                        <span class="block text-[11px] font-black text-slate-300"
                            x-text="imagen ? 'Elegir otra imagen' : 'Elegir un archivo'"></span>
                        <span class="mt-0.5 block text-[9px] text-slate-600">JPG, PNG o WEBP</span>
                    </label>

                    @if ($editing)
                        <label class="mt-2 flex cursor-pointer items-center gap-2 text-[10px] font-black text-slate-500 transition hover:text-rose-300">
                            <input type="hidden" name="remove_image" value="0">
                            <input type="checkbox" name="remove_image" value="1" @change="if ($event.target.checked) imagen = null"
                                class="rounded border-slate-700 bg-slate-900 text-rose-500">
                            Quitar la imagen que tiene
                        </label>
                    @endif

                    @error('image')
                        <span class="mt-1 block text-[10px] font-bold text-rose-300">{{ $message }}</span>
                    @enderror
                </div>

            </div>
        </div>


        {{-- ---------- LA VISTA PREVIA ---------- --}}

        <div class="lg:sticky lg:top-24 lg:self-start">

            <p class="mb-2 text-[10px] font-black uppercase tracking-wider text-slate-600">
                Así se verá en el índice
            </p>

            <article class="overflow-hidden rounded-2xl border bg-slate-900/50"
                :style="'border-color: ' + color + '55'">

                <div class="relative aspect-[16/9] overflow-hidden bg-slate-950">
                    <template x-if="imagen">
                        <img :src="imagen" alt="" class="h-full w-full object-cover">
                    </template>

                    <template x-if="! imagen">
                        <span class="flex h-full w-full items-center justify-center text-4xl"
                            :style="'color: ' + color + '88; background: radial-gradient(120% 90% at 50% 0%, ' + color + '22, transparent 70%)'"
                            x-text="icono"></span>
                    </template>

                    <span class="absolute left-2 top-2 rounded-lg bg-slate-950/85 px-2 py-1 text-[9px] font-black uppercase tracking-wider"
                        :style="'color: ' + color" x-text="etiquetaTipo"></span>
                </div>

                <div class="p-3">
                    <p class="truncate text-[13px] font-black text-white"
                        x-text="nombre || 'Sin nombre todavía'"
                        :class="nombre ? '' : 'text-slate-600'"></p>

                    <p class="font-mono text-[9px] text-slate-600">{{ $previewCode ?? $attribute->code }}</p>

                    <p class="mt-2 rounded-lg border border-dashed border-slate-800 px-2 py-1.5 text-center text-[10px] text-slate-600">
                        <span x-show="tipo === 'OPTION'">Sin valores todavía</span>
                        <span x-show="tipo !== 'OPTION'" x-cloak>Se escribe a mano</span>
                    </p>
                </div>
            </article>

            <p class="mt-2 text-[10px] leading-relaxed text-slate-600">
                Se actualiza mientras escribes.
            </p>
        </div>

    </section>


    {{-- ===================================================== --}}
    {{-- PASO 3 · CÓMO SE COMPORTA --}}
    {{-- ===================================================== --}}

    <section class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

        <div class="flex items-center gap-3 border-b border-slate-800 px-4 py-3">
            <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-violet-500 font-mono text-[11px] font-black text-white">3</span>

            <div class="min-w-0 flex-1">
                <h2 class="text-[13px] font-black text-white">¿Cómo se comporta?</h2>
                <p class="text-[10px] text-slate-500">
                    Dónde aparece este atributo en el resto de la aplicación.
                </p>
            </div>
        </div>

        <div class="space-y-3 p-4">

            {{-- Varios valores: solo para catálogo --}}
            <div x-show="tipo === 'OPTION'" x-cloak x-collapse>
                <input type="hidden" name="allows_multiple" value="0">

                <label class="flex cursor-pointer items-start gap-3 rounded-xl border p-3 transition"
                    :class="varios ? 'border-violet-500 bg-violet-500/10' : 'border-slate-800 bg-slate-950'">

                    <input type="checkbox" name="allows_multiple" value="1" x-model="varios"
                        class="mt-0.5 rounded border-slate-700 bg-slate-900 text-violet-500">

                    <span class="min-w-0">
                        <span class="block text-[12px] font-black text-white">Admite varios valores a la vez</span>
                        <span class="mt-0.5 block text-[10px] leading-4 text-slate-500">
                            Un personaje tiene <strong class="text-slate-300">una</strong> aldea, pero puede
                            tener <strong class="text-slate-300">varias</strong> naturalezas. Mira la vista
                            previa de arriba: cambia el campo.
                        </span>
                    </span>
                </label>
            </div>


            {{-- Obligatorio --}}
            <div>
                <input type="hidden" name="is_required" value="0">

                <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-800 bg-slate-950 p-3 transition has-[:checked]:border-amber-500 has-[:checked]:bg-amber-500/10">
                    <input type="checkbox" name="is_required" value="1"
                        @checked(old('is_required', $attribute->is_required ?? false))
                        class="mt-0.5 rounded border-slate-700 bg-slate-900 text-amber-500">

                    <span class="min-w-0">
                        <span class="block text-[12px] font-black text-white">Es obligatorio</span>
                        <span class="mt-0.5 block text-[10px] leading-4 text-slate-500">
                            No se podrá guardar una entidad que lo tenga asignado y sin valor. Piénsatelo:
                            con muchos obligatorios, crear una entidad se hace cuesta arriba.
                        </span>
                    </span>
                </label>
            </div>


            {{-- Los cinco interruptores --}}
            <div>
                <p class="mb-1.5 text-[10px] font-black uppercase tracking-wider text-slate-600">
                    Dónde aparece
                </p>

                <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($interruptores as $campo => [$etiqueta, $ayuda, $porDefecto])
                        <label class="group cursor-pointer rounded-xl border border-slate-800 bg-slate-950 p-2.5 transition has-[:checked]:border-slate-600 has-[:checked]:bg-slate-800/40">
                            <input type="hidden" name="{{ $campo }}" value="0">
                            <input type="checkbox" name="{{ $campo }}" value="1"
                                @checked(old($campo, $editing ? $attribute->{$campo} : $porDefecto))
                                class="sr-only">

                            <span class="flex items-center gap-1.5">
                                <span class="h-2 w-2 rounded-full bg-slate-700 transition group-has-[:checked]:bg-emerald-400"></span>
                                <span class="text-[11px] font-black text-slate-200">{{ $etiqueta }}</span>
                            </span>

                            <span class="mt-0.5 block text-[10px] leading-4 text-slate-500">{{ $ayuda }}</span>
                        </label>
                    @endforeach
                </div>

                <p class="mt-1.5 text-[10px] leading-relaxed text-slate-600">
                    <strong class="text-slate-400">Filtrable</strong> y
                    <strong class="text-slate-400">comparable</strong> solo funcionan de verdad con
                    catálogos, números y fechas: un texto largo no se puede comparar.
                </p>
            </div>

        </div>
    </section>


    {{-- ===================================================== --}}
    {{-- PASO 4 · LÍMITES (SOLO SI EL TIPO LOS TIENE) --}}
    {{-- ===================================================== --}}

    <section x-show="['INTEGER', 'DECIMAL', 'TEXT', 'LONG_TEXT'].includes(tipo)" x-cloak x-collapse
        class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

        <div class="flex items-center gap-3 border-b border-slate-800 px-4 py-3">
            <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-slate-700 font-mono text-[11px] font-black text-white">4</span>

            <div class="min-w-0 flex-1">
                <h2 class="text-[13px] font-black text-white">Límites</h2>
                <p class="text-[10px] text-slate-500">
                    Opcional. Sirven para que nadie meta un valor imposible.
                </p>
            </div>
        </div>

        <div class="grid gap-3 p-4 sm:grid-cols-3">

            <template x-if="tipo === 'INTEGER' || tipo === 'DECIMAL'">
                <div class="contents">
                    <label class="block">
                        <span class="mb-1 block text-[10px] font-black uppercase tracking-wider text-slate-600">Mínimo</span>
                        <input type="number" name="min_numeric_value" step="any"
                            value="{{ old('min_numeric_value', $attribute->min_numeric_value ?? '') }}"
                            class="w-full rounded-xl border-slate-800 bg-slate-900 text-xs text-slate-200 focus:border-cyan-500 focus:ring-cyan-500">
                    </label>

                    <label class="block">
                        <span class="mb-1 block text-[10px] font-black uppercase tracking-wider text-slate-600">Máximo</span>
                        <input type="number" name="max_numeric_value" step="any"
                            value="{{ old('max_numeric_value', $attribute->max_numeric_value ?? '') }}"
                            class="w-full rounded-xl border-slate-800 bg-slate-900 text-xs text-slate-200 focus:border-cyan-500 focus:ring-cyan-500">
                    </label>

                    <label class="block">
                        <span class="mb-1 block text-[10px] font-black uppercase tracking-wider text-slate-600">
                            Unidad
                        </span>
                        <input type="text" name="unit" x-model="unidad" maxlength="30"
                            placeholder="cm, kg, %…"
                            class="w-full rounded-xl border-slate-800 bg-slate-900 text-xs text-slate-200 placeholder:text-slate-600 focus:border-cyan-500 focus:ring-cyan-500">
                        <span class="mt-1 block text-[10px] text-slate-600">Sale al lado del número.</span>
                    </label>
                </div>
            </template>

            <template x-if="tipo === 'TEXT' || tipo === 'LONG_TEXT'">
                <div class="contents">
                    <label class="block">
                        <span class="mb-1 block text-[10px] font-black uppercase tracking-wider text-slate-600">
                            Mínimo de caracteres
                        </span>
                        <input type="number" name="min_length" min="0"
                            value="{{ old('min_length', $attribute->min_length ?? '') }}"
                            class="w-full rounded-xl border-slate-800 bg-slate-900 text-xs text-slate-200 focus:border-slate-500 focus:ring-slate-500">
                    </label>

                    <label class="block">
                        <span class="mb-1 block text-[10px] font-black uppercase tracking-wider text-slate-600">
                            Máximo de caracteres
                        </span>
                        <input type="number" name="max_length" min="1"
                            value="{{ old('max_length', $attribute->max_length ?? '') }}"
                            class="w-full rounded-xl border-slate-800 bg-slate-900 text-xs text-slate-200 focus:border-slate-500 focus:ring-slate-500">
                    </label>
                </div>
            </template>

        </div>
    </section>


    {{-- ===================================================== --}}
    {{-- PASO 5 · GRUPOS --}}
    {{-- ===================================================== --}}

    <section class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

        <div class="flex flex-wrap items-center gap-3 border-b border-slate-800 px-4 py-3">
            <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-slate-700 font-mono text-[11px] font-black text-white">5</span>

            <div class="min-w-0 flex-1">
                <h2 class="text-[13px] font-black text-white">¿En qué grupos va?</h2>
                <p class="text-[10px] leading-relaxed text-slate-500">
                    Los grupos ordenan la ficha de una entidad: «Datos básicos», «Combate», «Historia». Un
                    atributo puede estar en varios, y no estar en ninguno también vale.
                </p>
            </div>

            <a href="{{ route('attribute-groups.index') }}"
                class="shrink-0 rounded-xl border border-slate-800 px-2.5 py-2 text-[10px] font-black text-slate-400 transition hover:border-violet-500 hover:text-violet-300">
                Gestionar grupos →
            </a>
        </div>

        @if ($groups->isEmpty())

            <div class="p-8 text-center">
                <span class="inline-flex text-slate-700"><x-omni-icon name="capas" size="h-8 w-8" /></span>

                <p class="mt-2 text-[12px] font-black text-white">Todavía no tienes grupos</p>

                <p class="mx-auto mt-1 max-w-md text-[11px] leading-relaxed text-slate-500">
                    No pasa nada: el atributo funciona igual. Los grupos solo sirven para que la ficha de una
                    entidad con veinte atributos no sea una lista interminable.
                </p>

                <a href="{{ route('attribute-groups.create') }}"
                    class="mt-3 inline-block rounded-xl border border-slate-700 px-4 py-2 text-[11px] font-black text-slate-300 transition hover:border-violet-500 hover:text-violet-300">
                    Crear un grupo →
                </a>
            </div>

        @else

            @php
                $gruposElegidos = collect(old('group_ids', $editing ? $attribute->groups->pluck('id')->all() : []))
                    ->map(fn($id) => (string) $id)
                    ->all();
            @endphp

            <div class="grid gap-2 p-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($groups as $grupo)
                    <label class="group cursor-pointer rounded-xl border border-slate-800 bg-slate-950 p-2.5 transition has-[:checked]:border-violet-500 has-[:checked]:bg-violet-500/10">
                        <input type="checkbox" name="group_ids[]" value="{{ $grupo->id }}"
                            @checked(in_array((string) $grupo->id, $gruposElegidos, true))
                            class="sr-only">

                        <span class="flex items-center gap-2">
                            <span class="h-2 w-2 rounded-full bg-slate-700 transition group-has-[:checked]:bg-violet-400"></span>
                            <span class="truncate text-[12px] font-black text-slate-200">{{ $grupo->name }}</span>
                        </span>

                        @if ($grupo->description)
                            <span class="mt-0.5 block truncate text-[10px] text-slate-500">{{ $grupo->description }}</span>
                        @endif
                    </label>
                @endforeach
            </div>

        @endif
    </section>


    {{-- ===================================================== --}}
    {{-- PASO 6 · QUIÉN LO VE --}}
    {{-- ===================================================== --}}

    <section class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

        <div class="flex items-center gap-3 border-b border-slate-800 px-4 py-3">
            <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-slate-700 font-mono text-[11px] font-black text-white">6</span>

            <div class="min-w-0 flex-1">
                <h2 class="text-[13px] font-black text-white">¿Quién lo ve?</h2>
                <p class="text-[10px] text-slate-500">Se puede cambiar en cualquier momento.</p>
            </div>
        </div>

        <div class="grid gap-2 p-4 lg:grid-cols-3">
            @foreach ($visibilidades as $valor => [$etiqueta, $icono, $ayuda, $tonoActivo, $tonoIcono])
                <label class="cursor-pointer rounded-xl border p-3 transition {{ old('scope', $attribute->scope ?? 'PUBLIC') === $valor ? $tonoActivo : 'border-slate-800 bg-slate-950 hover:border-slate-600' }}">
                    <input type="radio" name="scope" value="{{ $valor }}"
                        @checked(old('scope', $attribute->scope ?? 'PUBLIC') === $valor) class="sr-only">

                    <span class="flex items-center gap-2">
                        <span class="{{ $tonoIcono }}"><x-omni-icon :name="$icono" size="h-4 w-4" /></span>
                        <span class="text-[12px] font-black text-white">{{ $etiqueta }}</span>
                    </span>

                    <span class="mt-1 block text-[10px] leading-4 text-slate-500">{{ $ayuda }}</span>
                </label>
            @endforeach
        </div>

        <div class="grid gap-2 border-t border-slate-800 p-4 sm:grid-cols-2">

            <label class="block">
                <span class="mb-1 block text-[10px] font-black uppercase tracking-wider text-slate-600">Estado</span>
                <select name="status"
                    class="w-full rounded-xl border-slate-800 bg-slate-900 text-xs text-slate-200 focus:border-violet-500 focus:ring-violet-500">
                    @foreach (['ACTIVE' => 'Activo — se puede asignar', 'INACTIVE' => 'Inactivo — guardado, fuera de juego', 'ARCHIVED' => 'Archivado — retirado'] as $valor => $etiqueta)
                        <option value="{{ $valor }}" @selected(old('status', $attribute->status ?? 'ACTIVE') === $valor)>
                            {{ $etiqueta }}
                        </option>
                    @endforeach
                </select>
            </label>

            <label class="cursor-pointer rounded-xl border border-slate-800 bg-slate-950 p-3 transition has-[:checked]:border-emerald-500 has-[:checked]:bg-emerald-500/10">
                <input type="hidden" name="allow_cloning" value="0">
                <input type="checkbox" name="allow_cloning" value="1"
                    @checked(old('allow_cloning', $attribute->allow_cloning ?? true)) class="sr-only">

                <span class="text-[12px] font-black text-white">Dejar que lo copien</span>
                <span class="mt-0.5 block text-[10px] leading-4 text-slate-500">
                    Otros usuarios pueden llevárselo a su biblioteca, con sus valores. El tuyo no se toca.
                </span>
            </label>
        </div>
    </section>


    {{-- ===================================================== --}}
    {{-- GUARDAR --}}
    {{-- ===================================================== --}}

    <div class="sticky bottom-4 z-20 flex flex-wrap items-center gap-3 rounded-2xl border border-slate-800 bg-slate-950/95 px-4 py-3 backdrop-blur">

        <p class="min-w-0 flex-1 text-[11px] leading-relaxed text-slate-400">
            <span x-show="! nombre">Ponle nombre para poder guardarlo.</span>

            <span x-show="nombre" x-cloak>
                {{ $editing ? 'Se guardan los cambios de' : 'Se creará' }}
                <strong class="text-white" x-text="'«' + nombre + '»'"></strong>,
                <span x-text="etiquetaTipo.toLowerCase()"></span>.
                <span x-show="tipo === 'OPTION'" class="text-slate-500">
                    Nace vacío: los valores se añaden después, en su ficha.
                </span>
            </span>
        </p>

        <a href="{{ $editing ? route('attributes.show', $attribute) : route('attributes.index') }}"
            class="rounded-xl border border-slate-800 px-4 py-2 text-[11px] font-black text-slate-400 transition hover:text-white">
            Cancelar
        </a>

        <button type="submit" :disabled="! nombre"
            class="rounded-xl bg-violet-500 px-5 py-2.5 text-[11px] font-black text-white transition hover:bg-violet-400 disabled:cursor-not-allowed disabled:opacity-40">
            {{ $editing ? 'Guardar los cambios' : 'Crear el atributo' }}
        </button>
    </div>

</div>


<script>
    /*
     * El constructor de un atributo.
     *
     * Lo único que hace falta recordar aquí es que el TIPO manda: al elegirlo
     * se ajusta «admite varios» —un catálogo lo suele admitir, un número no
     * puede— y se decide qué secciones tienen sentido.
     */
    function constructorDeAtributo(config) {

        return {

            nombre: config.nombre ?? '',
            tipo: config.tipo ?? 'OPTION',
            varios: !! config.varios,
            icono: config.icono ?? '◆',
            color: config.color ?? '#6366f1',
            unidad: config.unidad ?? '',
            imagen: config.imagen ?? null,
            bloqueado: !! config.bloqueado,

            etiquetas: @js(collect($tipos)->map(fn($t) => $t[0])),
            ejemplos: @js(collect($tipos)->map(fn($t) => $t[3])),


            get etiquetaTipo() {
                return this.etiquetas[this.tipo] ?? this.tipo;
            },


            get ejemploTipo() {
                return this.ejemplos[this.tipo] ?? '';
            },


            elegirTipo(tipo) {
                if (this.bloqueado) return;

                this.tipo = tipo;

                /* Solo un catálogo admite varios valores de verdad. */
                this.varios = tipo === 'OPTION';
            },


            verImagen(evento) {
                const archivo = evento.target.files?.[0];

                if (! archivo) return;

                const lector = new FileReader();

                lector.onload = (e) => { this.imagen = e.target.result; };

                lector.readAsDataURL(archivo);
            },
        };
    }
</script>
