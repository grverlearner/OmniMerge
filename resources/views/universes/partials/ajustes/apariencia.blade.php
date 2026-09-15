{{--
    APARIENCIA — cómo se reconoce este mundo.

    El color tiñe la línea de arriba de todas sus pantallas, su marca del
    sidebar, la cabecera, la portada del Resumen y su tarjeta. El icono sale
    donde no hay portada. El encuadre decide qué parte de la portada se ve
    cuando se recorta.
--}}

<section id="apariencia" data-seccion class="scroll-mt-24 overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

    <header class="flex flex-wrap items-center gap-2 border-b border-slate-800 px-4 py-3"
        style="background: linear-gradient(120deg, #f472b61a, transparent 60%)">
        <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-pink-500/15 text-pink-300">
            <x-omni-icon name="chispa" size="h-4 w-4" />
        </span>
        <div class="min-w-0 flex-1">
            <h2 class="text-[14px] font-black text-white">Apariencia</h2>
            <p class="text-[10px] text-slate-500">Se nota en el sidebar, la cabecera, el Resumen y «Mis universos».</p>
        </div>
        <button type="button" @click="restablecer(['accent', 'icon', 'cover_position'])"
            class="flex items-center gap-1 rounded-lg border border-slate-800 px-2 py-1 text-[10px] font-black text-slate-500 transition hover:border-slate-600 hover:text-white">
            <x-omni-icon name="deshacer" size="h-3 w-3" />
            Restablecer
        </button>
    </header>

    <div class="space-y-5 p-4">

        {{-- ============ EL COLOR ============ --}}

        <div>
            <p class="text-[10px] font-black uppercase tracking-wider text-slate-500">Su color</p>

            <input type="hidden" name="settings[accent]" :value="s.accent">

            <div class="mt-2 flex flex-wrap items-center gap-1.5">
                @foreach (\App\Support\Universes\UniverseSettings::PALETTE as $tono)
                    <button type="button" @click="s.accent = '{{ $tono }}'" title="{{ $tono }}"
                        class="h-8 w-8 rounded-xl transition hover:scale-110"
                        style="background-color: {{ $tono }}"
                        :class="s.accent === '{{ $tono }}' ? 'ring-2 ring-white ring-offset-2 ring-offset-slate-900' : ''"></button>
                @endforeach

                <label class="ml-1 flex items-center gap-2 rounded-xl border border-slate-700 bg-slate-950 py-1 pl-1 pr-2" title="Cualquier otro color">
                    <input type="color" x-model="s.accent" class="h-7 w-9 cursor-pointer rounded-lg border-0 bg-transparent p-0">
                    <input type="text" x-model.lazy="s.accent" maxlength="7" @keydown.enter.prevent
                        class="w-20 border-0 bg-transparent p-0 font-mono text-[12px] uppercase text-slate-200 focus:ring-0">
                </label>
            </div>

            {{-- Cómo queda --}}
            <div class="mt-3 grid gap-2 sm:grid-cols-3">
                <div class="rounded-xl border bg-slate-950 p-2.5" :style="`border-color: ${s.accent}66`">
                    <span class="block h-1 rounded-full" :style="`background-color: ${s.accent}`"></span>
                    <span class="mt-2 block text-[10px] text-slate-500">Borde y línea superior</span>
                </div>
                <div class="flex items-center gap-2 rounded-xl border border-slate-800 bg-slate-950 p-2.5">
                    <span class="rounded-lg px-2 py-0.5 text-[10px] font-black text-slate-950" :style="`background-color: ${s.accent}`">Botón</span>
                    <span class="rounded-lg px-2 py-0.5 text-[10px] font-black" :style="`color: ${s.accent}; background-color: ${s.accent}22`">Etiqueta</span>
                </div>
                <div class="rounded-xl border border-slate-800 p-2.5" :style="`background: linear-gradient(120deg, ${s.accent}33, #020617 70%)`">
                    <span class="text-[11px] font-black" :style="`color: ${s.accent}`">Degradado</span>
                </div>
            </div>
        </div>


        {{-- ============ EL ICONO ============ --}}

        <div>
            <p class="text-[10px] font-black uppercase tracking-wider text-slate-500">Su icono</p>
            <p class="text-[10px] text-slate-600">Sale en su marca del sidebar y en su tarjeta cuando no hay portada.</p>

            <input type="hidden" name="settings[icon]" :value="s.icon">

            <div class="mt-2 grid grid-cols-6 gap-1.5 sm:grid-cols-9">
                @foreach (\App\Support\Universes\UniverseSettings::ICONS as $icono)
                    <button type="button" @click="s.icon = '{{ $icono }}'" title="{{ $icono }}"
                        class="flex aspect-square items-center justify-center rounded-xl border transition hover:-translate-y-0.5"
                        :style="s.icon === '{{ $icono }}' ? `border-color: ${s.accent}; background-color: ${s.accent}22; color: ${s.accent}` : 'border-color: #1e293b; color: #94a3b8'">
                        <x-omni-icon :name="$icono" size="h-5 w-5" />
                    </button>
                @endforeach
            </div>
        </div>


        {{-- ============ EL ENCUADRE ============ --}}

        <div>
            <p class="text-[10px] font-black uppercase tracking-wider text-slate-500">Encuadre de la portada</p>
            <p class="text-[10px] text-slate-600">Qué parte de la imagen se ve cuando se recorta en cuadrado.</p>

            <input type="hidden" name="settings[cover_position]" :value="s.cover_position">

            <div class="mt-2 grid grid-cols-3 gap-2">
                @foreach (\App\Support\Universes\UniverseSettings::COVER_POSITIONS as $valor => $texto)
                    <button type="button" @click="s.cover_position = '{{ $valor }}'"
                        class="overflow-hidden rounded-xl border text-left transition"
                        :style="s.cover_position === '{{ $valor }}' ? `border-color: ${s.accent}` : 'border-color: #1e293b'">
                        <span class="block h-20 bg-slate-950">
                            <template x-if="portada">
                                <img :src="portada" alt="" class="h-full w-full object-cover" style="object-position: {{ $valor }}">
                            </template>
                            <template x-if="! portada">
                                <span class="flex h-full w-full items-center justify-center text-[10px] text-slate-600">Sin portada</span>
                            </template>
                        </span>
                        <span class="block px-2 py-1 text-[11px] font-black"
                            :style="s.cover_position === '{{ $valor }}' ? `color: ${s.accent}` : 'color: #94a3b8'">{{ $texto }}</span>
                    </button>
                @endforeach
            </div>
        </div>
    </div>
</section>
