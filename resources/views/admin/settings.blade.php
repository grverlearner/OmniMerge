<x-admin-layout title="Configuración">

    <x-slot:header>Configuración del sitio</x-slot:header>

    @php
        $favicon = $sitio->faviconUrl();
        $logo = $sitio->logoUrl();
    @endphp

    <form method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data"
        x-data="{
            nombre: @js(old('site_name', $valores['site_name'])),
            lema: @js(old('tagline', $valores['tagline'])),
            color: @js(old('accent', $valores['accent'])),
            anuncio: @js((bool) old('announcement_active', $valores['announcement_active'])),
            textoAnuncio: @js(old('announcement_text', $valores['announcement_text'])),
            tono: @js(old('announcement_tone', $valores['announcement_tone'])),
            tonos: @js(collect($tonos)->map(fn ($t) => $t['color'])),
            mantenimiento: @js((bool) old('maintenance_active', $valores['maintenance_active'])),
            registro: @js((bool) old('registration_open', $valores['registration_open'])),
            comunidad: @js((bool) old('community_open', $valores['community_open'])),
            favicon: @js($favicon),
            logo: @js($logo),
            quitarFavicon: false,
            quitarLogo: false,
            sucio: false,
            enviando: false,
            ver(evento, campo) {
                const archivo = evento.target.files[0];
                if (archivo) { this[campo] = URL.createObjectURL(archivo); this['quitar' + campo[0].toUpperCase() + campo.slice(1)] = false }
            },
            init() { window.OmniUnsaved?.watch(() => this.sucio && ! this.enviando) },
        }"
        @input="sucio = true" @change="sucio = true" @submit="enviando = true"
        class="space-y-5">
        @csrf
        @method('PUT')

        <div class="grid gap-5 xl:grid-cols-3">

            <div class="space-y-5 xl:col-span-2">

                {{-- ===================================================== --}}
                {{-- IDENTIDAD --}}
                {{-- ===================================================== --}}

                <section class="rounded-2xl border border-violet-500/25 bg-slate-900/60 p-5">
                    <h2 class="flex items-center gap-2 text-sm font-black text-white"><x-omni-icon name="pincel" size="h-4 w-4" class="text-violet-300" /> Identidad</h2>
                    <p class="text-xs text-slate-500">Cómo se llama y cómo se reconoce el sitio en todas las pantallas.</p>

                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <label class="block">
                            <span class="text-xs font-black text-slate-300">Nombre del sitio</span>
                            <input type="text" name="site_name" x-model="nombre" maxlength="40" required
                                class="mt-1 w-full rounded-xl border-slate-700 bg-slate-950 text-sm text-slate-100 focus:border-violet-500 focus:ring-violet-500">
                            <span class="mt-1 block text-[11px] text-slate-500">En el título de todas las pestañas, la portada y la cabecera.</span>
                        </label>

                        <label class="block">
                            <span class="text-xs font-black text-slate-300">Lema</span>
                            <input type="text" name="tagline" x-model="lema" maxlength="80"
                                class="mt-1 w-full rounded-xl border-slate-700 bg-slate-950 text-sm text-slate-100 focus:border-violet-500 focus:ring-violet-500">
                            <span class="mt-1 block text-[11px] text-slate-500">Debajo del nombre en la portada.</span>
                        </label>
                    </div>

                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        {{-- Icono de la pestaña --}}
                        <div class="rounded-xl border border-slate-800 bg-slate-950/50 p-3">
                            <p class="text-xs font-black text-slate-300">Icono de la web</p>
                            <p class="text-[11px] text-slate-500">El que sale en la pestaña del navegador y en los marcadores. Cuadrado, PNG o JPG, hasta 1 MB.</p>

                            <div class="mt-3 flex items-center gap-3">
                                <span class="flex h-14 w-14 items-center justify-center overflow-hidden rounded-xl border border-slate-700 bg-slate-900">
                                    <img :src="quitarFavicon ? @js(asset('images/joganboruto.jpg')) : favicon" alt="" class="h-full w-full object-cover">
                                </span>
                                <div class="space-y-1.5">
                                    <label class="inline-flex cursor-pointer items-center gap-1.5 rounded-lg border border-slate-700 px-2.5 py-1.5 text-[11px] font-black text-slate-200 hover:border-violet-500/60">
                                        <x-omni-icon name="subir" size="h-3.5 w-3.5" /> Subir icono
                                        <input type="file" name="favicon" accept="image/png,image/jpeg,image/webp,image/gif" class="sr-only" @change="ver($event, 'favicon')">
                                    </label>
                                    @if ($valores['favicon'])
                                        <label class="flex items-center gap-1.5 text-[11px] text-slate-400">
                                            <input type="checkbox" name="remove_favicon" value="1" x-model="quitarFavicon" class="rounded border-slate-600 bg-slate-950 text-rose-500">
                                            Volver al de siempre
                                        </label>
                                    @endif
                                </div>
                            </div>

                            {{-- Así se ve en una pestaña --}}
                            <div class="mt-3 flex items-center gap-2 rounded-t-lg border border-b-0 border-slate-700 bg-slate-800 px-3 py-1.5 text-[11px] text-slate-200">
                                <img :src="quitarFavicon ? @js(asset('images/joganboruto.jpg')) : favicon" alt="" class="h-4 w-4 rounded-sm object-cover">
                                <span class="truncate" x-text="'Centro | ' + (nombre || 'OmniMerge')"></span>
                            </div>
                        </div>

                        {{-- Logo --}}
                        <div class="rounded-xl border border-slate-800 bg-slate-950/50 p-3">
                            <p class="text-xs font-black text-slate-300">Logo</p>
                            <p class="text-[11px] text-slate-500">En la portada y en la página de mantenimiento. Si no hay, se usa el icono. Hasta 2 MB.</p>

                            <div class="mt-3 flex items-center gap-3">
                                <span class="flex h-14 w-14 items-center justify-center overflow-hidden rounded-xl border border-slate-700 bg-slate-900 text-slate-600">
                                    <template x-if="logo && ! quitarLogo"><img :src="logo" alt="" class="h-full w-full object-cover"></template>
                                    <template x-if="! logo || quitarLogo"><x-omni-icon name="galeria" size="h-6 w-6" /></template>
                                </span>
                                <div class="space-y-1.5">
                                    <label class="inline-flex cursor-pointer items-center gap-1.5 rounded-lg border border-slate-700 px-2.5 py-1.5 text-[11px] font-black text-slate-200 hover:border-violet-500/60">
                                        <x-omni-icon name="subir" size="h-3.5 w-3.5" /> Subir logo
                                        <input type="file" name="logo" accept="image/png,image/jpeg,image/webp,image/gif" class="sr-only" @change="ver($event, 'logo')">
                                    </label>
                                    @if ($valores['logo'])
                                        <label class="flex items-center gap-1.5 text-[11px] text-slate-400">
                                            <input type="checkbox" name="remove_logo" value="1" x-model="quitarLogo" class="rounded border-slate-600 bg-slate-950 text-rose-500">
                                            Quitar el logo
                                        </label>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Color de marca --}}
                    <div class="mt-4 rounded-xl border border-slate-800 bg-slate-950/50 p-3">
                        <p class="text-xs font-black text-slate-300">Color de marca</p>
                        <p class="text-[11px] text-slate-500">
                            Colorea los botones principales de la portada, el nombre en la cabecera de la portada y la barra del navegador en el móvil.
                            Cada módulo conserva su propio color para que se distingan entre sí.
                        </p>

                        <div class="mt-3 flex flex-wrap items-center gap-2">
                            <input type="color" x-model="color" class="h-10 w-14 cursor-pointer rounded-lg border border-slate-700 bg-slate-900 p-1">
                            <input type="text" name="accent" x-model="color" maxlength="7" pattern="#[0-9a-fA-F]{6}"
                                class="w-28 rounded-xl border-slate-700 bg-slate-950 font-mono text-sm text-slate-100">
                            @foreach (['#8b5cf6', '#6366f1', '#0ea5e9', '#10b981', '#f59e0b', '#f43f5e', '#ec4899'] as $muestra)
                                <button type="button" @click="color = '{{ $muestra }}'; sucio = true" title="{{ $muestra }}"
                                    class="h-7 w-7 rounded-full border-2 transition" :class="color === '{{ $muestra }}' ? 'border-white' : 'border-transparent'"
                                    style="background-color: {{ $muestra }}"></button>
                            @endforeach

                            <span class="ml-auto inline-flex items-center gap-2 rounded-xl px-4 py-2 text-xs font-black text-white" :style="'background-color: ' + color">
                                Así se ve un botón
                            </span>
                        </div>
                    </div>
                </section>


                {{-- ===================================================== --}}
                {{-- ANUNCIO --}}
                {{-- ===================================================== --}}

                <section class="rounded-2xl border border-sky-500/25 bg-slate-900/60 p-5">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h2 class="flex items-center gap-2 text-sm font-black text-white"><x-omni-icon name="megafono" size="h-4 w-4" class="text-sky-300" /> Anuncio para todos</h2>
                            <p class="text-xs text-slate-500">Una franja arriba del todo en todos los módulos y en la portada. Quien la cierra no la vuelve a ver hasta que cambies el texto.</p>
                        </div>
                        <label class="relative inline-flex shrink-0 cursor-pointer items-center">
                            <input type="checkbox" name="announcement_active" value="1" x-model="anuncio" class="peer sr-only">
                            <span class="h-6 w-11 rounded-full bg-slate-700 transition peer-checked:bg-sky-500"></span>
                            <span class="absolute left-0.5 top-0.5 h-5 w-5 rounded-full bg-white transition peer-checked:translate-x-5"></span>
                        </label>
                    </div>

                    <div class="mt-4 space-y-3" :class="anuncio ? '' : 'opacity-50'">
                        <textarea name="announcement_text" x-model="textoAnuncio" rows="2" maxlength="240" placeholder="Por ejemplo: esta noche a las 23:00 haremos mejoras durante media hora."
                            class="w-full rounded-xl border-slate-700 bg-slate-950 text-sm text-slate-100 placeholder:text-slate-600"></textarea>

                        <div class="flex flex-wrap items-center gap-2">
                            @foreach ($tonos as $clave => $tono)
                                <label class="cursor-pointer">
                                    <input type="radio" name="announcement_tone" value="{{ $clave }}" x-model="tono" class="peer sr-only">
                                    <span class="inline-flex items-center gap-1.5 rounded-full border border-slate-700 px-3 py-1 text-[11px] font-black text-slate-400 peer-checked:text-white"
                                        :style="tono === '{{ $clave }}' ? 'border-color: {{ $tono['color'] }}; background-color: {{ $tono['color'] }}22' : ''">
                                        <span class="h-2 w-2 rounded-full" style="background-color: {{ $tono['color'] }}"></span> {{ $tono['label'] }}
                                    </span>
                                </label>
                            @endforeach

                            <input type="url" name="announcement_link" value="{{ old('announcement_link', $valores['announcement_link']) }}" placeholder="Enlace opcional (https://…)"
                                class="min-w-[14rem] flex-1 rounded-xl border-slate-700 bg-slate-950 text-xs text-slate-100 placeholder:text-slate-600">
                        </div>

                        {{-- Así se verá --}}
                        <div class="overflow-hidden rounded-xl border border-slate-800">
                            <p class="bg-slate-950 px-3 py-1 text-[10px] font-black uppercase tracking-wider text-slate-600">Vista previa</p>
                            <div class="flex items-center gap-2 border-b px-4 py-2 text-xs font-bold"
                                :style="'color: ' + tonos[tono] + '; border-color: ' + tonos[tono] + '55; background-color: ' + tonos[tono] + '1a'">
                                <x-omni-icon name="megafono" size="h-4 w-4" />
                                <span x-text="textoAnuncio || 'Escribe el anuncio para verlo aquí'"></span>
                            </div>
                        </div>
                    </div>
                </section>


                {{-- ===================================================== --}}
                {{-- MANTENIMIENTO --}}
                {{-- ===================================================== --}}

                <section id="mantenimiento" class="rounded-2xl border bg-slate-900/60 p-5" :class="mantenimiento ? 'border-amber-500/60' : 'border-amber-500/25'">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h2 class="flex items-center gap-2 text-sm font-black text-white"><x-omni-icon name="llave" size="h-4 w-4" class="text-amber-300" /> Modo mantenimiento</h2>
                            <p class="text-xs text-slate-500">
                                Todo el que no sea admin verá una página de aviso con tu mensaje en lugar del sitio. Los admins siguen entrando con normalidad
                                para poder terminar y volver a abrirlo.
                            </p>
                        </div>
                        <label class="relative inline-flex shrink-0 cursor-pointer items-center">
                            <input type="checkbox" name="maintenance_active" value="1" x-model="mantenimiento" class="peer sr-only">
                            <span class="h-6 w-11 rounded-full bg-slate-700 transition peer-checked:bg-amber-500"></span>
                            <span class="absolute left-0.5 top-0.5 h-5 w-5 rounded-full bg-white transition peer-checked:translate-x-5"></span>
                        </label>
                    </div>

                    <textarea name="maintenance_message" rows="2" maxlength="500"
                        class="mt-3 w-full rounded-xl border-slate-700 bg-slate-950 text-sm text-slate-100">{{ old('maintenance_message', $valores['maintenance_message']) }}</textarea>

                    <p x-show="mantenimiento" x-cloak class="mt-2 rounded-lg border border-amber-500/40 bg-amber-500/10 px-3 py-2 text-[11px] font-bold text-amber-200">
                        Al guardar, los usuarios que estén dentro verán la página de aviso en su siguiente clic.
                    </p>
                </section>
            </div>


            {{-- ========================================================= --}}
            {{-- ACCESO --}}
            {{-- ========================================================= --}}

            <aside class="space-y-5">
                <section class="rounded-2xl border border-emerald-500/25 bg-slate-900/60 p-5">
                    <h2 class="flex items-center gap-2 text-sm font-black text-white"><x-omni-icon name="puerta" size="h-4 w-4" class="text-emerald-300" /> Acceso</h2>

                    <div class="mt-4 space-y-3">
                        <label class="flex cursor-pointer items-start justify-between gap-3 rounded-xl border border-slate-800 bg-slate-950/50 p-3">
                            <span>
                                <span class="block text-xs font-black text-slate-200">Registro abierto</span>
                                <span class="block text-[11px] text-slate-500" x-text="registro ? 'Cualquiera puede crearse una cuenta.' : 'La página de registro manda a la de entrada con un aviso.'"></span>
                            </span>
                            <span class="relative inline-flex shrink-0 items-center">
                                <input type="checkbox" name="registration_open" value="1" x-model="registro" class="peer sr-only">
                                <span class="h-6 w-11 rounded-full bg-slate-700 transition peer-checked:bg-emerald-500"></span>
                                <span class="absolute left-0.5 top-0.5 h-5 w-5 rounded-full bg-white transition peer-checked:translate-x-5"></span>
                            </span>
                        </label>

                        <label class="flex cursor-pointer items-start justify-between gap-3 rounded-xl border border-slate-800 bg-slate-950/50 p-3">
                            <span>
                                <span class="block text-xs font-black text-slate-200">Comunidad abierta</span>
                                <span class="block text-[11px] text-slate-500" x-text="comunidad ? 'Se puede explorar y clonar lo que hacen otros.' : 'La comunidad muestra un aviso de cerrada. Lo de cada uno sigue funcionando.'"></span>
                            </span>
                            <span class="relative inline-flex shrink-0 items-center">
                                <input type="checkbox" name="community_open" value="1" x-model="comunidad" class="peer sr-only">
                                <span class="h-6 w-11 rounded-full bg-slate-700 transition peer-checked:bg-emerald-500"></span>
                                <span class="absolute left-0.5 top-0.5 h-5 w-5 rounded-full bg-white transition peer-checked:translate-x-5"></span>
                            </span>
                        </label>
                    </div>
                </section>

                {{-- Resumen de lo que va a pasar --}}
                <section class="sticky top-24 rounded-2xl border border-slate-800 bg-slate-900/60 p-5">
                    <h2 class="text-sm font-black text-white">Al guardar</h2>
                    <ul class="mt-3 space-y-2 text-xs">
                        <li class="flex items-center gap-2 text-slate-300"><x-omni-icon name="check" size="h-3.5 w-3.5" class="text-emerald-400" /> El sitio se llamará <span class="font-black text-white" x-text="nombre || '—'"></span></li>
                        <li class="flex items-center gap-2" :class="anuncio && textoAnuncio ? 'text-sky-300' : 'text-slate-500'"><x-omni-icon name="megafono" size="h-3.5 w-3.5" /> <span x-text="anuncio && textoAnuncio ? 'Anuncio visible para todos' : 'Sin anuncio'"></span></li>
                        <li class="flex items-center gap-2" :class="mantenimiento ? 'text-amber-300' : 'text-slate-500'"><x-omni-icon name="llave" size="h-3.5 w-3.5" /> <span x-text="mantenimiento ? 'Sitio cerrado por mantenimiento' : 'Sitio abierto'"></span></li>
                        <li class="flex items-center gap-2" :class="registro ? 'text-slate-300' : 'text-orange-300'"><x-omni-icon name="puerta" size="h-3.5 w-3.5" /> <span x-text="registro ? 'Registro abierto' : 'Registro cerrado'"></span></li>
                        <li class="flex items-center gap-2" :class="comunidad ? 'text-slate-300' : 'text-orange-300'"><x-omni-icon name="globo" size="h-3.5 w-3.5" /> <span x-text="comunidad ? 'Comunidad abierta' : 'Comunidad cerrada'"></span></li>
                    </ul>

                    <button type="submit" class="mt-4 flex w-full items-center justify-center gap-2 rounded-xl bg-rose-500 px-4 py-2.5 text-sm font-black text-white hover:bg-rose-400">
                        <x-omni-icon name="guardar" size="h-4 w-4" /> Guardar configuración
                    </button>
                    <p x-show="sucio" x-cloak class="mt-2 text-center text-[11px] font-bold text-amber-300">Hay cambios sin guardar</p>
                </section>
            </aside>
        </div>
    </form>

</x-admin-layout>
