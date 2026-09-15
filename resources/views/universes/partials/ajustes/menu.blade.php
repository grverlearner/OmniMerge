{{--
    MENÚ E INICIO — qué se ve de este mundo y por dónde se entra.

    Esconder una sección la quita del sidebar del universo (sus datos siguen
    ahí). Y «al entrar» decide a dónde llevan los enlaces a este universo
    desde «Mis universos», el Centro y el panel de Universos.
--}}

<section id="menu" data-seccion class="scroll-mt-24 overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

    <header class="flex flex-wrap items-center gap-2 border-b border-slate-800 px-4 py-3"
        style="background: linear-gradient(120deg, #2dd4bf1a, transparent 60%)">
        <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-teal-500/15 text-teal-300">
            <x-omni-icon name="panel" size="h-4 w-4" />
        </span>
        <div class="min-w-0 flex-1">
            <h2 class="text-[14px] font-black text-white">Menú e inicio</h2>
            <p class="text-[10px] text-slate-500">Qué secciones salen en el sidebar y a dónde lleva entrar en el universo.</p>
        </div>
        <button type="button" @click="restablecer(['hidden_nav', 'home'])"
            class="flex items-center gap-1 rounded-lg border border-slate-800 px-2 py-1 text-[10px] font-black text-slate-500 transition hover:border-slate-600 hover:text-white">
            <x-omni-icon name="deshacer" size="h-3 w-3" />
            Restablecer
        </button>
    </header>

    <div class="space-y-5 p-4">

        <div>
            <p class="text-[10px] font-black uppercase tracking-wider text-slate-500">Secciones del menú</p>
            <p class="text-[10px] text-slate-600">«Resumen» y «Configuración» siempre están. Esconder una sección no borra nada.</p>

            <div class="mt-2 grid gap-1.5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach (\App\Support\Universes\UniverseSettings::NAV as $clave => [$ruta, $texto, $icono])
                    <button type="button" @click="alternar('hidden_nav', '{{ $clave }}')"
                        class="flex items-center gap-2 rounded-xl border px-2.5 py-2 text-left transition"
                        :style="navVisible('{{ $clave }}') ? `border-color: ${s.accent}55; background-color: ${s.accent}0f` : 'border-color: #1e293b'">
                        <span :style="navVisible('{{ $clave }}') ? `color: ${s.accent}` : 'color: #475569'"><x-omni-icon :name="$icono" size="h-4 w-4" /></span>
                        <span class="min-w-0 flex-1 truncate text-[12px] font-black"
                            :class="navVisible('{{ $clave }}') ? 'text-slate-100' : 'text-slate-600 line-through'"
                            x-text="navLabel('{{ $clave }}')"></span>
                        <span class="relative h-5 w-9 shrink-0 rounded-full transition"
                            :style="navVisible('{{ $clave }}') ? `background-color: ${s.accent}` : 'background-color: #334155'">
                            <span class="absolute top-0.5 h-4 w-4 rounded-full bg-white transition-all"
                                :class="navVisible('{{ $clave }}') ? 'left-[18px]' : 'left-0.5'"></span>
                        </span>
                    </button>
                @endforeach
            </div>
        </div>


        <div>
            <p class="text-[10px] font-black uppercase tracking-wider text-slate-500">Al entrar en el universo, abrir</p>
            <p class="text-[10px] text-slate-600">Lo usan los enlaces desde «Mis universos», el Centro y el panel de Universos.</p>

            <input type="hidden" name="settings[home]" :value="s.home">

            <div class="mt-2 grid gap-1.5 sm:grid-cols-3">
                @foreach (\App\Support\Universes\UniverseSettings::HOMES as $clave => [$ruta, $texto])
                    <button type="button" @click="s.home = '{{ $clave }}'"
                        :disabled="'{{ $clave }}' !== 'show' && ! navVisible('{{ $clave }}')"
                        class="rounded-xl border px-3 py-2 text-left transition disabled:cursor-not-allowed disabled:opacity-40"
                        :style="s.home === '{{ $clave }}' ? `border-color: ${s.accent}; background-color: ${s.accent}1a` : 'border-color: #1e293b'">
                        <span class="flex items-center gap-1.5">
                            <span class="h-2.5 w-2.5 rounded-full border-2" :style="s.home === '{{ $clave }}' ? `border-color: ${s.accent}; background-color: ${s.accent}` : 'border-color: #475569'"></span>
                            <span class="text-[12px] font-black text-slate-100" x-text="'{{ $clave }}' === 'show' ? 'Resumen' : navLabel('{{ $clave }}')"></span>
                        </span>
                        <span x-show="'{{ $clave }}' !== 'show' && ! navVisible('{{ $clave }}')" class="mt-0.5 block text-[9px] text-slate-500">Escondida en el menú</span>
                    </button>
                @endforeach
            </div>

            <p x-show="! homeValido" x-cloak class="mt-2 flex items-center gap-1.5 text-[10px] font-bold text-amber-300">
                <x-omni-icon name="aviso" size="h-3.5 w-3.5" />
                Esa sección está escondida, así que al entrar se abrirá el Resumen.
            </p>
        </div>
    </div>
</section>
