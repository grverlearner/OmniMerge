@php
    /*
     * La definición de una fase.
     *
     * Esta pantalla responde a QUÉ es la fase y CÓMO se reconoce; no a cómo
     * se juega. Todo lo que decide el juego —seeding, emparejamientos,
     * calendario, desempates, salidas— vive en la Super Edición, y por eso
     * aquí ya no está el BEST OF: cuántos juegos tiene un enfrentamiento se
     * decide en el torneo que se juega, no en la plantilla que lo describe.
     *
     * Lo que sí se añadió es la presentación —icono, color y una frase—,
     * porque una biblioteca de cuarenta fases no se recorre leyendo nombres.
     * Se guarda en `settings` y no lo lee ningún motor: solo la biblioteca.
     *
     * A la derecha, la vista previa enseña la ficha EXACTA que se verá en la
     * biblioteca. Es deliberado: elegir un color a ciegas y descubrir el
     * resultado en otra pantalla es lo que hacía que nadie los cambiara.
     */

    $editing = isset($phaseTemplate);

    $removeImage = (bool) old('remove_image', false);

    $currentImage = $editing && !$removeImage ? $phaseTemplate->image_url : null;

    $capacityMode = old('capacity_mode');

    if (!$capacityMode) {
        $capacityMode = $editing
            ? ($phaseTemplate->exact_participants !== null
                ? 'EXACT'
                : ($phaseTemplate->max_participants !== null
                    ? 'RANGE'
                    : 'OPEN'))
            : 'OPEN';
    }

    $phaseType = $editing ? $phaseTemplate->phase_type : old('phase_type', 'SINGLE_ELIMINATION');

    /*
     * Las clases van literales, nunca compuestas: Tailwind lee este archivo
     * y una clase armada con 'border-' . $color no existiría en el CSS. Por
     * eso el mapa viaja entero al cliente y allí solo se ELIGE, no se arma.
     */
    $tonos = [
        'amber' => [
            'borde' => 'border-amber-500/40',
            'texto' => 'text-amber-300',
            'fondo' => 'bg-amber-500/10',
        ],
        'violet' => [
            'borde' => 'border-violet-500/40',
            'texto' => 'text-violet-300',
            'fondo' => 'bg-violet-500/10',
        ],
        'cyan' => [
            'borde' => 'border-cyan-500/40',
            'texto' => 'text-cyan-300',
            'fondo' => 'bg-cyan-500/10',
        ],
        'emerald' => [
            'borde' => 'border-emerald-500/40',
            'texto' => 'text-emerald-300',
            'fondo' => 'bg-emerald-500/10',
        ],
        'rose' => [
            'borde' => 'border-rose-500/40',
            'texto' => 'text-rose-300',
            'fondo' => 'bg-rose-500/10',
        ],
        'sky' => [
            'borde' => 'border-sky-500/40',
            'texto' => 'text-sky-300',
            'fondo' => 'bg-sky-500/10',
        ],
        'slate' => [
            'borde' => 'border-slate-700',
            'texto' => 'text-slate-300',
            'fondo' => 'bg-slate-800/60',
        ],
    ];

    /* El punto de color del selector, suelto, para que Tailwind lo vea */
    $puntos = [
        'amber' => 'bg-amber-400',
        'violet' => 'bg-violet-400',
        'cyan' => 'bg-cyan-400',
        'emerald' => 'bg-emerald-400',
        'rose' => 'bg-rose-400',
        'sky' => 'bg-sky-400',
        'slate' => 'bg-slate-500',
    ];

    /*
     * Los cuatro motores que existen de verdad. LEAGUE y CUSTOM tienen
     * etiqueta en el modelo pero no tienen motor, así que aquí se explican
     * apagados en vez de ofrecerse y fallar después.
     */
    $motores = [
        'SINGLE_ELIMINATION' => [
            'label' => 'Eliminación directa',
            'icono' => '🏆',
            'color' => 'amber',
            'resumen' => 'Quien pierde, se va. El cuadro se parte por la mitad en cada ronda.',
            'reglas' => 'Seeding, emparejamientos, BYEs, reglas por ronda y estructura interna.',
            'ruta' => 'tournaments.single-elimination.show',
        ],
        'ROUND_ROBIN' => [
            'label' => 'Todos contra todos',
            'icono' => '🔄',
            'color' => 'cyan',
            'resumen' => 'Cada uno se enfrenta a cada uno. Gana quien más suma al final.',
            'reglas' => 'Ciclos, calendario, puntuación, empates y criterios de desempate.',
            'ruta' => 'tournaments.round-robin.show',
        ],
        'GROUP_STAGE' => [
            'label' => 'Fase de grupos',
            'icono' => '▦',
            'color' => 'violet',
            'resumen' => 'Varios grupos pequeños en paralelo; de cada uno pasan los mejores.',
            'reglas' => 'Grupos, distribución, calendario interno y reglas de clasificación.',
            'ruta' => 'tournaments.group-stage.show',
        ],
        'SWISS' => [
            'label' => 'Sistema suizo',
            'icono' => '⇄',
            'color' => 'emerald',
            'resumen' => 'Nadie se elimina: cada ronda te empareja con quien va como tú.',
            'reglas' => 'Rondas, score groups, revanchas, BYEs, puntuación y desempates.',
            'ruta' => 'tournaments.swiss.show',
        ],
    ];

    $motorActual = $motores[$phaseType] ?? null;

    $engineUrl =
        $editing && $motorActual && Route::has($motorActual['ruta'])
            ? route($motorActual['ruta'], $phaseTemplate)
            : null;

    /* Iconos sugeridos: atajo, no jaula. El campo admite cualquier cosa. */
    $iconosSugeridos = ['🏆', '🔄', '▦', '⇄', '⚔', '🥇', '🎯', '🔥', '⭐', '👑', '🎲', '◆'];

    $designerConfig = [
        'editing' => $editing,
        'currentImage' => $currentImage,
        'removeImage' => $removeImage,
        'name' => old('name', $editing ? $phaseTemplate->name : ''),
        'phaseType' => $phaseType,
        'participantMode' => old('participant_mode', $editing ? $phaseTemplate->participant_mode : 'INDIVIDUAL'),
        'capacityMode' => $capacityMode,
        'minParticipants' => old('min_participants', $editing ? $phaseTemplate->min_participants : 2),
        'maxParticipants' => old('max_participants', $editing ? $phaseTemplate->max_participants : ''),
        'exactParticipants' => old('exact_participants', $editing ? $phaseTemplate->exact_participants : ''),
        'participantMultiple' => old('participant_multiple', $editing ? $phaseTemplate->participant_multiple : ''),
        'allowByes' => (bool) old('allow_byes', $editing ? $phaseTemplate->allow_byes : false),
        'icon' => old('icon', $editing ? (string) data_get($phaseTemplate->settings, 'icon', '') : ''),
        'accent' => old('accent', $editing ? (string) data_get($phaseTemplate->settings, 'accent', '') : ''),
        'summary' => old('summary', $editing ? (string) $phaseTemplate->summary : ''),
        'status' => old('status', $editing ? $phaseTemplate->status : 'DRAFT'),
        'visibility' => old('visibility', $editing ? $phaseTemplate->visibility : 'PRIVATE'),
        'allowCloning' => (bool) old('allow_cloning', $editing ? $phaseTemplate->allow_cloning : true),
        'tones' => $tonos,
    ];

    $codigo = $editing ? $phaseTemplate->code : $previewCode;
