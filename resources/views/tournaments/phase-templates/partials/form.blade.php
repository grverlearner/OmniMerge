@php
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

    $engine = $editing
        ? match ($phaseTemplate->phase_type) {
            'SINGLE_ELIMINATION' => [
                'name' => 'Single Elimination Engine',
                'icon' => '⚔',
                'url' => route('tournaments.single-elimination.show', $phaseTemplate),
                'description' => 'Seeding, pairing, BYEs, reglas por ronda y estructura interna.',
                'box' => 'border-amber-200 bg-gradient-to-br from-amber-50 to-orange-50',
                'eyebrow' => 'text-amber-700',
                'title' => 'text-amber-950',
                'body' => 'text-amber-800/80',
                'button' => 'bg-amber-500 shadow-amber-500/20 hover:bg-amber-600',
            ],
            'ROUND_ROBIN' => [
                'name' => 'Round Robin Engine',
                'icon' => '↻',
                'url' => route('tournaments.round-robin.show', $phaseTemplate),
                'description' => 'Ciclos, calendario, puntuación, empates y criterios de desempate.',
                'box' => 'border-cyan-200 bg-gradient-to-br from-cyan-50 to-emerald-50',
                'eyebrow' => 'text-cyan-700',
                'title' => 'text-cyan-950',
                'body' => 'text-cyan-800/80',
                'button' => 'bg-cyan-600 shadow-cyan-600/20 hover:bg-cyan-700',
            ],
            'GROUP_STAGE' => [
                'name' => 'Group Stage Engine',
                'icon' => '▦',
                'url' => route('tournaments.group-stage.show', $phaseTemplate),
                'description' => 'Grupos, distribución, calendario interno y reglas de clasificación.',
                'box' => 'border-indigo-200 bg-gradient-to-br from-indigo-50 to-violet-50',
                'eyebrow' => 'text-indigo-700',
                'title' => 'text-indigo-950',
                'body' => 'text-indigo-800/80',
                'button' => 'bg-indigo-600 shadow-indigo-600/20 hover:bg-indigo-700',
            ],
            'SWISS' => [
                'name' => 'Swiss Engine',
                'icon' => '◆',
                'url' => route('tournaments.swiss.show', $phaseTemplate),
                'description' => 'Rondas, score groups, rematches, BYEs, scoring y desempates.',
                'box' => 'border-violet-200 bg-gradient-to-br from-violet-50 to-fuchsia-50',
                'eyebrow' => 'text-violet-700',
                'title' => 'text-violet-950',
                'body' => 'text-violet-800/80',
                'button' => 'bg-violet-600 shadow-violet-600/20 hover:bg-violet-700',
            ],
            default => null,
        }
        : null;

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
        'bestOf' => (int) old('best_of', $editing ? $phaseTemplate->best_of : 1),
        'status' => old('status', $editing ? $phaseTemplate->status : 'DRAFT'),
        'visibility' => old('visibility', $editing ? $phaseTemplate->visibility : 'PRIVATE'),
        'allowCloning' => (bool) old('allow_cloning', $editing ? $phaseTemplate->allow_cloning : true),
        'engine' => $engine,
    ];
@endphp

