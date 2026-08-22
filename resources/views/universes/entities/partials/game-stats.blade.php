@php
    $palette = [
        'emerald' => [
            'gradient' => 'from-emerald-500 to-emerald-600',
            'shadow' => 'shadow-emerald-600/20',
            'text' => 'text-emerald-700',
            'soft' => 'bg-emerald-50 border-emerald-200',
            'bar' => 'bg-emerald-500',
            'ring' => 'focus:border-emerald-400 focus:ring-emerald-400',
            'button' => 'bg-emerald-600 hover:bg-emerald-700',
        ],
        'violet' => [
            'gradient' => 'from-violet-500 to-violet-600',
            'shadow' => 'shadow-violet-600/20',
            'text' => 'text-violet-700',
            'soft' => 'bg-violet-50 border-violet-200',
            'bar' => 'bg-violet-500',
            'ring' => 'focus:border-violet-400 focus:ring-violet-400',
            'button' => 'bg-violet-600 hover:bg-violet-700',
        ],
        'amber' => [
            'gradient' => 'from-amber-500 to-amber-600',
            'shadow' => 'shadow-amber-600/20',
            'text' => 'text-amber-700',
            'soft' => 'bg-amber-50 border-amber-200',
            'bar' => 'bg-amber-500',
            'ring' => 'focus:border-amber-400 focus:ring-amber-400',
            'button' => 'bg-amber-600 hover:bg-amber-700',
        ],
    ];
@endphp

