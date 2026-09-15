{{--
    Una edición que usa lo del torneo: qué dice el torneo, sin poder
    tocarlo aquí, y cómo pasar a lo propio. Va dentro de un x-if.
--}}

<section class="space-y-2">

    <div class="rounded-2xl border border-amber-500/30 bg-slate-900/60 p-3">
        <div class="flex items-center gap-2">
            <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-amber-500/15 text-amber-300">
                <x-omni-icon name="trofeo" size="h-4 w-4" />
            </span>
            <div class="min-w-0 flex-1">
                <p class="text-[13px] font-black text-white">Como dice el torneo</p>
                <p class="truncate text-[10px] text-slate-500" x-text="'Configurado en la sala de ' + (tournament.name ?? 'el torneo')"></p>
            </div>
        </div>

        <p class="mt-2 text-[10px] leading-4 text-slate-400">
            Esta edición usa quién entra, el reparto por puertas y las caras que se decidieron para el torneo.
            Si cambias el torneo antes de crearla, esta edición lo recoge.
        </p>

        <dl class="mt-3 space-y-2">
            <div class="rounded-xl border border-slate-800 bg-slate-950/60 px-2.5 py-2">
                <dt class="text-[9px] font-black uppercase tracking-wider text-rose-300">Quién entra</dt>
                <dd class="mt-0.5 text-[11px] leading-4 text-slate-200" x-text="ruleText(base)"></dd>
                <dd class="mt-1 flex flex-wrap gap-1 text-[9px] font-black">
                    <span class="rounded bg-emerald-500/15 px-1.5 py-0.5 text-emerald-300" x-text="baseCount + ' lo cumplen'"></span>
                    <span x-show="(base?.include ?? []).length" class="rounded bg-emerald-500/10 px-1.5 py-0.5 text-emerald-200" x-text="(base?.include ?? []).length + ' metidos a mano'"></span>
                    <span x-show="(base?.exclude ?? []).length" class="rounded bg-rose-500/10 px-1.5 py-0.5 text-rose-200" x-text="(base?.exclude ?? []).length + ' sacados a mano'"></span>
                </dd>
            </div>

            <div class="rounded-xl border border-slate-800 bg-slate-950/60 px-2.5 py-2">
                <dt class="text-[9px] font-black uppercase tracking-wider text-sky-300">Puertas</dt>
                <dd class="mt-0.5 text-[11px] leading-4 text-slate-200" x-text="doorsText(tournamentDoors)"></dd>
                <dd x-show="doorsMismatch" class="mt-1 text-[10px] leading-4 text-amber-300">
                    El reparto del torneo es para otra plantilla, así que aquí se reparte solo, equilibrado.
                </dd>
            </div>

            <div class="rounded-xl border border-slate-800 bg-slate-950/60 px-2.5 py-2">
                <dt class="text-[9px] font-black uppercase tracking-wider text-violet-300">Caras</dt>
                <dd class="mt-0.5 text-[11px] leading-4 text-slate-200"
                    x-text="(base?.face_mode === 'BASE' ? 'Siempre la de siempre' : 'La que toca según lo que pide el torneo')
                        + (Object.keys(base?.faces ?? {}).length ? ' · ' + Object.keys(base.faces).length + ' elegidas a mano' : '')"></dd>
            </div>
        </dl>

        <div class="mt-3 grid gap-1.5">
            <button type="button" @click="customize()"
                class="flex items-center justify-center gap-1.5 rounded-xl bg-rose-500 px-3 py-2 text-[12px] font-black text-slate-950 transition hover:bg-rose-400">
                <x-omni-icon name="pincel" size="h-4 w-4" />
                Personalizar esta edición
            </button>

            <a :href="tournament.room_url" target="_blank" rel="noopener"
                class="flex items-center justify-center gap-1.5 rounded-xl border border-slate-700 px-3 py-2 text-[11px] font-black text-slate-300 transition hover:border-amber-400 hover:text-amber-200">
                <x-omni-icon name="flecha-derecha" size="h-3.5 w-3.5" />
                Abrir la sala del torneo
            </a>
        </div>
    </div>

    <p class="px-1 text-[10px] leading-4 text-slate-600">
        Pulsa cualquier cara para ver por qué entra y con qué versión sale. Si decides algo en su ficha,
        la edición pasa a tener su propia configuración.
    </p>
</section>
