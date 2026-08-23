@php
    /*
     * ETAPA 5 · PREMIOS
     *
     * Qué se ha llevado cada competidor, y —lo que de verdad importa— si
     * dura o no.
     *
     * Un +1 que se evapora al acabar el torneo y un +1 que queda grabado
     * en el competidor se parecen mucho en una tarjeta y no se parecen en
     * nada de verdad. Por eso la naturaleza va primero, en la etiqueta más
     * visible de cada línea, y no escondida en la letra pequeña.
     */

    /* Lo repartido en esta competición, de un vistazo */
    $totalTrofeos = $awards->sum(fn($a) => count($a['trophies'] ?? []));
    $totalPermanentes = $awards->sum(fn($a) => count($a['permanent']));
    $totalTemporales = $awards->sum(fn($a) => count($a['temporary']));
@endphp

<div class="mx-auto max-w-6xl p-5">

    @if ($awards->isEmpty())

        <div class="flex min-h-[60vh] items-center justify-center">
            <div class="max-w-md text-center">
                <div class="text-6xl opacity-20">🎁</div>

                <h3 class="mt-6 text-xl font-black text-white">
                    Todavía no se ha repartido nada
                </h3>

                @if ($competition->isClosed())
                    <p class="mt-3 text-sm leading-relaxed text-slate-400">
                        La competición terminó sin recompensas configuradas. Los premios
                        se definen antes, en el torneo: trofeos y cambios de estadística
                        por posición, y bonus temporales por fase.
                    </p>
                @else
                    <p class="mt-3 text-sm leading-relaxed text-slate-400">
                        Los bonus temporales aparecen cuando termina la fase que los
                        concede. Las recompensas permanentes, cuando acaba la competición.
                    </p>
                @endif
            </div>
        </div>

    @else

        {{-- ============================================ --}}
        {{-- LO REPARTIDO --}}
        {{-- ============================================ --}}

        <div class="mb-4 flex flex-wrap items-end justify-between gap-4">

            <div>
                <h2 class="text-2xl font-black tracking-tight text-white">
                    {{ $competition->isClosed() ? 'Lo que se llevaron' : 'Lo repartido hasta ahora' }}
                </h2>

                <p class="mt-1 text-xs text-slate-500">
                    {{ $awards->count() }}
                    {{ $awards->count() === 1 ? 'competidor premiado' : 'competidores premiados' }}
                    de {{ $competition->participants()->count() }}
                </p>
            </div>

            <div class="flex flex-wrap gap-2">

                @if ($totalTrofeos)
                    <div class="rounded-2xl border border-amber-500/30 bg-amber-500/10 px-4 py-2 text-center">
                        <p class="font-mono text-xl font-black text-amber-300">{{ $totalTrofeos }}</p>
                        <p class="text-[9px] font-black uppercase tracking-wider text-amber-500/80">
                            {{ $totalTrofeos === 1 ? 'Trofeo' : 'Trofeos' }}
                        </p>
                    </div>
                @endif

                <div class="rounded-2xl border border-amber-500/20 bg-slate-900/60 px-4 py-2 text-center">
                    <p class="font-mono text-xl font-black text-amber-300">{{ $totalPermanentes }}</p>
                    <p class="text-[9px] font-black uppercase tracking-wider text-slate-500">Permanentes</p>
                </div>

                <div class="rounded-2xl border border-sky-500/20 bg-slate-900/60 px-4 py-2 text-center">
                    <p class="font-mono text-xl font-black text-sky-300">{{ $totalTemporales }}</p>
                    <p class="text-[9px] font-black uppercase tracking-wider text-slate-500">Temporales</p>
                </div>

            </div>

        </div>


        {{-- ============================================ --}}
        {{-- QUÉ SIGNIFICA CADA COSA --}}
        {{-- ============================================ --}}

        <div class="mb-5 flex flex-wrap items-center gap-4 rounded-2xl border border-slate-800 bg-slate-900/40 px-5 py-3">

            <div class="flex items-center gap-2">
                <span class="rounded-full bg-sky-500/15 px-2.5 py-1 text-[9px] font-black uppercase tracking-wider text-sky-300">
                    Temporal
                </span>
                <span class="text-[11px] text-slate-500">
                    Solo dentro de esta competición. No toca al competidor.
                </span>
            </div>

            <div class="flex items-center gap-2">
                <span class="rounded-full bg-amber-500/15 px-2.5 py-1 text-[9px] font-black uppercase tracking-wider text-amber-300">
                    Permanente
                </span>
                <span class="text-[11px] text-slate-500">
                    Cambió la stat guardada. Se queda.
                </span>
            </div>

            <div class="flex items-center gap-2">
                <span class="text-sm">🏆</span>
                <span class="text-[11px] text-slate-500">
                    Trofeo: no cambia nada, es lo que queda de haber ganado.
                </span>
            </div>

        </div>


        <div class="grid gap-4 lg:grid-cols-2 2xl:grid-cols-3">

            @foreach ($awards as $award)

                <div class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/40">

                    {{-- COMPETIDOR --}}

                    <div class="flex items-center gap-3 border-b border-slate-800 px-4 py-3">

                        <div class="h-10 w-10 shrink-0 overflow-hidden rounded-xl bg-slate-800">
                            @if ($award['image_url'])
                                <img src="{{ $award['image_url'] }}" alt="" class="h-full w-full object-cover">
                            @endif
                        </div>

                        <p class="min-w-0 flex-1 truncate text-sm font-black text-white">
                            {{ $award['name'] }}
                        </p>

                        @unless ($readonly)
                            <button type="button"
                                @click="openAdjust({{ $award['universe_entity_id'] }}, @js($award['name']))"
                                class="shrink-0 rounded-lg border border-slate-700 px-2.5 py-1 text-[10px] font-black text-slate-400 transition hover:border-amber-400 hover:text-amber-300">
                                Ajustar
                            </button>
                        @endunless

                    </div>


                    {{-- TROFEOS --}}
                    {{--
                        Van arriba y con su propio fondo porque no son una
                        línea más del recuento: son el título.
                    --}}
                    @if (! empty($award['trophies']))
                        <div class="flex flex-wrap gap-2 border-b border-slate-800 bg-amber-500/[0.07] px-3 py-2.5">
                            @foreach ($award['trophies'] as $trophy)
                                <div class="flex items-center gap-2 rounded-xl border border-amber-500/30 bg-slate-950/50 px-2.5 py-1.5">

                                    <span class="text-base leading-none">{{ $trophy['icon'] }}</span>

                                    <div class="min-w-0">
                                        <p class="truncate text-[11px] font-black text-amber-200">
                                            {{ $trophy['name'] }}
                                        </p>

                                        @if ($trophy['position'])
                                            <p class="text-[9px] font-black uppercase tracking-wider text-amber-500/70">
                                                {{ $trophy['position'] }}.º puesto
                                            </p>
                                        @endif
                                    </div>

                                </div>
                            @endforeach
                        </div>
                    @endif


                    <div class="space-y-2 p-3">

                        {{-- PERMANENTE --}}

                        @foreach ($award['permanent'] as $change)

                            <div class="rounded-xl border border-amber-500/30 bg-amber-500/5 px-3 py-2">

                                <div class="flex items-center gap-2">

                                    <span class="rounded-full bg-amber-500/20 px-2 py-0.5 text-[8px] font-black uppercase tracking-wider text-amber-300">
                                        Permanente
                                    </span>

                                    <span class="min-w-0 flex-1 truncate text-[11px] font-bold text-slate-300">
                                        {{ $change['stat_label'] }}
                                    </span>

                                    <span class="shrink-0 font-mono text-[11px] font-black text-amber-300">
                                        {{ rtrim(rtrim(number_format($change['before'], 2, '.', ''), '0'), '.') }}
                                        →
                                        {{ rtrim(rtrim(number_format($change['after'], 2, '.', ''), '0'), '.') }}
                                    </span>

                                </div>

                                @if ($change['reason'] || $change['trophy'])
                                    <p class="mt-1 truncate text-[10px] text-amber-200/50">
                                        @if ($change['trophy'])🏆 {{ $change['trophy'] }} · @endif
                                        {{ $change['reason'] }}
                                    </p>
                                @endif

                            </div>
                        @endforeach


                        {{-- TEMPORAL --}}

                        @foreach ($award['temporary'] as $bonus)

                            <div class="rounded-xl border border-sky-500/25 bg-sky-500/5 px-3 py-2">

                                <div class="flex items-center gap-2">

                                    <span class="rounded-full bg-sky-500/20 px-2 py-0.5 text-[8px] font-black uppercase tracking-wider text-sky-300">
                                        Temporal
                                    </span>

                                    <span class="min-w-0 flex-1 truncate text-[11px] font-bold text-slate-300">
                                        {{ $bonus['stat_label'] }}
                                    </span>

                                    <span class="shrink-0 font-mono text-[11px] font-black text-sky-300">
                                        {{ $bonus['effect'] }}
                                    </span>

                                </div>

                                <p class="mt-1 truncate text-[10px] text-sky-200/50">
                                    @if ($bonus['earned'])
                                        🏅 Ganado · {{ $bonus['position'] }}º de {{ $bonus['phase'] }}
                                    @else
                                        Configurado de antemano
                                    @endif

                                    @if ($bonus['scope'] === 'PHASE')
                                        · solo en {{ $bonus['scope_value'] }}
                                    @elseif ($bonus['scope'] === 'ROUND')
                                        · solo en la ronda {{ $bonus['scope_value'] }}
                                    @else
                                        · toda la competición
                                    @endif
                                </p>

                            </div>
                        @endforeach

                        @if (empty($award['permanent']) && empty($award['temporary']))
                            <p class="px-1 py-2 text-[11px] italic text-slate-600">
                                Solo el título: ninguna estadística cambió.
                            </p>
                        @endif

                    </div>

                </div>
            @endforeach

        </div>
    @endif


    {{-- ============================================ --}}
    {{-- AJUSTE MANUAL --}}
    {{-- ============================================ --}}
    {{--
        Pide permiso a propósito. Tocar a mano una stat guardada salta por
        encima de todo lo que la explica —el torneo, la recompensa, el
        registro de por qué cambió— así que tiene que ser una decisión
        consciente, no un campo más que se edita sin querer.
    --}}

    @unless ($readonly)

        <div x-show="adjust.open" x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/80 p-5"
            @click.self="adjust.open = false">

            <div class="w-full max-w-md overflow-hidden rounded-2xl border border-amber-500/40 bg-slate-900 shadow-2xl">

                <div class="border-b border-slate-800 px-5 py-4">

                    <p class="text-[10px] font-black uppercase tracking-wider text-amber-400">
                        ⚠ Cambio manual y permanente
                    </p>

                    <h3 class="mt-1 text-lg font-black text-white" x-text="adjust.name"></h3>

                </div>

                <form method="POST"
                    action="{{ route('universes.competitions.adjust', [$universe, $competition]) }}"
                    class="space-y-4 p-5">
                    @csrf

                    <input type="hidden" name="universe_entity_id" :value="adjust.entityId">

                    <p class="rounded-xl bg-amber-500/10 px-3 py-2.5 text-[11px] leading-relaxed text-amber-200/80">
                        Esto cambia el valor guardado del competidor, fuera de cualquier
                        recompensa. Queda registrado como ajuste manual para que se pueda
                        distinguir de lo que ganó jugando, pero <strong class="font-black">no se
                        deshace solo</strong> al acabar la competición.
                    </p>

                    <div>
                        <label class="text-[10px] font-black uppercase tracking-wider text-slate-500">
                            Estadística
                        </label>

                        <select name="stat_key"
                            class="mt-1.5 w-full rounded-xl border-slate-700 bg-slate-950 text-sm text-white focus:border-amber-400 focus:ring-amber-400">
                            @foreach (($definition['stats'] ?? []) as $stat)
                                <option value="{{ $stat['key'] }}">{{ $stat['label'] }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2">

                        <div>
                            <label class="text-[10px] font-black uppercase tracking-wider text-slate-500">
                                Operación
                            </label>

                            <select name="operation"
                                class="mt-1.5 w-full rounded-xl border-slate-700 bg-slate-950 text-sm text-white focus:border-amber-400 focus:ring-amber-400">
                                @foreach (\App\Models\UniverseTournamentReward::OPERATIONS as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="text-[10px] font-black uppercase tracking-wider text-slate-500">
                                Cantidad
                            </label>

                            <input type="number" step="0.1" name="amount" value="1"
                                class="mt-1.5 w-full rounded-xl border-slate-700 bg-slate-950 text-sm text-white focus:border-amber-400 focus:ring-amber-400">
                        </div>

                    </div>

                    <div>
                        <label class="text-[10px] font-black uppercase tracking-wider text-slate-500">
                            Por qué
                        </label>

                        <input type="text" name="reason" maxlength="150"
                            placeholder="Queda escrito junto al cambio"
                            class="mt-1.5 w-full rounded-xl border-slate-700 bg-slate-950 text-sm text-white focus:border-amber-400 focus:ring-amber-400">
                    </div>

                    <label class="flex items-start gap-2.5 rounded-xl border border-amber-500/30 bg-amber-500/5 px-3 py-2.5">
                        <input type="checkbox" name="confirm" value="1" x-model="adjust.confirmed"
                            class="mt-0.5 rounded border-amber-500/50 bg-slate-950 text-amber-500 focus:ring-amber-400">

                        <span class="text-[11px] font-bold text-amber-200/90">
                            Entiendo que esto modifica al competidor de forma permanente.
                        </span>
                    </label>

                    <div class="flex items-center justify-end gap-2">

                        <button type="button" @click="adjust.open = false"
                            class="rounded-xl border border-slate-700 px-4 py-2 text-xs font-black text-slate-400 transition hover:text-white">
                            Cancelar
                        </button>

                        <button type="submit" :disabled="!adjust.confirmed"
                            class="rounded-xl bg-amber-500 px-5 py-2 text-xs font-black text-slate-950 transition hover:bg-amber-400 disabled:opacity-30">
                            Aplicar cambio
                        </button>

                    </div>

                </form>

            </div>

        </div>
    @endunless

</div>
