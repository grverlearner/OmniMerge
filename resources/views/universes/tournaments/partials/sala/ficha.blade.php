{{--
    LA FICHA DE UN COMPETIDOR

    Por qué está dentro o fuera, por qué puerta entra, con qué cara sale y
    todas sus versiones para elegir. Se abre al pulsar cualquier cara.
--}}

<div x-show="fichaC" x-cloak x-transition.opacity
    class="fixed inset-0 z-50 flex justify-end bg-slate-950/70 backdrop-blur-sm"
    @keydown.escape.window="ficha = null" @click.self="ficha = null">

    <template x-if="fichaC">
        <aside class="h-full w-full max-w-md overflow-y-auto border-l border-slate-800 bg-slate-950 shadow-2xl shadow-black">

            {{-- La cara --}}
            <div class="relative aspect-[4/3] overflow-hidden bg-slate-900">
                <template x-if="face(fichaC).image_url">
                    <img :src="face(fichaC).image_url" alt="" class="h-full w-full object-cover" :class="isIn(fichaC.id) ? '' : 'grayscale'">
                </template>
                <template x-if="! face(fichaC).image_url">
                    <span class="flex h-full w-full items-center justify-center font-mono text-5xl font-black text-slate-700" x-text="initials(fichaC.name)"></span>
                </template>

                <span class="absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-950/30 to-transparent"></span>

                <button type="button" @click="ficha = null" title="Cerrar"
                    class="absolute right-3 top-3 flex h-8 w-8 items-center justify-center rounded-full bg-slate-950/70 text-slate-300 backdrop-blur transition hover:text-white">
                    <x-omni-icon name="cerrar" size="h-4 w-4" />
                </button>

                <div class="absolute inset-x-0 bottom-0 p-4">
                    <p class="text-[10px] font-black uppercase tracking-wider text-slate-400" x-text="fichaC.type || 'Competidor'"></p>
                    <p class="text-2xl font-black leading-tight text-white" x-text="fichaC.name"></p>
                    <p x-show="face(fichaC).version_id" class="text-[12px] font-bold" :style="`color: ${fromTone(face(fichaC).from)}`" x-text="'Sale como ' + face(fichaC).name"></p>
                </div>
            </div>

            <div class="space-y-3 p-4">

                {{-- Dentro o fuera --}}
                <div class="flex flex-wrap items-center gap-2 rounded-xl border px-3 py-2"
                    :class="isIn(fichaC.id) ? 'border-emerald-500/40 bg-emerald-500/10' : 'border-slate-700 bg-slate-900'">
                    <span class="text-[13px] font-black" :class="isIn(fichaC.id) ? 'text-emerald-200' : 'text-slate-300'"
                        x-text="isIn(fichaC.id) ? 'Juega' : 'Se queda fuera'"></span>
                    <span class="text-[11px] text-slate-400" x-text="'· ' + reasonText(fichaC.id)"></span>
                    <span class="flex-1"></span>
                    <template x-if="isIn(fichaC.id) && calc.doorOf[fichaC.id]">
                        <span class="rounded-md px-1.5 py-0.5 text-[10px] font-black text-slate-950" :style="`background-color: ${doorColor(calc.doorOf[fichaC.id])}`"
                            x-text="doorShort(calc.doorOf[fichaC.id]) + ' · ' + doorName(calc.doorOf[fichaC.id])"></span>
                    </template>
                    <template x-if="isIn(fichaC.id) && starts.length && ! calc.doorOf[fichaC.id]">
                        <span class="rounded-md bg-amber-400 px-1.5 py-0.5 text-[10px] font-black text-slate-950">sin puerta</span>
                    </template>
                </div>

                <p x-show="inherits" class="rounded-xl border border-amber-500/30 bg-amber-500/10 px-3 py-2 text-[10px] leading-4 text-amber-200">
                    Esta edición usa lo del torneo. Si cambias algo aquí, pasa a tener su propia configuración: parte de lo del torneo y el torneo no se toca.
                </p>

                {{-- La mano --}}
                <div>
                    <p class="text-[9px] font-black uppercase tracking-wider text-slate-500">Quién decide</p>
                    <div class="mt-1 grid grid-cols-3 gap-1">
                        <button type="button" @click="setHand(fichaC.id, null)"
                            :class="! handOf(fichaC.id) ? 'border-slate-400 bg-slate-800 text-white' : 'border-slate-800 text-slate-400 hover:border-slate-600'"
                            class="rounded-lg border px-2 py-1.5 text-[11px] font-black transition">Las condiciones</button>
                        <button type="button" @click="setHand(fichaC.id, 'IN')"
                            :class="handOf(fichaC.id) === 'IN' ? 'border-emerald-400 bg-emerald-500/20 text-emerald-100' : 'border-slate-800 text-slate-400 hover:border-emerald-500/50'"
                            class="rounded-lg border px-2 py-1.5 text-[11px] font-black transition">Dentro siempre</button>
                        <button type="button" @click="setHand(fichaC.id, 'OUT')"
                            :class="handOf(fichaC.id) === 'OUT' ? 'border-rose-400 bg-rose-500/20 text-rose-100' : 'border-slate-800 text-slate-400 hover:border-rose-500/50'"
                            class="rounded-lg border px-2 py-1.5 text-[11px] font-black transition">Fuera siempre</button>
                    </div>
                </div>

                {{-- Su puerta, cuando se reparte a mano --}}
                <div x-show="isIn(fichaC.id) && starts.length > 1 && effDoors.mode === 'MANUAL'">
                    <p class="text-[9px] font-black uppercase tracking-wider text-slate-500">Entra por</p>
                    <div class="mt-1 flex flex-wrap gap-1">
                        <template x-for="s in starts" :key="'fs' + s.id">
                            <button type="button" @click="assignTo(fichaC.id, s.id)"
                                class="rounded-lg border px-2 py-1 text-[11px] font-black transition"
                                :style="calc.doorOf[fichaC.id] === s.id ? `border-color: ${doorColor(s.id)}; background-color: ${doorColor(s.id)}33; color: #f8fafc` : 'border-color: #1e293b; color: #94a3b8'"
                                x-text="doorShort(s.id) + ' · ' + s.name"></button>
                        </template>
                        <button type="button" @click="assignTo(fichaC.id, null)" class="rounded-lg border border-slate-800 px-2 py-1 text-[11px] font-black text-slate-500 hover:text-white">Ninguna</button>
                    </div>
                </div>

                {{-- Sus atributos --}}
                <div>
                    <p class="text-[9px] font-black uppercase tracking-wider text-slate-500">Sus atributos</p>
                    <p class="text-[10px] text-slate-600">En rosa, lo que pide alguna condición. Pulsa un valor para añadirlo a la regla.</p>

                    <template x-if="! fichaC.attributes.length">
                        <p class="mt-1 rounded-lg border border-dashed border-slate-800 px-2 py-2 text-center text-[10px] text-slate-500">Sin atributos: solo puede entrar sin condiciones o a mano.</p>
                    </template>

                    <div class="mt-1 space-y-1">
                        <template x-for="a in fichaC.attributes" :key="'fa' + a.name">
                            <div class="flex flex-wrap items-center gap-1 rounded-lg bg-slate-900 px-2 py-1">
                                <span class="mr-1 text-[10px] font-black text-slate-400" x-text="a.label"></span>
                                <template x-for="(etiqueta, i) in a.labels" :key="'fav' + a.name + i">
                                    <button type="button" @click="addRule(a.name, a.values[i]); panel = 'who'"
                                        class="rounded px-1.5 py-0.5 text-[10px] font-bold transition"
                                        :class="valueWanted(a.name, a.values[i]) ? 'bg-rose-500/25 text-rose-100' : 'bg-slate-950 text-slate-300 hover:bg-slate-800'"
                                        x-text="etiqueta"></button>
                                </template>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- Sus caras --}}
                <div>
                    <p class="text-[9px] font-black uppercase tracking-wider text-slate-500">Con qué cara sale en este torneo</p>
                    <p class="text-[10px] leading-4" :style="`color: ${fromTone(face(fichaC).from)}`" x-text="fromLabel(face(fichaC).from)"></p>

                    <div class="mt-2 grid grid-cols-3 gap-1.5">

                        <button type="button" @click="setFace(fichaC.id, null)"
                            class="overflow-hidden rounded-xl border text-left transition"
                            :class="faceChoice(fichaC.id) === null ? 'border-violet-400 ring-2 ring-violet-400/30' : 'border-slate-800 hover:border-slate-600'">
                            <span class="relative block aspect-square bg-slate-900">
                                <template x-if="autoFace(fichaC).image_url"><img :src="autoFace(fichaC).image_url" alt="" class="h-full w-full object-cover opacity-80"></template>
                                <span class="absolute left-1 top-1 flex items-center gap-0.5 rounded bg-violet-500 px-1 text-[8px] font-black uppercase text-slate-950">
                                    <x-omni-icon name="chispa" size="h-2.5 w-2.5" /> auto
                                </span>
                            </span>
                            <span class="block truncate px-1.5 py-1 text-[10px] font-black text-slate-200" x-text="autoFace(fichaC).version_id ? autoFace(fichaC).name : 'De siempre'"></span>
                        </button>

                        <template x-for="v in fichaC.versions" :key="'fv' + v.id">
                            <button type="button" @click="setFace(fichaC.id, v.id)"
                                class="overflow-hidden rounded-xl border text-left transition"
                                :class="faceChoice(fichaC.id) === v.id ? 'border-amber-400 ring-2 ring-amber-400/30' : 'border-slate-800 hover:border-slate-600'">
                                <span class="relative block aspect-square bg-slate-900">
                                    <template x-if="v.image_url"><img :src="v.image_url" alt="" class="h-full w-full object-cover"></template>
                                    <template x-if="! v.image_url">
                                        <span class="flex h-full w-full flex-col items-center justify-center gap-1 text-amber-400">
                                            <x-omni-icon name="aviso" size="h-5 w-5" />
                                            <span class="text-[8px] font-black uppercase">sin imagen</span>
                                        </span>
                                    </template>
                                    <span class="absolute left-1 top-1 flex gap-0.5">
                                        <span x-show="v.is_base" class="rounded bg-emerald-400 px-1 text-[8px] font-black uppercase text-slate-950">base</span>
                                        <span x-show="v.is_default" class="rounded bg-teal-400 px-1 text-[8px] font-black uppercase text-slate-950">defecto</span>
                                    </span>
                                </span>
                                <span class="block truncate px-1.5 pt-1 text-[10px] font-black text-slate-200" x-text="v.name"></span>
                                <span class="flex flex-wrap gap-0.5 px-1.5 pb-1">
                                    <template x-for="(l, li) in v.activation" :key="'act' + v.id + li">
                                        <span class="truncate rounded bg-violet-500/15 px-1 text-[8px] font-bold text-violet-300" :title="'La activa ' + l.attribute_label + ' → ' + l.value_label" x-text="l.value_label"></span>
                                    </template>
                                    <span x-show="! v.activation.length" class="text-[8px] text-slate-600">sin vínculo</span>
                                </span>
                            </button>
                        </template>

                        <button type="button" @click="setFace(fichaC.id, 0)" x-show="fichaC.versions.length"
                            class="overflow-hidden rounded-xl border text-left transition"
                            :class="faceChoice(fichaC.id) === 0 ? 'border-amber-400 ring-2 ring-amber-400/30' : 'border-slate-800 hover:border-slate-600'">
                            <span class="block aspect-square bg-slate-900">
                                <template x-if="fichaC.image_url"><img :src="fichaC.image_url" alt="" class="h-full w-full object-cover grayscale"></template>
                            </span>
                            <span class="block truncate px-1.5 py-1 text-[10px] font-black text-slate-200">De siempre</span>
                        </button>
                    </div>

                    <p x-show="! fichaC.versions.length" class="mt-2 text-[10px] leading-4 text-slate-500">
                        No tiene versiones: sale siempre con su imagen. Las versiones se crean en la Biblioteca y se
                        traen al universo al sincronizarla.
                    </p>
                </div>
            </div>
        </aside>
    </template>
</div>
