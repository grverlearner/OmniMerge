@php
    /*
     * La definición de una plantilla de torneo.
     *
     * Esta pantalla responde a QUÉ es el torneo y CÓMO se reconoce; no a por
     * dónde pasa la gente. El recorrido —entradas, fases, conexiones,
     * finales— se construye en la Super Edición, porque hasta que la
     * plantilla no existe no hay grafo que montar.
     *
     * Lo que se añadió aquí es todo lo que permite ENCONTRAR una plantilla
     * entre cuarenta sin abrirla: un icono, un color, una frase, un tipo y
     * unas etiquetas. Vive en `settings` y no lo lee ningún motor.
     *
     * Y a la derecha, la ficha exacta que se verá en la biblioteca, en vivo:
     * elegir un color a ciegas y descubrir el resultado en otra pantalla es
     * lo que hacía que nadie los cambiara.
     */

    use App\Models\TournamentTemplate;

    $editing = isset($tournamentTemplate);

    $removeImage = (bool) old('remove_image', false);

    $currentImage = $editing && !$removeImage ? $tournamentTemplate->image_url : null;

    /*
     * Las clases van literales, nunca compuestas: Tailwind lee este archivo
     * y una clase armada con 'border-' . $color no existiría en el CSS.
     */
    $tonos = [
        'amber' => ['borde' => 'border-amber-500/40', 'texto' => 'text-amber-300', 'fondo' => 'bg-amber-500/10'],
        'violet' => ['borde' => 'border-violet-500/40', 'texto' => 'text-violet-300', 'fondo' => 'bg-violet-500/10'],
        'cyan' => ['borde' => 'border-cyan-500/40', 'texto' => 'text-cyan-300', 'fondo' => 'bg-cyan-500/10'],
        'emerald' => ['borde' => 'border-emerald-500/40', 'texto' => 'text-emerald-300', 'fondo' => 'bg-emerald-500/10'],
        'rose' => ['borde' => 'border-rose-500/40', 'texto' => 'text-rose-300', 'fondo' => 'bg-rose-500/10'],
        'sky' => ['borde' => 'border-sky-500/40', 'texto' => 'text-sky-300', 'fondo' => 'bg-sky-500/10'],
        'slate' => ['borde' => 'border-slate-700', 'texto' => 'text-slate-300', 'fondo' => 'bg-slate-800/60'],
    ];

    $puntos = [
        'amber' => 'bg-amber-400',
        'violet' => 'bg-violet-400',
        'cyan' => 'bg-cyan-400',
        'emerald' => 'bg-emerald-400',
        'rose' => 'bg-rose-400',
        'sky' => 'bg-sky-400',
        'slate' => 'bg-slate-500',
    ];

    /* Qué clase de torneo es. Organiza la biblioteca y da icono y color por defecto. */
    $categorias = [
        'CUP' => ['icono' => '🏆', 'color' => 'amber', 'texto' => 'Se juega hasta que queda uno. Eliminatorias, final, campeón.'],
        'LEAGUE' => ['icono' => '≡', 'color' => 'cyan', 'texto' => 'Todos se cruzan y gana quien más suma a lo largo del recorrido.'],
        'QUALIFIER' => ['icono' => '⇢', 'color' => 'sky', 'texto' => 'No corona a nadie: reparte plazas para otro torneo.'],
        'FRIENDLY' => ['icono' => '◇', 'color' => 'emerald', 'texto' => 'Sin consecuencias en la clasificación. Para probar cosas.'],
        'RANKING' => ['icono' => '▲', 'color' => 'violet', 'texto' => 'Lo que importa es el orden final, no un único ganador.'],
        'SPECIAL' => ['icono' => '✦', 'color' => 'rose', 'texto' => 'Formato propio, fuera del calendario habitual.'],
    ];

    $iconosSugeridos = ['🏆', '🥇', '⚔', '👑', '🎯', '🔥', '⭐', '🛡', '≡', '⇢', '▲', '✦'];

    /* Con techo o sin él: el modelo solo tiene min y max, así que son dos modos */
    $capacityMode = old('capacity_mode');

    if (!$capacityMode) {
        $capacityMode = $editing && $tournamentTemplate->max_participants !== null ? 'RANGE' : 'OPEN';
    }

    $etiquetasIniciales = old('tags', $editing ? $tournamentTemplate->tags : []);

    $config = [
        'editing' => $editing,
        'currentImage' => $currentImage,
        'removeImage' => $removeImage,
        'name' => old('name', $editing ? $tournamentTemplate->name : ''),
        'summary' => old('summary', $editing ? (string) $tournamentTemplate->summary : ''),
        'icon' => old('icon', $editing ? (string) data_get($tournamentTemplate->settings, 'icon', '') : ''),
        'accent' => old('accent', $editing ? (string) data_get($tournamentTemplate->settings, 'accent', '') : ''),
        'category' => old('category', $editing ? (string) $tournamentTemplate->category : ''),
        'tags' => array_values((array) $etiquetasIniciales),
        'capacityMode' => $capacityMode,
        'minParticipants' => old('min_participants', $editing ? $tournamentTemplate->min_participants : 8),
        'maxParticipants' => old('max_participants', $editing ? $tournamentTemplate->max_participants : ''),
        'allowByes' => (bool) old('allow_byes', $editing ? $tournamentTemplate->allow_byes : false),
        'status' => old('status', $editing ? $tournamentTemplate->status : 'DRAFT'),
        'visibility' => old('visibility', $editing ? $tournamentTemplate->visibility : 'PRIVATE'),
        'allowCloning' => (bool) old('allow_cloning', $editing ? $tournamentTemplate->allow_cloning : true),
        'tones' => $tonos,
        'categories' => collect($categorias)
            ->map(fn($datos, $clave) => [
                'label' => TournamentTemplate::CATEGORIES[$clave],
                'icon' => $datos['icono'],
                'accent' => $datos['color'],
            ])
            ->all(),
    ];

    $codigo = $editing ? $tournamentTemplate->code : $previewCode;
