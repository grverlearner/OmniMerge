<x-universe-layout :universe="$universe">

    <x-slot name="header">Consecuencias</x-slot>


    <div class="mb-7">

        <a href="{{ route('universes.tournaments.show', [$universe, $universeTournament]) }}"
            class="text-xs font-black text-slate-400 hover:text-violet-600">
            ← {{ $universeTournament->name }}
        </a>

        <p class="mt-5 text-xs font-black uppercase tracking-wider text-violet-600">
            {{ $universeTournament->name }} · {{ $definition['name'] }}
        </p>

        <h2 class="mt-2 text-3xl font-black text-slate-900">Qué deja este torneo</h2>

        <p class="mt-2 max-w-3xl text-slate-500">
            Una <strong class="font-black text-slate-700">recompensa</strong> cambia al
            competidor para siempre. Un <strong class="font-black text-slate-700">bonus</strong>
            solo existe mientras se juega y desaparece con el torneo.
        </p>

    </div>


    @if ($errors->any())
        <div class="mb-6 rounded-2xl border border-rose-200 bg-rose-50 p-4">
            @foreach ($errors->all() as $error)
                <p class="text-sm font-bold text-rose-700">{{ $error }}</p>
            @endforeach
        </div>
    @endif


    <div class="grid gap-6 xl:grid-cols-2">

        {{-- ============================================ --}}
        {{-- RECOMPENSAS PERMANENTES --}}
        {{-- ============================================ --}}

        <section class="rounded-3xl border border-slate-200 bg-white">

            <div class="flex items-center gap-3 border-b border-slate-100 p-6">

                <div
                    class="flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br from-amber-500 to-amber-600 text-xl shadow-lg shadow-amber-600/20">
                    🏆
                </div>

                <div>
                    <h3 class="text-lg font-black text-slate-900">Recompensas permanentes</h3>
                    <p class="text-xs text-slate-500">Se aplican al terminar cada edición.</p>
                </div>

            </div>


            @if ($rewards->isEmpty())
                <p class="border-b border-slate-100 px-6 py-8 text-center text-sm text-slate-400">
                    Este torneo todavía no cambia nada de forma permanente.
                </p>
            @else
                <div class="divide-y divide-slate-100">

                    @foreach ($rewards as $reward)
                        <div class="flex items-center gap-4 px-6 py-4">

                            <div class="min-w-0 flex-1">

                                <div class="flex flex-wrap items-center gap-2">

                                    <span
                                        class="rounded-full bg-slate-900 px-2.5 py-1 text-[10px] font-black uppercase tracking-wide text-white">
                                        {{ $reward->condition_label }}
                                    </span>

                                    @if ($reward->effect_label)
                                        <span
                                            class="rounded-full bg-emerald-100 px-2.5 py-1 text-[10px] font-black text-emerald-700">
                                            {{ $reward->effect_label }}
                                        </span>
                                    @endif

                                    @if ($reward->trophy)
                                        <span
                                            class="rounded-full bg-amber-100 px-2.5 py-1 text-[10px] font-black text-amber-700">
                                            {{ $reward->trophy->display_icon }} {{ $reward->trophy->name }}
                                        </span>
                                    @endif

                                    {{-- De que juego son las stats que toca --}}
                                    @if ($reward->stat_key && $reward->game_key)
                                        @php
                                            $rewardGame = $availableGames->firstWhere('key', $reward->game_key);
                                        @endphp

                                        @if ($rewardGame)
                                            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[10px] font-black text-slate-600">
                                                {{ $rewardGame['icon'] ?? '🎲' }} {{ $rewardGame['name'] }}
                                            </span>
                                        @endif
                                    @endif

                                </div>

                                @if ($reward->label)
                                    <p class="mt-1.5 text-xs text-slate-400">{{ $reward->label }}</p>
                                @endif

                            </div>

                            <form method="POST"
                                action="{{ route('universes.tournaments.rewards.destroy', [$universe, $universeTournament, $reward]) }}"
                                onsubmit="return confirm('¿Eliminar esta recompensa? Lo ya concedido no se revierte.')">
                                @csrf
                                @method('DELETE')

                                <button class="text-xs font-black text-slate-300 hover:text-rose-600">
                                    Eliminar
                                </button>
                            </form>

                        </div>
                    @endforeach

                </div>
            @endif


            {{-- ALTA --}}

            <form method="POST"
                action="{{ route('universes.tournaments.rewards.store', [$universe, $universeTournament]) }}"
                class="space-y-4 border-t border-slate-100 bg-slate-50/60 p-6"
                x-data="{
                    trigger: '{{ old('trigger', 'POSITION') }}',
                    game: '{{ old('game_key', $definition['key']) }}',
                    statsByGame: {{ Illuminate\Support\Js::from($statsByGame) }},

                    get stats() {
                        return this.statsByGame[this.game] ?? [];
                    },
                }">

                @csrf

                <div class="grid gap-3 sm:grid-cols-2">

                    <div>
                        <label class="text-[10px] font-black uppercase tracking-wider text-slate-500">
                            Se gana por
                        </label>

                        <select name="trigger" x-model="trigger"
                            class="mt-1.5 w-full rounded-xl border-slate-300 text-sm focus:border-violet-400 focus:ring-violet-400">
                            @foreach (\App\Models\UniverseTournamentReward::TRIGGERS as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div x-show="['POSITION', 'WIN_COUNT', 'ENCOUNTER_WIN_COUNT'].includes(trigger)" x-cloak>
                        <label class="text-[10px] font-black uppercase tracking-wider text-slate-500">
                            <span x-text="trigger === 'POSITION' ? 'Puesto' : 'Cantidad'"></span>
                        </label>

                        <input type="number" name="threshold" min="1" max="999"
                            value="{{ old('threshold', 1) }}"
                            class="mt-1.5 w-full rounded-xl border-slate-300 text-sm focus:border-violet-400 focus:ring-violet-400">
                    </div>

                </div>


                {{--
                    A que juego afecta.

                    Cada juego declara sus propias estadisticas, asi que
                    "+0.5 max_value" solo significa algo dentro del juego
                    que define esa stat.
                --}}
                @if ($availableGames->count() > 1)
                    <div>
                        <label class="text-[10px] font-black uppercase tracking-wider text-slate-500">
                            Juego al que afecta
                        </label>

                        <div class="mt-1.5 grid gap-2 sm:grid-cols-2">
                            @foreach ($availableGames as $game)
                                <button type="button" @click="game = '{{ $game['key'] }}'"
                                    :class="game === '{{ $game['key'] }}'
                                        ? 'border-violet-500 bg-violet-50 text-violet-800'
                                        : 'border-slate-200 bg-white text-slate-500'"
                                    class="flex items-center gap-2 rounded-xl border-2 px-3 py-2 text-left transition">
                                    <span class="text-base">{{ $game['icon'] ?? '🎲' }}</span>
                                    <span class="min-w-0">
                                        <span class="block truncate text-[11px] font-black">{{ $game['name'] }}</span>
                                        <span class="block text-[9px] opacity-70">{{ $game['type_label'] }}</span>
                                    </span>
                                </button>
                            @endforeach
                        </div>

                        <input type="hidden" name="game_key" :value="game">

                        <p class="mt-1.5 text-[10px] text-slate-500">
                            El torneo se juega a <strong class="font-black text-slate-700">{{ $definition['name'] }}</strong>,
                            pero la recompensa puede subir estadísticas de otro juego.
                        </p>
                    </div>
                @else
                    <input type="hidden" name="game_key" :value="game">
                @endif


                <div class="grid gap-3 sm:grid-cols-3">

                    <div>
                        <label class="text-[10px] font-black uppercase tracking-wider text-slate-500">
                            Estadística
                        </label>

                        <select name="stat_key"
                            class="mt-1.5 w-full rounded-xl border-slate-300 text-sm focus:border-violet-400 focus:ring-violet-400">
                            <option value="">— solo trofeo —</option>

                            {{-- Cambian con el juego: cada uno tiene las suyas --}}
                            <template x-for="stat in stats" :key="stat.key">
                                <option :value="stat.key" x-text="stat.label"></option>
                            </template>
                        </select>
                    </div>

                    <div>
                        <label class="text-[10px] font-black uppercase tracking-wider text-slate-500">
                            Operación
                        </label>

                        <select name="operation"
                            class="mt-1.5 w-full rounded-xl border-slate-300 text-sm focus:border-violet-400 focus:ring-violet-400">
                            @foreach (\App\Models\UniverseTournamentReward::OPERATIONS as $value => $label)
                                <option value="{{ $value }}" @selected(old('operation') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="text-[10px] font-black uppercase tracking-wider text-slate-500">
                            Cantidad
                        </label>

                        <input type="number" step="0.01" name="amount" value="{{ old('amount', '0.5') }}"
                            class="mt-1.5 w-full rounded-xl border-slate-300 text-sm focus:border-violet-400 focus:ring-violet-400">
                    </div>

                </div>


                <div class="grid gap-3 sm:grid-cols-2">

                    <div>
                        <label class="text-[10px] font-black uppercase tracking-wider text-slate-500">
                            Trofeo
                        </label>

                        <select name="universe_trophy_id"
                            class="mt-1.5 w-full rounded-xl border-slate-300 text-sm focus:border-violet-400 focus:ring-violet-400">
                            <option value="">— ninguno —</option>
                            @foreach ($trophies as $trophy)
                                <option value="{{ $trophy->id }}">{{ $trophy->display_icon }} {{ $trophy->name }}</option>
                            @endforeach
                        </select>

                        @if ($trophies->isEmpty())
                            <p class="mt-1.5 text-[10px] text-slate-400">
                                <a href="{{ route('universes.trophies.index', $universe) }}"
                                    class="font-black text-violet-600">Crea un trofeo</a>
                                para poder asignarlo.
                            </p>
                        @endif
                    </div>

                    <div>
                        <label class="text-[10px] font-black uppercase tracking-wider text-slate-500">
                            Nombre (opcional)
                        </label>

                        <input type="text" name="label" value="{{ old('label') }}"
                            placeholder="Ej. Premio del campeón"
                            class="mt-1.5 w-full rounded-xl border-slate-300 text-sm focus:border-violet-400 focus:ring-violet-400">
                    </div>

                </div>


                <button class="rounded-xl bg-slate-950 px-5 py-3 text-xs font-black text-white hover:bg-slate-800">
                    Añadir recompensa
                </button>

            </form>

        </section>


        {{-- ============================================ --}}
        {{-- BONUS TEMPORALES --}}
        {{-- ============================================ --}}

        <section class="rounded-3xl border border-slate-200 bg-white">

            <div class="flex items-center gap-3 border-b border-slate-100 p-6">

                <div
                    class="flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br from-sky-500 to-sky-600 text-xl shadow-lg shadow-sky-600/20">
                    ⚡
                </div>

                <div>
                    <h3 class="text-lg font-black text-slate-900">Bonus temporales</h3>
                    <p class="text-xs text-slate-500">Solo durante el juego. No cambian nada guardado.</p>
                </div>

            </div>


            @if ($modifiers->isEmpty())
                <p class="border-b border-slate-100 px-6 py-8 text-center text-sm text-slate-400">
                    Sin ventajas especiales. Todos compiten con sus estadísticas tal cual.
                </p>
            @else
                <div class="divide-y divide-slate-100">

                    @foreach ($modifiers as $modifier)

                        @php
                            $earned = $modifier->target === 'PHASE_PODIUM';

                            $who = match ($modifier->target) {
                                'PHASE_PODIUM' =>
                                    $modifier->selector_label . ' de '
                                        . ($modifier->award_phase ?: 'cada fase que termine'),

                                'ENTITY' =>
                                    $modifier->universeEntity?->display_label ?? 'un competidor',

                                default => 'todos los participantes',
                            };
                        @endphp

                        <div @class([
                            'flex items-center gap-4 px-6 py-4',
                            'bg-amber-50/50' => $earned,
                        ])>

                            <div class="min-w-0 flex-1">

                                <div class="flex flex-wrap items-center gap-2">

                                    {{-- Lo que hay que hacer para tenerlo --}}
                                    @if ($earned)
                                        <span class="rounded-full bg-amber-100 px-2.5 py-1 text-[10px] font-black text-amber-700">
                                            🏅 Se gana jugando
                                        </span>
                                    @endif

                                    <span
                                        class="rounded-full bg-sky-100 px-2.5 py-1 text-[10px] font-black text-sky-700">
                                        {{ $modifier->scope_label }}
                                    </span>

                                    <span
                                        class="rounded-full bg-emerald-100 px-2.5 py-1 text-[10px] font-black text-emerald-700">
                                        {{ $modifier->effect_label }}
                                    </span>

                                    <span class="text-[10px] font-bold text-slate-400">
                                        {{ $who }}
                                    </span>

                                    @if ($modifier->game_key)
                                        @php
                                            $modifierGame = $availableGames->firstWhere('key', $modifier->game_key);
                                        @endphp

                                        @if ($modifierGame)
                                            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[10px] font-black text-slate-600">
                                                {{ $modifierGame['icon'] ?? '🎲' }} {{ $modifierGame['name'] }}
                                            </span>
                                        @endif
                                    @endif

                                </div>

                                @if ($modifier->label)
                                    <p class="mt-1.5 text-xs text-slate-400">{{ $modifier->label }}</p>
                                @endif

                            </div>

                            <form method="POST"
                                action="{{ route('universes.tournaments.modifiers.destroy', [$universe, $universeTournament, $modifier]) }}">
                                @csrf
                                @method('DELETE')

                                <button class="text-xs font-black text-slate-300 hover:text-rose-600">
                                    Eliminar
                                </button>
                            </form>

                        </div>
                    @endforeach

                </div>
            @endif


            <form method="POST"
                action="{{ route('universes.tournaments.modifiers.store', [$universe, $universeTournament]) }}"
                class="space-y-4 border-t border-slate-100 bg-slate-50/60 p-6"
                x-data="{
                    scope: 'TOURNAMENT',
                    target: 'ALL',
                    game: '{{ old('game_key', $definition['key']) }}',
                    statsByGame: {{ Illuminate\Support\Js::from($statsByGame) }},

                    get stats() {
                        return this.statsByGame[this.game] ?? [];
                    },

                    selector: '{{ old('selector_type', 'TOP_N') }}',

                    /* Un bonus que hay que ganarse jugando */
                    get earned() {
                        return this.target === 'PHASE_PODIUM';
                    },
                }">

                @csrf

                <div class="grid gap-3 sm:grid-cols-2">

                    <div>
                        <label class="text-[10px] font-black uppercase tracking-wider text-slate-500">
                            Cuándo se aplica
                        </label>

                        <select name="scope" x-model="scope"
                            class="mt-1.5 w-full rounded-xl border-slate-300 text-sm focus:border-violet-400 focus:ring-violet-400">
                            @foreach (\App\Models\UniverseTournamentModifier::SCOPES as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- La fase, elegida de las que tiene este torneo --}}
                    <div x-show="scope === 'PHASE'" x-cloak>
                        <label class="text-[10px] font-black uppercase tracking-wider text-slate-500">
                            Fase
                        </label>

                        @if ($phases->isEmpty())
                            <input type="text" name="scope_value" value="{{ old('scope_value') }}"
                                placeholder="Nombre exacto de la fase"
                                class="mt-1.5 w-full rounded-xl border-slate-300 text-sm focus:border-violet-400 focus:ring-violet-400">

                            <p class="mt-1 text-[10px] text-amber-600">
                                Este torneo no tiene un recorrido asignado, así que hay que escribirlo.
                            </p>
                        @else
                            <select name="scope_value"
                                class="mt-1.5 w-full rounded-xl border-slate-300 text-sm focus:border-violet-400 focus:ring-violet-400">
                                @foreach ($phases as $phase)
                                    <option value="{{ $phase['name'] }}"
                                        @selected(old('scope_value') === $phase['name'])>
                                        {{ $phase['name'] }}@if ($phase['type']) · {{ $phase['type'] }}@endif
                                    </option>
                                @endforeach
                            </select>
                        @endif
                    </div>

                    {{-- La ronda, sacada de lo que este torneo ha jugado --}}
                    <div x-show="scope === 'ROUND'" x-cloak>
                        <label class="text-[10px] font-black uppercase tracking-wider text-slate-500">
                            Ronda
                        </label>

                        @if ($rounds->isEmpty())
                            <input type="number" name="scope_value" min="1" max="99"
                                value="{{ old('scope_value') }}"
                                class="mt-1.5 w-full rounded-xl border-slate-300 text-sm focus:border-violet-400 focus:ring-violet-400">

                            <p class="mt-1 text-[10px] text-amber-600">
                                Este torneo no se ha jugado todavía, así que aún no se sabe
                                cuántas rondas tendrá.
                            </p>
                        @else
                            <select name="scope_value"
                                class="mt-1.5 w-full rounded-xl border-slate-300 text-sm focus:border-violet-400 focus:ring-violet-400">
                                @foreach ($rounds as $round)
                                    <option value="{{ $round['number'] }}"
                                        @selected((int) old('scope_value') === $round['number'])>
                                        Ronda {{ $round['number'] }}
                                        @if ($round['phases'] > 1)
                                            · en {{ $round['phases'] }} fases
                                        @endif
                                    </option>
                                @endforeach
                            </select>

                            <p class="mt-1 text-[10px] text-slate-400">
                                Sacadas de la última edición jugada. Se aplica en esa ronda
                                de cualquier fase que la tenga.
                            </p>
                        @endif
                    </div>

                </div>


                <div class="grid gap-3 sm:grid-cols-2">

                    <div>
                        <label class="text-[10px] font-black uppercase tracking-wider text-slate-500">
                            A quién
                        </label>

                        <select name="target" x-model="target"
                            class="mt-1.5 w-full rounded-xl border-slate-300 text-sm focus:border-violet-400 focus:ring-violet-400">
                            @foreach (\App\Models\UniverseTournamentModifier::TARGETS as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div x-show="target === 'ENTITY'" x-cloak>
                        <label class="text-[10px] font-black uppercase tracking-wider text-slate-500">
                            Competidor
                        </label>

                        <select name="universe_entity_id"
                            class="mt-1.5 w-full rounded-xl border-slate-300 text-sm focus:border-violet-400 focus:ring-violet-400">
                            <option value="">— elige —</option>
                            @foreach ($entities as $entity)
                                <option value="{{ $entity->id }}">{{ $entity->display_label }}</option>
                            @endforeach
                        </select>
                    </div>

                </div>


                {{-- ============================================ --}}
                {{-- BONUS QUE SE GANA JUGANDO --}}
                {{-- ============================================ --}}

                <div x-show="earned" x-cloak
                    class="rounded-2xl border border-amber-200 bg-amber-50/70 p-4">

                    <p class="text-[11px] font-black uppercase tracking-wider text-amber-700">
                        🏅 Se concede al terminar la fase
                    </p>

                    <p class="mt-1 text-xs leading-relaxed text-amber-800/80">
                        Nadie sabe quién lo recibirá hasta que la fase acabe. En cuanto
                        termina, los mejores de esa clasificación entran a la fase
                        siguiente con el bonus ya puesto.
                    </p>

                    <div class="mt-3 space-y-3">

                        <div>
                            <label class="text-[10px] font-black uppercase tracking-wider text-amber-700">
                                Fase que lo concede
                            </label>

                            <select name="award_phase"
                                class="mt-1.5 w-full rounded-xl border-amber-300 text-sm focus:border-amber-500 focus:ring-amber-500">
                                <option value="">Cualquier fase que termine</option>
                                @foreach ($phases as $phase)
                                    <option value="{{ $phase['name'] }}"
                                        @selected(old('award_phase') === $phase['name'])>
                                        {{ $phase['name'] }}@if ($phase['type']) · {{ $phase['type'] }}@endif
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="grid gap-3 sm:grid-cols-3">

                            <div>
                                <label class="text-[10px] font-black uppercase tracking-wider text-amber-700">
                                    Qué parte
                                </label>

                                <select name="selector_type" x-model="selector"
                                    class="mt-1.5 w-full rounded-xl border-amber-300 text-sm focus:border-amber-500 focus:ring-amber-500">
                                    @foreach (\App\Models\UniverseTournamentModifier::SELECTORS as $value => $label)
                                        <option value="{{ $value }}" @selected(old('selector_type') === $value)>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="text-[10px] font-black uppercase tracking-wider text-amber-700">
                                    <span x-text="selector === 'RANK_RANGE' ? 'Desde el puesto'
                                        : (selector === 'RANK_POSITION' ? 'Puesto' : 'Cuántos')"></span>
                                </label>

                                <input type="number" name="selector_from" min="1" max="999"
                                    value="{{ old('selector_from', 3) }}"
                                    class="mt-1.5 w-full rounded-xl border-amber-300 text-sm focus:border-amber-500 focus:ring-amber-500">
                            </div>

                            <div x-show="selector === 'RANK_RANGE'" x-cloak>
                                <label class="text-[10px] font-black uppercase tracking-wider text-amber-700">
                                    Hasta el puesto
                                </label>

                                <input type="number" name="selector_to" min="1" max="999"
                                    value="{{ old('selector_to', 4) }}"
                                    class="mt-1.5 w-full rounded-xl border-amber-300 text-sm focus:border-amber-500 focus:ring-amber-500">
                            </div>

                        </div>

                        <p class="text-[10px] leading-relaxed text-amber-700/80">
                            <strong class="font-black">Los N primeros</strong> con 3 es el podio ·
                            <strong class="font-black">Un puesto exacto</strong> con 2 es solo el
                            subcampeón · <strong class="font-black">Un rango</strong> del 3 al 4 son
                            los semifinalistas de una eliminatoria ·
                            <strong class="font-black">Los N últimos</strong> penaliza la cola.
                            En fase de grupos el corte se aplica dentro de cada grupo.
                        </p>

                    </div>

                </div>


                @if ($availableGames->count() > 1)
                    <div>
                        <label class="text-[10px] font-black uppercase tracking-wider text-slate-500">
                            Juego al que afecta
                        </label>

                        <div class="mt-1.5 grid gap-2 sm:grid-cols-2">
                            @foreach ($availableGames as $game)
                                <button type="button" @click="game = '{{ $game['key'] }}'"
                                    :class="game === '{{ $game['key'] }}'
                                        ? 'border-sky-500 bg-sky-50 text-sky-800'
                                        : 'border-slate-200 bg-white text-slate-500'"
                                    class="flex items-center gap-2 rounded-xl border-2 px-3 py-2 text-left transition">
                                    <span class="text-base">{{ $game['icon'] ?? '🎲' }}</span>
                                    <span class="min-w-0">
                                        <span class="block truncate text-[11px] font-black">{{ $game['name'] }}</span>
                                    </span>
                                </button>
                            @endforeach
                        </div>

                        <input type="hidden" name="game_key" :value="game">
                    </div>
                @else
                    <input type="hidden" name="game_key" :value="game">
                @endif


                <div class="grid gap-3 sm:grid-cols-3">

                    <div>
                        <label class="text-[10px] font-black uppercase tracking-wider text-slate-500">
                            Estadística
                        </label>

                        <select name="stat_key"
                            class="mt-1.5 w-full rounded-xl border-slate-300 text-sm focus:border-violet-400 focus:ring-violet-400">
                            <template x-for="stat in stats" :key="stat.key">
                                <option :value="stat.key" x-text="stat.label"></option>
                            </template>
                        </select>
                    </div>

                    <div>
                        <label class="text-[10px] font-black uppercase tracking-wider text-slate-500">
                            Operación
                        </label>

                        <select name="operation"
                            class="mt-1.5 w-full rounded-xl border-slate-300 text-sm focus:border-violet-400 focus:ring-violet-400">
                            @foreach (\App\Models\UniverseTournamentReward::OPERATIONS as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="text-[10px] font-black uppercase tracking-wider text-slate-500">
                            Cantidad
                        </label>

                        <input type="number" step="0.01" name="amount" value="{{ old('amount', '1') }}"
                            class="mt-1.5 w-full rounded-xl border-slate-300 text-sm focus:border-violet-400 focus:ring-violet-400">
                    </div>

                </div>


                <div>
                    <label class="text-[10px] font-black uppercase tracking-wider text-slate-500">
                        Nombre (opcional)
                    </label>

                    <input type="text" name="label" value="{{ old('label') }}"
                        placeholder="Ej. Ventaja de anfitrión"
                        class="mt-1.5 w-full rounded-xl border-slate-300 text-sm focus:border-violet-400 focus:ring-violet-400">
                </div>


                <button class="rounded-xl bg-slate-950 px-5 py-3 text-xs font-black text-white hover:bg-slate-800">
                    Añadir bonus
                </button>

                <p class="text-[10px] leading-relaxed text-slate-400">
                    Los bonus se congelan al crear una competición. Cambiarlos no afecta a
                    las que ya están en curso.
                </p>

            </form>

                @if ($running->isNotEmpty())

                    {{-- ============================================ --}}
                    {{-- LLEVARLOS A UNA COMPETICIÓN EN MARCHA --}}
                    {{-- ============================================ --}}

                    <div class="mb-4 rounded-2xl border border-violet-200 bg-violet-50/70 p-4">

                        <p class="text-[11px] font-black uppercase tracking-wider text-violet-700">
                            ⚡ Aplicar a una competición en curso
                        </p>

                        <p class="mt-1 text-xs leading-relaxed text-violet-800/80">
                            Lleva estas reglas a un torneo que ya está jugándose. Lo que
                            alguien ya se ganó jugando no se toca, y las fases que ya
                            terminaron conceden su podio al momento.
                        </p>

                        <div class="mt-3 space-y-2">

                            @foreach ($running as $edition)
                                <form method="POST"
                                    action="{{ route('universes.tournaments.modifiers.sync', [$universe, $universeTournament, $edition]) }}"
                                    class="flex items-center gap-3 rounded-xl bg-white px-3 py-2">
                                    @csrf
                                    @method('PUT')

                                    <span class="font-mono text-[10px] font-black text-violet-500">
                                        {{ $edition->code }}
                                    </span>

                                    <span class="min-w-0 flex-1 truncate text-xs font-bold text-slate-700">
                                        {{ $edition->name }}
                                    </span>

                                    <span class="shrink-0 rounded-full bg-slate-100 px-2 py-0.5 text-[9px] font-black text-slate-500">
                                        {{ $edition->status_label }}
                                    </span>

                                    <button class="shrink-0 rounded-lg bg-violet-600 px-3 py-1.5 text-[10px] font-black text-white transition hover:bg-violet-500">
                                        Aplicar
                                    </button>
                                </form>
                            @endforeach

                        </div>

                    </div>
                @endif


        </section>

    </div>


    {{-- ============================================ --}}
    {{-- EDICIONES JUGADAS --}}
    {{-- ============================================ --}}

    @if ($editions->isNotEmpty())

        <section class="mt-6 rounded-3xl border border-slate-200 bg-white p-6">

            <h3 class="text-lg font-black text-slate-900">Ediciones jugadas</h3>

            <p class="mt-1.5 max-w-2xl text-sm text-slate-500">
                Si cambias las reglas después de jugar, puedes volver a procesarlas.
                Es seguro repetirlo: solo se aplica lo que aún no se había concedido.
            </p>

            <div class="mt-5 divide-y divide-slate-100">

                @foreach ($editions as $edition)
                    <div class="flex flex-wrap items-center gap-3 py-3">

                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-black text-slate-900">{{ $edition->name }}</p>

                            <p class="text-xs text-slate-400">
                                {{ $edition->season ? 'Temporada ' . $edition->season->number : 'Sin temporada' }}
                                @if ($edition->rewards_processed_at)
                                    · procesada {{ $edition->rewards_processed_at->diffForHumans() }}
                                @else
                                    · sin procesar
                                @endif
                            </p>
                        </div>

                        <form method="POST"
                            action="{{ route('universes.tournaments.rewards.reprocess', [$universe, $universeTournament, $edition]) }}">
                            @csrf
                            @method('PUT')

                            <button
                                class="rounded-xl border border-slate-200 px-4 py-2 text-xs font-black text-slate-600 hover:border-violet-300 hover:text-violet-700">
                                Reprocesar
                            </button>
                        </form>

                    </div>
                @endforeach

            </div>

        </section>
    @endif

</x-universe-layout>
