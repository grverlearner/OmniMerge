<x-universe-layout :universe="$universe">

    <x-slot name="header">
        {{ $entity->display_label }}
    </x-slot>


    <div class="mb-5">

        <a href="{{ route('universes.entities.index', $universe) }}"
            class="text-xs font-black text-slate-400 hover:text-violet-600">
            ← Entidades
        </a>

    </div>


    {{-- ============================================ --}}
    {{-- CABECERA --}}
    {{-- ============================================ --}}

    <section
        class="overflow-hidden rounded-3xl border border-slate-200 bg-gradient-to-br from-slate-950 via-indigo-950 to-violet-950">

        <div class="grid items-center gap-6 p-7 sm:grid-cols-[auto_minmax(0,1fr)]">

            <div
                class="mx-auto flex h-28 w-28 items-center justify-center overflow-hidden rounded-3xl bg-white/10 text-5xl text-violet-200 ring-2 ring-white/20 sm:mx-0">

                @if ($entity->image_url)
                    <img src="{{ $entity->image_url }}" alt="{{ $entity->display_label }}"
                        class="h-full w-full object-cover">
                @else
                    ✦
                @endif

            </div>


            <div class="text-center sm:text-left">

                <div class="flex flex-wrap items-center justify-center gap-2 sm:justify-start">

                    <span class="rounded-full bg-white/10 px-3 py-1 font-mono text-[9px] font-black text-white/70">
                        {{ $entity->code }}
                    </span>

                    <span class="rounded-full bg-violet-500 px-3 py-1 text-[9px] font-black uppercase text-white">
                        Entidad de este Universo
                    </span>

                    @if ($rank)
                        <a href="{{ route('universes.ranking', $universe) }}"
                            class="rounded-full bg-white px-3 py-1 text-[9px] font-black uppercase text-slate-900">
                            📊 #{{ $rank->position }} · {{ $rank->points }} pts
                        </a>
                    @endif

                </div>


                <h1 class="mt-3 text-3xl font-black tracking-tight text-white">
                    {{ $entity->display_label }}
                </h1>

                <p class="mt-1 text-xs text-slate-400">
                    {{ $entity->entity_type_name ?: 'Sin tipo' }}

                    @if ($entity->imported_at)
                        · importada {{ $entity->imported_at->format('d/m/Y') }}
                    @endif
                </p>


                <div class="mt-5 flex flex-wrap justify-center gap-2 sm:justify-start">

                    @foreach ([
        ['🏆', 'Títulos', $summary['championships']],
        ['⚔', 'Torneos', $summary['tournaments']],
        ['✅', 'Victorias', $summary['wins']],
        ['❌', 'Derrotas', $summary['losses']],
    ] as [$icon, $label, $value])
                        <div class="rounded-2xl bg-white/10 px-4 py-2.5 backdrop-blur">

                            <p class="text-[9px] font-black uppercase tracking-wider text-slate-400">
                                {{ $icon }} {{ $label }}
                            </p>

                            <p class="mt-0.5 text-xl font-black text-white">
                                {{ $value }}
                            </p>

                        </div>
                    @endforeach


                    <div class="rounded-2xl bg-violet-500 px-4 py-2.5">

                        <p class="text-[9px] font-black uppercase tracking-wider text-violet-100">
                            📈 Win rate
                        </p>

                        <p class="mt-0.5 text-xl font-black text-white">
                            {{ $summary['win_rate'] !== null ? $summary['win_rate'] . '%' : '—' }}
                        </p>

                    </div>

                </div>

            </div>

        </div>

    </section>


    {{-- Procedencia + independencia --}}

    <section class="mt-5 flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-slate-200 bg-white p-5">

        <div>
            <p class="text-xs font-black text-slate-800">
                Copia independiente
            </p>

            <p class="mt-1 text-xs text-slate-500">
                @if ($entity->sourceEntity)
                    Importada de <strong>{{ $entity->sourceEntity->name }}</strong> de tu Biblioteca.
                    Editar allí no cambia nada aquí.
                @else
                    Su entidad de origen ya no está en la Biblioteca. Esta copia sigue intacta.
                @endif
            </p>
        </div>


        @if ($entity->sourceEntity)
            <a href="{{ route('entities.show', $entity->sourceEntity) }}"
                class="shrink-0 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-black text-slate-700">
                Ver origen en Biblioteca →
            </a>
        @endif

    </section>


    <div class="mt-6 grid gap-6 lg:grid-cols-[minmax(0,1fr)_360px]">

        <div class="space-y-6">

            {{-- ============================================ --}}
            {{-- DATOS COPIADOS --}}
            {{-- ============================================ --}}

            @if ($entity->description)
                <section class="rounded-3xl border border-slate-200 bg-white p-6">

                    <p class="text-xs font-black uppercase tracking-[0.18em] text-violet-600">
                        Descripción
                    </p>

                    <p class="mt-3 whitespace-pre-line text-sm leading-7 text-slate-600">
                        {{ $entity->description }}
                    </p>

                </section>
            @endif


            {{-- ATRIBUTOS --}}

            <section class="rounded-3xl border border-slate-200 bg-white p-6">

                <p class="text-xs font-black uppercase tracking-[0.18em] text-violet-600">
                    Copiados al importar
                </p>

                <h2 class="mt-2 text-2xl font-black text-slate-900">
                    ☷ Atributos
                </h2>

                @php
                    $attributes = $entity->attribute_snapshot ?? [];
                @endphp

                @if (empty($attributes))
                    <p class="mt-5 rounded-2xl border border-dashed border-slate-200 p-6 text-center text-sm text-slate-400">
                        Esta entidad no tenía atributos visibles al importarla.
                    </p>
                @else
                    <div class="mt-5 grid gap-2 sm:grid-cols-2">

                        @foreach ($attributes as $attribute)
                            <div
                                class="flex items-center justify-between gap-3 rounded-xl {{ ($attribute['featured'] ?? false) ? 'bg-violet-50' : 'bg-slate-50' }} px-4 py-3">

                                <span class="truncate text-[11px] font-bold {{ ($attribute['featured'] ?? false) ? 'text-violet-700' : 'text-slate-500' }}">
                                    {{ ($attribute['featured'] ?? false) ? '★ ' : '' }}{{ $attribute['name'] }}
                                </span>

                                <span class="shrink-0 text-xs font-black text-slate-800">
                                    {{ $attribute['display'] }}
                                </span>

                            </div>
                        @endforeach

                    </div>
                @endif

            </section>


            {{-- JUEGOS (Fase 11) --}}

            @include('universes.entities.partials.game-stats')


            {{-- PALMARES Y PROGRESION (Fase 12) --}}

            @include('universes.entities.partials.palmares')


            {{-- VERSIONES --}}

            @php
                $versions = $entity->version_snapshot ?? [];
            @endphp

            @if (!empty($versions))
                <section class="rounded-3xl border border-slate-200 bg-white p-6">

                    <p class="text-xs font-black uppercase tracking-[0.18em] text-violet-600">
                        Copiadas al importar
                    </p>

                    <h2 class="mt-2 text-2xl font-black text-slate-900">
                        ◈ Versiones
                    </h2>

                    <div class="mt-5 space-y-2">

                        @foreach ($versions as $version)
                            <div class="flex items-center gap-3 rounded-2xl {{ ($version['is_base'] ?? false) ? 'bg-violet-50' : 'bg-slate-50' }} p-3">

                                <div class="min-w-0 flex-1">

                                    <p class="truncate text-sm font-black text-slate-800">
                                        {{ ($version['is_base'] ?? false) ? '★ ' : '' }}{{ $version['name'] }}
                                    </p>

                                    @if (!empty($version['description']))
                                        <p class="mt-0.5 line-clamp-1 text-[10px] text-slate-400">
                                            {{ $version['description'] }}
                                        </p>
                                    @endif

                                </div>

                                @if ($version['is_base'] ?? false)
                                    <span class="shrink-0 rounded-full bg-violet-600 px-2.5 py-1 text-[9px] font-black uppercase text-white">
                                        En uso
                                    </span>
                                @endif

                            </div>
                        @endforeach

                    </div>

                </section>
            @endif


            {{-- ============================================ --}}
            {{-- RENDIMIENTO POR FORMATO --}}
            {{-- ============================================ --}}

            @if ($byEngine->isNotEmpty())
                <section class="rounded-3xl border border-slate-200 bg-white p-6">

                    <p class="text-xs font-black uppercase tracking-[0.18em] text-violet-600">
                        Dentro de este Universo
                    </p>

                    <h2 class="mt-2 text-2xl font-black text-slate-900">
                        Rendimiento por formato
                    </h2>


                    <div class="mt-5 grid gap-4 sm:grid-cols-3">

                        @foreach ($byEngine as $row)
                            @php
                                $label = match ($row->phase_type) {
                                    'SINGLE_ELIMINATION' => 'Eliminación directa',
                                    'ROUND_ROBIN' => 'Todos contra todos',
                                    'GROUP_STAGE' => 'Fase de grupos',
                                    default => $row->phase_type,
                                };

                                $icon = match ($row->phase_type) {
                                    'SINGLE_ELIMINATION' => '🏆',
                                    'ROUND_ROBIN' => '🔄',
                                    'GROUP_STAGE' => '▦',
                                    default => '◆',
                                };

                                $decided = (int) $row->wins + (int) $row->losses + (int) $row->draws;
                                $rate = $decided > 0 ? round($row->wins / $decided * 100) : null;
                            @endphp

                            <article class="rounded-2xl border border-slate-200 p-5">

                                <p class="text-[10px] font-black uppercase tracking-wider text-violet-600">
                                    {{ $icon }} {{ $label }}
                                </p>

                                <div class="mt-4 flex items-baseline gap-2">
                                    <span class="text-3xl font-black text-slate-900">
                                        {{ $rate !== null ? $rate . '%' : '—' }}
                                    </span>
                                    <span class="text-[10px] font-bold text-slate-400">victorias</span>
                                </div>

                                @if ($rate !== null)
                                    <div class="mt-3 h-1.5 overflow-hidden rounded-full bg-slate-100">
                                        <div class="h-full rounded-full bg-violet-500" style="width: {{ $rate }}%"></div>
                                    </div>
                                @endif

                                <div class="mt-4 flex justify-between border-t border-slate-100 pt-3 text-[11px] font-bold text-slate-500">
                                    <span>{{ $row->phases }} {{ $row->phases == 1 ? 'fase' : 'fases' }}</span>
                                    <span>
                                        <span class="text-emerald-600">{{ $row->wins }}G</span>
                                        · <span class="text-red-500">{{ $row->losses }}P</span>
                                    </span>
                                </div>

                            </article>
                        @endforeach

                    </div>

                </section>
            @endif


            {{-- HISTORIAL --}}

            <section class="rounded-3xl border border-slate-200 bg-white p-6">

                <p class="text-xs font-black uppercase tracking-[0.18em] text-violet-600">
                    Cronología
                </p>

                <h2 class="mt-2 text-2xl font-black text-slate-900">
                    ◷ Competiciones
                </h2>


                @if ($history->isEmpty())
                    <p class="mt-5 rounded-2xl border border-dashed border-slate-200 p-6 text-center text-sm text-slate-400">
                        Todavía no ha competido en este Universo.
                    </p>
                @else
                    @foreach ($historyBySeason as $seasonNumber => $participations)

                        <p class="mt-5 text-[10px] font-black uppercase tracking-wider text-violet-600">
                            {{ $seasonNumber > 0 ? 'Temporada ' . $seasonNumber : 'Sin temporada' }}
                        </p>

                        <div class="mt-2 space-y-2">

                            @foreach ($participations as $participation)
                            @php
                                $instance = $participation->tournamentInstance;

                                $badge = match ($participation->outcome) {
                                    'CHAMPION' => ['🏆 Campeón', 'bg-violet-600 text-white'],
                                    'ELIMINATED' => ['Eliminado', 'bg-slate-200 text-slate-600'],
                                    'QUALIFIED' => ['Clasificado', 'bg-emerald-100 text-emerald-700'],
                                    'IN_PROGRESS' => ['En curso', 'bg-amber-100 text-amber-700'],
                                    default => ['Participante', 'bg-slate-100 text-slate-500'],
                                };
                            @endphp

                            <a href="{{ $instance ? route('universes.competitions.show', [$universe, $instance]) : '#' }}"
                                class="flex items-center gap-3 rounded-2xl bg-slate-50 p-3 transition hover:bg-violet-50">

                                <div class="min-w-0 flex-1">

                                    <p class="truncate text-sm font-black text-slate-800">
                                        {{ $instance?->name ?? 'Competición' }}
                                    </p>

                                    <p class="mt-0.5 text-[10px] text-slate-400">
                                        {{ $instance?->started_at?->format('d/m/Y') ?? 'sin fecha' }}
                                        · {{ $participation->wins }}G-{{ $participation->losses }}P
                                    </p>

                                </div>

                                <span class="{{ $badge[1] }} shrink-0 rounded-full px-2.5 py-1 text-[9px] font-black uppercase">
                                    {{ $badge[0] }}
                                </span>

                            </a>
                            @endforeach

                        </div>
                    @endforeach
                @endif

            </section>

        </div>


        {{-- ============================================ --}}
        {{-- LATERAL --}}
        {{-- ============================================ --}}

        <div class="space-y-6">

            {{-- DESTACADOS --}}

            <section class="rounded-3xl border border-slate-200 bg-white p-6">

                <p class="text-xs font-black uppercase tracking-[0.18em] text-violet-600">
                    Destacados
                </p>

                <div class="mt-4 space-y-3">

                    @foreach ([
        ['Mejor resultado', $summary['best_result'] ?? '—'],
        ['Mejor racha', $streaks['best_win_streak'] . ' victorias'],
        ['Peor racha', $streaks['worst_loss_streak'] . ' derrotas'],
    ] as [$label, $value])
                        <div class="rounded-2xl bg-slate-50 p-4">

                            <p class="text-[9px] font-black uppercase tracking-wider text-slate-400">
                                {{ $label }}
                            </p>

                            <p class="mt-1 text-base font-black text-slate-900">
                                {{ $value }}
                            </p>

                        </div>
                    @endforeach

                </div>

            </section>


            {{-- RIVALES --}}

            <section class="rounded-3xl border border-slate-200 bg-white p-6">

                <p class="text-xs font-black uppercase tracking-[0.18em] text-violet-600">
                    Cara a cara
                </p>

                <h2 class="mt-2 text-2xl font-black text-slate-900">
                    ⚔ Rivales
                </h2>


                @if ($rivals->isEmpty())
                    <p class="mt-5 rounded-2xl border border-dashed border-slate-200 p-6 text-center text-sm text-slate-400">
                        Todavía no se ha enfrentado a nadie.
                    </p>
                @else
                    <div class="mt-5 space-y-2">

                        @foreach ($rivals as $rival)
                            <a href="{{ route('universes.entities.head-to-head', [$universe, $entity, 'rival' => $rival['entity_id']]) }}"
                                class="group flex items-center gap-3 rounded-2xl bg-slate-50 p-3 transition hover:bg-violet-50">

                                <div class="min-w-0 flex-1">

                                    <p class="truncate text-xs font-black text-slate-800">
                                        {{ $rival['name'] }}
                                    </p>

                                    <p class="mt-0.5 text-[10px] text-slate-400">
                                        {{ $rival['matches'] }} enfrentamientos
                                    </p>

                                </div>

                                <span class="shrink-0 text-[11px] font-black tabular-nums">
                                    <span class="text-emerald-600">{{ $rival['wins'] }}</span>
                                    <span class="text-slate-300">-</span>
                                    <span class="text-red-500">{{ $rival['losses'] }}</span>
                                </span>

                                <span class="text-violet-500 transition group-hover:translate-x-0.5">→</span>

                            </a>
                        @endforeach

                    </div>
                @endif

            </section>


            {{-- AJUSTES --}}

            @can('update', $universe)
                <section class="rounded-3xl border border-slate-200 bg-white p-6">

                    <p class="text-xs font-black uppercase tracking-[0.18em] text-violet-600">
                        Dentro del Universo
                    </p>

                    <form method="POST"
                        action="{{ route('universes.entities.update', [$universe, $entity]) }}"
                        class="mt-4 space-y-4">

                        @csrf
                        @method('PUT')

                        <div>
                            <label class="text-[9px] font-black uppercase tracking-wider text-slate-500">
                                Alias en este Universo
                            </label>

                            <input type="text" name="display_name"
                                value="{{ $entity->display_name }}"
                                placeholder="{{ $entity->name }}"
                                class="mt-1.5 w-full rounded-xl border-slate-300 text-sm focus:border-violet-400 focus:ring-violet-400">
                        </div>

                        <div>
                            <label class="text-[9px] font-black uppercase tracking-wider text-slate-500">
                                Estado
                            </label>

                            <select name="status"
                                class="mt-1.5 w-full rounded-xl border-slate-300 text-sm focus:border-violet-400 focus:ring-violet-400">

                                @foreach (\App\Models\UniverseEntity::statuses() as $value => $label)
                                    <option value="{{ $value }}" @selected($entity->status === $value)>
                                        {{ $label }}
                                    </option>
                                @endforeach

                            </select>
                        </div>

                        <div>
                            <label class="text-[9px] font-black uppercase tracking-wider text-slate-500">
                                Notas
                            </label>

                            <textarea name="notes" rows="3"
                                class="mt-1.5 w-full rounded-xl border-slate-300 text-sm focus:border-violet-400 focus:ring-violet-400">{{ $entity->notes }}</textarea>
                        </div>

                        <button type="submit"
                            class="w-full rounded-xl bg-violet-600 px-4 py-2.5 text-xs font-black text-white">
                            Guardar
                        </button>

                    </form>


                    <form method="POST"
                        action="{{ route('universes.entities.destroy', [$universe, $entity]) }}"
                        onsubmit="return confirm('¿Quitar esta entidad del Universo? Tu Biblioteca no se toca.');"
                        class="mt-3 border-t border-slate-100 pt-3">

                        @csrf
                        @method('DELETE')

                        <button type="submit" class="text-[11px] font-black text-red-500">
                            Quitar del Universo
                        </button>

                    </form>

                </section>
            @endcan

        </div>

    </div>

</x-universe-layout>
