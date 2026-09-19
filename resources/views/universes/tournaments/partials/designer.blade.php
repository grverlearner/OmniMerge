@php
    /*
     * EL DISEÑADOR DE UN TORNEO OFICIAL
     *
     * Un torneo de universo es una MARCA —«la Copa»— y lo que se configura
     * aquí es lo que TODAS sus ediciones heredan salvo que una diga otra
     * cosa. Por eso esta pantalla decide y las competiciones matizan.
     *
     * Seis bloques plegables, uno abierto cada vez: son largos, y verlos
     * todos a la vez convierte una decisión en un muro.
     *
     * Cada bloque lleva en su cabecera un resumen de lo que hay dentro, así
     * que plegado sigue diciendo algo. Es la única forma de que plegar no
     * signifique esconder.
     *
     * Espera: $universe, $universeTournament (o null), $templates, $games,
     * $defaultGameKey, $eligibilityCatalog, $eligibilityRules, $trophies,
     * $previewUrl.
     */

    $t = $universeTournament ?? null;

    /* Con qué nace un torneo nuevo en este universo: ver su configuración */
    $nace = $universe->ajustes()->competitionDefaults();

    /* Los bloques, con su color. Literales: Tailwind lee el código fuente */
    $blocks = [
        'identity' => ['n' => '01', 'label' => 'Identidad', 'icon' => 'chispa', 'dot' => 'bg-slate-400', 'text' => 'text-slate-300', 'soft' => 'bg-slate-500/10', 'border' => 'border-slate-500/30'],
        'game'     => ['n' => '02', 'label' => 'El juego', 'icon' => 'dado', 'dot' => 'bg-emerald-400', 'text' => 'text-emerald-300', 'soft' => 'bg-emerald-500/10', 'border' => 'border-emerald-500/30'],
        'battle'   => ['n' => '03', 'label' => 'La batalla', 'icon' => 'espadas', 'dot' => 'bg-amber-400', 'text' => 'text-amber-300', 'soft' => 'bg-amber-500/10', 'border' => 'border-amber-500/30'],
        'seasons'  => ['n' => '04', 'label' => 'Temporadas', 'icon' => 'calendario', 'dot' => 'bg-cyan-400', 'text' => 'text-cyan-300', 'soft' => 'bg-cyan-500/10', 'border' => 'border-cyan-500/30'],
        'prizes'   => ['n' => '05', 'label' => 'Trofeo y premios', 'icon' => 'trofeo', 'dot' => 'bg-violet-400', 'text' => 'text-violet-300', 'soft' => 'bg-violet-500/10', 'border' => 'border-violet-500/30'],
        'who'      => ['n' => '06', 'label' => 'Quién compite', 'icon' => 'usuario', 'dot' => 'bg-rose-400', 'text' => 'text-rose-300', 'soft' => 'bg-rose-500/10', 'border' => 'border-rose-500/30'],
    ];
@endphp

