{{--
    El buscador de la comunidad entera.

    Escribes y responde en vivo, agrupado por clase de pieza y con las personas
    al final; Enter lleva a la página de resultados. La tecla / lo enfoca
    desde cualquier parte de la portada.
--}}

<div x-data="buscadorComunidad(@js(route('community.buscar')))" class="relative"
    @keydown.escape="abierto = false" @click.outside="abierto = false"
    @keydown.slash.window="if (! ['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement.tagName)) { $event.preventDefault(); $refs.campo.focus(); }">

    <form method="GET" action="{{ route('community.buscar') }}" class="relative">
        <span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-emerald-400">
            <x-omni-icon name="brujula" size="h-5 w-5" />
        </span>

        <input x-ref="campo" type="search" name="q" x-model="q" @input.debounce.250ms="buscar()"
            @focus="if (q.trim().length >= 2) abierto = true" autocomplete="off"
            placeholder="Buscar entidades, colecciones, atributos, torneos, fases o personas…"
            class="w-full rounded-2xl border-emerald-500/30 bg-slate-950/90 py-3.5 pl-12 pr-24 text-sm text-slate-100 placeholder:text-slate-600 focus:border-emerald-400 focus:ring-emerald-400">

        <kbd class="pointer-events-none absolute right-4 top-1/2 hidden -translate-y-1/2 rounded-md border border-slate-700 px-1.5 py-0.5 font-mono text-[10px] text-slate-500 sm:block">/</kbd>
    </form>

    <div x-show="abierto" x-cloak x-transition.opacity
        class="absolute inset-x-0 top-full z-30 mt-2 max-h-[65vh] overflow-y-auto rounded-2xl border border-slate-700 bg-slate-900 p-2 shadow-2xl shadow-black/60">

        <p x-show="cargando" class="px-3 py-4 text-center text-[11px] text-slate-500">Buscando…</p>

        <p x-show="! cargando && vacio" class="px-3 py-4 text-center text-[11px] text-slate-500">
            Nada publicado con «<span class="text-slate-300" x-text="q"></span>».
        </p>

        <template x-for="g in grupos" :key="g.clave">
            <div x-show="! cargando && g.total > 0" class="mb-1">

                <div class="flex items-center gap-2 px-2 pb-1 pt-2">
                    <span class="h-3 w-1 rounded-full" :style="`background-color: ${g.tono}`"></span>
                    <span class="text-[10px] font-black uppercase tracking-wider" :style="`color: ${g.tono}`" x-text="g.etiqueta"></span>
                    <span class="font-mono text-[10px] text-slate-600" x-text="g.total"></span>
                    <span class="flex-1"></span>
                    <a :href="g.ver_todo" class="text-[10px] font-black text-slate-500 transition hover:text-emerald-300">Ver todas →</a>
                </div>

                <div class="grid gap-1 sm:grid-cols-2">
                    <template x-for="i in g.items" :key="i.url">
                        <a :href="i.url" class="flex items-center gap-2 rounded-xl px-2 py-1.5 transition hover:bg-slate-800">
                            <span class="h-9 w-9 shrink-0 overflow-hidden rounded-lg border bg-slate-950" :style="`border-color: ${i.tono}55`">
                                <template x-if="i.img">
                                    <img :src="i.img" alt="" class="h-full w-full object-cover">
                                </template>
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-[12px] font-bold text-slate-200" x-text="i.nombre"></span>
                                <span class="block truncate text-[10px] text-slate-500"
                                    x-text="i.autor ? ('de ' + i.autor.nombre + (i.pie ? ' · ' + i.pie : '')) : i.pie"></span>
                            </span>
                            <span x-show="i.copias > 0" class="shrink-0 font-mono text-[10px] text-emerald-400" x-text="i.copias + '×'"></span>
                        </a>
                    </template>
                </div>
            </div>
        </template>

        <a x-show="! cargando && ! vacio" :href="`{{ route('community.buscar') }}?q=${encodeURIComponent(q)}`"
            class="mt-1 block rounded-xl border border-slate-800 py-2 text-center text-[11px] font-black text-emerald-300 transition hover:bg-emerald-500/10">
            Ver todos los resultados
        </a>
    </div>
</div>

@once
    <script>
        function buscadorComunidad(url) {
            return {
                q: '',
                grupos: [],
                abierto: false,
                cargando: false,
                pedido: 0,

                get vacio() {
                    return this.grupos.length > 0 && this.grupos.every((g) => g.total === 0);
                },

                async buscar() {
                    const q = this.q.trim();

                    if (q.length < 2) {
                        this.grupos = [];
                        this.abierto = false;
                        return;
                    }

                    const pedido = ++this.pedido;
                    this.cargando = true;
                    this.abierto = true;

                    try {
                        const r = await fetch(`${url}?q=${encodeURIComponent(q)}`, { headers: { Accept: 'application/json' } });
                        const datos = await r.json();

                        /* Solo cuenta la última respuesta: escribir rápido no mezcla resultados */
                        if (pedido === this.pedido) this.grupos = datos.grupos;
                    } catch (e) {
                        if (pedido === this.pedido) this.grupos = [];
                    } finally {
                        if (pedido === this.pedido) this.cargando = false;
                    }
                },
            };
        }
    </script>
@endonce
