<x-universe-layout :universe="$universe">

    <x-slot name="header">Clasificación</x-slot>


    <div>
        <p class="text-xs font-black uppercase tracking-wider text-violet-600">
            {{ $universe->name }} · Clasificación
        </p>

        <h2 class="mt-2 text-3xl font-black text-slate-900">Clasificación del Universo</h2>

        <p class="mt-2 max-w-2xl text-slate-500">
            Se calcula a partir de lo jugado aquí. Es propia de este Universo:
            la misma entidad puede ser #1 aquí y #18 en otro.
        </p>
    </div>


    {{-- FILTRO --}}

    <form method="GET" class="mt-6 grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 md:grid-cols-3">

        <select name="season"
            class="rounded-xl border-slate-300 bg-white text-sm text-slate-900 focus:border-violet-400 focus:ring-violet-400">

            <option value="">Clasificación histórica</option>

            @foreach ($seasons as $season)
                <option value="{{ $season->id }}" @selected($seasonId === $season->id)>
                    Solo Temporada {{ $season->number }} · {{ $season->name }}
                </option>
            @endforeach

        </select>

        <button class="rounded-xl bg-slate-950 px-4 py-3 text-sm font-black text-white">Aplicar</button>

    </form>


    @if ($ranking->isEmpty())

        <div class="mt-8 rounded-3xl border border-dashed border-slate-300 bg-white p-12 text-center">

            <div class="text-5xl">📊</div>

            <h3 class="mt-4 text-xl font-black text-slate-900">Todavía no hay clasificación</h3>

            <p class="mx-auto mt-2 max-w-lg text-sm text-slate-500">
                Aparecerá en cuanto termine la primera competición de este Universo.
            </p>

        </div>
    @else

        {{-- PODIO --}}

        @php
            $podium = $ranking->take(3)->values();

            /* Orden visual: 2º, 1º, 3º — el campeón al centro y elevado. */
            $order = $podium->count() >= 3
                ? [$podium[1], $podium[0], $podium[2]]
                : [];
        @endphp

        @if ($order)
            <section class="mt-6 grid gap-3 sm:grid-cols-3">

                @foreach ($order as $slot)
                    <article
                        class="rounded-3xl border p-6 text-center
                            {{ $slot->position === 1
                                ? 'border-violet-300 bg-gradient-to-br from-violet-50 to-white sm:-mt-3'
                                : 'border-slate-200 bg-white' }}">

                        <div
                            class="mx-auto flex items-center justify-center overflow-hidden rounded-2xl bg-violet-100 text-violet-400
                                {{ $slot->position === 1 ? 'h-24 w-24 text-4xl ring-4 ring-violet-500/20' : 'h-20 w-20 text-3xl' }}">

                            @if ($slot->entity->image_url)
                                <img src="{{ $slot->entity->image_url }}" alt="{{ $slot->entity->display_label }}"
                                    class="h-full w-full object-cover">
                            @else
                                ✦
                            @endif

                        </div>

                        <p class="mt-3 text-2xl">
                            {{ $slot->position === 1 ? '🥇' : ($slot->position === 2 ? '🥈' : '🥉') }}
                        </p>

                        <p class="mt-1 truncate text-sm font-black text-slate-900">
                            {{ $slot->entity->display_label }}
                        </p>

                        <p class="mt-2 text-3xl font-black text-violet-600 tabular-nums">{{ $slot->points }}</p>
                        <p class="text-[9px] font-black uppercase tracking-wider text-slate-400">puntos</p>

                    </article>
                @endforeach

            </section>
        @endif


        {{-- TABLA --}}

        <section class="mt-6 overflow-hidden rounded-3xl border border-slate-200 bg-white">

            <div class="overflow-x-auto">

                <table class="w-full min-w-max text-left text-sm">

                    <thead>
                        <tr class="border-b border-slate-200 text-[9px] font-black uppercase tracking-wider text-slate-400">
                            <th class="px-5 py-3">#</th>
                            <th class="px-3 py-3">Entidad</th>
                            <th class="px-2 py-3 text-center">Torneos</th>
                            <th class="px-2 py-3 text-center">🏆</th>
                            <th class="px-2 py-3 text-center">G</th>
                            <th class="px-2 py-3 text-center">E</th>
                            <th class="px-2 py-3 text-center">P</th>
                            <th class="px-2 py-3 text-center">%</th>
                            <th class="px-5 py-3 text-center">Puntos</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($ranking as $row)
                            <tr class="border-b border-slate-100 transition hover:bg-violet-50/40">

                                <td class="px-5 py-3">
                                    <span
                                        class="inline-flex h-7 w-7 items-center justify-center rounded-lg text-[11px] font-black
                                            {{ $row->position <= 3 ? 'bg-violet-600 text-white' : 'bg-slate-100 text-slate-500' }}">
                                        {{ $row->position }}
                                    </span>
                                </td>

                                <td class="px-3 py-3">
                                    <a href="{{ route('universes.entities.show', [$universe, $row->entity]) }}"
                                        class="group flex items-center gap-3">

                                        <div class="flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-violet-100 text-violet-500">
                                            @if ($row->entity->image_url)
                                                <img src="{{ $row->entity->image_url }}"
                                                    alt="{{ $row->entity->display_label }}" class="h-full w-full object-cover">
                                            @else
                                                <span class="text-xs">✦</span>
                                            @endif
                                        </div>

                                        <div class="min-w-0">
                                            <p class="truncate text-xs font-black text-slate-900 group-hover:text-violet-600">
                                                {{ $row->entity->display_label }}
                                            </p>
                                            <p class="truncate text-[10px] text-slate-400">
                                                {{ $row->entity->entity_type_name }}
                                            </p>
                                        </div>

                                    </a>
                                </td>

                                <td class="px-2 py-3 text-center text-xs tabular-nums">{{ $row->tournaments }}</td>

                                <td class="px-2 py-3 text-center text-xs font-black tabular-nums {{ $row->titles > 0 ? 'text-violet-600' : 'text-slate-400' }}">
                                    {{ $row->titles }}
                                </td>

                                <td class="px-2 py-3 text-center text-xs tabular-nums text-emerald-600">{{ $row->wins }}</td>
                                <td class="px-2 py-3 text-center text-xs tabular-nums text-slate-500">{{ $row->draws }}</td>
                                <td class="px-2 py-3 text-center text-xs tabular-nums text-red-500">{{ $row->losses }}</td>

                                <td class="px-2 py-3 text-center text-xs tabular-nums text-slate-500">
                                    {{ $row->win_rate !== null ? $row->win_rate . '%' : '—' }}
                                </td>

                                <td class="px-5 py-3 text-center text-sm font-black tabular-nums text-slate-900">
                                    {{ $row->points }}
                                </td>

                            </tr>
                        @endforeach
                    </tbody>

                </table>

            </div>

        </section>
    @endif


    {{-- SISTEMA DE PUNTOS --}}

    @can('update', $universe)
        <section class="mt-6 rounded-3xl border border-slate-200 bg-white p-6">

            <p class="text-xs font-black uppercase tracking-[0.18em] text-violet-600">Competencia</p>

            <h3 class="mt-2 text-xl font-black text-slate-900">Sistema de puntos</h3>

            <p class="mt-2 max-w-2xl text-sm text-slate-500">
                Define cómo se puntúa en este Universo. La clasificación se recalcula
                sola: no hay nada que sincronizar.
            </p>


            <form method="POST" action="{{ route('universes.ranking.points', $universe) }}" class="mt-5">

                @csrf
                @method('PUT')

                <div class="grid gap-3 sm:grid-cols-3 lg:grid-cols-5">

                    @foreach ([
        'points_champion' => 'Por campeonato',
        'points_win' => 'Por victoria',
        'points_draw' => 'Por empate',
        'points_loss' => 'Por derrota',
        'points_participation' => 'Por participar',
    ] as $key => $label)
                        <div>
                            <label class="text-[9px] font-black uppercase tracking-wider text-slate-500">
                                {{ $label }}
                            </label>

                            <input type="number" name="{{ $key }}" min="0" max="1000"
                                value="{{ old($key, $settings[$key] ?? 0) }}"
                                class="mt-1.5 w-full rounded-xl border-slate-300 text-sm focus:border-violet-400 focus:ring-violet-400">
                        </div>
                    @endforeach

                </div>

                <button type="submit"
                    class="mt-4 rounded-xl bg-violet-600 px-5 py-2.5 text-xs font-black text-white">
                    Guardar puntuación
                </button>

            </form>

        </section>
    @endcan

</x-universe-layout>
