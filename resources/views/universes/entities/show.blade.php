@php
    /*
     * LA FICHA DE UN COMPETIDOR
     *
     * Todo lo que hay de uno, en pestañas: quién es, qué lleva, con qué
     * juega, qué ha ganado, contra quién y cuándo.
     *
     * Por pestañas y no en una columna de tres metros porque son seis cosas
     * distintas y casi nunca se vienen a ver todas: se viene a mirar una.
     *
     * Espera lo que ya pasaba el controlador —summary, byEngine, rivals,
     * streaks, rank, gameProfile, palmares, podiums, trophyAwards,
     * statHistory, historyBySeason— y nada más: la ficha no consulta.
     */

    $attrs = collect($entity->attribute_snapshot ?? []);
    $versiones = app(\App\Services\Universes\UniverseEntityVersionResolver::class)->all($entity);

    $pestanas = [
        'about' => ['◈', 'Quién es'],
        'games' => ['🎲', 'Juegos y stats'],
        'palmares' => ['🏆', 'Palmarés'],
        'rivals' => ['⚔', 'Rivales'],
        'history' => ['🕘', 'Historial'],
    ];
@endphp

<x-universe-layout :universe="$universe" surface="dark">

    <x-slot name="header">{{ $entity->display_label }}</x-slot>

    <div x-data="{
            tab: 'about',
            syncOpen: false,
            syncBusy: false,
            diff: null,

            async loadDiff() {
                this.syncOpen = true;
                this.syncBusy = true;

                try {
                    const r = await fetch(@js(route('universes.entities.sync.preview', [$universe, $entity])), {
                        headers: { Accept: 'application/json' },
                    });

                    this.diff = await r.json();
                } catch (e) {
                    this.diff = { available: false, reason: 'No se pudo consultar la Biblioteca.' };
                } finally {
                    this.syncBusy = false;
                }
            },
        }">


        {{-- ============ LA CABECERA ============ --}}

        <div class="mb-3 overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

            <div class="flex flex-wrap gap-3 p-3">

                <a href="{{ route('universes.entities.index', $universe) }}"
                    class="self-start rounded-lg border border-slate-800 px-2 py-1.5 text-[11px] font-black text-slate-400 transition hover:border-slate-600 hover:text-slate-100">←</a>

                <span class="flex h-28 w-24 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-slate-950">
                    @if ($entity->image_url)
                        <img src="{{ $entity->image_url }}" alt="" class="h-full w-full object-cover">
                    @else
                        <span class="font-mono text-[20px] font-black text-slate-700">
                            {{ mb_strtoupper(mb_substr($entity->display_label, 0, 2)) }}
                        </span>
                    @endif
                </span>

                <div class="min-w-0 flex-1">

                    <div class="flex flex-wrap items-center gap-2">
                        <h1 class="text-xl font-black text-slate-100">{{ $entity->display_label }}</h1>

                        <span class="rounded px-1.5 py-0.5 text-[9px] font-black
                            {{ $entity->status === 'ACTIVE' ? 'bg-emerald-500/20 text-emerald-300' : 'bg-slate-800 text-slate-400' }}">
                            {{ $entity->status_label }}
                        </span>

                        {{--
                            positionOf() devuelve la FILA entera del ranking
                            —con su entidad, sus puntos y su posición—, no un
                            número. Tratarla como un número reventaba la
                            página entera.
                        --}}
                        @php $puesto = is_object($rank) ? ($rank->position ?? null) : $rank; @endphp

                        @if ($puesto)
                            <span class="rounded bg-amber-500/20 px-1.5 py-0.5 text-[9px] font-black text-amber-300"
                                title="{{ is_object($rank) ? $rank->points . ' puntos' : '' }}">
                                #{{ $puesto }} del universo
                            </span>
                        @endif
                    </div>

                    <p class="font-mono text-[9px] text-slate-600">
                        {{ $entity->code }}{{ $entity->entity_type_name ? ' · ' . $entity->entity_type_name : '' }}
                        @if ($entity->imported_at)
                            · llegó el {{ $entity->imported_at->format('d/m/Y') }}
                        @endif
                        @if ($entity->synced_at)
                            · al día desde el {{ $entity->synced_at->format('d/m/Y') }}
                        @endif
                    </p>

                    @if ($entity->description)
                        <p class="mt-1 line-clamp-2 text-[11px] leading-relaxed text-slate-400">
                            {{ $entity->description }}
                        </p>
                    @endif

                    {{-- Su récord, de un vistazo --}}

                    <div class="mt-2 grid grid-cols-3 gap-1.5 sm:grid-cols-6">
                        @foreach ([
                            ['Torneos', $summary['tournaments'] ?? 0, 'text-slate-200'],
                            ['Títulos', $summary['championships'] ?? 0, 'text-amber-300'],
                            ['Ganadas', $summary['wins'] ?? 0, 'text-emerald-300'],
                            ['Perdidas', $summary['losses'] ?? 0, 'text-rose-300'],
                            ['Empates', $summary['draws'] ?? 0, 'text-slate-400'],
                            ['Trofeos', $palmares['trophies'] ?? 0, 'text-violet-300'],
                        ] as [$label, $cifra, $tono])
                            <div class="rounded-lg bg-slate-950 px-2 py-1.5 text-center">
                                <p class="font-mono text-[16px] font-black leading-none {{ $tono }}">{{ $cifra }}</p>
                                <p class="text-[8px] uppercase tracking-wider text-slate-600">{{ $label }}</p>
                            </div>
                        @endforeach
                    </div>

                    <p class="mt-1.5 flex flex-wrap items-center gap-2 text-[10px] text-slate-500">
                        @if (($summary['matches'] ?? 0) > 0)
                            <span>
                                <span class="font-mono font-black text-slate-300">{{ $summary['win_rate'] }}%</span>
                                de {{ $summary['matches'] }} enfrentamientos
                            </span>
                            <span class="text-slate-700">·</span>
                        @endif

                        <span>mejor racha
                            <span class="font-mono font-black text-emerald-300">{{ $streaks['best_win_streak'] ?? 0 }}</span>
                        </span>

                        <span class="text-slate-700">·</span>

                        <span>peor
                            <span class="font-mono font-black text-rose-300">{{ $streaks['worst_loss_streak'] ?? 0 }}</span>
                        </span>

                        @if ($summary['best_result'] ?? null)
                            <span class="text-slate-700">·</span>
                            <span>mejor resultado
                                <span class="font-black text-slate-300">{{ $summary['best_result'] }}</span>
                            </span>
                        @endif
                    </p>
                </div>

                {{-- Traer de la Biblioteca --}}

                <div class="flex shrink-0 flex-col gap-1.5">
                    @if ($entity->source_entity_id)
                        <button type="button" @click="loadDiff()"
                            class="rounded-lg border border-sky-500/40 px-2.5 py-1.5 text-[10px] font-black text-sky-300 transition hover:bg-sky-500/20">
                            ↻ traer de la Biblioteca
                        </button>

                        <a href="{{ route('entities.show', $entity->source_entity_id) }}"
                            class="rounded-lg border border-slate-800 px-2.5 py-1.5 text-center text-[10px] font-black text-slate-400 transition hover:border-slate-600 hover:text-slate-100">
                            ver el original →
                        </a>
                    @else
                        <p class="max-w-[10rem] rounded-lg border border-dashed border-slate-700 px-2 py-1.5 text-[9px] leading-relaxed text-slate-600">
                            Creado a mano dentro del Universo: no hay Biblioteca de la que traer nada.
                        </p>
                    @endif

                    <a href="{{ route('universes.entities.head-to-head', [$universe, $entity]) }}"
                        class="rounded-lg border border-slate-800 px-2.5 py-1.5 text-center text-[10px] font-black text-slate-400 transition hover:border-slate-600 hover:text-slate-100">
                        cara a cara
                    </a>
                </div>
            </div>
        </div>


        @include('universes.entities.partials.sync-dialog')


        {{-- ============ LAS PESTAÑAS ============ --}}

        <div class="mb-3 flex flex-wrap gap-1.5">
            @foreach ($pestanas as $clave => [$icono, $label])
                <button type="button" @click="tab = '{{ $clave }}'"
                    class="rounded-xl border px-3 py-1.5 text-[11px] font-black transition"
                    :class="tab === '{{ $clave }}'
                        ? 'border-emerald-500/50 bg-emerald-500/10 text-emerald-300'
                        : 'border-slate-800 bg-slate-900/50 text-slate-400 hover:border-slate-700'">
                    <span class="mr-1">{{ $icono }}</span>{{ $label }}
                </button>
            @endforeach
        </div>


        @include('universes.entities.partials.tab-about')
        @include('universes.entities.partials.tab-games')
        @include('universes.entities.partials.tab-palmares')
        @include('universes.entities.partials.tab-rivals')
        @include('universes.entities.partials.tab-history')

    </div>

</x-universe-layout>
