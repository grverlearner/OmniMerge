{{--
    La cabecera de la sala de una edición: de dónde sale la configuración,
    desde dónde se parte, las cifras, y cerrar aplicando o sin aplicar.
    Nada se guarda aquí: se guarda con el resto de la edición.
--}}

<section class="overflow-hidden rounded-2xl border border-rose-500/30 bg-slate-900/80">

    <div class="flex flex-wrap items-center gap-3 px-3 py-2.5">

        <span class="flex h-11 w-11 shrink-0 items-center justify-center overflow-hidden rounded-xl border border-rose-500/30 bg-slate-950 text-rose-300">
            <template x-if="tournament.image_url"><img :src="tournament.image_url" alt="" class="h-full w-full object-cover"></template>
            <template x-if="! tournament.image_url"><x-omni-icon name="usuario" size="h-5 w-5" /></template>
        </span>

        <div class="min-w-0">
            <p class="text-[9px] font-black uppercase tracking-[0.2em] text-rose-400">Participantes de esta edición</p>
            <p class="truncate text-[16px] font-black leading-tight text-white" x-text="tournament.name"></p>
            <p class="truncate text-[10px] text-slate-500">
                Forma <span class="text-slate-300" x-text="template?.name ?? 'sin elegir'"></span>
                · <span x-text="starts.length === 1 ? '1 puerta' : starts.length + ' puertas'"></span>
            </p>
        </div>

        {{-- De dónde sale --}}
        <span class="flex rounded-xl border border-slate-800 bg-slate-950 p-1">
            <button type="button" @click="useTournament()"
                :class="inherits ? 'bg-amber-500 text-slate-950' : 'text-slate-400 hover:text-white'"
                class="flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-[11px] font-black transition">
                <x-omni-icon name="trofeo" size="h-3.5 w-3.5" />
                Como el torneo
            </button>
            <button type="button" @click="customize()"
                :class="! inherits ? 'bg-rose-500 text-slate-950' : 'text-slate-400 hover:text-white'"
                class="flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-[11px] font-black transition">
                <x-omni-icon name="pincel" size="h-3.5 w-3.5" />
                Distinta en esta edición
            </button>
        </span>

        {{-- Desde dónde se parte --}}
        <span x-show="! inherits" x-cloak class="flex rounded-xl border border-slate-800 bg-slate-950 p-1">
            <button type="button" @click="setScope('TOURNAMENT')"
                :class="scope === 'TOURNAMENT' ? 'bg-slate-700 text-white' : 'text-slate-500 hover:text-white'"
                class="rounded-lg px-2.5 py-1.5 text-[10px] font-black transition"
                title="Parte de los que deja entrar el torneo y los estrecha con tus condiciones">
                De lo que permite el torneo <span class="font-mono opacity-60" x-text="baseCount"></span>
            </button>
            <button type="button" @click="setScope('UNIVERSE')"
                :class="scope === 'UNIVERSE' ? 'bg-slate-700 text-white' : 'text-slate-500 hover:text-white'"
                class="rounded-lg px-2.5 py-1.5 text-[10px] font-black transition"
                title="Ignora las reglas del torneo solo en esta edición">
                De todo el universo <span class="font-mono opacity-60" x-text="roster.length"></span>
            </button>
        </span>

        <span class="flex-1"></span>

        <div class="flex flex-wrap items-stretch gap-1.5">
            <div class="rounded-xl border border-emerald-500/30 bg-emerald-500/10 px-3 py-1 text-center">
                <span class="block font-mono text-xl font-black leading-tight text-emerald-300" x-text="totalIn"></span>
                <span class="block text-[8px] font-black uppercase tracking-wider text-emerald-400/70">juegan</span>
            </div>
            <div x-show="starts.length" class="rounded-xl border px-3 py-1 text-center"
                :class="unplaced.length ? 'border-amber-500/40 bg-amber-500/10' : 'border-sky-500/30 bg-sky-500/10'">
                <span class="block font-mono text-xl font-black leading-tight" :class="unplaced.length ? 'text-amber-300' : 'text-sky-300'"
                    x-text="placed + (capacityTotal ? '/' + capacityTotal : '')"></span>
                <span class="block text-[8px] font-black uppercase tracking-wider text-slate-500">en puertas</span>
            </div>
            <button type="button" x-show="noImage.length" x-cloak @click="issuesOnly = true; view = 'stage'"
                class="rounded-xl border border-amber-500/40 bg-amber-500/10 px-3 py-1 text-center transition hover:bg-amber-500/20">
                <span class="block font-mono text-xl font-black leading-tight text-amber-300" x-text="noImage.length"></span>
                <span class="block text-[8px] font-black uppercase tracking-wider text-amber-400/70">sin imagen</span>
            </button>
        </div>

        <div class="flex items-center gap-1.5">
            <button type="button" @click="cancelRoom()"
                class="rounded-xl border border-slate-700 px-3 py-2 text-[11px] font-black text-slate-300 transition hover:border-slate-500 hover:text-white">
                Cancelar
            </button>
            <button type="button" @click="applyRoom()"
                class="flex items-center gap-1.5 rounded-xl bg-rose-500 px-4 py-2 text-[12px] font-black text-slate-950 transition hover:bg-rose-400">
                <x-omni-icon name="check" size="h-4 w-4" />
                Usar esto
            </button>
        </div>
    </div>

    <div class="h-1 bg-slate-800">
        <div class="h-full bg-gradient-to-r from-emerald-500 to-emerald-300 transition-all duration-300"
            :style="`width: ${roster.length ? (totalIn / roster.length) * 100 : 0}%`"></div>
    </div>

    <template x-if="notice">
        <p class="flex items-start gap-2 border-t border-sky-500/30 bg-sky-500/10 px-3 py-2 text-[10px] leading-4 text-sky-200">
            <x-omni-icon name="chispa" size="h-4 w-4" class="shrink-0 text-sky-300" />
            <span x-text="notice"></span>
            <button type="button" @click="notice = ''" class="ml-auto text-sky-300 hover:text-white"><x-omni-icon name="cerrar" size="h-3.5 w-3.5" /></button>
        </p>
    </template>

    <template x-if="totalIn === 0">
        <p class="flex items-start gap-2 border-t border-rose-500/30 bg-rose-500/10 px-3 py-2 text-[10px] font-bold leading-4 text-rose-200">
            <x-omni-icon name="aviso" size="h-4 w-4" class="shrink-0 text-rose-300" />
            Así no entra nadie y la edición no se podría crear. Afloja alguna condición o parte de todo el universo.
        </p>
    </template>

    <p class="border-t border-slate-800 px-3 py-1.5 text-[9px] text-slate-600">
        «Usar esto» cierra la sala con estos cambios; se guardan al crear o guardar la edición. «Cancelar» deja lo que había al abrirla.
    </p>
</section>