@endphp

<div x-data="phaseTemplateDesigner(@js($designerConfig))" x-init="init()" @input="markDirty()" @change="markDirty()"
    class="space-y-5 pb-4">

    <input type="hidden" name="remove_image" :value="removeImage ? 1 : 0">
    <input type="hidden" name="capacity_mode" :value="capacityMode">
    <input type="hidden" name="participant_mode" :value="participantMode">
    <input type="hidden" name="accent" :value="accent">
    <input type="hidden" name="status" :value="status">
    <input type="hidden" name="visibility" :value="visibility">

    @if ($errors->any())
        <section class="rounded-2xl border border-rose-500/40 bg-rose-500/10 p-4" role="alert">
            <p class="text-xs font-black uppercase tracking-wider text-rose-300">
                No se pudo guardar la fase
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

                    {{-- La portada --}}
                    <div>
                        <p class="mb-2 text-[9px] font-black uppercase tracking-wider text-slate-500">
                            Portada
                        </p>

                        <div
                            class="relative aspect-[16/10] overflow-hidden rounded-xl border border-dashed border-slate-700 bg-slate-950">
                            <template x-if="preview">
                                <img :src="preview" alt="Portada de la fase" class="h-full w-full object-cover">
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

                    {{-- Nombre, frase y descripción --}}
                    <div class="space-y-4">

                        <div>
                            <label for="phase-name"
                                class="text-[9px] font-black uppercase tracking-wider text-slate-500">
                                Nombre *
                            </label>

                            <input id="phase-name" type="text" name="name" x-model="name" maxlength="150" required
                                placeholder="Ej. Eliminación directa principal"
                                class="mt-1.5 w-full rounded-xl border-slate-800 bg-slate-950 text-sm font-bold text-white placeholder:text-slate-700 focus:border-amber-500 focus:ring-amber-500">

                            @error('name')
                                <p class="mt-1.5 text-[11px] font-bold text-rose-300">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <div class="flex items-baseline justify-between">
                                <label for="phase-summary"
                                    class="text-[9px] font-black uppercase tracking-wider text-slate-500">
                                    Frase corta
                                </label>
                                <span class="font-mono text-[9px] text-slate-600">
                                    <span x-text="summary.length"></span>/120
                                </span>
                            </div>

                            <input id="phase-summary" type="text" name="summary" x-model="summary" maxlength="120"
                                placeholder="Lo que esta fase hace, en una línea"
                                class="mt-1.5 w-full rounded-xl border-slate-800 bg-slate-950 text-xs text-slate-200 placeholder:text-slate-700 focus:border-amber-500 focus:ring-amber-500">

                            <p class="mt-1.5 text-[10px] leading-4 text-slate-600">
                                Es lo que se lee en la ficha de la biblioteca. Si la dejas vacía se
                                enseña la descripción.
                            </p>

                            @error('summary')
                                <p class="mt-1.5 text-[11px] font-bold text-rose-300">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="phase-description"
                                class="text-[9px] font-black uppercase tracking-wider text-slate-500">
                                Descripción
                            </label>

                            <textarea id="phase-description" name="description" rows="4" maxlength="5000"
                                placeholder="Para qué sirve esta fase, cuándo usarla..."
                                class="mt-1.5 w-full rounded-xl border-slate-800 bg-slate-950 text-xs leading-relaxed text-slate-300 placeholder:text-slate-700 focus:border-amber-500 focus:ring-amber-500">{{ old('description', $editing ? $phaseTemplate->description : '') }}</textarea>

                            @error('description')
                                <p class="mt-1.5 text-[11px] font-bold text-rose-300">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex flex-wrap items-center gap-2 rounded-xl border border-slate-800 bg-slate-950 px-3 py-2">
                            <span class="text-[9px] font-black uppercase tracking-wider text-slate-600">Código</span>
                            <span class="font-mono text-xs font-black text-amber-300">{{ $codigo }}</span>
                            <span class="text-[9px] text-slate-600">se genera solo, no depende del nombre</span>
                        </div>

                    </div>

                </div>
            </section>


            {{-- ============ 02 · CÓMO SE RECONOCE ============ --}}

            <section class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

                <header class="flex items-center gap-3 border-b border-slate-800 px-5 py-3">
                    <span
                        class="flex h-8 w-8 items-center justify-center rounded-lg bg-sky-500/15 text-[11px] font-black text-sky-300">
                        02
                    </span>
                    <div>
                        <h2 class="text-sm font-black text-white">Cómo se reconoce</h2>
                        <p class="text-[10px] text-slate-500">
                            Un icono y un color. No cambian nada de cómo se juega.
                        </p>
                    </div>
                </header>

                <div class="space-y-5 p-5">

                    {{-- El icono --}}
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

                    {{-- El color --}}
                    <div>
                        <p class="text-[9px] font-black uppercase tracking-wider text-slate-500">Color</p>

                        <div class="mt-2 flex flex-wrap items-center gap-1.5">

                            <button type="button" @click="accent = ''; markDirty()"
                                :class="accent === '' ?
                                    'border-amber-500 bg-amber-500/15 text-amber-300' :
                                    'border-slate-800 bg-slate-950 text-slate-500 hover:border-slate-600'"
                                class="rounded-lg border px-2.5 py-1.5 text-[10px] font-black transition">
                                El de su motor
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


            {{-- ============ 03 · MOTOR Y PARTICIPACIÓN ============ --}}

            <section class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

                <header class="flex items-center gap-3 border-b border-slate-800 px-5 py-3">
                    <span
                        class="flex h-8 w-8 items-center justify-center rounded-lg bg-violet-500/15 text-[11px] font-black text-violet-300">
                        03
                    </span>
                    <div>
                        <h2 class="text-sm font-black text-white">Motor y participación</h2>
                        <p class="text-[10px] text-slate-500">Qué clase de fase es y quién compite en ella.</p>
                    </div>
                </header>

                <div class="space-y-5 p-5">

                    {{-- El motor --}}
                    <div>
                        <p class="text-[9px] font-black uppercase tracking-wider text-slate-500">
                            Tipo de fase *
                        </p>

                        @if ($editing)
                            @php $tonoMotor = $tonos[$motorActual['color'] ?? 'slate']; @endphp

                            <input type="hidden" name="phase_type" value="{{ $phaseTemplate->phase_type }}">

                            <div
                                class="mt-2 flex items-start gap-3 rounded-xl border {{ $tonoMotor['borde'] }} {{ $tonoMotor['fondo'] }} p-4">
                                <span class="text-2xl">{{ $motorActual['icono'] ?? '◇' }}</span>
                                <div>
                                    <p class="text-sm font-black {{ $tonoMotor['texto'] }}">
                                        {{ $phaseTemplate->type_label }}
                                    </p>
                                    <p class="mt-1 text-[11px] leading-4 text-slate-400">
                                        {{ $motorActual['resumen'] ?? '' }}
                                    </p>
                                    <p class="mt-1.5 text-[10px] text-slate-600">
                                        El motor queda bloqueado: cambiarlo invalidaría las reglas y
                                        la estructura ya configuradas.
                                    </p>
                                </div>
                            </div>
                        @else
                            <input type="hidden" name="phase_type" :value="phaseType">

                            <div class="mt-2 grid gap-2 sm:grid-cols-2">
                                @foreach ($motores as $clave => $motor)
                                    @php $tonoMotor = $tonos[$motor['color']]; @endphp

                                    <button type="button" @click="phaseType = '{{ $clave }}'; markDirty()"
                                        :aria-pressed="phaseType === '{{ $clave }}'"
                                        :class="phaseType === '{{ $clave }}' ?
                                            '{{ $tonoMotor['borde'] }} {{ $tonoMotor['fondo'] }}' :
                                            'border-slate-800 bg-slate-950 hover:border-slate-700'"
                                        class="rounded-xl border p-3 text-left transition">
                                        <span class="flex items-center gap-2">
                                            <span class="text-lg">{{ $motor['icono'] }}</span>
                                            <span class="text-xs font-black {{ $tonoMotor['texto'] }}">
                                                {{ $motor['label'] }}
                                            </span>
                                        </span>
                                        <span class="mt-1 block text-[10px] leading-4 text-slate-500">
                                            {{ $motor['resumen'] }}
                                        </span>
                                    </button>
                                @endforeach

                                <div class="rounded-xl border border-dashed border-slate-800 p-3 sm:col-span-2">
                                    <p class="text-[10px] font-bold text-slate-600">
                                        Liga / División y Personalizada todavía no tienen motor: se
                                        ofrecerán cuando lo tengan.
                                    </p>
                                </div>
                            </div>
                        @endif

                        @error('phase_type')
                            <p class="mt-1.5 text-[11px] font-bold text-rose-300">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Quién compite --}}
                    <div>
                        <p class="text-[9px] font-black uppercase tracking-wider text-slate-500">
                            Quién compite *
                        </p>

                        <div class="mt-2 grid gap-2 sm:grid-cols-3">
                            @foreach ([['INDIVIDUAL', 'Individual', 'Compite uno contra uno.'], ['TEAM', 'Equipos', 'Compiten grupos de participantes.'], ['FLEXIBLE', 'Flexible', 'Acepta cualquiera de los dos.']] as [$valor, $titulo, $texto])
                                <button type="button" @click="participantMode = '{{ $valor }}'; markDirty()"
                                    :aria-pressed="participantMode === '{{ $valor }}'"
                                    :class="participantMode === '{{ $valor }}' ?
                                        'border-violet-500/50 bg-violet-500/10' :
                                        'border-slate-800 bg-slate-950 hover:border-slate-700'"
                                    class="rounded-xl border p-3 text-left transition">
                                    <span class="block text-xs font-black text-white">{{ $titulo }}</span>
                                    <span class="mt-0.5 block text-[10px] leading-4 text-slate-500">{{ $texto }}</span>
                                </button>
                            @endforeach
                        </div>

                        @error('participant_mode')
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
                            El contrato que el grafo del torneo tiene que poder cumplir.
                        </p>
                    </div>
                </header>

                <div class="space-y-4 p-5">

                    <div class="grid gap-2 sm:grid-cols-3">
                        @foreach ([['EXACT', 'Exacta', 'Solo una cantidad concreta.'], ['RANGE', 'Por rango', 'Entre un mínimo y un máximo.'], ['OPEN', 'Abierta', 'Desde un mínimo, sin techo.']] as [$valor, $titulo, $texto])
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

                    <div class="rounded-xl border border-slate-800 bg-slate-950 p-4">

                        <div x-show="capacityMode === 'EXACT'" x-cloak>
                            <label for="exact-participants"
                                class="text-[9px] font-black uppercase tracking-wider text-slate-500">
                                Cantidad exacta *
                            </label>
                            <input id="exact-participants" type="number" name="exact_participants"
                                x-model="exactParticipants" min="2" max="512" :required="capacityMode === 'EXACT'"
                                class="mt-1.5 w-full max-w-[160px] rounded-xl border-slate-800 bg-slate-900 text-sm font-black text-white focus:border-cyan-500 focus:ring-cyan-500">
                        </div>

                        <div x-show="capacityMode === 'RANGE'" x-cloak class="grid gap-3 sm:grid-cols-2">
                            <div>
                                <label for="range-min"
                                    class="text-[9px] font-black uppercase tracking-wider text-slate-500">
                                    Mínimo *
                                </label>
                                <input id="range-min" type="number" name="min_participants" x-model="minParticipants"
                                    min="2" max="512" :required="capacityMode === 'RANGE'"
                                    class="mt-1.5 w-full rounded-xl border-slate-800 bg-slate-900 text-sm font-black text-white focus:border-cyan-500 focus:ring-cyan-500">
                            </div>

                            <div>
                                <label for="range-max"
                                    class="text-[9px] font-black uppercase tracking-wider text-slate-500">
                                    Máximo *
                                </label>
                                <input id="range-max" type="number" name="max_participants" x-model="maxParticipants"
                                    min="2" max="512" :required="capacityMode === 'RANGE'"
                                    class="mt-1.5 w-full rounded-xl border-slate-800 bg-slate-900 text-sm font-black text-white focus:border-cyan-500 focus:ring-cyan-500">
                            </div>
                        </div>

                        <div x-show="capacityMode === 'OPEN'" x-cloak>
                            <label for="open-min" class="text-[9px] font-black uppercase tracking-wider text-slate-500">
                                Mínimo *
                            </label>
                            <input id="open-min" type="number" name="min_participants" x-model="minParticipants"
                                min="2" max="512" :required="capacityMode === 'OPEN'"
                                class="mt-1.5 w-full max-w-[160px] rounded-xl border-slate-800 bg-slate-900 text-sm font-black text-white focus:border-cyan-500 focus:ring-cyan-500">
                            <p class="mt-1.5 text-[10px] text-slate-600">Sin límite máximo.</p>
                        </div>

                        @error('min_participants')
                            <p class="mt-2 text-[11px] font-bold text-rose-300">{{ $message }}</p>
                        @enderror
                        @error('max_participants')
                            <p class="mt-1 text-[11px] font-bold text-rose-300">{{ $message }}</p>
                        @enderror
                        @error('exact_participants')
                            <p class="mt-1 text-[11px] font-bold text-rose-300">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2">

                        <div class="rounded-xl border border-slate-800 bg-slate-950 p-3">
                            <label for="participant-multiple"
                                class="text-[9px] font-black uppercase tracking-wider text-slate-500">
                                Requerir múltiplos de
                            </label>

                            <input id="participant-multiple" type="number" name="participant_multiple"
                                x-model="participantMultiple" min="2" max="512" placeholder="—"
                                class="mt-1.5 w-full max-w-[120px] rounded-xl border-slate-800 bg-slate-900 text-sm font-black text-white placeholder:text-slate-700 focus:border-cyan-500 focus:ring-cyan-500">

                            <p class="mt-1.5 text-[10px] leading-4 text-slate-600">
                                Solo si la estructura necesita bloques completos —grupos de 4, por ejemplo—.
                            </p>

                            @error('participant_multiple')
                                <p class="mt-1.5 text-[11px] font-bold text-rose-300">{{ $message }}</p>
                            @enderror
                        </div>

                        <label
                            class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-800 bg-slate-950 p-3 transition hover:border-amber-500/40">
                            <input type="checkbox" name="allow_byes" value="1" x-model="allowByes"
                                class="mt-0.5 rounded border-slate-700 bg-slate-900 text-amber-500 focus:ring-amber-500">
                            <span>
                                <span class="block text-xs font-black text-white">Permitir BYE</span>
                                <span class="mt-0.5 block text-[10px] leading-4 text-slate-500">
                                    Si no llega gente suficiente para llenar la estructura, alguien
                                    avanza sin jugar en vez de bloquearse la fase.
                                </span>
                            </span>
                        </label>

                    </div>

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
                            @foreach ([['DRAFT', 'Borrador', 'Se está montando.'], ['ACTIVE', 'Activa', 'Lista para usarse en torneos.'], ['ARCHIVED', 'Archivada', 'Fuera de circulación.']] as [$valor, $titulo, $texto])
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
                                Solo tiene efecto cuando la fase está activa y es pública.
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

            {{-- La ficha, tal cual saldrá en la biblioteca --}}

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
                                x-text="typeLabel()"></span>
                        </span>

                        <span
                            class="absolute right-2 top-2 rounded px-1.5 py-0.5 text-[8px] font-black uppercase tracking-wider"
                            :class="status === 'ACTIVE' ?
                                'bg-emerald-500/15 text-emerald-300' :
                                (status === 'DRAFT' ? 'bg-amber-500/15 text-amber-300' : 'bg-slate-800 text-slate-500')"
                            x-text="statusLabel()"></span>
                    </div>

                    <div class="p-3">
                        <p class="truncate text-[13px] font-black text-white" x-text="name || 'Fase sin nombre'"></p>
                        <p class="font-mono text-[9px] text-slate-600">{{ $codigo }}</p>

                        <p class="mt-1.5 line-clamp-2 text-[11px] leading-relaxed text-slate-500"
                            x-text="summary || 'Sin descripción.'"></p>

                        <div class="mt-2.5 flex flex-wrap gap-1.5">
                            <span
                                class="rounded-lg border border-slate-800 bg-slate-950 px-2 py-1 text-[9px] font-bold text-slate-400"
                                x-text="participantModeLabel()"></span>

                            <span
                                class="rounded-lg border border-slate-800 bg-slate-950 px-2 py-1 text-[9px] font-bold text-slate-400"
                                x-text="contractLabel()"></span>
                        </div>
                    </div>

                </article>

                <p class="mt-2 text-[10px] leading-4 text-slate-600">
                    Las entradas y salidas que la ficha cuenta se crean en la Super Edición,
                    no aquí.
                </p>

            </section>


            {{-- El contrato, en palabras --}}

            <section class="rounded-2xl border border-slate-800 bg-slate-900/50 p-4">

                <p class="text-[9px] font-black uppercase tracking-wider text-slate-500">Contrato</p>

                <dl class="mt-2.5 space-y-2 text-[11px]">
                    <div class="flex items-baseline justify-between gap-3">
                        <dt class="text-slate-600">Motor</dt>
                        <dd class="font-black text-slate-200" x-text="typeLabel()"></dd>
                    </div>

                    <div class="flex items-baseline justify-between gap-3">
                        <dt class="text-slate-600">Compiten</dt>
                        <dd class="font-black text-slate-200" x-text="participantModeLabel()"></dd>
                    </div>

                    <div class="flex items-baseline justify-between gap-3">
                        <dt class="text-slate-600">Capacidad</dt>
                        <dd class="font-black text-slate-200" x-text="contractLabel()"></dd>
                    </div>

                    <div class="flex items-baseline justify-between gap-3">
                        <dt class="text-slate-600">BYE</dt>
                        <dd class="font-black" :class="allowByes ? 'text-amber-300' : 'text-slate-500'"
                            x-text="allowByes ? 'Permitido' : 'No'"></dd>
                    </div>

                    <div class="flex items-baseline justify-between gap-3">
                        <dt class="text-slate-600">Publicación</dt>
                        <dd class="text-right">
                            <span class="block font-black text-slate-200" x-text="statusLabel()"></span>
                            <span class="text-[10px] text-slate-500" x-text="visibilityLabel()"></span>
                        </dd>
                    </div>
                </dl>

            </section>


            {{-- Dónde se configura lo que aquí no está --}}

            @if ($engineUrl)
                @php $tonoMotor = $tonos[$motorActual['color']]; @endphp

                <section class="rounded-2xl border {{ $tonoMotor['borde'] }} {{ $tonoMotor['fondo'] }} p-4">
                    <p class="text-[9px] font-black uppercase tracking-wider {{ $tonoMotor['texto'] }}">
                        Lo que no se decide aquí
                    </p>

                    <p class="mt-2 text-[11px] leading-4 text-slate-400">
                        {{ $motorActual['reglas'] }} Y el formato de los enfrentamientos —cuántos
                        juegos tiene cada uno— se decide en el torneo que se juega, no en esta
                        plantilla.
                    </p>

                    <a href="{{ $engineUrl }}"
                        class="mt-3 block rounded-xl border {{ $tonoMotor['borde'] }} bg-slate-950 px-4 py-2.5 text-center text-[11px] font-black text-white transition hover:bg-slate-900">
                        {{ $motorActual['icono'] }} Abrir sus reglas
                    </a>
                </section>
            @else
                <section class="rounded-2xl border border-slate-800 bg-slate-900/50 p-4">
                    <p class="text-[9px] font-black uppercase tracking-wider text-slate-500">
                        Lo que no se decide aquí
                    </p>

                    <p class="mt-2 text-[11px] leading-4 text-slate-500">
                        Emparejamientos, calendario, grupos, desempates y salidas se configuran en
                        la Super Edición, que se abre en cuanto la fase existe. Y el formato de los
                        enfrentamientos se decide en el torneo que se juega.
                    </p>
                </section>
            @endif

        </aside>

    </div>


    {{-- ===================================================== --}}
    {{-- LA BARRA DE GUARDAR --}}
    {{-- ===================================================== --}}

    {{--
        Pegada abajo, pero DENTRO del contenido y no a la ventana: con el
        sidebar delante, una barra fija a la ventana empezaría debajo de él.
    --}}

    <div class="sticky bottom-4 z-30 rounded-2xl border border-slate-800 bg-slate-950/95 shadow-2xl shadow-slate-950/60 backdrop-blur">

        <div class="flex flex-wrap items-center justify-between gap-3 px-4 py-3">

            <div class="min-h-4 text-[11px] font-bold">
                <span x-show="dirty && !submitting" x-cloak class="text-amber-300">
                    ● Hay cambios sin guardar
                </span>
                <span x-show="!dirty && !submitting" class="text-slate-600">
                    {{ $editing ? 'Sin cambios pendientes' : 'Rellena lo necesario y crea la fase' }}
                </span>
                <span x-show="submitting" x-cloak class="text-emerald-300">
                    Guardando…
                </span>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ $editing
                    ? route('tournaments.phase-templates.show', $phaseTemplate)
                    : route('tournaments.phase-templates.index') }}"
                    class="rounded-xl border border-slate-800 px-4 py-2.5 text-[11px] font-black text-slate-400 transition hover:border-slate-600 hover:text-slate-200">
                    Cancelar
                </a>

                <button type="submit" :disabled="submitting"
                    class="rounded-xl bg-amber-500 px-5 py-2.5 text-[11px] font-black text-slate-950 transition hover:bg-amber-400 disabled:cursor-wait disabled:opacity-60">
                    <span x-show="!submitting">
                        {{ $editing ? 'Guardar cambios' : 'Crear la fase' }}
                    </span>
                    <span x-show="submitting" x-cloak>Guardando…</span>
                </button>
            </div>

        </div>

    </div>

</div>
