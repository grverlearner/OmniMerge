@php
    /*
     * La decisión que el motor está esperando.
     *
     * Tres tipos, y los tres bloquean la fase igual:
     *
     *   GROUP_ASSIGNMENT          quién va a cada grupo
     *   PARTICIPANT_ORDER         en qué orden entran
     *   SINGLE_ELIMINATION_SETUP  el orden, y quién descansa
     *
     * Todos se responden con las caras delante. Repartir doce competidores
     * leyendo claves «UC-000123» no lo hace nadie.
     */

    $decision = $pendiente['decision'];
    $tipo = $decision['type'] ?? null;

    $grupos = $decision['groups'] ?? [];
    $pideOrden = $tipo === 'PARTICIPANT_ORDER'
        || (bool) data_get($decision, 'constraints.requires_order', false);
    $byes = (int) data_get($decision, 'constraints.bye_count', 0);
@endphp

<div x-data="phaseDecision({
        nodeId: {{ $pendiente['node_id'] }},
        decisionId: @js($decision['id']),
        type: @js($tipo),
        participants: @js($pendiente['participants']),
        groups: @js($grupos),
        byeCount: {{ $byes }},

        /*
         * La URL viaja en la configuración y no se toma del componente de
         * arriba a propósito: un x-data anidado hereda el ámbito para
         * EVALUAR expresiones, pero no para el `this` de sus propios
         * métodos. Pedirle `execute` al padre desde aquí dentro fallaría.
         */
        actionUrl: @js(route('universes.competitions.action', [$universe, $competition])),
        revision: @js((int) ($payload['revision'] ?? 0)),
    })"
    class="mx-auto max-w-5xl">

    {{-- ============ CABECERA ============ --}}

    <div class="rounded-t-2xl border border-b-0 border-amber-500/40 bg-amber-500/10 px-5 py-4">

        <div class="flex flex-wrap items-center gap-3">

            <span class="text-2xl">✋</span>

            <div class="min-w-0 flex-1">
                <p class="text-[10px] font-black uppercase tracking-[0.2em] text-amber-400">
                    {{ $pendiente['node_name'] }} · esperando tu decisión
                </p>
                <h3 class="mt-0.5 text-lg font-black text-white">
                    {{ $decision['title'] ?? 'Decisión manual' }}
                </h3>
            </div>

        </div>

        <p class="mt-2 max-w-3xl text-[12px] leading-relaxed text-slate-300">
            {{ $decision['description'] ?? '' }}
            Esta fase se configuró <span class="font-bold text-amber-200">a mano</span>,
            así que el motor no reparte solo: no arrancará hasta que lo decidas aquí.
        </p>

    </div>


    <div class="rounded-b-2xl border border-amber-500/40 bg-slate-900/60 p-5">

        {{-- ============ ERROR DEL MOTOR ============ --}}

        <template x-if="error">
            <p class="mb-4 rounded-xl border border-rose-500/40 bg-rose-500/10 px-3 py-2 text-[12px] font-bold text-rose-300"
                x-text="error"></p>
        </template>


        @if ($tipo === 'GROUP_ASSIGNMENT')

            {{-- ============================================ --}}
            {{-- REPARTIR EN GRUPOS --}}
            {{-- ============================================ --}}

            <div class="mb-4 flex flex-wrap items-center gap-2">

                <span class="text-[10px] font-black uppercase tracking-wider text-slate-500">
                    Sin repartir
                </span>

                <span class="rounded-lg bg-slate-800 px-2 py-1 font-mono text-[13px] font-black"
                    :class="unassigned.length ? 'text-amber-300' : 'text-emerald-300'"
                    x-text="unassigned.length"></span>

                <button type="button" @click="spreadEvenly()"
                    class="ml-auto rounded-xl border border-slate-700 px-3 py-1.5 text-[11px] font-black text-slate-300 transition hover:border-amber-400 hover:text-amber-300">
                    ⇄ repartir por mí
                </button>

                <button type="button" @click="clearAll()"
                    class="rounded-xl border border-slate-800 px-3 py-1.5 text-[11px] font-black text-slate-500 transition hover:border-slate-600">
                    vaciar
                </button>
            </div>


            {{-- LOS QUE FALTAN POR COLOCAR --}}

            <template x-if="unassigned.length">
                <div class="mb-4 rounded-xl border border-dashed border-slate-700 bg-slate-950/60 p-3">

                    <p class="mb-2 text-[10px] font-black uppercase tracking-wider text-slate-500">
                        Pulsa a uno y luego su grupo
                    </p>

                    <div class="flex flex-wrap gap-1.5">
                        <template x-for="c in unassigned" :key="'u' + c.key">
                            <button type="button" @click="selected = selected === c.key ? null : c.key"
                                class="flex items-center gap-1.5 rounded-lg border py-0.5 pl-0.5 pr-2 transition"
                                :class="selected === c.key
                                    ? 'border-amber-400 bg-amber-500/20'
                                    : 'border-slate-800 bg-slate-950 hover:border-slate-600'">

                                <span class="relative block h-7 w-7 shrink-0 overflow-hidden rounded bg-slate-900">
                                    <template x-if="c.image_url">
                                        <img :src="c.image_url" alt="" loading="lazy" class="h-full w-full object-cover">
                                    </template>
                                    <template x-if="!c.image_url">
                                        <span class="flex h-full w-full items-center justify-center font-mono text-[9px] font-black text-slate-700"
                                            x-text="c.name.slice(0, 2).toUpperCase()"></span>
                                    </template>
                                </span>

                                <span class="max-w-[140px] truncate text-[11px] font-bold text-slate-200" x-text="c.name"></span>
                            </button>
                        </template>
                    </div>

                </div>
            </template>


            {{-- LOS GRUPOS --}}

            <div class="grid gap-3 sm:grid-cols-2">

                <template x-for="g in groups" :key="g.key">
                    <div class="overflow-hidden rounded-xl border bg-slate-950/60 transition"
                        :class="{
                            'border-emerald-500/50': inGroup(g.key).length === g.size,
                            'border-rose-500/50': inGroup(g.key).length > g.size,
                            'border-slate-800': inGroup(g.key).length < g.size,
                        }">

                        {{-- Cabecera: cuántos van de cuántos caben --}}
                        <button type="button" @click="dropInto(g.key)"
                            class="flex w-full items-center gap-2 px-3 py-2 text-left transition"
                            :class="selected ? 'cursor-pointer bg-amber-500/10 hover:bg-amber-500/20' : ''">

                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-[12px] font-black text-slate-100" x-text="g.name"></span>
                                <span class="text-[9px] font-bold"
                                    :class="{
                                        'text-emerald-400': inGroup(g.key).length === g.size,
                                        'text-rose-400': inGroup(g.key).length > g.size,
                                        'text-amber-400': inGroup(g.key).length < g.size,
                                    }"
                                    x-text="inGroup(g.key).length === g.size
                                        ? '✓ completo'
                                        : (inGroup(g.key).length > g.size
                                            ? 'sobran ' + (inGroup(g.key).length - g.size)
                                            : 'faltan ' + (g.size - inGroup(g.key).length))"></span>
                            </span>

                            <span class="font-mono text-[15px] font-black leading-none">
                                <span :class="inGroup(g.key).length === g.size ? 'text-emerald-300' : 'text-slate-300'"
                                    x-text="inGroup(g.key).length"></span><span class="text-slate-600" x-text="'/' + g.size"></span>
                            </span>

                            <span x-show="selected" class="shrink-0 rounded-lg bg-amber-400 px-2 py-1 text-[10px] font-black text-slate-950">
                                poner aquí
                            </span>
                        </button>

                        {{-- Quién está dentro --}}
                        <div class="border-t border-slate-800/70 p-2">

                            <template x-if="inGroup(g.key).length === 0">
                                <p class="px-1 py-1 text-[10px] text-slate-600">Vacío.</p>
                            </template>

                            <div class="flex flex-wrap gap-1.5">
                                <template x-for="c in inGroup(g.key)" :key="g.key + c.key">
                                    <span class="flex items-center gap-1.5 rounded-lg border border-slate-800 bg-slate-950 py-0.5 pl-0.5 pr-1.5">

                                        <span class="relative block h-6 w-6 shrink-0 overflow-hidden rounded bg-slate-900">
                                            <template x-if="c.image_url">
                                                <img :src="c.image_url" alt="" loading="lazy" class="h-full w-full object-cover">
                                            </template>
                                            <template x-if="!c.image_url">
                                                <span class="flex h-full w-full items-center justify-center font-mono text-[8px] font-black text-slate-700"
                                                    x-text="c.name.slice(0, 2).toUpperCase()"></span>
                                            </template>
                                        </span>

                                        <span class="max-w-[110px] truncate text-[10px] font-bold text-slate-200" x-text="c.name"></span>

                                        <button type="button" @click.stop="takeOut(c.key)"
                                            title="Sacar del grupo"
                                            class="shrink-0 px-0.5 text-[11px] leading-none text-slate-600 transition hover:text-rose-400">×</button>
                                    </span>
                                </template>
                            </div>
                        </div>
                    </div>
                </template>

            </div>

        @elseif ($pideOrden)

            {{-- ============================================ --}}
            {{-- EL ORDEN DE ENTRADA --}}
            {{-- ============================================ --}}

            <p class="mb-3 text-[11px] leading-relaxed text-slate-400">
                El primero de la lista entra por la primera plaza del cuadro, el
                segundo por la siguiente, y así.
                @if ($byes > 0)
                    Marca además a los <span class="font-black text-amber-300">{{ $byes }}</span>
                    que <span class="font-bold">descansan</span> en la primera ronda.
                @endif
            </p>

            <div class="space-y-1.5">
                <template x-for="(c, i) in ordered" :key="'o' + c.key">
                    <div class="flex items-center gap-2 rounded-xl border border-slate-800 bg-slate-950/60 p-1.5">

                        <span class="w-7 shrink-0 text-center font-mono text-[12px] font-black text-slate-600" x-text="i + 1"></span>

                        <span class="relative block h-8 w-8 shrink-0 overflow-hidden rounded bg-slate-900">
                            <template x-if="c.image_url">
                                <img :src="c.image_url" alt="" loading="lazy" class="h-full w-full object-cover">
                            </template>
                            <template x-if="!c.image_url">
                                <span class="flex h-full w-full items-center justify-center font-mono text-[9px] font-black text-slate-700"
                                    x-text="c.name.slice(0, 2).toUpperCase()"></span>
                            </template>
                        </span>

                        <span class="min-w-0 flex-1 truncate text-[12px] font-bold text-slate-200" x-text="c.name"></span>

                        @if ($byes > 0)
                            <button type="button" @click="toggleBye(c.key)"
                                class="shrink-0 rounded-lg border px-2 py-1 text-[10px] font-black transition"
                                :class="isBye(c.key)
                                    ? 'border-amber-400 bg-amber-500/20 text-amber-300'
                                    : 'border-slate-800 text-slate-500 hover:border-amber-500/50'"
                                x-text="isBye(c.key) ? '✓ descansa' : 'descansa'"></button>
                        @endif

                        <button type="button" @click="move(i, -1)" :disabled="i === 0"
                            class="shrink-0 rounded-lg border border-slate-800 px-2 py-1 text-[11px] leading-none text-slate-400 transition hover:border-slate-600 disabled:opacity-25">↑</button>

                        <button type="button" @click="move(i, 1)" :disabled="i === ordered.length - 1"
                            class="shrink-0 rounded-lg border border-slate-800 px-2 py-1 text-[11px] leading-none text-slate-400 transition hover:border-slate-600 disabled:opacity-25">↓</button>
                    </div>
                </template>
            </div>

            @if ($byes > 0)
                <p class="mt-3 text-[11px]"
                    :class="byes.length === byeCount ? 'text-emerald-300' : 'text-amber-300'">
                    <span x-text="byes.length"></span> de <span x-text="byeCount"></span> descansos marcados.
                </p>
            @endif

        @else

            <p class="rounded-xl border border-slate-800 bg-slate-950/60 px-3 py-4 text-[12px] leading-relaxed text-slate-400">
                Esta fase espera una decisión de tipo
                <span class="font-mono font-black text-slate-200">{{ $tipo }}</span>,
                que todavía no se puede responder desde aquí. Resuélvela en el
                Competition Lab de la plantilla, o cambia la fase a reparto
                automático.
            </p>

        @endif


        {{-- ============ ENVIAR ============ --}}

        <div class="mt-5 flex flex-wrap items-center gap-3 border-t border-slate-800 pt-4">

            <p class="min-w-0 flex-1 text-[11px]"
                :class="ready ? 'text-emerald-300' : 'text-slate-500'"
                x-text="readyMessage"></p>

            <button type="button" @click="submit()"
                :disabled="!ready || sending"
                class="shrink-0 rounded-xl bg-amber-500 px-6 py-3 text-[12px] font-black text-slate-950 shadow-lg shadow-amber-900/30 transition hover:bg-amber-400 disabled:cursor-not-allowed disabled:opacity-40">
                <span x-show="!sending">Confirmar y abrir la fase →</span>
                <span x-show="sending" x-cloak>Repartiendo…</span>
            </button>

        </div>

    </div>

</div>
