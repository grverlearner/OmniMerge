{{--
    CARAS

    Con qué imagen sale cada uno en este torneo. Una entidad con versiones
    —Naruto niño, clásico, Shippuden— sale con la que la Biblioteca vincula
    al elemento de catálogo que pide la regla, salvo que se elija otra.
--}}

@php
    $fuentes = [
        'MANUAL' => 'Elegida a mano',
        'CATALOG' => 'Por su vínculo con el catálogo',
        'ATTRIBUTES' => 'Por sus atributos',
        'BASE' => 'Su versión base',
        'DEFAULT' => 'Su versión por defecto',
        'ENTITY' => 'Su imagen de siempre',
    ];
@endphp

<div x-show="panel === 'faces'" x-cloak class="space-y-2">

    {{-- ============ QUÉ CARA ============ --}}

    <section class="rounded-2xl border border-violet-500/30 bg-slate-900/50 p-3">
        <p class="text-[9px] font-black uppercase tracking-wider text-slate-500">Qué cara pone la sala</p>

        <div class="mt-1.5 grid grid-cols-2 gap-1">
            <button type="button" @click="faceMode = 'AUTO'"
                :class="faceMode === 'AUTO' ? 'border-violet-400 bg-violet-500/15 text-violet-100' : 'border-slate-800 text-slate-400 hover:border-slate-600'"
                class="rounded-lg border px-2 py-1.5 text-left transition">
                <span class="block text-[11px] font-black">La que toca</span>
                <span class="block text-[9px] leading-3 opacity-70">Según lo que pide el torneo o su puerta</span>
            </button>
            <button type="button" @click="faceMode = 'BASE'"
                :class="faceMode === 'BASE' ? 'border-violet-400 bg-violet-500/15 text-violet-100' : 'border-slate-800 text-slate-400 hover:border-slate-600'"
                class="rounded-lg border px-2 py-1.5 text-left transition">
                <span class="block text-[11px] font-black">La de siempre</span>
                <span class="block text-[9px] leading-3 opacity-70">Su base, aunque la regla pida otra cosa</span>
            </button>
        </div>

        <ol x-show="faceMode === 'AUTO'" class="mt-2 space-y-0.5 text-[10px] leading-4 text-slate-500">
            <li><span class="font-black text-amber-300">1.</span> La que elijas a mano en su ficha.</li>
            <li><span class="font-black text-violet-300">2.</span> La versión que la Biblioteca activa con un elemento de catálogo que pide la regla: «Naruto clásico» con «Anime → Naruto».</li>
            <li><span class="font-black text-sky-300">3.</span> La única versión cuyos atributos cumplen la regla.</li>
            <li><span class="font-black text-emerald-300">4.</span> Su versión base, luego la de por defecto, y si no, su imagen de siempre.</li>
        </ol>
        <p x-show="faceMode === 'AUTO' && starts.length > 1 && effDoors.mode === 'RULES'" class="mt-1.5 text-[10px] leading-4 text-sky-300/80">
            Con reparto por reglas, cuenta primero la condición de la puerta por la que entra.
        </p>
    </section>


    {{-- ============ DE DÓNDE SALE CADA CARA ============ --}}

    <section class="rounded-2xl border border-slate-800 bg-slate-900/50 p-3">
        <div class="flex items-center gap-2">
            <p class="mr-auto text-[12px] font-black text-white">De dónde sale cada cara</p>
            <button type="button" x-show="faceFilter" @click="faceFilter = null" class="text-[10px] font-black text-slate-400 underline hover:text-white">ver todas</button>
        </div>

        <div class="mt-2 grid grid-cols-2 gap-1">
            @foreach ($fuentes as $clave => $texto)
                <button type="button" @click="faceFilter = faceFilter === '{{ $clave }}' ? null : '{{ $clave }}'; view = 'stage'"
                    class="flex items-center gap-2 rounded-lg border px-2 py-1.5 text-left transition"
                    :style="faceFilter === '{{ $clave }}' ? `border-color: ${fromTone('{{ $clave }}')}; background-color: ${fromTone('{{ $clave }}')}22` : 'border-color: #1e293b'">
                    <span class="font-mono text-[15px] font-black" :style="`color: ${fromCount('{{ $clave }}') ? fromTone('{{ $clave }}') : '#475569'}`" x-text="fromCount('{{ $clave }}')"></span>
                    <span class="text-[10px] font-bold leading-3 text-slate-400">{{ $texto }}</span>
                </button>
            @endforeach
        </div>
    </section>


    {{-- ============ LOS QUE TIENEN VARIAS CARAS ============ --}}

    <section class="rounded-2xl border border-slate-800 bg-slate-900/50 p-3">
        <p class="text-[12px] font-black text-white">
            Con varias versiones <span class="font-mono text-[10px] text-slate-500" x-text="withVersions.length"></span>
        </p>
        <p class="mt-0.5 text-[10px] leading-4 text-slate-500">Pulsa una versión para fijarla en este torneo.</p>

        <template x-if="! withVersions.length">
            <p class="mt-2 rounded-xl border border-dashed border-slate-700 px-3 py-3 text-center text-[10px] leading-4 text-slate-500">
                Nadie de los que entran tiene versiones: todos salen con su imagen de siempre.
            </p>
        </template>

        <template x-for="c in withVersions" :key="'wv' + c.id">
            <div class="mt-2 rounded-xl border border-slate-800 bg-slate-950/60 p-2">
                <div class="flex items-center gap-2">
                    <button type="button" @click="ficha = c.id" class="h-10 w-10 shrink-0 overflow-hidden rounded-lg border bg-slate-900" :style="`border-color: ${fromTone(face(c).from)}`">
                        <template x-if="face(c).image_url"><img :src="face(c).image_url" alt="" class="h-full w-full object-cover"></template>
                    </button>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-[11px] font-black text-white" x-text="c.name"></p>
                        <p class="truncate text-[9px] font-bold" :style="`color: ${fromTone(face(c).from)}`" x-text="(face(c).version_id ? face(c).name + ' · ' : '') + fromLabel(face(c).from)"></p>
                    </div>
                </div>

                <div class="mt-1.5 flex gap-1 overflow-x-auto pb-0.5">
                    <button type="button" @click="setFace(c.id, null)"
                        :class="faceChoice(c.id) === null ? 'border-violet-400 text-violet-200' : 'border-slate-800 text-slate-500 hover:text-slate-300'"
                        class="flex h-12 w-12 shrink-0 flex-col items-center justify-center rounded-lg border text-[8px] font-black uppercase" title="Que decida la sala">
                        <x-omni-icon name="chispa" size="h-3.5 w-3.5" />
                        auto
                    </button>
                    <template x-for="v in c.versions" :key="'wvv' + c.id + v.id">
                        <button type="button" @click="setFace(c.id, v.id)" :title="v.name"
                            class="relative h-12 w-12 shrink-0 overflow-hidden rounded-lg border bg-slate-900 transition"
                            :class="faceChoice(c.id) === v.id ? 'border-amber-400 ring-2 ring-amber-400/40' : (face(c).version_id === v.id ? 'border-violet-400/60' : 'border-slate-800 opacity-70 hover:opacity-100')">
                            <template x-if="v.image_url"><img :src="v.image_url" alt="" class="h-full w-full object-cover"></template>
                            <template x-if="! v.image_url"><span class="flex h-full w-full items-center justify-center text-amber-400"><x-omni-icon name="aviso" size="h-3.5 w-3.5" /></span></template>
                        </button>
                    </template>
                    <button type="button" @click="setFace(c.id, 0)"
                        :class="faceChoice(c.id) === 0 ? 'border-amber-400 ring-2 ring-amber-400/40' : 'border-slate-800 opacity-70 hover:opacity-100'"
                        class="relative h-12 w-12 shrink-0 overflow-hidden rounded-lg border bg-slate-900" title="Su imagen de siempre">
                        <template x-if="c.image_url"><img :src="c.image_url" alt="" class="h-full w-full object-cover grayscale"></template>
                        <span class="absolute inset-x-0 bottom-0 bg-slate-950/80 text-center text-[7px] font-black uppercase text-slate-300">siempre</span>
                    </button>
                </div>
            </div>
        </template>
    </section>


    {{-- ============ SIN IMAGEN ============ --}}

    <template x-if="noImage.length">
        <section class="rounded-2xl border border-amber-500/30 bg-amber-500/5 p-3">
            <p class="flex items-center gap-1.5 text-[11px] font-black text-amber-200">
                <x-omni-icon name="aviso" size="h-4 w-4" />
                <span x-text="noImage.length === 1 ? '1 sale sin imagen' : noImage.length + ' salen sin imagen'"></span>
            </p>
            <p class="mt-0.5 text-[10px] leading-4 text-slate-500">
                Su versión no tiene archivo o la entidad no tiene imagen. Elige otra versión en su ficha o súbela en la Biblioteca.
            </p>
            <div class="mt-2 flex flex-wrap gap-1">
                <template x-for="c in noImage" :key="'ni' + c.id">
                    <button type="button" @click="ficha = c.id" class="rounded-lg border border-amber-500/30 bg-slate-950 px-2 py-1 text-[10px] font-bold text-slate-200 hover:border-amber-400" x-text="c.name"></button>
                </template>
            </div>
        </section>
    </template>
</div>
