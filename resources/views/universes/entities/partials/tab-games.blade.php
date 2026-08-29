@php
    /*
     * JUEGOS Y STATS.
     *
     * Un Universo puede tener varios juegos y cada uno define sus propias
     * estadísticas: el rango de Highest Number no significa nada en otro.
     * Por eso van uno por bloque y no en una tabla común — una columna
     * compartida entre juegos distintos sería una columna que miente.
     */
@endphp

<div x-show="tab === 'games'" x-cloak class="space-y-2">

    @forelse ($gameProfile as $perfil)
        @php
            $def = $perfil['definition'];
            $record = $perfil['record'];
            $valores = (array) ($perfil['stats']->stats ?? []);
            $iniciales = (array) ($perfil['initial'] ?? []);
            $jugado = (bool) ($record['has_activity'] ?? false);
        @endphp

        <section class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

            <div class="flex flex-wrap items-center gap-2 border-b border-slate-800 bg-slate-950/60 px-4 py-2">
                <span class="text-[13px]">{{ $def['icon'] ?? '🎲' }}</span>
                <h2 class="text-[12px] font-black text-slate-100">{{ $def['name'] }}</h2>

                <span class="rounded bg-slate-900 px-1.5 py-0.5 text-[8px] font-black text-slate-500">
                    {{ $def['type_label'] ?? ($def['type'] ?? '') }}
                </span>

                <span class="ml-auto font-mono text-[9px] {{ $jugado ? 'text-slate-400' : 'text-slate-700' }}">
                    {{ $jugado
                        ? $record['battles'] . ' batallas · ' . $record['battle_win_rate'] . '%'
                        : 'todavía no ha jugado a esto' }}
                </span>
            </div>

            <div class="grid gap-3 p-3 lg:grid-cols-[1fr_260px]">

                <div>
                    <p class="text-[9px] font-black uppercase tracking-wider text-slate-500">
                        Sus estadísticas en este juego
                    </p>

                    <div class="mt-1.5 grid gap-1.5 sm:grid-cols-2">
                        @foreach (($def['stats'] ?? []) as $stat)
                            @php
                                $actual = $valores[$stat['key']] ?? null;
                                $inicial = $iniciales[$stat['key']] ?? null;
                                $subio = $actual !== null && $inicial !== null && $actual > $inicial;
                                $bajo = $actual !== null && $inicial !== null && $actual < $inicial;
                                $num = fn ($v) => rtrim(rtrim(number_format((float) $v, 2, ',', ''), '0'), ',');
                            @endphp

                            <div class="rounded-xl border border-slate-800 bg-slate-950/60 p-2">
                                <p class="text-[10px] font-black text-slate-300">{{ $stat['label'] }}</p>

                                <p class="flex items-baseline gap-1.5">
                                    <span class="font-mono text-[20px] font-black leading-none
                                        {{ $subio ? 'text-emerald-300' : ($bajo ? 'text-rose-300' : 'text-slate-100') }}">
                                        {{ $actual !== null ? $num($actual) : '—' }}
                                    </span>

                                    {{--
                                        De cuánto partió. Un número solo no
                                        dice si el competidor ha mejorado, y
                                        mejorar es justo lo que le hacen los
                                        premios de un torneo.
                                    --}}
                                    @if ($subio || $bajo)
                                        <span class="font-mono text-[9px] text-slate-600">
                                            desde {{ $num($inicial) }}
                                        </span>
                                        <span class="text-[9px] font-black {{ $subio ? 'text-emerald-400' : 'text-rose-400' }}">
                                            {{ $subio ? '▲' : '▼' }} {{ $num(abs((float) $actual - (float) $inicial)) }}
                                        </span>
                                    @endif
                                </p>

                                <p class="mt-0.5 text-[9px] leading-relaxed text-slate-600">{{ $stat['help'] ?? '' }}</p>
                            </div>
                        @endforeach
                    </div>

                    @if ($jugado)
                        <div class="mt-2 grid grid-cols-3 gap-1.5 sm:grid-cols-6">
                            @foreach ([
                                ['Batallas', $record['battles']],
                                ['Ganadas', $record['battles_won']],
                                ['Enfrent.', $record['encounters']],
                                ['Ganados', $record['encounters_won']],
                                ['Mejor', $record['best_value'] ?? '—'],
                                ['Media', $record['average_value'] ?? '—'],
                            ] as [$label, $cifra])
                                <div class="rounded-lg bg-slate-950 px-1.5 py-1 text-center">
                                    <p class="font-mono text-[13px] font-black leading-none text-slate-200">{{ $cifra }}</p>
                                    <p class="text-[7px] uppercase tracking-wider text-slate-600">{{ $label }}</p>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    {{--
                        Se ajustan AQUI, no en otra pantalla.

                        Antes esto era un enlace, y no llevaba a ningun
                        sitio: la ruta de stats es un PUT -un formulario que
                        se envia-, no una pagina que se visita, asi que
                        pulsarlo devolvia 405.
                    --}}

                    <form method="POST"
                        action="{{ route('universes.entities.games.stats', [$universe, $entity, $def['key']]) }}"
                        class="mt-3 rounded-xl border border-slate-800 bg-slate-950/60 p-2.5">

                        @csrf
                        @method('PUT')

                        <p class="text-[9px] font-black uppercase tracking-wider text-slate-500">
                            Ajustar sus capacidades
                        </p>

                        <div class="mt-1.5 grid gap-2 sm:grid-cols-2">
                            @foreach (($def['stats'] ?? []) as $stat)
                                <label class="block">
                                    <span class="text-[10px] font-black text-slate-300">{{ $stat['label'] }}</span>

                                    <input type="number"
                                        name="stats[{{ $stat['key'] }}]"
                                        value="{{ $valores[$stat['key']] ?? ($stat['default'] ?? '') }}"
                                        step="{{ $stat['step'] ?? 'any' }}"
                                        @if (isset($stat['min'])) min="{{ $stat['min'] }}" @endif
                                        @if (isset($stat['max'])) max="{{ $stat['max'] }}" @endif
                                        class="mt-0.5 w-full rounded-lg border-slate-700 bg-slate-950 px-2 py-1 font-mono text-[13px] font-black text-slate-100 focus:border-emerald-500 focus:ring-emerald-500">

                                    @if (! empty($stat['help']))
                                        <span class="mt-0.5 block text-[9px] leading-relaxed text-slate-600">{{ $stat['help'] }}</span>
                                    @endif
                                </label>
                            @endforeach
                        </div>

                        <div class="mt-2 flex flex-wrap items-center gap-2">
                            <p class="mr-auto text-[9px] leading-relaxed text-slate-600">
                                Cambiarlas no toca los torneos ya jugados: cada uno congeló
                                las suyas al empezar.
                            </p>

                            <button class="rounded-lg bg-emerald-500 px-3 py-1.5 text-[11px] font-black text-slate-950 transition hover:bg-emerald-400">
                                Guardar
                            </button>
                        </div>
                    </form>
                </div>

                {{-- Cómo se juega, para saber qué significan esos números --}}

                <div class="rounded-xl border border-slate-800 bg-slate-950/60 p-2.5">
                    <p class="text-[9px] font-black uppercase tracking-wider text-slate-500">Cómo se gana</p>
                    <p class="mt-0.5 text-[10px] leading-relaxed text-slate-400">{{ $def['win_condition'] ?? '' }}</p>

                    @if ($def['tiebreak'] ?? null)
                        <p class="mt-1.5 text-[9px] font-black uppercase tracking-wider text-slate-600">Empates</p>
                        <p class="text-[10px] leading-relaxed text-slate-500">{{ $def['tiebreak'] }}</p>
                    @endif

                    <p class="mt-1.5 font-mono text-[9px] text-slate-600">
                        {{ $def['minimum_participants'] ?? 2 }}{{ ($def['maximum_participants'] ?? null) ? '–' . $def['maximum_participants'] : '+' }}
                        por enfrentamiento
                        · {{ ($def['allows_draws'] ?? false) ? 'puede empatar' : 'nunca empata' }}
                    </p>
                </div>
            </div>
        </section>
    @empty
        <p class="rounded-2xl border border-dashed border-slate-700 px-4 py-8 text-center text-[11px] leading-relaxed text-slate-600">
            Este Universo no tiene ningún juego habilitado todavía.
        </p>
    @endforelse
</div>