<div x-data="phaseTemplateDesigner(@js($designerConfig))" x-init="init()" @input="markDirty()" @change="markDirty()" class="space-y-6">

    <input type="hidden" name="remove_image" :value="removeImage ? 1 : 0">
    <input type="hidden" name="capacity_mode" :value="capacityMode">

    @if ($errors->any())
        <section class="rounded-2xl border border-red-200 bg-red-50 p-5" role="alert">
            <p class="text-sm font-black text-red-800">
                No se pudo guardar la Fase. Revisa estos campos:
            </p>

            <ul class="mt-3 list-disc space-y-1 pl-5 text-xs font-bold text-red-700">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </section>
    @endif

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_360px] xl:items-start">

        <div class="space-y-6">

            <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex items-start gap-4">
                    <div
                        class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-amber-50 font-black text-amber-700">
                        01
                    </div>

                    <div>
                        <p class="text-[10px] font-black uppercase tracking-[0.18em] text-amber-600">
                            Identidad
                        </p>
                        <h2 class="mt-1 text-xl font-black text-slate-900">
                            Información principal
                        </h2>
                        <p class="mt-1 text-sm leading-6 text-slate-500">
                            Lo que reconocerás en la Biblioteca y en el Tournament Builder.
                        </p>
                    </div>
                </div>

                <div class="mt-6 grid gap-7 lg:grid-cols-[210px_1fr]">

                    <div>
                        <div
                            class="aspect-square overflow-hidden rounded-3xl border border-dashed border-slate-300 bg-slate-50">
                            <template x-if="preview">
                                <img :src="preview" alt="Vista previa de la portada"
                                    class="h-full w-full object-cover">
                            </template>

                            <template x-if="!preview">
                                <div class="flex h-full flex-col items-center justify-center gap-2 text-center">
                                    <span class="text-4xl text-slate-300">◇</span>
                                    <span class="text-xs font-black text-slate-400">Sin portada</span>
                                </div>
                            </template>
                        </div>

                        <label
                            class="mt-3 block cursor-pointer rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-center text-xs font-black text-slate-700 transition hover:border-amber-300 hover:bg-amber-50">
                            Seleccionar imagen
                            <input type="file" name="image" accept="image/png,image/jpeg,image/webp"
                                @change="loadImage($event)" class="hidden">
                        </label>

                        <button type="button" x-show="preview" x-cloak @click="clearImage()"
                            class="mt-2 w-full rounded-xl bg-red-50 px-4 py-2.5 text-xs font-black text-red-600 transition hover:bg-red-100">
                            Quitar imagen
                        </button>

                        <x-input-error :messages="$errors->get('image')" class="mt-2" />
                    </div>

                    <div class="space-y-5">
                        <div>
                            <label for="phase-name" class="text-xs font-black uppercase tracking-wider text-slate-500">
                                Nombre *
                            </label>

                            <input id="phase-name" type="text" name="name" x-model="name" maxlength="150" required
                                placeholder="Ej. Eliminación directa principal"
                                class="mt-2 w-full rounded-xl border-slate-300 focus:border-amber-400 focus:ring-amber-400">

                            <x-input-error :messages="$errors->get('name')" class="mt-2" />
                        </div>

                        <div>
                            <label for="phase-description"
                                class="text-xs font-black uppercase tracking-wider text-slate-500">
                                Descripción
                            </label>

                            <textarea id="phase-description" name="description" rows="6" maxlength="5000"
                                placeholder="Describe el propósito de esta Fase..."
                                class="mt-2 w-full rounded-xl border-slate-300 focus:border-amber-400 focus:ring-amber-400">{{ old('description', $editing ? $phaseTemplate->description : '') }}</textarea>

                            <x-input-error :messages="$errors->get('description')" class="mt-2" />
                        </div>

                        <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4">
                            <p class="text-[9px] font-black uppercase tracking-wider text-amber-700">
                                Código OmniMerge
                            </p>
                            <p class="mt-1 font-mono text-sm font-black text-amber-950">
                                {{ $editing ? $phaseTemplate->code : $previewCode }}
                            </p>
                            <p class="mt-1 text-xs text-amber-800/70">
                                Se genera automáticamente y no depende del nombre.
                            </p>
                        </div>
                    </div>

                </div>
            </section>

            <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex items-start gap-4">
                    <div
                        class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-amber-50 font-black text-amber-700">
                        02
                    </div>

                    <div>
                        <p class="text-[10px] font-black uppercase tracking-[0.18em] text-amber-600">
                            Comportamiento
                        </p>
                        <h2 class="mt-1 text-xl font-black text-slate-900">
                            Tipo y participación
                        </h2>
                    </div>
                </div>

                <div class="mt-6 grid gap-5 md:grid-cols-2">
                    <div>
                        <label class="text-xs font-black uppercase tracking-wider text-slate-500">
                            Tipo de Fase *
                        </label>

                        @if ($editing)
                            <input type="hidden" name="phase_type" value="{{ $phaseTemplate->phase_type }}">

                            <div class="mt-2 rounded-2xl border border-amber-200 bg-amber-50 p-4">
                                <p class="text-sm font-black text-amber-950">
                                    {{ $phaseTemplate->type_label }}
                                </p>
                                <p class="mt-1 text-xs leading-5 text-amber-800/75">
                                    Bloqueado para no invalidar el Engine, sus reglas ni su estructura.
                                </p>
                            </div>
                        @else
                            <select name="phase_type" x-model="phaseType" required
                                class="mt-2 w-full rounded-xl border-slate-300 focus:border-amber-400 focus:ring-amber-400">
                                <option value="SINGLE_ELIMINATION">Eliminación directa</option>
                                <option value="ROUND_ROBIN">Todos contra todos</option>
                                <option value="GROUP_STAGE">Fase de grupos</option>
                                <option value="LEAGUE">Liga / División</option>
                                <option value="SWISS">Sistema suizo</option>
                                <option value="CUSTOM">Personalizada</option>
                            </select>
                        @endif

                        <x-input-error :messages="$errors->get('phase_type')" class="mt-2" />
                    </div>

                    <div>
                        <label for="participant-mode"
                            class="text-xs font-black uppercase tracking-wider text-slate-500">
                            Participación *
                        </label>

                        <select id="participant-mode" name="participant_mode" x-model="participantMode" required
                            class="mt-2 w-full rounded-xl border-slate-300 focus:border-amber-400 focus:ring-amber-400">
                            <option value="INDIVIDUAL">Individual</option>
                            <option value="TEAM">Equipos</option>
                            <option value="FLEXIBLE">Flexible</option>
                        </select>

                        <p class="mt-2 text-xs leading-5 text-slate-400">
                            Define qué clase de competidor podrá entrar a la Fase.
                        </p>

                        <x-input-error :messages="$errors->get('participant_mode')" class="mt-2" />
                    </div>
                </div>
            </section>

            <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex items-start gap-4">
                    <div
                        class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-cyan-50 font-black text-cyan-700">
                        03
                    </div>

                    <div>
                        <p class="text-[10px] font-black uppercase tracking-[0.18em] text-cyan-600">
                            Input Contract
                        </p>
                        <h2 class="mt-1 text-xl font-black text-slate-900">
                            Entrada de participantes
                        </h2>
                        <p class="mt-1 text-sm leading-6 text-slate-500">
                            Elige una forma simple de expresar la capacidad. El sistema la convierte
                            al contrato min/max/exact que consume el grafo.
                        </p>
                    </div>
                </div>

                <div class="mt-6 grid gap-3 md:grid-cols-3">
                    @foreach ([['value' => 'EXACT', 'title' => 'Exacta', 'description' => 'Solo acepta una cantidad concreta.'], ['value' => 'RANGE', 'title' => 'Por rango', 'description' => 'Acepta entre un mínimo y un máximo.'], ['value' => 'OPEN', 'title' => 'Abierta', 'description' => 'Acepta desde un mínimo, sin máximo.']] as $mode)
                        <button type="button" @click="chooseCapacityMode('{{ $mode['value'] }}')"
                            :aria-pressed="capacityMode === '{{ $mode['value'] }}'"
                            :class="capacityMode === '{{ $mode['value'] }}'
                                ?
                                'border-cyan-400 bg-cyan-50 ring-2 ring-cyan-100' :
                                'border-slate-200 bg-white hover:border-cyan-200 hover:bg-cyan-50/40'"
                            class="rounded-2xl border p-4 text-left transition">
                            <span class="block text-sm font-black text-slate-900">
                                {{ $mode['title'] }}
                            </span>
                            <span class="mt-1 block text-xs leading-5 text-slate-500">
                                {{ $mode['description'] }}
                            </span>
                        </button>
                    @endforeach
                </div>

                <x-input-error :messages="$errors->get('capacity_mode')" class="mt-3" />

                <div class="mt-5 rounded-2xl border border-slate-200 bg-slate-50 p-5">
                    <div x-show="capacityMode === 'EXACT'" x-cloak>
                        <label for="exact-participants"
                            class="text-xs font-black uppercase tracking-wider text-slate-500">
                            Cantidad exacta *
                        </label>
                        <input id="exact-participants" type="number" name="exact_participants"
                            x-model="exactParticipants" min="2" max="512"
                            :required="capacityMode === 'EXACT'"
                            class="mt-2 w-full max-w-xs rounded-xl border-slate-300 focus:border-cyan-400 focus:ring-cyan-400">
                    </div>

                    <div x-show="capacityMode === 'RANGE'" x-cloak class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="range-min" class="text-xs font-black uppercase tracking-wider text-slate-500">
                                Mínimo *
                            </label>
                            <input id="range-min" type="number" name="min_participants" x-model="minParticipants"
                                min="2" max="512" :required="capacityMode === 'RANGE'"
                                class="mt-2 w-full rounded-xl border-slate-300 focus:border-cyan-400 focus:ring-cyan-400">
                        </div>

                        <div>
                            <label for="range-max" class="text-xs font-black uppercase tracking-wider text-slate-500">
                                Máximo *
                            </label>
                            <input id="range-max" type="number" name="max_participants" x-model="maxParticipants"
                                min="2" max="512" :required="capacityMode === 'RANGE'"
                                class="mt-2 w-full rounded-xl border-slate-300 focus:border-cyan-400 focus:ring-cyan-400">
                        </div>
                    </div>

                    <div x-show="capacityMode === 'OPEN'" x-cloak>
                        <label for="open-min" class="text-xs font-black uppercase tracking-wider text-slate-500">
                            Mínimo *
                        </label>
                        <input id="open-min" type="number" name="min_participants" x-model="minParticipants"
                            min="2" max="512" :required="capacityMode === 'OPEN'"
                            class="mt-2 w-full max-w-xs rounded-xl border-slate-300 focus:border-cyan-400 focus:ring-cyan-400">
                        <p class="mt-2 text-xs text-slate-400">Sin límite máximo.</p>
                    </div>

                    <x-input-error :messages="$errors->get('min_participants')" class="mt-3" />
                    <x-input-error :messages="$errors->get('max_participants')" class="mt-1" />
                    <x-input-error :messages="$errors->get('exact_participants')" class="mt-1" />
                </div>

                <details class="mt-4 rounded-2xl border border-slate-200 bg-white p-4">
                    <summary class="cursor-pointer text-xs font-black text-slate-700">
                        Ajuste opcional de capacidad
                    </summary>

                    <div class="mt-4 max-w-xs">
                        <label for="participant-multiple"
                            class="text-xs font-black uppercase tracking-wider text-slate-500">
                            Requerir múltiplos de
                        </label>

                        <input id="participant-multiple" type="number" name="participant_multiple"
                            x-model="participantMultiple" min="2" max="512" placeholder="Ej. 4"
                            class="mt-2 w-full rounded-xl border-slate-300 focus:border-cyan-400 focus:ring-cyan-400">

                        <p class="mt-2 text-xs leading-5 text-slate-400">
                            Úsalo solo cuando la estructura necesite bloques completos.
                        </p>

                        <x-input-error :messages="$errors->get('participant_multiple')" class="mt-2" />
                    </div>
                </details>

                <label
                    class="mt-4 flex cursor-pointer items-start gap-3 rounded-2xl border border-slate-200 p-4 transition hover:border-amber-200 hover:bg-amber-50/40">
                    <input type="checkbox" name="allow_byes" value="1" x-model="allowByes"
                        class="mt-0.5 rounded border-slate-300 text-amber-500 focus:ring-amber-500">

                    <span>
                        <span class="block text-sm font-black text-slate-800">Permitir BYE</span>
                        <span class="mt-1 block text-xs leading-5 text-slate-500">
                            Autoriza avances automáticos cuando el Engine no completa su estructura ideal.
                        </span>
                    </span>
                </label>
            </section>

            <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex items-start gap-4">
                    <div
                        class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-violet-50 font-black text-violet-700">
                        04
                    </div>

                    <div>
                        <p class="text-[10px] font-black uppercase tracking-[0.18em] text-violet-600">
                            Base y publicación
                        </p>
                        <h2 class="mt-1 text-xl font-black text-slate-900">
                            Configuración inicial
                        </h2>
                    </div>
                </div>

                <div class="mt-6 grid gap-5 md:grid-cols-3">
                    <div>
                        <label for="best-of" class="text-xs font-black uppercase tracking-wider text-slate-500">
                            Best of
                        </label>
                        <select id="best-of" name="best_of" x-model.number="bestOf" required
                            class="mt-2 w-full rounded-xl border-slate-300 focus:border-violet-400 focus:ring-violet-400">
                            @foreach ([1, 3, 5, 7, 9] as $value)
                                <option value="{{ $value }}">Best of {{ $value }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('best_of')" class="mt-2" />
                    </div>

                    <div>
                        <label for="phase-status" class="text-xs font-black uppercase tracking-wider text-slate-500">
                            Estado
                        </label>
                        <select id="phase-status" name="status" x-model="status" required
                            class="mt-2 w-full rounded-xl border-slate-300 focus:border-violet-400 focus:ring-violet-400">
                            <option value="DRAFT">Borrador</option>
                            <option value="ACTIVE">Activa</option>
                            <option value="ARCHIVED">Archivada</option>
                        </select>
                        <x-input-error :messages="$errors->get('status')" class="mt-2" />
                    </div>

                    <div>
                        <label for="phase-visibility"
                            class="text-xs font-black uppercase tracking-wider text-slate-500">
                            Visibilidad
                        </label>
                        <select id="phase-visibility" name="visibility" x-model="visibility" required
                            class="mt-2 w-full rounded-xl border-slate-300 focus:border-violet-400 focus:ring-violet-400">
                            <option value="PRIVATE">Privada</option>
                            <option value="PUBLIC">Pública</option>
                            <option value="UNLISTED">No listada</option>
                        </select>
                        <x-input-error :messages="$errors->get('visibility')" class="mt-2" />
                    </div>
                </div>

                <label
                    class="mt-5 flex cursor-pointer items-start gap-3 rounded-2xl border border-violet-200 bg-violet-50/60 p-4">
                    <input type="checkbox" name="allow_cloning" value="1" x-model="allowCloning"
                        class="mt-0.5 rounded border-violet-300 text-violet-600 focus:ring-violet-500">
                    <span>
                        <span class="block text-sm font-black text-violet-900">
                            Permitir clonación cuando sea pública
                        </span>
                        <span class="mt-1 block text-xs leading-5 text-violet-700">
                            Solo tendrá efecto cuando la Fase esté activa y con visibilidad pública.
                        </span>
                    </span>
                </label>
            </section>

        </div>

        <aside class="space-y-5 xl:sticky xl:top-6">
            <section class="overflow-hidden rounded-3xl border border-slate-200 bg-slate-950 text-white shadow-xl">
                <div class="border-b border-white/10 bg-gradient-to-br from-amber-400/20 to-violet-500/10 p-6">
                    <p class="text-[10px] font-black uppercase tracking-[0.18em] text-amber-300">
                        Live Inspector
                    </p>
                    <h2 class="mt-2 break-words text-xl font-black" x-text="name || 'Fase sin nombre'"></h2>
                    <p class="mt-1 font-mono text-[10px] text-slate-400">
                        {{ $editing ? $phaseTemplate->code : $previewCode }}
                    </p>
                </div>

                <div class="space-y-3 p-6">
                    <div class="rounded-2xl bg-white/5 p-4">
                        <p class="text-[9px] font-black uppercase tracking-wider text-slate-500">Tipo</p>
                        <p class="mt-2 text-sm font-black" x-text="typeLabel()"></p>
                    </div>

                    <div class="rounded-2xl bg-white/5 p-4">
                        <p class="text-[9px] font-black uppercase tracking-wider text-slate-500">Participación</p>
                        <p class="mt-2 text-sm font-black" x-text="participantModeLabel()"></p>
                    </div>

                    <div class="rounded-2xl bg-white/5 p-4">
                        <p class="text-[9px] font-black uppercase tracking-wider text-slate-500">Contrato</p>
                        <p class="mt-2 text-sm font-black" x-text="contractLabel()"></p>
                        <p class="mt-1 text-xs text-slate-400" x-show="allowByes">BYE permitido</p>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="rounded-2xl bg-white/5 p-4">
                            <p class="text-[9px] font-black uppercase tracking-wider text-slate-500">Formato</p>
                            <p class="mt-2 text-sm font-black">BO<span x-text="bestOf"></span></p>
                        </div>

                        <div class="rounded-2xl bg-white/5 p-4">
                            <p class="text-[9px] font-black uppercase tracking-wider text-slate-500">Publicación</p>
                            <p class="mt-2 text-xs font-black" x-text="statusLabel()"></p>
                            <p class="mt-1 text-[10px] text-slate-400" x-text="visibilityLabel()"></p>
                        </div>
                    </div>
                </div>
            </section>

            @if ($engine)
                <section class="rounded-3xl border p-5 {{ $engine['box'] }}">
                    <p class="text-[9px] font-black uppercase tracking-wider {{ $engine['eyebrow'] }}">
                        {{ $engine['name'] }}
                    </p>
                    <h3 class="mt-2 text-sm font-black {{ $engine['title'] }}">
                        {{ $engine['icon'] }} Configuración avanzada separada
                    </h3>
                    <p class="mt-2 text-xs leading-5 {{ $engine['body'] }}">
                        {{ $engine['description'] }}
                    </p>
                    <a href="{{ $engine['url'] }}"
                        class="mt-4 block rounded-xl px-4 py-3 text-center text-xs font-black text-white shadow-lg transition {{ $engine['button'] }}">
                        {{ $engine['icon'] }} Abrir Engine
                    </a>
                </section>
            @else
                <section class="rounded-3xl border border-slate-200 bg-slate-50 p-5">
                    <p class="text-[9px] font-black uppercase tracking-wider text-slate-400">
                        Separación de responsabilidades
                    </p>
                    <p class="mt-2 text-xs leading-5 text-slate-600">
                        Este formulario solo define la Fase. Brackets, calendarios, grupos,
                        emparejamientos y reglas avanzadas pertenecen al Engine.
                    </p>
                </section>
            @endif
        </aside>

    </div>

    <div
        class="sticky bottom-4 z-20 flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white/95 p-4 shadow-2xl shadow-slate-900/10 backdrop-blur sm:flex-row sm:items-center sm:justify-between">

        <div class="min-h-5 text-xs font-bold">
            <span x-show="dirty && !submitting" x-cloak class="text-amber-700">
                ● Hay cambios sin guardar
            </span>
            <span x-show="!dirty && !submitting" class="text-slate-400">
                Todo listo para continuar
            </span>
            <span x-show="submitting" x-cloak class="text-emerald-700">
                Guardando la Fase…
            </span>
        </div>

        <div class="flex flex-col-reverse gap-3 sm:flex-row">
            <a href="{{ $editing
                ? route('tournaments.phase-templates.show', $phaseTemplate)
                : route('tournaments.phase-templates.index') }}"
                class="rounded-xl border border-slate-200 bg-white px-5 py-3 text-center text-sm font-black text-slate-600 transition hover:bg-slate-50">
                Cancelar
            </a>

            <button type="submit" :disabled="submitting"
                class="rounded-xl bg-amber-500 px-6 py-3 text-sm font-black text-white shadow-lg shadow-amber-500/20 transition hover:bg-amber-600 disabled:cursor-wait disabled:opacity-60">
                <span x-show="!submitting">
                    {{ $editing ? 'Guardar cambios' : 'Crear Fase' }}
                </span>
                <span x-show="submitting" x-cloak>Guardando…</span>
            </button>
        </div>

    </div>

</div>