<div class="mx-auto max-w-5xl"
    x-data="tournamentDesigner({
        open: 'identity',
        games: @js(collect($games)->values()),
        catalog: @js($eligibilityCatalog),
        previewUrl: @js($previewUrl),
        csrf: @js(csrf_token()),
        defaultGameKey: @js($defaultGameKey),
        gameMode: @js(old('game_mode', $t->game_mode ?? 'SINGLE')),
        gameKey: @js(old('game_key', $t->game_key ?? $defaultGameKey)),
        battleParticipants: @js(old('battle_participants', $t->battle_participants ?? '')),
        seriesFormat: @js(old('series_format', $t->series_format ?? $nace['series_format'])),
        bestOf: @js((int) old('best_of', $t->best_of ?? $nace['best_of'])),
        fixedGames: @js((int) old('fixed_games', $t->fixed_games ?? $nace['fixed_games'])),
        decisionMode: @js(old('decision_mode', $t->decision_mode ?? $nace['decision_mode'])),
        allowDraws: @js((bool) old('allow_draws', $t->allow_draws ?? $nace['allow_draws'])),
        allowPhaseGame: @js((bool) old('allow_phase_game', $t->allow_phase_game ?? false)),
        allowPhaseBattle: @js((bool) old('allow_phase_battle', $t->allow_phase_battle ?? false)),
        recurrenceMode: @js(old('recurrence_mode', $t->recurrence_mode ?? $nace['recurrence_mode'])),
        eligibilityMode: @js($eligibilityRules['mode'] ?? 'ALL'),
        rules: @js($eligibilityRules['rules'] ?? []),
        groups: @js($eligibilityRules['groups'] ?? []),
        include: @js($eligibilityRules['include'] ?? []),
        exclude: @js($eligibilityRules['exclude'] ?? []),
        preview: @js($eligibilityPreview),
        roster: @js($eligibilityRoster),
    })">

    {{-- ============ EL ÍNDICE ============ --}}

    {{--
        Seis pastillas que dicen dónde estás y qué hay dentro de cada
        bloque sin abrirlo. Es el índice y el resumen a la vez.
    --}}

    <div class="mb-4 grid grid-cols-2 gap-1.5 sm:grid-cols-3 lg:grid-cols-6">
        @foreach ($blocks as $key => $b)
            <button type="button" @click="toggle('{{ $key }}')"
                class="rounded-xl border px-2 py-2 text-left transition"
                :class="isOpen('{{ $key }}')
                    ? '{{ $b['border'] }} {{ $b['soft'] }}'
                    : 'border-slate-800 bg-slate-900/50 hover:border-slate-700'">

                <div class="flex items-center gap-1.5">
                    <span class="font-mono text-[9px] text-slate-600">{{ $b['n'] }}</span>
                    <x-omni-icon :name="$b['icon']" size="h-3.5 w-3.5" />
                </div>

                <p class="mt-0.5 truncate text-[10px] font-black"
                    :class="isOpen('{{ $key }}') ? '{{ $b['text'] }}' : 'text-slate-300'">
                    {{ $b['label'] }}
                </p>

                @if ($key === 'game')
                    <p class="truncate text-[8px] text-slate-600" x-text="gameSummary"></p>
                @elseif ($key === 'battle')
                    <p class="truncate text-[8px] text-slate-600" x-text="battleSummary"></p>
                @elseif ($key === 'who')
                    <p class="truncate text-[8px]"
                        :class="eligibilityEmpty ? 'text-rose-400' : 'text-slate-600'"
                        x-text="eligibilitySummary"></p>
                @endif
            </button>
        @endforeach
    </div>


    @include('universes.tournaments.partials.designer-identity')
    @include('universes.tournaments.partials.designer-game')
    @include('universes.tournaments.partials.designer-battle')
    @include('universes.tournaments.partials.designer-seasons')
    @include('universes.tournaments.partials.designer-prizes')
    @include('universes.tournaments.partials.designer-who')


    {{-- ============ GUARDAR ============ --}}

    <div class="sticky bottom-0 mt-4 flex flex-wrap items-center gap-2 rounded-2xl border border-slate-800 bg-slate-950/95 px-4 py-3 backdrop-blur">

        <a href="{{ route('universes.tournaments.index', $universe) }}"
            class="rounded-lg border border-slate-800 px-3 py-1.5 text-[11px] font-black text-slate-400 transition hover:border-slate-600 hover:text-slate-100">
            Cancelar
        </a>

        <p class="mr-auto text-[10px] leading-relaxed text-slate-600">
            Esto es lo que heredarán <span class="font-bold text-slate-400">todas</span> las
            ediciones. Cada competición podrá matizarlo.
        </p>

        <button class="rounded-lg bg-amber-500 px-4 py-1.5 text-[11px] font-black text-slate-950 transition hover:bg-amber-400">
            {{ $t ? 'Guardar torneo' : 'Crear torneo' }}
        </button>

    </div>

</div>
