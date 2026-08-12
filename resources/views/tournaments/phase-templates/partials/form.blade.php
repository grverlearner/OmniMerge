@php
    $editing = isset($phaseTemplate);

    $currentImage = $editing ? $phaseTemplate->image_url : null;
@endphp

<div x-data="{
    preview: @js($currentImage),
    removeImage: false,

    loadImage(event) {
        const file = event.target.files[0];

        if (!file) return;

        this.preview = URL.createObjectURL(file);
        this.removeImage = false;
    },

    clearImage() {
        this.preview = null;
        this.removeImage = true;
    }
}" class="space-y-6">

    <input type="hidden" name="remove_image" :value="removeImage ? 1 : 0">

    {{-- ===================================================== --}}
    {{-- IDENTIDAD --}}
    {{-- ===================================================== --}}

    <section class="rounded-3xl border border-slate-200 bg-white p-6">
        <p class="text-[10px] font-black uppercase tracking-[0.18em] text-amber-600">01 · Identidad</p>

        <div class="mt-2">
            <h3 class="text-xl font-black text-slate-900">Información de la Fase</h3>
            <p class="mt-1 text-sm leading-6 text-slate-500">
                La Fase describe cómo funciona una etapa competitiva,
                independientemente del Torneo que la utilice.
            </p>
        </div>

        <div class="mt-6 grid gap-7 lg:grid-cols-[220px_1fr]">

            {{-- IMAGEN --}}

            <div>
                <div
                    class="aspect-square overflow-hidden rounded-2xl border border-dashed border-slate-300 bg-slate-50">

                    <template x-if="preview">
                        <img :src="preview" alt="" class="h-full w-full object-cover">
                    </template>

                    <template x-if="!preview">
                        <div class="flex h-full flex-col items-center justify-center gap-2 text-center">
                            <span class="text-5xl">⌘</span>
                            <span class="text-xs font-bold text-slate-400">Sin portada</span>
                        </div>
                    </template>

                </div>

                <label
                    class="mt-3 block cursor-pointer rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-center text-xs font-black text-slate-700 transition hover:bg-slate-50">
                    Seleccionar imagen

                    <input type="file" name="image" accept="image/png,image/jpeg,image/webp"
                        @change="loadImage($event)" class="hidden">
                </label>

                <button type="button" x-show="preview" @click="clearImage()"
                    class="mt-2 w-full rounded-xl bg-red-50 px-4 py-2.5 text-xs font-black text-red-600">
                    Quitar imagen
                </button>

                <x-input-error :messages="$errors->get('image')" class="mt-2" />
            </div>

            {{-- INFORMACIÓN --}}

            <div class="space-y-5">

                <div>
                    <label class="text-xs font-black uppercase tracking-wider text-slate-500">Nombre *</label>

                    <input type="text" name="name" value="{{ old('name', $editing ? $phaseTemplate->name : '') }}"
                        placeholder="Ej. Eliminación directa básica"
                        class="mt-2 w-full rounded-xl border-slate-300 focus:border-amber-400 focus:ring-amber-400">

                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                </div>

                <div>
                    <label class="text-xs font-black uppercase tracking-wider text-slate-500">Descripción</label>

                    <textarea name="description" rows="6" placeholder="Describe el comportamiento y propósito de esta Fase..."
                        class="mt-2 w-full rounded-xl border-slate-300 focus:border-amber-400 focus:ring-amber-400">{{ old('description', $editing ? $phaseTemplate->description : '') }}</textarea>

                    <x-input-error :messages="$errors->get('description')" class="mt-2" />
                </div>

                <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4">
                    <p class="text-[9px] font-black uppercase tracking-wider text-amber-700">Código OmniMerge</p>

                    <p class="mt-1 font-mono text-sm font-black text-amber-950">
                        {{ $editing ? $phaseTemplate->code : $previewCode }}
                    </p>

                    <p class="mt-1 text-xs text-amber-800/70">
                        El código se genera automáticamente.
                    </p>
                </div>

            </div>
        </div>
    </section>

    {{-- ===================================================== --}}
    {{-- TIPO --}}
    {{-- ===================================================== --}}

    <section class="rounded-3xl border border-slate-200 bg-white p-6">
        <p class="text-[10px] font-black uppercase tracking-[0.18em] text-amber-600">02 · Comportamiento</p>

        <h3 class="mt-2 text-xl font-black text-slate-900">Tipo de Fase</h3>

        <p class="mt-1 text-sm leading-6 text-slate-500">
            El tipo determina qué motor competitivo utilizará
            la Fase cuando se ejecute.
        </p>

        <div class="mt-6 grid gap-5 md:grid-cols-2">

            <div>
                <label class="text-xs font-black uppercase text-slate-500">Tipo *</label>

                <select name="phase_type"
                    class="mt-2 w-full rounded-xl border-slate-300 focus:border-amber-400 focus:ring-amber-400">

                    @foreach ([
        'SINGLE_ELIMINATION' => 'Eliminación directa',
        'ROUND_ROBIN' => 'Todos contra todos',
        'GROUP_STAGE' => 'Fase de grupos',
        'LEAGUE' => 'Liga / División',
        'SWISS' => 'Sistema suizo',
        'CUSTOM' => 'Personalizada',
    ] as $value => $label)
                        <option value="{{ $value }}" @selected(old('phase_type', $editing ? $phaseTemplate->phase_type : 'SINGLE_ELIMINATION') === $value)>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>

                <p class="mt-2 text-xs text-slate-400">
                    En este Sprint la infraestructura es común.
                    El primer motor completo será Eliminación directa.
                </p>
            </div>

            <div>
                <label class="text-xs font-black uppercase text-slate-500">Participación</label>

                <select name="participant_mode"
                    class="mt-2 w-full rounded-xl border-slate-300 focus:border-amber-400 focus:ring-amber-400">

                    @foreach ([
        'INDIVIDUAL' => 'Individual',
        'TEAM' => 'Equipos',
        'FLEXIBLE' => 'Flexible',
    ] as $value => $label)
                        <option value="{{ $value }}" @selected(old('participant_mode', $editing ? $phaseTemplate->participant_mode : 'INDIVIDUAL') === $value)>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>

        </div>
    </section>

    {{-- ===================================================== --}}
    {{-- CONTRATO DE ENTRADA --}}
    {{-- ===================================================== --}}

    <section class="rounded-3xl border border-slate-200 bg-white p-6">
        <p class="text-[10px] font-black uppercase tracking-[0.18em] text-amber-600">03 · Entrada</p>

        <h3 class="mt-2 text-xl font-black text-slate-900">Contrato de participantes</h3>

        <p class="mt-1 text-sm leading-6 text-slate-500">
            La Fase declara qué cantidad de competidores puede recibir.
            El futuro Tournament Builder utilizará estos valores para
            validar conexiones.
        </p>

        <div class="mt-6 grid gap-5 sm:grid-cols-2 xl:grid-cols-4">

            <div>
                <label class="text-xs font-black uppercase text-slate-500">Mínimo *</label>

                <input type="number" name="min_participants" min="2" max="512"
                    value="{{ old('min_participants', $editing ? $phaseTemplate->min_participants : 2) }}"
                    class="mt-2 w-full rounded-xl border-slate-300 focus:border-amber-400 focus:ring-amber-400">
            </div>

            <div>
                <label class="text-xs font-black uppercase text-slate-500">Máximo</label>

                <input type="number" name="max_participants" min="2" max="512"
                    value="{{ old('max_participants', $editing ? $phaseTemplate->max_participants : '') }}"
                    placeholder="Sin límite"
                    class="mt-2 w-full rounded-xl border-slate-300 focus:border-amber-400 focus:ring-amber-400">
            </div>

            <div>
                <label class="text-xs font-black uppercase text-slate-500">Cantidad exacta</label>

                <input type="number" name="exact_participants" min="2" max="512"
                    value="{{ old('exact_participants', $editing ? $phaseTemplate->exact_participants : '') }}"
                    placeholder="Opcional"
                    class="mt-2 w-full rounded-xl border-slate-300 focus:border-amber-400 focus:ring-amber-400">

                <p class="mt-2 text-[11px] leading-4 text-slate-400">
                    Si se establece, reemplaza mínimo y máximo.
                </p>
            </div>

            <div>
                <label class="text-xs font-black uppercase text-slate-500">Múltiplo de</label>

                <input type="number" name="participant_multiple" min="2" max="512"
                    value="{{ old('participant_multiple', $editing ? $phaseTemplate->participant_multiple : '') }}"
                    placeholder="Ej. 4"
                    class="mt-2 w-full rounded-xl border-slate-300 focus:border-amber-400 focus:ring-amber-400">

                <p class="mt-2 text-[11px] leading-4 text-slate-400">
                    Útil para grupos de tamaño fijo.
                </p>
            </div>

        </div>

        <x-input-error :messages="$errors->get('min_participants')" class="mt-3" />
        <x-input-error :messages="$errors->get('max_participants')" class="mt-1" />
        <x-input-error :messages="$errors->get('exact_participants')" class="mt-1" />
        <x-input-error :messages="$errors->get('participant_multiple')" class="mt-1" />

        <label class="mt-6 flex cursor-pointer items-start gap-3 rounded-2xl border border-slate-200 p-4">
            <input type="checkbox" name="allow_byes" value="1" @checked(old('allow_byes', $editing ? $phaseTemplate->allow_byes : false))
                class="mt-0.5 rounded border-slate-300 text-amber-500 focus:ring-amber-500">

            <span>
                <span class="block text-sm font-black text-slate-800">Permitir BYE</span>

                <span class="mt-1 block text-xs leading-5 text-slate-500">
                    La Fase podrá admitir avances automáticos cuando
                    la cantidad de participantes no complete la estructura ideal.
                </span>
            </span>
        </label>
    </section>

    {{-- ===================================================== --}}
    {{-- CONFIGURACIÓN INICIAL --}}
    {{-- ===================================================== --}}

    <section class="rounded-3xl border border-slate-200 bg-white p-6">
        <p class="text-[10px] font-black uppercase tracking-[0.18em] text-amber-600">04 · Configuración</p>

        <h3 class="mt-2 text-xl font-black text-slate-900">Configuración competitiva básica</h3>

        <div class="mt-6 max-w-sm">
            <label class="text-xs font-black uppercase text-slate-500">Best of</label>

            <select name="best_of"
                class="mt-2 w-full rounded-xl border-slate-300 focus:border-amber-400 focus:ring-amber-400">

                @foreach ([1, 3, 5, 7, 9] as $value)
                    <option value="{{ $value }}" @selected((int) old('best_of', $editing ? $phaseTemplate->best_of : 1) === $value)>
                        Best of {{ $value }}
                    </option>
                @endforeach
            </select>

            <p class="mt-2 text-xs text-slate-400">
                Esta propiedad será especialmente importante
                para las Fases eliminatorias.
            </p>
        </div>
    </section>

    {{-- ===================================================== --}}
    {{-- PUBLICACIÓN --}}
    {{-- ===================================================== --}}

    <section class="rounded-3xl border border-slate-200 bg-white p-6">
        <p class="text-[10px] font-black uppercase tracking-[0.18em] text-amber-600">05 · Organización</p>

        <div class="mt-5 grid gap-5 md:grid-cols-2">

            <div>
                <label class="text-xs font-black uppercase text-slate-500">Estado</label>

                <select name="status"
                    class="mt-2 w-full rounded-xl border-slate-300 focus:border-amber-400 focus:ring-amber-400">

                    @foreach ([
        'DRAFT' => 'Borrador',
        'ACTIVE' => 'Activa',
        'ARCHIVED' => 'Archivada',
    ] as $value => $label)
                        <option value="{{ $value }}" @selected(old('status', $editing ? $phaseTemplate->status : 'DRAFT') === $value)>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="text-xs font-black uppercase text-slate-500">Visibilidad</label>

                <select name="visibility"
                    class="mt-2 w-full rounded-xl border-slate-300 focus:border-amber-400 focus:ring-amber-400">

                    @foreach ([
        'PRIVATE' => 'Privada',
        'PUBLIC' => 'Pública',
        'UNLISTED' => 'No listada',
    ] as $value => $label)
                        <option value="{{ $value }}" @selected(old('visibility', $editing ? $phaseTemplate->visibility : 'PRIVATE') === $value)>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>

        </div>

        <label
            class="mt-5 flex cursor-pointer items-start gap-3 rounded-2xl border border-violet-200 bg-violet-50/50 p-4">
            <input type="checkbox" name="allow_cloning" value="1" @checked(old('allow_cloning', $editing ? $phaseTemplate->allow_cloning : true))
                class="mt-0.5 rounded border-violet-300 text-violet-600 focus:ring-violet-500">

            <span>
                <span class="block text-sm font-black text-violet-900">
                    Permitir clonación cuando sea pública
                </span>

                <span class="mt-1 block text-xs leading-5 text-violet-700">
                    Esta configuración prepara la Fase para la futura
                    Comunidad de Torneos.
                </span>
            </span>
        </label>
    </section>

    <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
        <a href="{{ $editing
            ? route('tournaments.phase-templates.show', $phaseTemplate)
            : route('tournaments.phase-templates.index') }}"
            class="rounded-xl border border-slate-200 bg-white px-5 py-3 text-center text-sm font-black text-slate-600">
            Cancelar
        </a>

        <button type="submit"
            class="rounded-xl bg-amber-500 px-6 py-3 text-sm font-black text-white shadow-lg shadow-amber-500/20 transition hover:bg-amber-600">
            {{ $editing ? 'Guardar cambios' : 'Crear Fase' }}
        </button>
    </div>

</div>
