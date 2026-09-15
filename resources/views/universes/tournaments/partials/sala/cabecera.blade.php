{{--
    La cabecera de la sala: de qué torneo es, las cifras que importan y
    guardar. Las cifras son la respuesta corta a «¿cómo va esto?».
--}}

<section class="overflow-hidden rounded-2xl border border-rose-500/30 bg-slate-900/70">

    <div class="flex flex-wrap items-center gap-3 px-3 py-2.5">

        <a href="{{ route('universes.tournaments.show', [$universe, $torneo]) }}" title="Volver al torneo"
            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-slate-800 text-slate-400 transition hover:border-slate-600 hover:text-white">
            <x-omni-icon name="flecha-izquierda" size="h-4 w-4" />
        </a>

        <span class="flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-xl border border-rose-500/30 bg-slate-950 text-rose-300">
            @if ($torneo->image_url)
                <img src="{{ $torneo->image_url }}" alt="" class="h-full w-full object-cover">
            @else
                <x-omni-icon name="trofeo" size="h-5 w-5" />
            @endif
        </span>

        <div class="min-w-0 flex-1">
            <p class="text-[9px] font-black uppercase tracking-[0.2em] text-rose-400">Sala de participantes</p>
            <h1 class="truncate text-[17px] font-black leading-tight text-white">{{ $torneo->name }}</h1>
            <p class="truncate text-[10px] text-slate-500">
                Plantilla {{ $torneo->tournamentTemplate?->name ?? 'sin plantilla' }}
                · <span x-text="starts.length === 1 ? '1 puerta de entrada' : starts.length + ' puertas de entrada'"></span>
                · lo heredan sus ediciones nuevas
            </p>
        </div>

        <div class="flex flex-wrap items-stretch gap-1.5">
            <div class="rounded-xl border border-emerald-500/30 bg-emerald-500/10 px-3 py-1 text-center">
                <span class="block font-mono text-xl font-black leading-tight text-emerald-300" x-text="totalIn"></span>
                <span class="block text-[8px] font-black uppercase tracking-wider text-emerald-400/70">dentro</span>
            </div>

            <div class="rounded-xl border border-slate-700 bg-slate-950/60 px-3 py-1 text-center">
                <span class="block font-mono text-xl font-black leading-tight text-slate-400" x-text="totalOut"></span>
                <span class="block text-[8px] font-black uppercase tracking-wider text-slate-500">fuera</span>
            </div>

            <div x-show="starts.length" class="rounded-xl border px-3 py-1 text-center"
                :class="unplaced.length ? 'border-amber-500/40 bg-amber-500/10' : 'border-sky-500/30 bg-sky-500/10'">
                <span class="block font-mono text-xl font-black leading-tight"
                    :class="unplaced.length ? 'text-amber-300' : 'text-sky-300'"
                    x-text="placed + (capacityTotal ? '/' + capacityTotal : '')"></span>
                <span class="block text-[8px] font-black uppercase tracking-wider text-slate-500">en puertas</span>
            </div>

            <button type="button" x-show="noImage.length" x-cloak @click="issuesOnly = true; view = 'stage'"
                title="Ver quién sale sin imagen"
                class="rounded-xl border border-amber-500/40 bg-amber-500/10 px-3 py-1 text-center transition hover:bg-amber-500/20">
                <span class="block font-mono text-xl font-black leading-tight text-amber-300" x-text="noImage.length"></span>
                <span class="block text-[8px] font-black uppercase tracking-wider text-amber-400/70">sin imagen</span>
            </button>
        </div>

        <form method="POST" action="{{ route('universes.tournaments.participants.update', [$universe, $torneo]) }}"
            @submit="prepareSubmit()" class="flex items-center gap-1.5">
            @csrf
            @method('PUT')
            <input type="hidden" name="design" :value="designJson">

            <span x-show="dirty" x-cloak class="flex items-center gap-1.5 text-[10px] font-black text-amber-300">
                <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-amber-400"></span>
                Sin guardar
            </span>

            <button type="button" x-show="dirty" x-cloak @click="discard()" title="Volver a lo guardado"
                class="flex items-center gap-1 rounded-xl border border-slate-700 px-2.5 py-2 text-[11px] font-black text-slate-300 transition hover:border-slate-500 hover:text-white">
                <x-omni-icon name="deshacer" size="h-3.5 w-3.5" />
                Descartar
            </button>

            <button type="submit" :disabled="! dirty"
                class="flex items-center gap-1.5 rounded-xl bg-rose-500 px-4 py-2 text-[12px] font-black text-slate-950 transition hover:bg-rose-400 disabled:cursor-not-allowed disabled:opacity-40">
                <x-omni-icon name="guardar" size="h-3.5 w-3.5" />
                Guardar
            </button>
        </form>
    </div>

    {{-- Cuánto del universo entra --}}
    <div class="h-1 bg-slate-800">
        <div class="h-full bg-gradient-to-r from-emerald-500 to-emerald-300 transition-all duration-300"
            :style="`width: ${roster.length ? (totalIn / roster.length) * 100 : 0}%`"></div>
    </div>

    <template x-if="serverMismatch">
        <p class="flex items-start gap-2 border-t border-amber-500/30 bg-amber-500/10 px-3 py-2 text-[10px] leading-4 text-amber-200">
            <x-omni-icon name="aviso" size="h-4 w-4" class="shrink-0 text-amber-300" />
            Esta pantalla y el servidor no calculan lo mismo con lo guardado. Manda el servidor: guarda y vuelve
            a abrir la sala para ver la lista buena, y avisa del caso.
        </p>
    </template>

    <template x-if="totalIn === 0">
        <p class="flex items-start gap-2 border-t border-rose-500/30 bg-rose-500/10 px-3 py-2 text-[10px] font-bold leading-4 text-rose-200">
            <x-omni-icon name="aviso" size="h-4 w-4" class="shrink-0 text-rose-300" />
            Nadie cumple estas condiciones: con ellas no se podría crear ninguna edición. Afloja alguna o cambia cómo se combinan.
        </p>
    </template>
</section>
