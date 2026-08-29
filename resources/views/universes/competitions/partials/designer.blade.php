@php
    /*
     * EL DISEÑADOR DE UNA EDICIÓN
     *
     * Un torneo es una marca —«la Copa»—; una competición es la edición que
     * se juega este año. Lo que se decide aquí vale solo para esta, y no
     * puede abrir lo que el torneo cerró.
     *
     * Siete bloques plegables, uno abierto cada vez. Cada uno lleva en su
     * cabecera un resumen de lo que hay dentro, así que plegado sigue
     * diciendo algo: es la única forma de que plegar no signifique esconder.
     *
     * Espera, además de lo que arma CompetitionDesignerContext:
     * $universe, $universeTournament, $competition (o null), $source, $seasons.
     */

    $editando = $competition !== null;

    $bloques = [
        'identity' => ['n' => '01', 'label' => 'Esta edición',   'icon' => '◈', 'text' => 'text-slate-300',  'soft' => 'bg-slate-500/10',  'border' => 'border-slate-500/30'],
        'shape'    => ['n' => '02', 'label' => 'La forma',       'icon' => '⑂', 'text' => 'text-sky-300',    'soft' => 'bg-sky-500/10',    'border' => 'border-sky-500/30'],
        'game'     => ['n' => '03', 'label' => 'El juego',       'icon' => '🎲', 'text' => 'text-emerald-300','soft' => 'bg-emerald-500/10','border' => 'border-emerald-500/30'],
        'battle'   => ['n' => '04', 'label' => 'La batalla',     'icon' => '⚔', 'text' => 'text-amber-300',  'soft' => 'bg-amber-500/10',  'border' => 'border-amber-500/30'],
        'phases'   => ['n' => '05', 'label' => 'Fase por fase',  'icon' => '⧉', 'text' => 'text-cyan-300',   'soft' => 'bg-cyan-500/10',   'border' => 'border-cyan-500/30'],
        'doors'    => ['n' => '06', 'label' => 'Quién entra',    'icon' => '⇥', 'text' => 'text-rose-300',   'soft' => 'bg-rose-500/10',   'border' => 'border-rose-500/30'],
        'prizes'   => ['n' => '07', 'label' => 'Trofeos y premios', 'icon' => '🏆', 'text' => 'text-violet-300','soft' => 'bg-violet-500/10','border' => 'border-violet-500/30'],
    ];
@endphp

<div class="mx-auto max-w-6xl"
    x-data="competitionDesigner({
        open: 'identity',
        inherited: @js($inherited),
        competition: @js($designerValues),
        templates: @js($templateBriefs),
        games: @js($games),
        phaseSettings: @js((object) $phaseSettings),
        competitors: @js($competitors),
        catalog: @js($eligibilityCatalog),
        startRules: @js(array_values((array) $designerValues['start_rules'])),
        currentAssignments: @js((object) $currentAssignments),
        canReassign: @js($canReassign),
        inheritedRewards: @js($inheritedRewards),
        previewUrl: @js(route('universes.competitions.start-preview', $universe)),
        csrf: @js(csrf_token()),
    })"
    x-init="$watch('startRules', () => refreshRouting())"
    @change.debounce.400ms="assignMode === 'RULES' && refreshRouting()">


    {{-- ============ EL ÍNDICE ============ --}}

    <div class="mb-4 grid grid-cols-2 gap-1.5 sm:grid-cols-4 lg:grid-cols-7">
        @foreach ($bloques as $key => $b)
            <button type="button" @click="toggle('{{ $key }}')"
                class="rounded-xl border px-2 py-2 text-left transition"
                :class="isOpen('{{ $key }}')
                    ? '{{ $b['border'] }} {{ $b['soft'] }}'
                    : 'border-slate-800 bg-slate-900/50 hover:border-slate-700'">

                <div class="flex items-center gap-1.5">
                    <span class="font-mono text-[9px] text-slate-600">{{ $b['n'] }}</span>
                    <span class="text-[11px]">{{ $b['icon'] }}</span>
                </div>

                <p class="mt-0.5 truncate text-[10px] font-black"
                    :class="isOpen('{{ $key }}') ? '{{ $b['text'] }}' : 'text-slate-300'">
                    {{ $b['label'] }}
                </p>

                @if ($key === 'shape')
                    <p class="truncate text-[8px] text-slate-600" x-text="template?.name ?? 'sin elegir'"></p>
                @elseif ($key === 'game')
                    <p class="truncate text-[8px] text-slate-600" x-text="gameSummary"></p>
                @elseif ($key === 'battle')
                    <p class="truncate text-[8px] text-slate-600" x-text="battleSummary"></p>
                @elseif ($key === 'phases')
                    <p class="truncate text-[8px] text-slate-600" x-text="phasesSummary"></p>
                @elseif ($key === 'doors')
                    <p class="truncate text-[8px]"
                        :class="totalIn === 0 ? 'text-rose-400' : 'text-slate-600'"
                        x-text="doorsSummary"></p>
                @endif
            </button>
        @endforeach
    </div>


    @include('universes.competitions.partials.designer-identity')
    @include('universes.competitions.partials.designer-shape')
    @include('universes.competitions.partials.designer-game')
    @include('universes.competitions.partials.designer-battle')
    @include('universes.competitions.partials.designer-phases')
    @include('universes.competitions.partials.designer-doors')
    @include('universes.competitions.partials.designer-prizes')


    {{-- ============ GUARDAR ============ --}}

    <div class="sticky bottom-0 mt-4 flex flex-wrap items-center gap-2 rounded-2xl border border-slate-800 bg-slate-950/95 px-4 py-3 backdrop-blur">

        <a href="{{ $editando
            ? route('universes.competitions.show', [$universe, $competition])
            : route('universes.tournaments.show', [$universe, $universeTournament]) }}"
            class="rounded-lg border border-slate-800 px-3 py-1.5 text-[11px] font-black text-slate-400 transition hover:border-slate-600 hover:text-slate-100">
            Cancelar
        </a>

        <p class="mr-auto text-[10px] leading-relaxed text-slate-600">
            @if ($editando)
                Esta edición todavía no ha empezado, así que aún se puede cambiar
                cómo se pelea. Lo que ya no se toca es la forma ni quién compite.
            @else
                Al crearla, la configuración queda <span class="font-bold text-slate-400">congelada</span>:
                editar el torneo o la plantilla ya no la afecta.
            @endif
        </p>

        <button class="rounded-lg bg-amber-500 px-4 py-1.5 text-[11px] font-black text-slate-950 transition hover:bg-amber-400">
            {{ $editando ? 'Guardar edición' : 'Crear edición' }}
        </button>
    </div>

</div>