@endphp

<div x-data="tournamentTemplateDesigner(@js($config))" x-init="init()" @input="markDirty()" @change="markDirty()"
    class="space-y-5 pb-4">

    <input type="hidden" name="remove_image" :value="removeImage ? 1 : 0">
    <input type="hidden" name="capacity_mode" :value="capacityMode">
    <input type="hidden" name="accent" :value="accent">
    <input type="hidden" name="category" :value="category">
    <input type="hidden" name="status" :value="status">
    <input type="hidden" name="visibility" :value="visibility">

    {{-- Las etiquetas viajan una por una; el campo visible solo las compone --}}
    <template x-for="etiqueta in tags" :key="etiqueta">
        <input type="hidden" name="tags[]" :value="etiqueta">
    </template>

    @if ($errors->any())
        <section class="rounded-2xl border border-rose-500/40 bg-rose-500/10 p-4" role="alert">
            <p class="text-xs font-black uppercase tracking-wider text-rose-300">
                No se pudo guardar la plantilla
            </p>

            <ul class="mt-2 list-disc space-y-1 pl-5 text-[11px] font-bold text-rose-200/80">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </section>
    @endif


    <div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_380px] xl:items-start">

        {{-- ===================================================== --}}
        {{-- IZQUIERDA · LO QUE SE DEFINE --}}
        {{-- ===================================================== --}}

        <div class="space-y-5">

            {{-- ============ 01 · IDENTIDAD ============ --}}

            <section class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

                <header class="flex items-center gap-3 border-b border-slate-800 px-5 py-3">
                    <span
                        class="flex h-8 w-8 items-center justify-center rounded-lg bg-amber-500/15 text-[11px] font-black text-amber-300">
                        01
                    </span>
                    <div>
                        <h2 class="text-sm font-black text-white">Identidad</h2>
                        <p class="text-[10px] text-slate-500">Cómo se llama y qué se ve de ella.</p>
                    </div>
                </header>

                <div class="grid gap-5 p-5 lg:grid-cols-[240px_1fr]">

                    <div>
                        <p class="mb-2 text-[9px] font-black uppercase tracking-wider text-slate-500">Portada</p>

                        <div
                            class="relative aspect-[16/10] overflow-hidden rounded-xl border border-dashed border-slate-700 bg-slate-950">
                            <template x-if="preview">
                                <img :src="preview" alt="Portada del torneo" class="h-full w-full object-cover">
                            </template>

                            <template x-if="!preview">
                                <div class="flex h-full flex-col items-center justify-center gap-1">
                                    <span class="text-4xl opacity-20" x-text="effectiveIcon()"></span>
                                    <span class="text-[10px] font-bold text-slate-600">Sin portada</span>
                                </div>
                            </template>
                        </div>

                        <label
                            class="mt-2 block cursor-pointer rounded-xl border border-slate-800 bg-slate-950 px-3 py-2 text-center text-[11px] font-black text-slate-300 transition hover:border-amber-500/60 hover:text-amber-300">
                            Elegir imagen
                            <input type="file" name="image" accept="image/png,image/jpeg,image/webp"
                                @change="loadImage($event)" class="hidden">
                        </label>

                        <button type="button" x-show="preview" x-cloak @click="clearImage()"
                            class="mt-1.5 w-full rounded-xl border border-rose-500/30 bg-rose-500/10 px-3 py-2 text-[11px] font-black text-rose-300 transition hover:bg-rose-500/20">
                            Quitar imagen
                        </button>

                        @error('image')
                            <p class="mt-2 text-[11px] font-bold text-rose-300">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="space-y-4">

                        <div>
                            <label for="tpl-name"
                                class="text-[9px] font-black uppercase tracking-wider text-slate-500">
                                Nombre *
                            </label>

                            <input id="tpl-name" type="text" name="name" x-model="name" maxlength="150" required
                                placeholder="Ej. Copa de Verano"
                                class="mt-1.5 w-full rounded-xl border-slate-800 bg-slate-950 text-sm font-bold text-white placeholder:text-slate-700 focus:border-amber-500 focus:ring-amber-500">

                            @error('name')
                                <p class="mt-1.5 text-[11px] font-bold text-rose-300">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <div class="flex items-baseline justify-between">
                                <label for="tpl-summary"
                                    class="text-[9px] font-black uppercase tracking-wider text-slate-500">
                                    Frase corta
                                </label>
                                <span class="font-mono text-[9px] text-slate-600">
                                    <span x-text="summary.length"></span>/140
                                </span>
                            </div>

                            <input id="tpl-summary" type="text" name="summary" x-model="summary" maxlength="140"
                                placeholder="Qué torneo es esto, en una línea"
                                class="mt-1.5 w-full rounded-xl border-slate-800 bg-slate-950 text-xs text-slate-200 placeholder:text-slate-700 focus:border-amber-500 focus:ring-amber-500">

                            <p class="mt-1.5 text-[10px] leading-4 text-slate-600">
                                Es lo que se lee en la ficha de la biblioteca. Si la dejas vacía se enseña
                                la descripción.
                            </p>

                            @error('summary')
                                <p class="mt-1.5 text-[11px] font-bold text-rose-300">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="tpl-description"
                                class="text-[9px] font-black uppercase tracking-wider text-slate-500">
                                Descripción
                            </label>

                            <textarea id="tpl-description" name="description" rows="4" maxlength="5000"
                                placeholder="Para qué sirve este torneo, cuándo se juega, qué lo distingue..."
                                class="mt-1.5 w-full rounded-xl border-slate-800 bg-slate-950 text-xs leading-relaxed text-slate-300 placeholder:text-slate-700 focus:border-amber-500 focus:ring-amber-500">{{ old('description', $editing ? $tournamentTemplate->description : '') }}</textarea>

                            @error('description')
                                <p class="mt-1.5 text-[11px] font-bold text-rose-300">{{ $message }}</p>
                            @enderror
                        </div>

                        <div
                            class="flex flex-wrap items-center gap-2 rounded-xl border border-slate-800 bg-slate-950 px-3 py-2">
                            <span class="text-[9px] font-black uppercase tracking-wider text-slate-600">Código</span>
                            <span class="font-mono text-xs font-black text-amber-300">{{ $codigo }}</span>
                            <span class="text-[9px] text-slate-600">se genera solo, no depende del nombre</span>
                        </div>

                    </div>

                </div>
            </section>


            {{-- ============ 02 · QUÉ CLASE DE TORNEO ES ============ --}}

            <section class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

                <header class="flex items-center gap-3 border-b border-slate-800 px-5 py-3">
                    <span
                        class="flex h-8 w-8 items-center justify-center rounded-lg bg-violet-500/15 text-[11px] font-black text-violet-300">
                        02
                    </span>
                    <div>
                        <h2 class="text-sm font-black text-white">Qué clase de torneo es</h2>
                        <p class="text-[10px] text-slate-500">
                            Organiza la biblioteca y le da su icono y su color por defecto.
                        </p>
                    </div>
                </header>

                <div class="space-y-5 p-5">

                    <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">

                        <button type="button" @click="category = ''; markDirty()"
                            :class="category === '' ?
                                'border-slate-600 bg-slate-800/60' :
                                'border-slate-800 bg-slate-950 hover:border-slate-700'"
                            class="rounded-xl border p-3 text-left transition">
                            <span class="flex items-center gap-2">
                                <span class="text-lg">◇</span>
                                <span class="text-xs font-black text-slate-300">Sin clasificar</span>
                            </span>
                            <span class="mt-1 block text-[10px] leading-4 text-slate-500">
                                No hace falta elegir: se puede decidir después.
                            </span>
                        </button>

                        @foreach ($categorias as $clave => $datos)
                            @php $t = $tonos[$datos['color']]; @endphp

                            <button type="button" @click="category = '{{ $clave }}'; markDirty()"
                                :aria-pressed="category === '{{ $clave }}'"
                                :class="category === '{{ $clave }}' ?
                                    '{{ $t['borde'] }} {{ $t['fondo'] }}' :
                                    'border-slate-800 bg-slate-950 hover:border-slate-700'"
                                class="rounded-xl border p-3 text-left transition">
                                <span class="flex items-center gap-2">
                                    <span class="text-lg">{{ $datos['icono'] }}</span>
                                    <span class="text-xs font-black {{ $t['texto'] }}">
                                        {{ TournamentTemplate::CATEGORIES[$clave] }}
                                    </span>
                                </span>
                                <span class="mt-1 block text-[10px] leading-4 text-slate-500">
                                    {{ $datos['texto'] }}
                                </span>
                            </button>
                        @endforeach

                    </div>

                    @error('category')
                        <p class="text-[11px] font-bold text-rose-300">{{ $message }}</p>
                    @enderror


                    {{-- ============ ETIQUETAS ============ --}}

                    <div>
                        <div class="flex items-baseline justify-between">
                            <p class="text-[9px] font-black uppercase tracking-wider text-slate-500">Etiquetas</p>
                            <span class="font-mono text-[9px] text-slate-600">
                                <span x-text="tags.length"></span>/6
                            </span>
                        </div>

                        <p class="mt-1 text-[10px] leading-4 text-slate-600">
                            Palabras tuyas para agrupar plantillas como te convenga: «verano»,
                            «oficial», «pruebas». Se pueden buscar desde la biblioteca.
                        </p>

                        <div class="mt-2 flex flex-wrap items-center gap-1.5">

                            <template x-for="etiqueta in tags" :key="etiqueta">
                                <span
                                    class="flex items-center gap-1 rounded-lg border border-slate-700 bg-slate-950 px-2 py-1 text-[10px] font-bold text-slate-300">
                                    <span x-text="'#' + etiqueta"></span>

                                    <button type="button" @click="removeTag(etiqueta)"
                                        class="text-slate-600 transition hover:text-rose-300" title="Quitar">
                                        ×
                                    </button>
                                </span>
                            </template>

                            <input type="text" x-model="tagDraft" @keydown.enter.prevent="addTag()"
                                @keydown.,.prevent="addTag()" @blur="addTag()" maxlength="24"
                                :disabled="tags.length >= 6"
                                :placeholder="tags.length >= 6 ? 'Ya son seis' : 'escribe y pulsa Enter'"
                                class="w-44 rounded-lg border-slate-800 bg-slate-950 py-1 text-[11px] text-slate-200 placeholder:text-slate-700 focus:border-amber-500 focus:ring-amber-500 disabled:opacity-40">
                        </div>

                        @error('tags')
                            <p class="mt-1.5 text-[11px] font-bold text-rose-300">{{ $message }}</p>
                        @enderror
                    </div>

                </div>
            </section>


            {{-- ============ 03 · CÓMO SE RECONOCE ============ --}}

            <section class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

                <header class="flex items-center gap-3 border-b border-slate-800 px-5 py-3">
                    <span
                        class="flex h-8 w-8 items-center justify-center rounded-lg bg-sky-500/15 text-[11px] font-black text-sky-300">
                        03
                    </span>
                    <div>
                        <h2 class="text-sm font-black text-white">Cómo se reconoce</h2>
                        <p class="text-[10px] text-slate-500">
                            Un icono y un color. No cambian nada de cómo se juega.
                        </p>
                    </div>
                </header>

                <div class="space-y-5 p-5">

                    <div>
                        <p class="text-[9px] font-black uppercase tracking-wider text-slate-500">Icono</p>

                        <div class="mt-2 flex flex-wrap items-center gap-1.5">

                            <button type="button" @click="icon = ''; markDirty()"
                                :class="icon === '' ?
                                    'border-amber-500 bg-amber-500/15 text-amber-300' :
                                    'border-slate-800 bg-slate-950 text-slate-500 hover:border-slate-600'"
                                class="rounded-lg border px-2.5 py-1.5 text-[10px] font-black transition">
                                Auto <span x-text="effectiveIcon()"></span>
                            </button>

                            @foreach ($iconosSugeridos as $sugerido)
                                <button type="button" @click="icon = '{{ $sugerido }}'; markDirty()"
                                    :class="icon === '{{ $sugerido }}' ?
                                        'border-amber-500 bg-amber-500/15' :
                                        'border-slate-800 bg-slate-950 hover:border-slate-600'"
                                    class="h-9 w-9 rounded-lg border text-base transition">
                                    {{ $sugerido }}
                                </button>
                            @endforeach

                            <input type="text" name="icon" x-model="icon" maxlength="8" placeholder="otro"
                                class="h-9 w-20 rounded-lg border-slate-800 bg-slate-950 text-center text-base text-white placeholder:text-[10px] placeholder:text-slate-700 focus:border-amber-500 focus:ring-amber-500">
                        </div>

                        @error('icon')
                            <p class="mt-1.5 text-[11px] font-bold text-rose-300">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <p class="text-[9px] font-black uppercase tracking-wider text-slate-500">Color</p>

                        <div class="mt-2 flex flex-wrap items-center gap-1.5">

                            <button type="button" @click="accent = ''; markDirty()"
                                :class="accent === '' ?
                                    'border-amber-500 bg-amber-500/15 text-amber-300' :
                                    'border-slate-800 bg-slate-950 text-slate-500 hover:border-slate-600'"
                                class="rounded-lg border px-2.5 py-1.5 text-[10px] font-black transition">
                                El de su tipo
                            </button>

                            @foreach ($puntos as $color => $punto)
                                <button type="button" @click="accent = '{{ $color }}'; markDirty()"
                                    :class="accent === '{{ $color }}' ?
                                        'border-white/40 ring-2 ring-white/20' :
                                        'border-slate-800 hover:border-slate-600'"
                                    title="{{ $color }}"
                                    class="flex h-9 w-9 items-center justify-center rounded-lg border bg-slate-950 transition">
                                    <span class="h-3.5 w-3.5 rounded-full {{ $punto }}"></span>
                                </button>
                            @endforeach
                        </div>

                        @error('accent')
                            <p class="mt-1.5 text-[11px] font-bold text-rose-300">{{ $message }}</p>
                        @enderror
                    </div>

                </div>
            </section>


            {{-- ============ 04 · CUÁNTA GENTE ADMITE ============ --}}

            <section class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

                <header class="flex items-center gap-3 border-b border-slate-800 px-5 py-3">
                    <span
                        class="flex h-8 w-8 items-center justify-center rounded-lg bg-cyan-500/15 text-[11px] font-black text-cyan-300">
                        04
                    </span>
                    <div>
                        <h2 class="text-sm font-black text-white">Cuánta gente admite</h2>
                        <p class="text-[10px] text-slate-500">
                            El límite del torneo entero, por encima del de cada fase.
                        </p>
                    </div>
                </header>

                <div class="space-y-4 p-5">

                    <div class="grid gap-2 sm:grid-cols-2">
                        @foreach ([['RANGE', 'Con techo', 'Entre un mínimo y un máximo.'], ['OPEN', 'Sin techo', 'Desde un mínimo, sin límite arriba.']] as [$valor, $titulo, $texto])
                            <button type="button" @click="chooseCapacityMode('{{ $valor }}')"
                                :aria-pressed="capacityMode === '{{ $valor }}'"
                                :class="capacityMode === '{{ $valor }}' ?
                                    'border-cyan-500/50 bg-cyan-500/10' :
                                    'border-slate-800 bg-slate-950 hover:border-slate-700'"
                                class="rounded-xl border p-3 text-left transition">
                                <span class="block text-xs font-black text-white">{{ $titulo }}</span>
                                <span class="mt-0.5 block text-[10px] leading-4 text-slate-500">{{ $texto }}</span>
                            </button>
                        @endforeach
                    </div>

                    <div class="grid gap-3 rounded-xl border border-slate-800 bg-slate-950 p-4 sm:grid-cols-2">

                        <div>
                            <label for="tpl-min" class="text-[9px] font-black uppercase tracking-wider text-slate-500">
                                Mínimo *
                            </label>
                            <input id="tpl-min" type="number" name="min_participants" x-model="minParticipants"
                                min="2" max="512" required
                                class="mt-1.5 w-full rounded-xl border-slate-800 bg-slate-900 text-sm font-black text-white focus:border-cyan-500 focus:ring-cyan-500">

                            @error('min_participants')
                                <p class="mt-1.5 text-[11px] font-bold text-rose-300">{{ $message }}</p>
                            @enderror
                        </div>

                        <div x-show="capacityMode === 'RANGE'" x-cloak>
                            <label for="tpl-max" class="text-[9px] font-black uppercase tracking-wider text-slate-500">
                                Máximo *
                            </label>
                            <input id="tpl-max" type="number" name="max_participants" x-model="maxParticipants"
                                min="2" max="512" :required="capacityMode === 'RANGE'"
                                class="mt-1.5 w-full rounded-xl border-slate-800 bg-slate-900 text-sm font-black text-white focus:border-cyan-500 focus:ring-cyan-500">

                            @error('max_participants')
                                <p class="mt-1.5 text-[11px] font-bold text-rose-300">{{ $message }}</p>
                            @enderror
                        </div>

                        <p x-show="capacityMode === 'OPEN'" x-cloak class="self-end text-[10px] text-slate-600">
                            Sin límite máximo: lo que aguante el recorrido.
                        </p>

                    </div>

                    <label
                        class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-800 bg-slate-950 p-3 transition hover:border-amber-500/40">
                        <input type="checkbox" name="allow_byes" value="1" x-model="allowByes"
                            class="mt-0.5 rounded border-slate-700 bg-slate-900 text-amber-500 focus:ring-amber-500">
                        <span>
                            <span class="block text-xs font-black text-white">Permitir BYE</span>
                            <span class="mt-0.5 block text-[10px] leading-4 text-slate-500">
                                Si no llega gente suficiente para llenar una estructura, alguien avanza
                                sin jugar en vez de bloquearse el torneo.
                            </span>
                        </span>
                    </label>

                </div>
            </section>


            {{-- ============ 05 · PUBLICACIÓN ============ --}}

            <section class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

                <header class="flex items-center gap-3 border-b border-slate-800 px-5 py-3">
                    <span
                        class="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-500/15 text-[11px] font-black text-emerald-300">
                        05
                    </span>
                    <div>
                        <h2 class="text-sm font-black text-white">Publicación</h2>
                        <p class="text-[10px] text-slate-500">Quién puede verla y si otros pueden copiarla.</p>
                    </div>
                </header>

                <div class="space-y-4 p-5">

                    <div>
                        <p class="text-[9px] font-black uppercase tracking-wider text-slate-500">Estado</p>

                        <div class="mt-2 flex flex-wrap gap-2">
                            @foreach ([['DRAFT', 'Borrador', 'Se está montando.'], ['ACTIVE', 'Activa', 'Lista para usarse en universos.'], ['ARCHIVED', 'Archivada', 'Fuera de circulación.']] as [$valor, $titulo, $texto])
                                <button type="button" @click="status = '{{ $valor }}'; markDirty()"
                                    :aria-pressed="status === '{{ $valor }}'"
                                    :class="status === '{{ $valor }}' ?
                                        'border-emerald-500/50 bg-emerald-500/10 text-emerald-300' :
                                        'border-slate-800 bg-slate-950 text-slate-400 hover:border-slate-700'"
                                    class="rounded-xl border px-3 py-2 text-left transition">
                                    <span class="block text-xs font-black">{{ $titulo }}</span>
                                    <span class="mt-0.5 block text-[10px] text-slate-500">{{ $texto }}</span>
                                </button>
                            @endforeach
                        </div>

                        @error('status')
                            <p class="mt-1.5 text-[11px] font-bold text-rose-300">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <p class="text-[9px] font-black uppercase tracking-wider text-slate-500">Visibilidad</p>

                        <div class="mt-2 flex flex-wrap gap-2">
                            @foreach ([['PRIVATE', 'Privada', 'Solo tú.'], ['PUBLIC', 'Pública', 'Aparece en la comunidad.'], ['UNLISTED', 'No listada', 'Solo con el enlace.']] as [$valor, $titulo, $texto])
                                <button type="button" @click="visibility = '{{ $valor }}'; markDirty()"
                                    :aria-pressed="visibility === '{{ $valor }}'"
                                    :class="visibility === '{{ $valor }}' ?
                                        'border-sky-500/50 bg-sky-500/10 text-sky-300' :
                                        'border-slate-800 bg-slate-950 text-slate-400 hover:border-slate-700'"
                                    class="rounded-xl border px-3 py-2 text-left transition">
                                    <span class="block text-xs font-black">{{ $titulo }}</span>
                                    <span class="mt-0.5 block text-[10px] text-slate-500">{{ $texto }}</span>
                                </button>
                            @endforeach
                        </div>

                        @error('visibility')
                            <p class="mt-1.5 text-[11px] font-bold text-rose-300">{{ $message }}</p>
                        @enderror
                    </div>

                    <label
                        class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-800 bg-slate-950 p-3 transition hover:border-sky-500/40">
                        <input type="checkbox" name="allow_cloning" value="1" x-model="allowCloning"
                            class="mt-0.5 rounded border-slate-700 bg-slate-900 text-sky-500 focus:ring-sky-500">
                        <span>
                            <span class="block text-xs font-black text-white">Permitir que la copien</span>
                            <span class="mt-0.5 block text-[10px] leading-4 text-slate-500">
                                Solo tiene efecto cuando la plantilla está activa y es pública.
                            </span>
                        </span>
                    </label>

                </div>
            </section>

        </div>


        {{-- ===================================================== --}}
        {{-- DERECHA · LO QUE SE VERÁ --}}
        {{-- ===================================================== --}}

        <aside class="space-y-4 xl:sticky xl:top-4">

            <section class="rounded-2xl border border-slate-800 bg-slate-900/50 p-4">

                <p class="text-[9px] font-black uppercase tracking-wider text-slate-500">
                    Así se verá en la biblioteca
                </p>

                <article class="mt-3 overflow-hidden rounded-2xl border bg-slate-900/50" :class="tone('borde')">

                    <div class="relative aspect-[16/9] overflow-hidden bg-slate-950">
                        <template x-if="preview">
                            <img :src="preview" alt="" class="h-full w-full object-cover">
                        </template>

                        <template x-if="!preview">
                            <span class="flex h-full w-full items-center justify-center text-5xl opacity-20"
                                x-text="effectiveIcon()"></span>
                        </template>

                        <span
                            class="absolute left-2 top-2 flex items-center gap-1.5 rounded-lg border bg-slate-950/85 px-2 py-1"
                            :class="tone('borde')">
                            <span class="text-[11px]" x-text="effectiveIcon()"></span>
                            <span class="text-[9px] font-black uppercase tracking-wider" :class="tone('texto')"
                                x-text="categoryLabel()"></span>
                        </span>

                        <span
                            class="absolute right-2 top-2 rounded px-1.5 py-0.5 text-[8px] font-black uppercase tracking-wider"
                            :class="status === 'ACTIVE' ?
                                'bg-emerald-500/15 text-emerald-300' :
                                (status === 'DRAFT' ? 'bg-amber-500/15 text-amber-300' : 'bg-slate-800 text-slate-500')"
                            x-text="statusLabel()"></span>
                    </div>

                    <div class="p-3">
                        <p class="truncate text-[13px] font-black text-white" x-text="name || 'Torneo sin nombre'"></p>
                        <p class="font-mono text-[9px] text-slate-600">{{ $codigo }}</p>

                        <p class="mt-1.5 line-clamp-2 text-[11px] leading-relaxed text-slate-500"
                            x-text="summary || 'Sin descripción.'"></p>

                        <div class="mt-2.5 grid grid-cols-4 gap-1.5">
                            @foreach ([['Entra', 'text-cyan-300'], ['Fases', 'text-amber-300'], ['Enlaces', 'text-slate-300'], ['Finales', 'text-violet-300']] as [$etiqueta, $color])
                                <span class="rounded-lg border border-slate-800 bg-slate-950 px-2 py-1.5 text-center">
                                    <span class="block font-mono text-[13px] font-black {{ $color }}">
                                        {{ $editing ? '·' : '0' }}
                                    </span>
                                    <span class="block text-[8px] font-black uppercase tracking-wider text-slate-600">
                                        {{ $etiqueta }}
                                    </span>
                                </span>
                            @endforeach
                        </div>

                        <p class="mt-2 flex flex-wrap items-center gap-1.5 text-[10px]">
                            <span
                                class="rounded-lg border border-slate-800 bg-slate-950 px-2 py-1 font-bold text-slate-400"
                                x-text="contractLabel()"></span>

                            <template x-for="etiqueta in tags" :key="etiqueta">
                                <span
                                    class="rounded-lg border border-slate-800 bg-slate-950 px-2 py-1 font-bold text-slate-500"
                                    x-text="'#' + etiqueta"></span>
                            </template>
                        </p>
                    </div>

                </article>

                <p class="mt-2 text-[10px] leading-4 text-slate-600">
                    Las cifras del recorrido —entradas, fases, enlaces y finales— se cuentan solas
                    a partir de lo que montes en la Super Edición.
                </p>

            </section>


            {{-- Dónde se construye lo que aquí no está --}}

            @if ($editing)
                <section class="rounded-2xl border border-violet-500/40 bg-violet-500/10 p-4">
                    <p class="text-[9px] font-black uppercase tracking-wider text-violet-300">
                        Lo que no se decide aquí
                    </p>

                    <p class="mt-2 text-[11px] leading-4 text-slate-400">
                        El recorrido entero —por dónde entra la gente, qué fases atraviesa, cómo se
                        enlazan y en qué finales acaba— se construye en la Super Edición. Y quién
                        juega, cuándo y con qué premios se decide en cada edición del torneo, no en
                        esta plantilla.
                    </p>

                    <a href="{{ route('tournaments.super.show', $tournamentTemplate) }}"
                        class="mt-3 block rounded-xl border border-violet-500/40 bg-slate-950 px-4 py-2.5 text-center text-[11px] font-black text-white transition hover:bg-slate-900">
                        ⚙ Abrir la Super Edición
                    </a>
                </section>
            @else
                <section class="rounded-2xl border border-slate-800 bg-slate-900/50 p-4">
                    <p class="text-[9px] font-black uppercase tracking-wider text-slate-500">
                        Y después
                    </p>

                    <p class="mt-2 text-[11px] leading-4 text-slate-500">
                        En cuanto la plantilla exista se abre la Super Edición, que es donde se monta
                        el recorrido: las entradas, las fases que se encadenan, cómo se enlazan y en
                        qué finales acaba cada uno.
                    </p>
                </section>
            @endif

        </aside>

    </div>


    {{-- ===================================================== --}}
    {{-- LA BARRA DE GUARDAR --}}
    {{-- ===================================================== --}}

    <div
        class="sticky bottom-4 z-30 rounded-2xl border border-slate-800 bg-slate-950/95 shadow-2xl shadow-slate-950/60 backdrop-blur">

        <div class="flex flex-wrap items-center justify-between gap-3 px-4 py-3">

            <div class="min-h-4 text-[11px] font-bold">
                <span x-show="dirty && !submitting" x-cloak class="text-amber-300">
                    ● Hay cambios sin guardar
                </span>
                <span x-show="!dirty && !submitting" class="text-slate-600">
                    {{ $editing ? 'Sin cambios pendientes' : 'Rellena lo necesario y crea la plantilla' }}
                </span>
                <span x-show="submitting" x-cloak class="text-emerald-300">
                    Guardando…
                </span>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ $editing
                    ? route('tournaments.templates.show', $tournamentTemplate)
                    : route('tournaments.templates.index') }}"
                    class="rounded-xl border border-slate-800 px-4 py-2.5 text-[11px] font-black text-slate-400 transition hover:border-slate-600 hover:text-slate-200">
                    Cancelar
                </a>

                <button type="submit" :disabled="submitting"
                    class="rounded-xl bg-amber-500 px-5 py-2.5 text-[11px] font-black text-slate-950 transition hover:bg-amber-400 disabled:cursor-wait disabled:opacity-60">
                    <span x-show="!submitting">
                        {{ $editing ? 'Guardar cambios' : 'Crear la plantilla' }}
                    </span>
                    <span x-show="submitting" x-cloak>Guardando…</span>
                </button>
            </div>

        </div>

    </div>

</div>
