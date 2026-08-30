<x-universe-layout :universe="$universe">

    <x-slot name="header">
        {{ $competition->name }}
    </x-slot>


    <div class="mb-6">

        <a href="{{ route('universes.competitions.index', $universe) }}"
            class="
                text-xs
                font-black
                text-slate-400
                hover:text-violet-600
            ">
            ← Competiciones
        </a>

    </div>


    @if ($errors->any())
        <section class="mb-5 rounded-2xl border border-red-200 bg-red-50 p-5">

            <p class="font-black text-red-900">
                No fue posible completar la acción
            </p>

            @foreach ($errors->all() as $error)
                <p class="mt-1 text-xs text-red-700">
                    • {{ $error }}
                </p>
            @endforeach
        </section>
    @endif


    {{--
        Retocar una edicion que todavia no empezo.

        Solo mientras es un borrador: en cuanto arranca, su configuracion
        queda congelada, y el boton desaparece en vez de llevar a una
        pantalla que iba a rechazarlo.
    --}}

    @if ($competition->status === 'DRAFT')
        <div class="mb-4 flex flex-wrap items-center gap-2 rounded-2xl border border-amber-500/30 bg-amber-500/5 px-4 py-3">

            <span class="text-lg">✎</span>

            <div class="min-w-0 flex-1">
                <p class="text-xs font-black text-amber-300">Todavía no ha empezado</p>
                <p class="text-[11px] text-slate-400">
                    Aún puedes cambiar el juego, cómo se pelea en cada fase y qué se
                    lleva quien gane. La forma y los competidores ya están dibujados.
                </p>
            </div>

            <a href="{{ route('universes.competitions.edit', [$universe, $competition]) }}"
                class="shrink-0 rounded-lg bg-amber-500 px-3 py-1.5 text-[11px] font-black text-slate-950 transition hover:bg-amber-400">
                Editar la configuración
            </a>

            <a href="{{ route('universes.competitions.create', $universe) }}?universe_tournament_id={{ $competition->universe_tournament_id }}&copy={{ $competition->id }}"
                class="shrink-0 rounded-lg border border-slate-700 px-3 py-1.5 text-[11px] font-black text-slate-400 transition hover:border-slate-500 hover:text-slate-100">
                Copiar en una edición nueva
            </a>
        </div>
    @else
        <div class="mb-4 flex flex-wrap items-center gap-2">
            <p class="mr-auto text-[11px] text-slate-500">
                Esta edición ya empezó: su configuración quedó congelada.
            </p>

            <a href="{{ route('universes.competitions.create', $universe) }}?universe_tournament_id={{ $competition->universe_tournament_id }}&copy={{ $competition->id }}"
                class="rounded-lg border border-slate-700 px-3 py-1.5 text-[11px] font-black text-slate-400 transition hover:border-slate-500 hover:text-slate-100">
                Copiar en una edición nueva
            </a>
        </div>
    @endif


    {{-- Acceso a la experiencia de ejecucion (Fase 13) --}}

    <div class="mb-6">
        <a href="{{ route('universes.competitions.play', [$universe, $competition]) }}"
            class="flex items-center gap-4 rounded-3xl border border-violet-500/30 bg-gradient-to-r from-violet-600 to-indigo-600 px-6 py-4 text-white shadow-lg shadow-violet-900/30 transition hover:from-violet-500 hover:to-indigo-500">

            <span class="text-2xl">{{ $competition->isClosed() ? '🎬' : '⚔' }}</span>

            <div class="min-w-0 flex-1">
                <p class="text-sm font-black">
                    {{ $competition->isClosed() ? 'Ver el torneo' : 'Jugar la competición' }}
                </p>
                <p class="text-xs text-violet-100">
                    {{ $competition->isClosed()
                        ? 'Recorre participantes, fases, batallas y campeon en modo lectura.'
                        : 'Participantes, estructura, batallas y simulador a pantalla completa.' }}
                </p>
            </div>

            <span class="text-lg font-black">→</span>

        </a>
    </div>


    <div x-data="competitionLab({
        initialState: @js($payload['state']),
        initialToken: null,
        persistent: true,
        revision: @js($payload['revision']),
        actionUrl: @js(route('universes.competitions.action', [$universe, $competition])),
        storageKey: null,
    })">

        {{-- ============================================ --}}
        {{-- CABECERA: ESTO ES UNA COMPETICIÓN REAL --}}
        {{-- ============================================ --}}

        <section
            class="relative overflow-hidden rounded-[30px] bg-gradient-to-br from-slate-950 via-violet-950 to-indigo-950 p-7 text-white shadow-xl">

            <div class="pointer-events-none absolute -right-20 -top-20 h-72 w-72 rounded-full bg-violet-500/20 blur-3xl">
            </div>

            <div class="relative flex flex-col justify-between gap-6 xl:flex-row xl:items-end">

                <div>
                    <div
                        class="inline-flex rounded-full border border-emerald-300/20 bg-emerald-400/10 px-4 py-2 text-[10px] font-black uppercase tracking-[0.18em] text-emerald-300">
                        ⚔ Competición real · se guarda sola
                    </div>

                    <h1 class="mt-5 text-3xl font-black">
                        {{ $competition->name }}
                    </h1>

                    <div class="mt-3 flex flex-wrap items-center gap-2 text-[10px] font-black uppercase tracking-wider">

                        <span class="rounded-full bg-white/10 px-3 py-1 font-mono text-white/70">
                            {{ $competition->code }}
                        </span>

                        <span
                            class="rounded-full px-3 py-1
                                {{ match ($competition->status) {
                                    'RUNNING' => 'bg-emerald-400 text-emerald-950',
                                    'PAUSED' => 'bg-amber-400 text-amber-950',
                                    'COMPLETED' => 'bg-white text-slate-900',
                                    'CANCELLED' => 'bg-red-400 text-red-950',
                                    default => 'bg-violet-400 text-violet-950',
                                } }}">
                            {{ $competition->status_label }}
                        </span>

                        @if ($competition->season)
                            <span class="rounded-full bg-white/10 px-3 py-1 text-violet-200">
                                ◷ Temporada {{ $competition->season->number }}
                            </span>
                        @endif

                        @if ($competition->started_at)
                            <span class="rounded-full bg-white/10 px-3 py-1 text-slate-300">
                                Inicio {{ $competition->started_at->format('d/m/Y') }}
                            </span>
                        @endif

                    </div>

                    <p class="mt-4 max-w-2xl text-sm leading-7 text-slate-300">
                        Los resultados se guardan en la base de datos en cuanto
                        los registras. Puedes cerrar el navegador o la sesión y
                        volver otro día: la competición continúa exactamente
                        donde la dejaste.
                    </p>
                </div>


                <div class="flex flex-col items-stretch gap-3">

                    <div class="rounded-2xl border border-white/10 bg-white/10 px-4 py-3 text-xs font-black">
                        Motor:
                        <span x-text="state?.graph_runtime?.status ?? state?.status ?? '—'"></span>
                    </div>


                    @can('update', $competition)
                        <div class="flex flex-wrap gap-2">

                            @if ($competition->status === 'RUNNING')
                                <form method="POST"
                                    action="{{ route('universes.competitions.pause', [$universe, $competition]) }}">
                                    @csrf
                                    @method('PATCH')

                                    <button type="submit"
                                        class="rounded-xl border border-white/15 bg-white/10 px-4 py-2.5 text-xs font-black text-white">
                                        ⏸ Pausar
                                    </button>
                                </form>
                            @endif


                            @if ($competition->status === 'PAUSED')
                                <form method="POST"
                                    action="{{ route('universes.competitions.resume', [$universe, $competition]) }}">
                                    @csrf
                                    @method('PATCH')

                                    <button type="submit"
                                        class="rounded-xl bg-emerald-400 px-4 py-2.5 text-xs font-black text-emerald-950">
                                        ▶ Reanudar
                                    </button>
                                </form>
                            @endif


                            @if (!$competition->isClosed())
                                <form method="POST"
                                    action="{{ route('universes.competitions.cancel', [$universe, $competition]) }}"
                                    data-omni-confirm data-confirm-variant="danger" data-confirm-icon="⨯"
                                    data-confirm-title="Cancelar la competición"
                                    data-confirm-message="Se conservará su historial, pero no podrá continuar."
                                    data-confirm-subject="{{ $competition->name }}"
                                    data-confirm-detail="Lo ya jugado se queda como está. Lo que falte no se jugará nunca."
                                    data-confirm-action="Sí, cancelarla">
                                    @csrf
                                    @method('PATCH')

                                    <button type="submit"
                                        class="rounded-xl border border-red-300/20 bg-red-500/20 px-4 py-2.5 text-xs font-black text-red-200">
                                        Cancelar
                                    </button>
                                </form>
                            @endif

                        </div>
                    @endcan

                </div>
            </div>
        </section>


        {{-- ============================================ --}}
        {{-- CAMPEÓN --}}
        {{-- ============================================ --}}

        @if ($history['champion'])
            <section
                class="mt-6 overflow-hidden rounded-3xl border border-violet-200 bg-gradient-to-br from-violet-50 via-white to-white">

                <div class="grid items-center gap-6 p-7 sm:grid-cols-[auto_minmax(0,1fr)]">

                    <div
                        class="mx-auto flex h-32 w-32 items-center justify-center overflow-hidden rounded-3xl bg-violet-100 text-6xl text-violet-400 shadow-lg shadow-violet-600/10 ring-4 ring-violet-500/20 sm:mx-0">

                        @if ($history['champion']->universeEntity?->image_url)
                            <img src="{{ $history['champion']->universeEntity->image_url }}"
                                alt="{{ $history['champion']->name }}"
                                class="h-full w-full object-cover">
                        @else
                            ✦
                        @endif

                    </div>


                    <div class="text-center sm:text-left">

                        <p class="text-[10px] font-black uppercase tracking-[0.22em] text-violet-600">
                            🏆 Campeón
                        </p>

                        <h2 class="mt-2 text-3xl font-black tracking-tight text-slate-900">
                            {{ $history['champion']->name }}
                        </h2>

                        <p class="mt-1 text-xs text-slate-500">
                            {{ $history['champion']->entity_type_name }}

                            @if ($history['champion']->entity_version_name)
                                · <span class="text-violet-500">
                                    {{ $history['champion']->entity_version_name }}
                                </span>
                            @endif
                        </p>


                        <div class="mt-4 flex flex-wrap justify-center gap-2 sm:justify-start">

                            <span class="rounded-xl bg-white px-3 py-2 text-[11px] font-black text-slate-700 shadow-sm">
                                {{ $history['champion']->wins }}G
                                · {{ $history['champion']->draws }}E
                                · {{ $history['champion']->losses }}P
                            </span>

                            <span class="rounded-xl bg-white px-3 py-2 text-[11px] font-black text-slate-700 shadow-sm">
                                {{ $history['champion']->points }} pts
                            </span>

                        </div>

                    </div>

                </div>

            </section>
        @endif


        {{-- ============================================ --}}
        {{-- CIFRAS --}}
        {{-- ============================================ --}}

        <section class="mt-6 grid grid-cols-2 gap-3 lg:grid-cols-5">

            @foreach ([
        ['Competidores', $history['participants'], '✦'],
        ['Fases', $history['phases'], '◆'],
        ['Encuentros', $history['matches'], '⚔'],
        ['Jugados', $history['matches_played'], '✓'],
        ['Duración', $history['duration'] ?? '—', '◷'],
    ] as [$label, $value, $icon])
                <article class="rounded-2xl border border-slate-200 bg-white p-4">

                    <div class="flex items-center justify-between gap-2">

                        <div class="min-w-0">
                            <p class="text-[9px] font-black uppercase tracking-wider text-slate-400">
                                {{ $label }}
                            </p>

                            <p class="mt-1.5 truncate text-2xl font-black text-slate-900">
                                {{ $value }}
                            </p>
                        </div>

                        <span
                            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-violet-50 text-violet-600">
                            {{ $icon }}
                        </span>

                    </div>

                </article>
            @endforeach

        </section>


        {{-- ESTADOS CERRADOS / PAUSADOS --}}

        @if ($competition->isClosed())
            <section class="mt-5 rounded-2xl border border-slate-200 bg-white p-5">

                <p class="text-sm font-black text-slate-900">
                    Esta competición está {{ mb_strtolower($competition->status_label) }}
                </p>

                <p class="mt-2 text-xs text-slate-500">
                    Se conserva en modo lectura: puedes revisar participantes,
                    encuentros e historial, pero ya no admite nuevas acciones.
                </p>

            </section>
        @elseif ($competition->status === 'PAUSED')
            <section class="mt-5 rounded-2xl border border-amber-200 bg-amber-50 p-5">

                <p class="text-sm font-black text-amber-900">
                    Competición pausada
                </p>

                <p class="mt-2 text-xs text-amber-800">
                    Reanúdala desde el botón de arriba para seguir registrando
                    resultados.
                </p>

            </section>
        @endif


        {{-- ERROR DEL MOTOR --}}

        <div x-show="error" x-cloak
            class="mt-5 rounded-2xl border border-red-200 bg-red-50 p-5 text-sm text-red-800">
            <span x-text="error"></span>
        </div>


        {{-- ============================================ --}}
        {{-- ARRANQUE --}}
        {{-- ============================================ --}}

        @if ($competition->isDraft())
            @can('update', $competition)
                <section class="mt-6 rounded-3xl border border-violet-200 bg-gradient-to-br from-white to-violet-50/60 p-7">

                    <p class="text-xs font-black uppercase tracking-[0.18em] text-violet-600">
                        Todo listo
                    </p>

                    <h2 class="mt-2 text-2xl font-black text-slate-900">
                        Comenzar la competición
                    </h2>

                    <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-500">
                        {{ $competition->participant_count }} competidores esperando.
                        Al comenzar, el Tournament Graph repartirá a los participantes
                        por las fases según la configuración congelada.
                    </p>

                    <button type="button" @click="execute('START_TOURNAMENT')"
                        :disabled="loading"
                        class="mt-5 rounded-xl bg-violet-600 px-6 py-3.5 text-sm font-black text-white shadow-lg shadow-violet-600/20 disabled:opacity-50">
                        <span x-show="!loading">▶ Comenzar competición</span>
                        <span x-show="loading" x-cloak>Comenzando…</span>
                    </button>

                </section>
            @endcan
        @endif


        {{-- ============================================ --}}
        {{-- RUNTIME (parciales reutilizados del Lab) --}}
        {{-- ============================================ --}}

        @if (!$competition->isDraft())

            {{-- Simulador del juego (Fase 11): antes que el recorrido
                 automatico, porque es la forma de jugar, no de saltarse. --}}

            @include('universes.competitions.partials.simulator')

            <div class="mt-6">
                @include('tournaments.lab.partials.automatic-runtime')
            </div>

            <div class="mt-6">
                @include('tournaments.lab.partials.manual-decision')
            </div>

            <div class="mt-6">
                @include('tournaments.lab.partials.participants-inspector')
            </div>
        @endif


        {{-- ============================================ --}}
        {{-- DESARROLLO DE LA COMPETICIÓN --}}
        {{-- ============================================ --}}

        @if ($phaseBlocks->isNotEmpty())
            <div x-data="{ phase: 0, total: {{ $phaseBlocks->count() }} }" class="mt-8">

                <div class="flex flex-wrap items-end justify-between gap-4">

                    <div>
                        <p class="text-xs font-black uppercase tracking-[0.18em] text-violet-600">
                            Cómo se desarrolló
                        </p>

                        <h3 class="mt-2 text-2xl font-black text-slate-900">
                            ◆ Fases
                        </h3>
                    </div>


                    @if ($phaseBlocks->count() > 1)
                        <div class="flex items-center gap-2">

                            <button type="button" @click="phase = Math.max(0, phase - 1)"
                                :disabled="phase === 0"
                                class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-black text-slate-600 disabled:opacity-40">
                                ← Anterior
                            </button>

                            <span class="text-xs font-black text-slate-400">
                                <span x-text="phase + 1"></span> / <span x-text="total"></span>
                            </span>

                            <button type="button" @click="phase = Math.min(total - 1, phase + 1)"
                                :disabled="phase === total - 1"
                                class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-black text-slate-600 disabled:opacity-40">
                                Siguiente →
                            </button>

                        </div>
                    @endif

                </div>


                @if ($phaseBlocks->count() > 1)
                    <div class="mt-4 flex flex-wrap gap-2">

                        @foreach ($phaseBlocks as $index => $block)
                            <button type="button" @click="phase = {{ $index }}"
                                :class="phase === {{ $index }}
                                    ? 'bg-violet-600 text-white shadow-lg shadow-violet-600/20'
                                    : 'bg-white text-slate-500 border border-slate-200'"
                                class="rounded-xl px-3 py-2 text-[11px] font-black transition">
                                {{ $block['phase']->node_name }}
                            </button>
                        @endforeach

                    </div>
                @endif


                <div class="mt-5 space-y-5">

                    @foreach ($phaseBlocks as $index => $block)
                        <div x-show="phase === {{ $index }}" x-cloak>
                            @include('universes.competitions.partials.history.phase', [
                                'block' => $block,
                            ])
                        </div>
                    @endforeach

                </div>

            </div>
        @endif


        {{-- ============================================ --}}
        {{-- PARTICIPANTES CONGELADOS --}}
        {{-- ============================================ --}}

        <section class="mt-8 rounded-3xl border border-slate-200 bg-white p-6">

            <div class="flex items-end justify-between gap-4">

                <div>
                    <p class="text-xs font-black uppercase tracking-[0.18em] text-violet-600">
                        Congelados al empezar
                    </p>

                    <h3 class="mt-2 text-2xl font-black text-slate-900">
                        ✦ Participantes
                    </h3>
                </div>

                <span class="text-xs font-black text-slate-400">
                    {{ $participants->count() }}
                </span>

            </div>


            <div class="mt-5 overflow-x-auto">

                <table class="w-full min-w-max text-left text-sm">

                    <thead>
                        <tr class="border-b border-slate-200 text-[9px] font-black uppercase tracking-wider text-slate-400">
                            <th class="pb-2 pr-4">Seed</th>
                            <th class="pb-2 pr-4">Competidor</th>
                            <th class="pb-2 pr-4">Estado</th>
                            <th class="pb-2 pr-4 text-center">PJ</th>
                            <th class="pb-2 pr-4 text-center">G</th>
                            <th class="pb-2 pr-4 text-center">E</th>
                            <th class="pb-2 pr-4 text-center">P</th>
                            <th class="pb-2 pr-4 text-center">Pts</th>
                            <th class="pb-2">Posición final</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($participants as $participant)
                            <tr class="border-b border-slate-100">

                                <td class="py-2.5 pr-4 font-mono text-xs text-slate-400">
                                    {{ $participant->seed }}
                                </td>

                                <td class="py-2.5 pr-4">

                                    {{-- Entidad, versión y atributos congelados al empezar --}}
                                    @include('universes.competitions.partials.participant-chip', [
                                        'name' => $participant->name,
                                        'imageUrl' => $participant->universeEntity?->image_url,
                                        'typeName' => $participant->entity_type_name,
                                        'versionName' => $participant->entity_version_name,
                                        'attributes' => $participant->attribute_snapshot ?? [],
                                        'seed' => null,
                                        'size' => 'sm',
                                        'maxAttributes' => 4,
                                    ])


                                    @unless ($participant->universe_entity_id)
                                        <span class="mt-1 block text-[9px] font-bold text-slate-400">
                                            Ya no está en el Universo
                                        </span>
                                    @endunless
                                </td>

                                <td class="py-2.5 pr-4">
                                    <span
                                        class="rounded-full px-2 py-0.5 text-[9px] font-black uppercase
                                            {{ match ($participant->status) {
                                                'ACTIVE' => 'bg-emerald-100 text-emerald-700',
                                                'ELIMINATED' => 'bg-red-100 text-red-700',
                                                'COMPLETED' => 'bg-slate-900 text-white',
                                                default => 'bg-slate-100 text-slate-600',
                                            } }}">
                                        {{ $participant->status }}
                                    </span>
                                </td>

                                <td class="py-2.5 pr-4 text-center text-xs">{{ $participant->matches }}</td>
                                <td class="py-2.5 pr-4 text-center text-xs">{{ $participant->wins }}</td>
                                <td class="py-2.5 pr-4 text-center text-xs">{{ $participant->draws }}</td>
                                <td class="py-2.5 pr-4 text-center text-xs">{{ $participant->losses }}</td>

                                <td class="py-2.5 pr-4 text-center text-xs font-black">
                                    {{ $participant->points }}
                                </td>

                                <td class="py-2.5 text-xs text-slate-500">
                                    {{ $participant->final_location_name ?? '—' }}
                                </td>

                            </tr>
                        @endforeach
                    </tbody>

                </table>

            </div>

        </section>


        {{-- ============================================ --}}
        {{-- HISTORIAL --}}
        {{-- ============================================ --}}

        <section class="mt-6 rounded-3xl border border-slate-200 bg-white p-6">

            <p class="text-xs font-black uppercase tracking-[0.18em] text-violet-600">
                Ledger
            </p>

            <h3 class="mt-2 text-2xl font-black text-slate-900">
                ◷ Historial
            </h3>

            <p class="mt-2 text-sm text-slate-500">
                Todo lo que ha ocurrido en esta competición, guardado en base
                de datos.
            </p>


            <div class="mt-5 space-y-2">

                @forelse ($events as $event)
                    <div class="flex items-start gap-3 rounded-xl bg-slate-50 p-3">

                        <span
                            class="mt-0.5 shrink-0 rounded-full px-2 py-0.5 text-[9px] font-black uppercase
                                {{ match ($event->level) {
                                    'SUCCESS' => 'bg-emerald-100 text-emerald-700',
                                    'WARNING' => 'bg-amber-100 text-amber-700',
                                    'ERROR' => 'bg-red-100 text-red-700',
                                    default => 'bg-slate-200 text-slate-600',
                                } }}">
                            {{ $event->level }}
                        </span>

                        <div class="min-w-0">
                            <p class="text-xs font-black text-slate-700">
                                {{ $event->type }}
                            </p>

                            <p class="mt-0.5 text-xs text-slate-500">
                                {{ $event->message }}
                            </p>
                        </div>

                    </div>
                @empty
                    <p class="rounded-xl border border-dashed border-slate-200 p-6 text-center text-sm text-slate-400">
                        Todavía no hay eventos.
                    </p>
                @endforelse

            </div>

        </section>


        {{-- ELIMINAR (solo si nunca arrancó) --}}

        @if ($competition->isDraft())
            @can('delete', $competition)
                <section class="mt-6 rounded-3xl border border-red-200 bg-red-50 p-6">

                    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">

                        <div>
                            <p class="text-sm font-black text-red-800">
                                Eliminar competición
                            </p>

                            <p class="mt-1 text-xs text-red-600">
                                Todavía no ha comenzado, así que puede borrarse.
                                Una vez iniciada solo podrá cancelarse.
                            </p>
                        </div>

                        <form method="POST"
                            action="{{ route('universes.competitions.destroy', [$universe, $competition]) }}"
                            data-omni-confirm data-confirm-variant="danger" data-confirm-icon="×"
                            data-confirm-title="Eliminar la edición"
                            data-confirm-message="Todavía no ha empezado, así que no se pierde nada jugado."
                            data-confirm-subject="{{ $competition->name }}"
                            data-confirm-detail="Se van con ella sus participantes y su reparto por puertas."
                            data-confirm-action="Sí, eliminarla">
                            @csrf
                            @method('DELETE')

                            <button type="submit"
                                class="shrink-0 rounded-xl bg-red-600 px-4 py-2.5 text-xs font-black text-white">
                                Eliminar
                            </button>
                        </form>

                    </div>

                </section>
            @endcan
        @endif

    </div>

</x-universe-layout>
