@php
    /*
     * Por dónde se entra y por dónde se sale.
     *
     * Una fase no vive sola: recibe gente de la fase anterior y entrega
     * gente a la siguiente. Esas dos aduanas son lo que la conecta con el
     * resto del torneo, y por eso van juntas y enfrentadas —entradas a la
     * izquierda, salidas a la derecha, con la flecha en medio— en vez de en
     * dos secciones separadas.
     *
     * Los colores son los mismos que en la Super Edición: cada puerta trae
     * el suyo dentro del payload, así que una salida violeta aquí es la
     * misma salida violeta allí.
     *
     * Solo lectura: aquí no se crea ni se borra nada.
     */

    $gates = $payload['gates'] ?? [];

    $exits = collect($payload['exits'] ?? [])
        ->where('status', 'ACTIVE')
        ->values()
        ->all();

    /* Fase de grupos reparte sus salidas con reglas, y son lo legible */
    $rulesByExit = collect($payload['rules'] ?? [])->groupBy('exit_id');
@endphp

<section class="mb-4">

    <div class="mb-2 flex items-center gap-2">
        <span class="h-3 w-1 rounded-full bg-slate-600"></span>
        <h2 class="text-[10px] font-black uppercase tracking-[0.18em] text-slate-400">
            Entradas y salidas
        </h2>
        <span class="text-[10px] text-slate-600">— cómo se conecta con el resto del torneo</span>
    </div>

    <div class="grid gap-2 lg:grid-cols-2">

        {{-- ============ ENTRAN ============ --}}

        <div class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

            <div class="flex items-center gap-2 border-b border-slate-800 bg-slate-950/50 px-3 py-1.5">
                <span class="text-[11px] text-emerald-400">▼</span>
                <h3 class="text-[10px] font-black uppercase tracking-wider text-emerald-300">
                    Entran a la fase
                </h3>
                <span class="ml-auto font-mono text-[10px] text-slate-600">
                    {{ count($gates) }}
                </span>
            </div>

            <div class="space-y-1.5 p-2">

                @forelse ($gates as $gate)
                    <div class="rounded-xl border {{ $gate['color']['border'] }} {{ $gate['color']['soft'] ?? '' }} px-2.5 py-1.5">

                        <div class="flex items-center gap-1.5">
                            <span class="h-3.5 w-1 shrink-0 rounded-full {{ $gate['color']['dot'] }}"></span>

                            <span class="truncate text-[11px] font-black {{ $gate['color']['text'] }}">
                                {{ $gate['name'] }}
                            </span>

                            @if (! empty($gate['is_required']))
                                <span class="shrink-0 rounded bg-rose-500/15 px-1.5 py-0.5 text-[8px] font-black uppercase tracking-wider text-rose-300">
                                    obligatoria
                                </span>
                            @endif

                            <span class="ml-auto shrink-0 font-mono text-[9px] text-slate-600">
                                {{ $gate['code'] }}
                            </span>
                        </div>

                        <p class="mt-0.5 pl-2.5 text-[9px] leading-relaxed text-slate-400">
                            {{ $gate['rule_label'] ?? $gate['summary'] ?? 'Sin regla definida.' }}
                        </p>

                        {{-- Qué puestos del cuadro o de la parrilla reclama --}}
                        @if (! empty($gate['seeds']))
                            <div class="mt-1 flex flex-wrap gap-0.5 pl-2.5">
                                @foreach (array_slice($gate['seeds'], 0, 16) as $seed)
                                    <span class="rounded bg-slate-950/70 px-1 py-0.5 font-mono text-[8px] {{ $gate['color']['text'] }}">
                                        {{ $seed }}
                                    </span>
                                @endforeach

                                @if (count($gate['seeds']) > 16)
                                    <span class="px-1 py-0.5 font-mono text-[8px] text-slate-600">
                                        +{{ count($gate['seeds']) - 16 }}
                                    </span>
                                @endif
                            </div>
                        @endif

                        {{-- O qué grupos, en una fase de grupos --}}
                        @if (! empty($gate['groups']))
                            <div class="mt-1 flex flex-wrap gap-0.5 pl-2.5">
                                @foreach ($gate['groups'] as $group)
                                    <span class="rounded bg-slate-950/70 px-1.5 py-0.5 text-[8px] font-black {{ $gate['color']['text'] }}">
                                        {{ is_array($group) ? ($group['label'] ?? $group['code'] ?? '?') : $group }}
                                    </span>
                                @endforeach
                            </div>
                        @endif

                    </div>
                @empty
                    <p class="px-2 py-4 text-center text-[10px] leading-relaxed text-slate-600">
                        Sin puertas de entrada.<br>
                        <span class="text-slate-700">Los competidores entran en el orden en que llegan.</span>
                    </p>
                @endforelse

            </div>

        </div>


        {{-- ============ SALEN ============ --}}

        <div class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

            <div class="flex items-center gap-2 border-b border-slate-800 bg-slate-950/50 px-3 py-1.5">
                <span class="text-[11px] text-violet-400">▲</span>
                <h3 class="text-[10px] font-black uppercase tracking-wider text-violet-300">
                    Salen de la fase
                </h3>
                <span class="ml-auto font-mono text-[10px] text-slate-600">
                    {{ count($exits) }}
                </span>
            </div>

            <div class="space-y-1.5 p-2">

                @forelse ($exits as $exit)
                    <div class="rounded-xl border {{ $exit['color']['border'] }} {{ $exit['color']['wash'] ?? '' }} px-2.5 py-1.5">

                        <div class="flex items-center gap-1.5">
                            <span class="h-3.5 w-1 shrink-0 rounded-full {{ $exit['color']['dot'] }}"></span>

                            <span class="truncate text-[11px] font-black {{ $exit['color']['text'] }}">
                                {{ $exit['name'] }}
                            </span>

                            {{-- Cuántos caben. Se sabe sin jugar nada --}}
                            @php $cupo = $exit['capacity'] ?? $exit['emits'] ?? null; @endphp

                            @if ($cupo !== null)
                                <span class="shrink-0 rounded bg-slate-950/70 px-1.5 py-0.5 font-mono text-[9px] font-black {{ $exit['color']['text'] }}">
                                    {{ $cupo }}
                                </span>
                            @endif

                            <span class="ml-auto shrink-0 font-mono text-[9px] text-slate-600">
                                {{ $exit['code'] }}
                            </span>
                        </div>

                        {{--
                            En fase de grupos la salida no dice nada por sí
                            sola —«se define mediante las reglas del
                            Engine»—: lo legible son sus reglas.
                        --}}
                        @php $rules = $rulesByExit[$exit['id']] ?? collect(); @endphp

                        @if ($rules->isNotEmpty())
                            <ul class="mt-0.5 space-y-0.5 pl-2.5">
                                @foreach ($rules as $rule)
                                    <li class="flex items-baseline gap-1.5 text-[9px] leading-relaxed">
                                        <span class="{{ $exit['color']['text'] }}">·</span>
                                        <span class="text-slate-400">{{ $rule['summary'] }}</span>
                                        @if (($rule['emits'] ?? null) !== null)
                                            <span class="ml-auto shrink-0 font-mono text-slate-600">
                                                {{ $rule['emits'] }}
                                            </span>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <p class="mt-0.5 pl-2.5 text-[9px] leading-relaxed text-slate-400">
                                {{ $exit['summary'] }}
                            </p>
                        @endif

                        {{-- Qué puestos finales se lleva --}}
                        @if (! empty($exit['positions']))
                            <p class="mt-0.5 pl-2.5 font-mono text-[9px] {{ $exit['color']['text'] }}">
                                {{ $exit['positions']['from'] === $exit['positions']['to']
                                    ? 'puesto ' . $exit['positions']['from']
                                    : 'puestos ' . $exit['positions']['from'] . '–' . $exit['positions']['to'] }}
                            </p>
                        @endif

                        {{-- O de qué rama del cuadro recoge --}}
                        @if (! empty($exit['branch']))
                            @php $rama = collect($payload['branches'] ?? [])->firstWhere('number', $exit['branch']); @endphp

                            <p class="mt-0.5 flex items-center gap-1 pl-2.5">
                                <span class="flex h-3 w-3 items-center justify-center rounded text-[8px] font-black text-slate-950 {{ $rama['color']['solid'] ?? 'bg-slate-600' }}">
                                    {{ $rama['letter'] ?? '?' }}
                                </span>
                                <span class="font-mono text-[9px] text-slate-500">
                                    sale de la {{ mb_strtolower($rama['label'] ?? 'rama ' . $exit['branch']) }}
                                </span>
                            </p>
                        @endif

                        {{-- Y si el cuadro no sabe a quién se refiere, se dice --}}
                        @if (array_key_exists('is_ready', $exit) && ! $exit['is_ready'])
                            <p class="mt-1 rounded bg-amber-500/10 px-1.5 py-1 text-[9px] font-bold leading-relaxed text-amber-300">
                                {{ $exit['blocked_hint'] }}
                            </p>
                        @endif

                    </div>
                @empty
                    <p class="px-2 py-4 text-center text-[10px] leading-relaxed text-rose-300/70">
                        Sin puertas de salida.<br>
                        <span class="text-slate-600">Nadie avanza a la siguiente fase.</span>
                    </p>
                @endforelse

            </div>

        </div>

    </div>

</section>