<section class="rounded-3xl border border-slate-200 bg-white p-6">

    <p class="text-xs font-black uppercase tracking-[0.18em] text-violet-600">
        Propio de este Universo
    </p>

    <h2 class="mt-2 text-2xl font-black text-slate-900">
        ⚄ Juegos
    </h2>

    <p class="mt-2 text-sm leading-relaxed text-slate-500">
        Lo que este competidor es capaz de hacer en cada juego, y lo que ha
        conseguido hasta ahora. Nada de esto existe en su ficha de la Biblioteca.
    </p>


    <div class="mt-6 space-y-5">

        @foreach ($gameProfile as $game)

            @php
                $definition = $game['definition'];
                $record = $game['record'];
                $stats = $game['stats'];
                $skin = $palette[$definition['accent'] ?? 'violet'] ?? $palette['violet'];
                $values = $stats->normalized_stats;
                $initial = $game['initial'] ?? $values;
            @endphp

            <div class="overflow-hidden rounded-2xl border border-slate-200">

                {{-- CABECERA DEL JUEGO --}}

                <div class="flex items-center gap-3 border-b border-slate-100 bg-slate-50/70 px-5 py-4">

                    <div
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br {{ $skin['gradient'] }} text-lg shadow-md {{ $skin['shadow'] }}">
                        {{ $definition['icon'] ?? '🎲' }}
                    </div>

                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-black text-slate-900">
                            {{ $definition['name'] }}
                        </p>
                        <p class="truncate text-[11px] text-slate-500">
                            {{ $definition['tagline'] }}
                        </p>
                    </div>

                    <a href="{{ route('universes.games.show', [$universe, $definition['key']]) }}"
                        class="shrink-0 text-[10px] font-black text-slate-400 hover:{{ $skin['text'] }}">
                        Ver juego →
                    </a>

                </div>


                {{-- ESTADÍSTICAS CONFIGURABLES --}}

                <form method="POST"
                    action="{{ route('universes.entities.games.stats', [$universe, $entity, $definition['key']]) }}"
                    class="border-b border-slate-100 px-5 py-5">

                    @csrf
                    @method('PUT')

                    <p class="text-[10px] font-black uppercase tracking-wider text-slate-400">
                        Sus capacidades
                    </p>


                    {{-- De donde partio y donde esta ahora (Fase 12) --}}

                    @php
                        $grown = collect($definition['stats'] ?? [])->filter(
                            fn($schema) =>
                                round((float) ($values[$schema['key']] ?? 0), 4)
                                !== round((float) ($initial[$schema['key']] ?? 0), 4)
                        );
                    @endphp

                    @if ($grown->isNotEmpty())
                        <div class="mt-3 flex flex-wrap gap-2">
                            @foreach ($grown as $schema)
                                @php
                                    $from = (float) ($initial[$schema['key']] ?? 0);
                                    $to = (float) ($values[$schema['key']] ?? 0);
                                @endphp

                                <span
                                    class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1 text-[10px] font-black text-emerald-700">
                                    {{ $schema['label'] }}
                                    <span class="font-mono text-emerald-500">
                                        {{ rtrim(rtrim(number_format($from, 2, '.', ''), '0'), '.') }}
                                        →
                                        {{ rtrim(rtrim(number_format($to, 2, '.', ''), '0'), '.') }}
                                    </span>
                                </span>
                            @endforeach
                        </div>
                    @endif

                    <div class="mt-3 grid gap-3 sm:grid-cols-2">

                        @foreach ($definition['stats'] as $schema)

                            <div>
                                <label class="text-[11px] font-black text-slate-600">
                                    {{ $schema['label'] }}
                                </label>

                                <input type="number" name="stats[{{ $schema['key'] }}]"
                                    value="{{ $values[$schema['key']] ?? ($schema['default'] ?? '') }}"
                                    step="{{ $schema['step'] ?? 'any' }}" min="{{ $schema['min'] ?? null }}"
                                    max="{{ $schema['max'] ?? null }}"
                                    class="mt-1.5 w-full rounded-xl border-slate-300 text-sm font-black text-slate-900 {{ $skin['ring'] }}">

                                @if (!empty($schema['help']))
                                    <p class="mt-1 text-[10px] leading-snug text-slate-400">
                                        {{ $schema['help'] }}
                                    </p>
                                @endif
                            </div>
                        @endforeach

                    </div>

                    <button
                        class="mt-4 rounded-xl {{ $skin['button'] }} px-4 py-2 text-[11px] font-black text-white transition">
                        Guardar
                    </button>

                </form>


                {{-- RÉCORD DERIVADO --}}

                @if ($record['has_activity'])

                    <div class="grid grid-cols-2 gap-px bg-slate-100 sm:grid-cols-4">

                        <div class="bg-white px-4 py-3.5">
                            <p class="text-[9px] font-black uppercase tracking-wider text-slate-400">Batallas</p>
                            <p class="mt-0.5 text-lg font-black tabular-nums text-slate-900">
                                {{ $record['battles'] }}
                            </p>
                        </div>

                        <div class="bg-white px-4 py-3.5">
                            <p class="text-[9px] font-black uppercase tracking-wider text-slate-400">Victorias</p>
                            <p class="mt-0.5 text-lg font-black tabular-nums text-emerald-600">
                                {{ $record['battles_won'] }}
                            </p>
                        </div>

                        <div class="bg-white px-4 py-3.5">
                            <p class="text-[9px] font-black uppercase tracking-wider text-slate-400">Derrotas</p>
                            <p class="mt-0.5 text-lg font-black tabular-nums text-rose-500">
                                {{ $record['battles_lost'] }}
                            </p>
                        </div>

                        <div class="bg-white px-4 py-3.5">
                            <p class="text-[9px] font-black uppercase tracking-wider text-slate-400">Win rate</p>
                            <p class="mt-0.5 text-lg font-black tabular-nums {{ $skin['text'] }}">
                                {{ number_format($record['battle_win_rate'], 1) }}%
                            </p>
                        </div>

                    </div>


                    {{-- BARRA --}}

                    <div class="px-5 pb-2 pt-3">
                        <div class="h-1.5 w-full overflow-hidden rounded-full bg-slate-100">
                            <div class="h-full rounded-full {{ $skin['bar'] }}"
                                style="width: {{ min(100, $record['battle_win_rate']) }}%"></div>
                        </div>
                    </div>


                    {{-- DETALLE DE ENFRENTAMIENTOS --}}

                    <div class="flex flex-wrap items-center gap-x-5 gap-y-1 px-5 pb-5 pt-2 text-[11px] text-slate-500">

                        <span>
                            <span class="font-black text-slate-700">{{ $record['encounters'] }}</span>
                            enfrentamientos
                        </span>

                        <span>
                            <span class="font-black text-slate-700">{{ $record['encounters_won'] }}</span>
                            ganados
                            ({{ number_format($record['encounter_win_rate'], 1) }}%)
                        </span>

                        @if ($record['best_value'] !== null)
                            <span>
                                mejor marca
                                <span class="font-mono font-black {{ $skin['text'] }}">
                                    {{ $record['best_value'] }}
                                </span>
                            </span>
                        @endif

                        @if ($record['average_value'] !== null)
                            <span>
                                media
                                <span class="font-mono font-black text-slate-700">
                                    {{ $record['average_value'] }}
                                </span>
                            </span>
                        @endif

                    </div>
                @else

                    <div class="px-5 py-4">
                        <p class="text-[11px] text-slate-400">
                            Todavía no ha jugado ninguna batalla de {{ $definition['name'] }}.
                            Sus cifras aparecerán aquí en cuanto compita.
                        </p>
                    </div>
                @endif

            </div>
        @endforeach

    </div>

</section>
