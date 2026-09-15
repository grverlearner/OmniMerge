{{--
    EXPLORAR — cómo se abre el mapa de este mundo.

    El criterio con el que se reparte a la gente al entrar y la forma de
    mirarlo. Si alguien ya lo abrió, recuerda lo último que miró hasta que
    esta configuración cambie.
--}}

<section id="explorar" data-seccion class="scroll-mt-24 overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

    <header class="flex flex-wrap items-center gap-2 border-b border-slate-800 px-4 py-3"
        style="background: linear-gradient(120deg, #818cf81a, transparent 60%)">
        <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-indigo-500/15 text-indigo-300">
            <x-omni-icon name="brujula" size="h-4 w-4" />
        </span>
        <div class="min-w-0 flex-1">
            <h2 class="text-[14px] font-black text-white">Explorar</h2>
            <p class="text-[10px] text-slate-500">Con qué criterio y de qué forma se abre el mapa del universo.</p>
        </div>
        <a href="{{ route('universes.explorer', $universe) }}" target="_blank" rel="noopener"
            class="rounded-lg border border-slate-800 px-2 py-1 text-[10px] font-black text-slate-500 transition hover:border-indigo-500 hover:text-indigo-300">Abrir el mapa</a>
        <button type="button" @click="restablecer(['explorer_default_criterion', 'explorer_default_mode'])"
            class="flex items-center gap-1 rounded-lg border border-slate-800 px-2 py-1 text-[10px] font-black text-slate-500 transition hover:border-slate-600 hover:text-white">
            <x-omni-icon name="deshacer" size="h-3 w-3" />
            Restablecer
        </button>
    </header>

    <div class="grid gap-4 p-4 md:grid-cols-2">

        <label class="block">
            <span class="text-[10px] font-black uppercase tracking-wider text-slate-500">Repartir a la gente por</span>
            <select name="settings[explorer_default_criterion]" x-model="s.explorer_default_criterion"
                class="mt-1.5 w-full rounded-xl border-slate-700 bg-slate-950 py-2 text-[12px] font-bold text-slate-100 focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">Lo decide el mapa (el criterio que más reparte)</option>
                <optgroup label="Del universo">
                    @foreach (array_slice($criteriosMapa, 0, 5, true) as $clave => $texto)
                        <option value="{{ $clave }}">{{ $texto }}</option>
                    @endforeach
                </optgroup>
                @if (count($criteriosMapa) > 5)
                    <optgroup label="Atributos de sus habitantes">
                        @foreach (array_slice($criteriosMapa, 5, null, true) as $clave => $texto)
                            <option value="{{ $clave }}">{{ $texto }}</option>
                        @endforeach
                    </optgroup>
                @endif
            </select>
            <span class="mt-1 block text-[10px] text-slate-600">Por ejemplo «aldea» forma un cuadro por cada aldea.</span>
        </label>

        <div>
            <span class="text-[10px] font-black uppercase tracking-wider text-slate-500">Forma de mirarlo</span>
            <input type="hidden" name="settings[explorer_default_mode]" :value="s.explorer_default_mode">

            <div class="mt-1.5 grid grid-cols-2 gap-1.5">
                @foreach ([['cuadros', 'capas', 'Cuadros', 'Uno por cada valor'], ['cruce', 'cuadricula', 'Cruce', 'Dos criterios a la vez'], ['compartidos', 'grafo', 'Compartidos', 'Quién está en dos sitios'], ['todo', 'orbita', 'Todo junto', 'El universo entero teñido']] as [$valor, $icono, $texto, $ayuda])
                    <button type="button" @click="s.explorer_default_mode = '{{ $valor }}'"
                        class="flex items-start gap-2 rounded-xl border px-2.5 py-2 text-left transition"
                        :class="s.explorer_default_mode === '{{ $valor }}' ? 'border-indigo-400 bg-indigo-500/15' : 'border-slate-800 hover:border-slate-600'">
                        <span class="mt-0.5 text-indigo-300"><x-omni-icon :name="$icono" size="h-4 w-4" /></span>
                        <span>
                            <span class="block text-[12px] font-black text-slate-100">{{ $texto }}</span>
                            <span class="block text-[9px] leading-3 text-slate-500">{{ $ayuda }}</span>
                        </span>
                    </button>
                @endforeach
            </div>
        </div>
    </div>
</section>
