@php
    /*
     * TRAER DE LA BIBLIOTECA — el diff antes de aplicarlo.
     *
     * Importar copia, no enlaza: un torneo no puede depender de datos que
     * se cambian por fuera. El precio era que la copia se quedaba
     * congelada, y esto lo paga sin renunciar a la decisión: se actualiza
     * cuando tú lo pides, viendo antes qué va a cambiar.
     *
     * Lo importante de esta pantalla es lo que NO se puede hacer: un
     * atributo del que dependa un torneo real no se quita. Se marca, se
     * explica quién lo usa, y se conserva.
     */
@endphp

<div x-show="syncOpen" x-cloak
    class="mb-3 overflow-hidden rounded-2xl border border-sky-500/40 bg-sky-500/5">

    <div class="flex items-center gap-2 border-b border-slate-800 bg-sky-500/10 px-4 py-2">
        <span class="text-[11px]">↻</span>
        <h2 class="text-[11px] font-black uppercase tracking-wider text-sky-300">
            Lo que cambiaría al traerlo
        </h2>

        <button type="button" @click="syncOpen = false"
            class="ml-auto px-1 text-[14px] text-slate-500 transition hover:text-slate-200">×</button>
    </div>

    <div class="p-3">

        <p x-show="syncBusy" class="py-4 text-center text-[11px] text-slate-500">
            comparando con la Biblioteca…
        </p>

        <template x-if="!syncBusy && diff && !diff.available">
            <p class="rounded-xl border border-slate-800 bg-slate-950/60 px-3 py-3 text-[11px] leading-relaxed text-slate-400"
                x-text="diff.reason"></p>
        </template>

        <template x-if="!syncBusy && diff && diff.available">
            <div class="space-y-2">

                <template x-if="!diff.has_changes">
                    <p class="rounded-xl border border-slate-800 bg-slate-950/60 px-3 py-3 text-center text-[11px] text-slate-400">
                        Ya está al día: la Biblioteca no tiene nada que esta copia no tenga.
                    </p>
                </template>


                {{-- Lo que llega --}}

                <template x-if="diff.attributes.added.length">
                    <div class="rounded-xl border border-emerald-500/30 bg-emerald-500/5 p-2.5">
                        <p class="text-[9px] font-black uppercase tracking-wider text-emerald-300">
                            Llegan <span class="font-mono" x-text="diff.attributes.added.length"></span>
                        </p>

                        <div class="mt-1 flex flex-wrap gap-1">
                            <template x-for="a in diff.attributes.added" :key="'add' + a.name">
                                <span class="rounded bg-slate-950 px-1.5 py-0.5 text-[10px]">
                                    <span class="text-slate-500" x-text="a.name"></span>
                                    <span class="ml-1 text-emerald-200" x-text="a.display"></span>
                                </span>
                            </template>
                        </div>
                    </div>
                </template>


                {{-- Lo que cambia --}}

                <template x-if="diff.attributes.changed.length">
                    <div class="rounded-xl border border-amber-500/30 bg-amber-500/5 p-2.5">
                        <p class="text-[9px] font-black uppercase tracking-wider text-amber-300">
                            Cambian <span class="font-mono" x-text="diff.attributes.changed.length"></span>
                        </p>

                        <div class="mt-1 space-y-0.5">
                            <template x-for="c in diff.attributes.changed" :key="'chg' + c.to.name">
                                <p class="text-[10px]">
                                    <span class="text-slate-500" x-text="c.to.name"></span>
                                    <span class="ml-1 text-slate-600 line-through" x-text="c.from.display"></span>
                                    <span class="mx-1 text-slate-700">→</span>
                                    <span class="text-amber-200" x-text="c.to.display"></span>
                                </p>
                            </template>
                        </div>
                    </div>
                </template>


                {{--
                    Lo que se iría, y lo que no se va aunque se haya ido de
                    la Biblioteca. Esta es la parte que importa: un atributo
                    con el que se escribieron las reglas de un torneo YA
                    JUGADO no se puede borrar sin dejar ese torneo hablando
                    de algo que ya no existe.
                --}}

                <template x-if="diff.attributes.removed.length">
                    <div class="rounded-xl border border-rose-500/30 bg-rose-500/5 p-2.5">
                        <p class="text-[9px] font-black uppercase tracking-wider text-rose-300">
                            Ya no están en la Biblioteca
                            <span class="font-mono" x-text="diff.attributes.removed.length"></span>
                        </p>

                        <div class="mt-1 space-y-1">
                            <template x-for="r in diff.attributes.removed" :key="'rem' + r.attribute.name">
                                <div class="rounded-lg px-2 py-1"
                                    :class="r.locked ? 'bg-slate-950' : 'bg-rose-500/10'">

                                    <p class="flex flex-wrap items-center gap-1.5 text-[10px]">
                                        <span class="font-black"
                                            :class="r.locked ? 'text-emerald-300' : 'text-rose-200'"
                                            x-text="r.attribute.name"></span>

                                        <span class="text-slate-500" x-text="r.attribute.display"></span>

                                        <span class="rounded px-1 py-0.5 text-[8px] font-black"
                                            :class="r.locked
                                                ? 'bg-emerald-500/20 text-emerald-300'
                                                : 'bg-rose-500/20 text-rose-300'"
                                            x-text="r.locked ? 'se conserva' : 'se quita'"></span>
                                    </p>

                                    <p class="mt-0.5 text-[9px] leading-relaxed text-slate-500"
                                        x-show="r.locked"
                                        x-text="'Un torneo real lo usa: ' + (r.locked_by.used_by ?? []).join('; ') + '.'"></p>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>


                {{-- Las versiones --}}

                <template x-if="diff.versions.added.length || diff.versions.changed.length">
                    <div class="rounded-xl border border-violet-500/30 bg-violet-500/5 p-2.5">
                        <p class="text-[9px] font-black uppercase tracking-wider text-violet-300">
                            Versiones
                            <span class="font-mono" x-text="'+' + diff.versions.added.length + ' ~' + diff.versions.changed.length"></span>
                        </p>

                        <div class="mt-1 flex flex-wrap gap-1">
                            <template x-for="v in diff.versions.added" :key="'va' + v.id">
                                <span class="flex items-center gap-1 rounded-lg bg-slate-950 px-1 py-0.5">
                                    <span class="flex h-6 w-6 items-center justify-center overflow-hidden rounded bg-slate-900">
                                        <span class="text-[8px] text-slate-600">nueva</span>
                                    </span>
                                    <span class="text-[10px] text-violet-200" x-text="v.name"></span>
                                </span>
                            </template>

                            <template x-for="c in diff.versions.changed" :key="'vc' + c.to.id">
                                <span class="rounded-lg bg-slate-950 px-1.5 py-0.5 text-[10px] text-amber-200"
                                    x-text="c.to.name"></span>
                            </template>
                        </div>
                    </div>
                </template>


                {{-- La identidad, que NO se trae salvo que se pida --}}

                <template x-if="Object.keys(diff.identity).length">
                    <div class="rounded-xl border border-slate-800 bg-slate-950/60 p-2.5">
                        <p class="text-[9px] font-black uppercase tracking-wider text-slate-500">
                            Su ficha en la Biblioteca también cambió
                        </p>

                        <div class="mt-1 space-y-0.5">
                            <template x-for="(cambio, campo) in diff.identity" :key="'id' + campo">
                                <p class="text-[10px]">
                                    <span class="text-slate-600" x-text="campo"></span>
                                    <span class="ml-1 text-slate-500 line-through" x-text="cambio.from ?? '—'"></span>
                                    <span class="mx-1 text-slate-700">→</span>
                                    <span class="text-slate-300" x-text="cambio.to ?? '—'"></span>
                                </p>
                            </template>
                        </div>

                        <p class="mt-1 text-[9px] leading-relaxed text-slate-600">
                            Esto no se trae salvo que lo marques: dentro de un Universo se
                            renombra a propósito, y machacarlo en cada actualización
                            convertiría traer atributos en una sorpresa.
                        </p>
                    </div>
                </template>


                {{-- Aplicarlo --}}

                <form method="POST" action="{{ route('universes.entities.sync.apply', [$universe, $entity]) }}"
                    class="flex flex-wrap items-center gap-2 border-t border-slate-800 pt-2">
                    @csrf

                    <label class="flex cursor-pointer items-center gap-1.5">
                        <input type="hidden" name="with_identity" value="0">
                        <input type="checkbox" name="with_identity" value="1"
                            class="rounded border-slate-600 bg-slate-950 text-sky-500 focus:ring-sky-500">
                        <span class="text-[10px] text-slate-400">traer también nombre, tipo e imagen</span>
                    </label>

                    <button type="button" @click="syncOpen = false"
                        class="ml-auto rounded-lg border border-slate-800 px-3 py-1.5 text-[11px] font-black text-slate-400 transition hover:border-slate-600">
                        cancelar
                    </button>

                    <button class="rounded-lg bg-sky-500 px-3 py-1.5 text-[11px] font-black text-slate-950 transition hover:bg-sky-400"
                        :disabled="!diff.has_changes"
                        :class="diff.has_changes ? '' : 'opacity-40'">
                        Traerlo
                    </button>
                </form>
            </div>
        </template>
    </div>
</div>
