{{--
    Buscar en todo lo tuyo, desde cualquier sitio del Centro.

    Se abre con el botón de la barra, con el de la portada o pulsando «/».
    Pregunta a HubController::search, que busca a la vez en entidades,
    colecciones, atributos, universos, torneos, fases y competiciones. Con
    las flechas se elige y con Intro se abre.
--}}

<div x-data="{
        abierto: false,
        q: '',
        resultados: [],
        cargando: false,
        activo: 0,
        temporizador: null,
        abrir() { this.abierto = true; this.$nextTick(() => this.$refs.caja.focus()) },
        cerrar() { this.abierto = false },
        buscar() {
            clearTimeout(this.temporizador);
            if (this.q.trim().length < 2) { this.resultados = []; return }
            this.temporizador = setTimeout(async () => {
                this.cargando = true;
                try {
                    const r = await fetch(@js(route('hub.search')) + '?q=' + encodeURIComponent(this.q), { headers: { Accept: 'application/json' } });
                    this.resultados = (await r.json()).results || [];
                    this.activo = 0;
                } catch (e) { this.resultados = [] }
                this.cargando = false;
            }, 220);
        },
        mover(paso) {
            if (! this.resultados.length) return;
            this.activo = (this.activo + paso + this.resultados.length) % this.resultados.length;
        },
        ir() { const r = this.resultados[this.activo]; if (r) window.location = r.url },
    }"
    @omni-buscar.window="abrir()"
    @keydown.window.slash="if (! ['INPUT', 'TEXTAREA', 'SELECT'].includes($event.target.tagName) && ! $event.target.isContentEditable) { $event.preventDefault(); abrir() }"
    @keydown.window.escape="cerrar()">

    <div x-show="abierto" x-cloak x-transition.opacity class="fixed inset-0 z-50 flex items-start justify-center bg-slate-950/80 p-4 pt-[12vh] backdrop-blur-sm"
        @click.self="cerrar()">

        <div class="w-full max-w-2xl overflow-hidden rounded-2xl border border-white/10 bg-slate-900 shadow-2xl shadow-black/60">

            <div class="flex items-center gap-3 border-b border-white/10 px-4">
                <x-omni-icon name="filtro" size="h-5 w-5" class="text-slate-500" />
                <input x-ref="caja" type="search" x-model="q" @input="buscar()"
                    @keydown.arrow-down.prevent="mover(1)" @keydown.arrow-up.prevent="mover(-1)" @keydown.enter.prevent="ir()"
                    placeholder="Busca una entidad, un mundo, un torneo, una competición…"
                    class="h-14 flex-1 border-0 bg-transparent text-base text-white placeholder:text-slate-500 focus:ring-0">
                <span x-show="cargando" class="h-4 w-4 animate-spin rounded-full border-2 border-slate-600 border-t-white"></span>
                <button type="button" @click="cerrar()" class="rounded-lg border border-white/10 px-2 py-1 font-mono text-[10px] text-slate-400">Esc</button>
            </div>

            <div class="max-h-[60vh] overflow-y-auto p-2">
                <template x-if="q.trim().length < 2">
                    <p class="px-3 py-8 text-center text-sm text-slate-500">
                        Escribe al menos dos letras. Busca por nombre o por código en los cuatro módulos a la vez.
                    </p>
                </template>

                <template x-if="q.trim().length >= 2 && ! cargando && ! resultados.length">
                    <p class="px-3 py-8 text-center text-sm text-slate-500">No hay nada tuyo con ese nombre.</p>
                </template>

                <template x-for="(r, i) in resultados" :key="r.url">
                    <a :href="r.url" @mouseenter="activo = i"
                        :class="activo === i ? 'bg-white/10' : ''"
                        class="flex items-center gap-3 rounded-xl px-3 py-2 transition">
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center overflow-hidden rounded-lg border"
                            :style="'border-color:' + r.tone + '55; background-color:' + r.tone + '1a; color:' + r.tone">
                            <template x-if="r.image"><img :src="r.image" alt="" class="h-full w-full object-cover"></template>
                            <template x-if="! r.image"><span class="text-xs font-black" x-text="r.kind.slice(0, 2).toUpperCase()"></span></template>
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-sm font-bold text-white" x-text="r.title"></span>
                            <span class="block truncate text-[11px] text-slate-500" x-text="r.subtitle"></span>
                        </span>
                        <span class="shrink-0 rounded-full px-2 py-0.5 text-[10px] font-black"
                            :style="'color:' + r.tone + '; background-color:' + r.tone + '1a'" x-text="r.kind"></span>
                    </a>
                </template>
            </div>

            <div class="flex items-center gap-4 border-t border-white/10 px-4 py-2 text-[10px] text-slate-500">
                <span><kbd class="rounded border border-white/10 px-1 font-mono">↑</kbd> <kbd class="rounded border border-white/10 px-1 font-mono">↓</kbd> elegir</span>
                <span><kbd class="rounded border border-white/10 px-1 font-mono">Intro</kbd> abrir</span>
                <span class="ml-auto" x-show="resultados.length" x-text="resultados.length + ' resultados'"></span>
            </div>
        </div>
    </div>
</div>
