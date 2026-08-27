@php
    /*
     * Un enfrentamiento del cuadro.
     *
     * Se dibuja igual esté jugado o no, porque el hueco vacío también dice
     * algo: significa que el partido que lo alimenta todavía no se ha
     * decidido. Por eso se pinta una silueta a rayas en vez de nada.
     *
     * Tres estados, y se distinguen sin leer:
     *
     *   sin jugar   bordes apagados, marcador «vs», botón de simular
     *   jugado      el ganador en verde con su ▲, el otro atenuado
     *   directo     un lado vacío: el otro pasa sin jugar, en ámbar
     *
     * Espera `match` en el ámbito de Alpine. Se usa dentro de un x-for.
     */
@endphp

<div class="rounded-lg border transition"
    :class="isBye(match)
        ? 'border-amber-500/30 bg-amber-500/5'
        : (decisionOf(match)
            ? 'border-slate-700 bg-slate-950/60'
            : 'border-slate-800 bg-slate-950/30')">

    {{-- Cabecera: número y botón --}}

    <div class="flex items-center gap-1 border-b border-slate-800/60 px-1.5 py-0.5">

        <span class="font-mono text-[8px] text-slate-600"
            x-text="'#' + String(match.index + 1).padStart(2, '0')"></span>

        <template x-if="isBye(match)">
            <span class="text-[8px] font-black text-amber-400/80">pasa directo</span>
        </template>

        <button type="button" x-show="!isBye(match)"
            @click="simulateMatch(match)"
            :disabled="!isPlayable(match)"
            class="ml-auto rounded px-1 text-[9px] font-black transition"
            :class="!isPlayable(match)
                ? 'cursor-not-allowed text-slate-700'
                : (decisionOf(match)
                    ? 'text-slate-400 hover:bg-slate-700'
                    : 'text-amber-400 hover:bg-amber-500/20')"
            :title="!isPlayable(match)
                ? 'Faltan los resultados de la ronda anterior'
                : (decisionOf(match) ? 'Volver a simular' : 'Simular')">
            ⚡
        </button>

    </div>


    {{-- Los dos lados --}}

    <div class="divide-y divide-slate-800/60">

        <template x-for="side in ['a', 'b']" :key="'s' + match.index + side">
            <div class="flex items-center gap-1 px-1.5 py-1 transition"
                :class="decisionOf(match) === side ? 'bg-emerald-500/10' : ''">

                {{-- Cara --}}

                <span class="h-5 w-5 shrink-0 overflow-hidden rounded bg-slate-800 ring-1"
                    :class="(() => {
                        if (match[side].type === 'BYE') return 'ring-slate-800';
                        const seed = seedOf(match[side]);
                        const gate = seed ? gateOfSeed(seed) : null;
                        return gate ? gate.color.ring : 'ring-slate-700';
                    })()">
                    <template x-if="occupant(match[side])?.image_url">
                        <img :src="occupant(match[side]).image_url" alt=""
                            class="h-full w-full object-cover"
                            :class="decisionOf(match) && decisionOf(match) !== side ? 'opacity-40 grayscale' : ''">
                    </template>
                </span>

                {{-- Nombre, o por qué no hay nombre --}}

                <span class="min-w-0 flex-1 truncate text-[10px] font-bold"
                    :class="(() => {
                        if (match[side].type === 'BYE') return 'text-slate-700 italic';
                        if (!occupant(match[side])) return 'text-slate-700';
                        const d = decisionOf(match);
                        if (!d) return 'text-slate-300';
                        return d === side ? 'text-emerald-300' : 'text-slate-600 line-through';
                    })()"
                    :title="occupant(match[side])?.name ?? ''"
                    x-text="match[side].type === 'BYE'
                        ? 'nadie'
                        : (occupant(match[side])?.short ?? '· · ·')"></span>

                {{-- Puesto del cuadro --}}

                <template x-if="seedOf(match[side])">
                    <span class="shrink-0 font-mono text-[8px] text-slate-600"
                        x-text="seedOf(match[side])"></span>
                </template>

                {{-- Quién pasa --}}

                <template x-if="decisionOf(match) === side">
                    <span class="shrink-0 text-[9px] text-emerald-400">▲</span>
                </template>

                {{--
                    Mover a alguien de puesto, solo en orden manual y solo en
                    la primera ronda: a partir de ahí el sitio lo decide quien
                    gana, no quien lo elige.
                --}}
                <template x-if="showsManual && !hasResults && match[side].type === 'SEED'">
                    <span class="flex shrink-0 gap-0.5">
                        <button type="button" @click="move(match[side].seed - 1, -1)"
                            class="text-[8px] text-slate-600 transition hover:text-slate-200">▲</button>
                        <button type="button" @click="move(match[side].seed - 1, 1)"
                            class="text-[8px] text-slate-600 transition hover:text-slate-200">▼</button>
                    </span>
                </template>

            </div>
        </template>

    </div>

</div>
