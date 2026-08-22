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
                x-data="{ trigger: '{{ old('trigger', 'POSITION') }}' }">

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


                <div class="grid gap-3 sm:grid-cols-3">

                    <div>
                        <label class="text-[10px] font-black uppercase tracking-wider text-slate-500">
                            Estadística
                        </label>

                        <select name="stat_key"
                            class="mt-1.5 w-full rounded-xl border-slate-300 text-sm focus:border-violet-400 focus:ring-violet-400">
                            <option value="">— solo trofeo —</option>
                            @foreach ($definition['stats'] ?? [] as $stat)
                                <option value="{{ $stat['key'] }}" @selected(old('stat_key') === $stat['key'])>
                                    {{ $stat['label'] }}
                                </option>
                            @endforeach
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
                        <div class="flex items-center gap-4 px-6 py-4">

                            <div class="min-w-0 flex-1">

                                <div class="flex flex-wrap items-center gap-2">

                                    <span
                                        class="rounded-full bg-sky-100 px-2.5 py-1 text-[10px] font-black text-sky-700">
                                        {{ $modifier->scope_label }}
                                    </span>

                                    <span
                                        class="rounded-full bg-emerald-100 px-2.5 py-1 text-[10px] font-black text-emerald-700">
                                        {{ $modifier->effect_label }}
                                    </span>

                                    <span class="text-[10px] font-bold text-slate-400">
                                        {{ $modifier->target === 'ENTITY'
                                            ? ($modifier->universeEntity?->display_label ?? 'competidor')
                                            : 'todos' }}
                                    </span>

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
                x-data="{ scope: 'TOURNAMENT', target: 'ALL' }">

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

                    <div x-show="scope !== 'TOURNAMENT'" x-cloak>
                        <label class="text-[10px] font-black uppercase tracking-wider text-slate-500">
                            <span x-text="scope === 'PHASE' ? 'Nombre de la fase' : 'Número de ronda'"></span>
                        </label>

                        <input type="text" name="scope_value" value="{{ old('scope_value') }}"
                            placeholder="Ej. Final"
                            class="mt-1.5 w-full rounded-xl border-slate-300 text-sm focus:border-violet-400 focus:ring-violet-400">
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


                <div class="grid gap-3 sm:grid-cols-3">

                    <div>
                        <label class="text-[10px] font-black uppercase tracking-wider text-slate-500">
                            Estadística
                        </label>

                        <select name="stat_key"
                            class="mt-1.5 w-full rounded-xl border-slate-300 text-sm focus:border-violet-400 focus:ring-violet-400">
                            @foreach ($definition['stats'] ?? [] as $stat)
                                <option value="{{ $stat['key'] }}">{{ $stat['label'] }}</option>
                            @endforeach
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
