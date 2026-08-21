<x-universe-layout :universe="$universe">

    <x-slot name="header">
        {{ $entity->display_label }} vs {{ $rival->display_label }}
    </x-slot>


    <div class="mb-5">

        <a href="{{ route('universes.entities.show', [$universe, $entity]) }}"
            class="text-xs font-black text-slate-400 hover:text-violet-600">
            ← {{ $entity->display_label }}
        </a>

    </div>


    {{-- CARA A CARA --}}

    <section
        class="overflow-hidden rounded-3xl border border-slate-200 bg-gradient-to-br from-slate-950 via-indigo-950 to-violet-950 p-7">

        <p class="text-center text-[10px] font-black uppercase tracking-[0.22em] text-violet-300">
            Cara a cara · {{ $universe->name }}
        </p>


        <div class="mt-6 grid items-center gap-4 sm:grid-cols-[minmax(0,1fr)_auto_minmax(0,1fr)]">

            @foreach ([[$entity, $comparison['left_wins']], [null, null], [$rival, $comparison['right_wins']]] as $index => [$side, $wins])

                @if ($side === null)
                    <div class="text-center">

                        <p class="text-2xl font-black text-slate-500">VS</p>

                        <div class="mt-3 rounded-2xl bg-white/10 px-4 py-3">

                            <p class="text-2xl font-black text-white tabular-nums">
                                {{ $comparison['total'] }}
                            </p>

                            <p class="text-[9px] font-black uppercase tracking-wider text-slate-400">
                                encuentros
                            </p>

                        </div>

                        @if ($comparison['draws'] > 0)
                            <p class="mt-2 text-[10px] font-bold text-amber-300">
                                {{ $comparison['draws'] }} empates
                            </p>
                        @endif

                    </div>
                @else
                    <div class="text-center">

                        <div
                            class="mx-auto flex h-24 w-24 items-center justify-center overflow-hidden rounded-3xl bg-white/10 text-4xl text-violet-200 ring-2 ring-white/20">

                            @if ($side->image_url)
                                <img src="{{ $side->image_url }}" alt="{{ $side->display_label }}"
                                    class="h-full w-full object-cover">
                            @else
                                ✦
                            @endif

                        </div>

                        <p class="mt-3 truncate text-lg font-black text-white">
                            {{ $side->display_label }}
                        </p>

                        <p class="mt-2 text-4xl font-black text-emerald-400 tabular-nums">
                            {{ $wins }}
                        </p>

                        <p class="text-[9px] font-black uppercase tracking-wider text-slate-400">
                            victorias
                        </p>

                    </div>
                @endif

            @endforeach

        </div>

    </section>


    {{-- ENFRENTAMIENTOS --}}

    <section class="mt-6 rounded-3xl border border-slate-200 bg-white p-6">

        <p class="text-xs font-black uppercase tracking-[0.18em] text-violet-600">
            Cronología
        </p>

        <h2 class="mt-2 text-2xl font-black text-slate-900">
            ⚔ Enfrentamientos
        </h2>


        @if ($comparison['matches']->isEmpty())
            <p class="mt-5 rounded-2xl border border-dashed border-slate-200 p-8 text-center text-sm text-slate-400">
                Estas dos entidades no se han enfrentado nunca en este Universo.
            </p>
        @else

            <div class="mt-5 space-y-2">

                @foreach ($comparison['matches'] as $match)
                    <div class="rounded-2xl bg-slate-50 p-4">

                        <div class="flex flex-wrap items-center justify-between gap-2">

                            <p class="text-[10px] font-black uppercase tracking-wider text-slate-400">
                                {{ $match->tournamentInstance?->name ?? 'Competición' }}
                                @if ($match->label)
                                    · {{ $match->label }}
                                @endif
                            </p>

                            <p class="text-[10px] text-slate-400">
                                {{ $match->completed_at?->format('d/m/Y') ?? '—' }}
                            </p>

                        </div>


                        <div class="mt-3 grid grid-cols-[minmax(0,1fr)_auto_minmax(0,1fr)] items-center gap-3">

                            <p
                                class="truncate text-right text-xs {{ (int) $match->winner_universe_entity_id === (int) $entity->id ? 'font-black text-slate-900' : 'font-bold text-slate-500' }}">
                                {{ $match->participant_a_name }}
                            </p>

                            <span class="rounded-lg bg-slate-900 px-3 py-1.5 text-xs font-black text-white tabular-nums">
                                {{ $match->score_a ?? '—' }} · {{ $match->score_b ?? '—' }}
                            </span>

                            <p
                                class="truncate text-xs {{ (int) $match->winner_universe_entity_id === (int) $rival->id ? 'font-black text-slate-900' : 'font-bold text-slate-500' }}">
                                {{ $match->participant_b_name }}
                            </p>

                        </div>

                    </div>
                @endforeach

            </div>
        @endif

    </section>

</x-universe-layout>
