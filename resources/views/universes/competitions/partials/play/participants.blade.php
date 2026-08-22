{{-- ETAPA 1 · Presentación del torneo --}}

<div class="mx-auto max-w-[1500px] px-5 py-8">

    <div class="text-center">

        <p class="text-xs font-black uppercase tracking-[0.25em] text-violet-400">
            {{ $universe->name }}
        </p>

        <h2 class="mt-3 text-4xl font-black tracking-tight text-white">
            {{ $competition->name }}
        </h2>

        <div class="mt-5 flex flex-wrap items-center justify-center gap-3">

            <span class="rounded-full border border-slate-800 bg-slate-900 px-4 py-2 text-xs font-black text-slate-300">
                {{ $definition['icon'] ?? '🎲' }} {{ $definition['name'] }}
            </span>

            <span class="rounded-full border border-slate-800 bg-slate-900 px-4 py-2 text-xs font-black text-slate-300">
                {{ $participants->count() }} competidores
            </span>

            @foreach ($phaseBlocks as $block)
                <span class="rounded-full border border-slate-800 bg-slate-900 px-4 py-2 text-xs font-black text-slate-300">
                    {{ $block['phase']->node_name }}
                </span>
            @endforeach

        </div>

        <p class="mx-auto mt-5 max-w-2xl text-sm leading-relaxed text-slate-400">
            {{ $definition['win_condition'] ?? '' }}
        </p>

    </div>


    {{-- PARRILLA DE COMPETIDORES --}}

    <div class="mt-10 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5">

        @foreach ($participants as $participant)

            @php
                $entity = $participant->universeEntity;
                $stats = $entity?->gameStats->firstWhere('game_key', $competition->game_key);
                $position = $entity ? $ranking->get($entity->id) : null;
            @endphp

            <div
                class="group overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/60 transition hover:border-violet-500/50 hover:bg-slate-900">

                {{-- RETRATO --}}

                <div class="relative aspect-[4/5] overflow-hidden bg-gradient-to-br from-slate-800 to-slate-900">

                    @if ($entity?->image_url)
                        <img src="{{ $entity->image_url }}" alt="{{ $participant->name }}"
                            class="h-full w-full object-cover transition duration-300 group-hover:scale-105">
                    @else
                        <div class="flex h-full w-full items-center justify-center text-5xl opacity-30">✦</div>
                    @endif

                    <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-slate-950 via-slate-950/80 to-transparent p-3">

                        <p class="truncate text-sm font-black text-white">
                            {{ $participant->name }}
                        </p>

                        <p class="truncate text-[10px] text-slate-400">
                            {{ $participant->entity_type_name ?? 'Competidor' }}
                        </p>

                    </div>

                    {{-- Seed --}}
                    <span
                        class="absolute left-2 top-2 rounded-lg bg-slate-950/80 px-2 py-1 font-mono text-[10px] font-black text-slate-300">
                        #{{ $participant->seed }}
                    </span>

                    {{-- Posición en el Universo --}}
                    @if ($position)
                        <span
                            class="absolute right-2 top-2 rounded-lg bg-violet-500/90 px-2 py-1 text-[10px] font-black text-white">
                            ▲ {{ $position->points }} pts
                        </span>
                    @endif

                </div>


                {{-- CAPACIDADES --}}

                @if ($stats)
                    <div class="space-y-1 px-3 py-2.5">
                        @foreach ($stats->display_stats as $stat)
                            <div class="flex items-center justify-between gap-2">
                                <span class="truncate text-[10px] text-slate-500">{{ $stat['label'] }}</span>
                                <span class="shrink-0 font-mono text-[11px] font-black text-emerald-400">
                                    {{ rtrim(rtrim(number_format((float) $stat['value'], 2, '.', ''), '0'), '.') }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                @endif


                {{-- PALMARÉS COMPACTO --}}

                @php
                    $trophies = $entity?->trophyAwards()->count() ?? 0;
                @endphp

                @if ($trophies > 0)
                    <div class="border-t border-slate-800 px-3 py-2">
                        <span class="text-[10px] font-black text-amber-400">
                            🏆 {{ $trophies }} {{ $trophies === 1 ? 'trofeo' : 'trofeos' }}
                        </span>
                    </div>
                @endif

            </div>
        @endforeach

    </div>


    {{-- CONTINUAR --}}

    <div class="mt-10 flex justify-center">

        @if ($competition->isDraft() && !$readonly)

            <button type="button" @click="startAndOpen()" :disabled="loading"
                class="rounded-2xl bg-violet-500 px-10 py-4 text-sm font-black text-white shadow-xl shadow-violet-900/40 transition hover:bg-violet-400 disabled:opacity-50">
                <span x-show="!loading">Comenzar competición →</span>
                <span x-show="loading" x-cloak>Repartiendo competidores…</span>
            </button>
        @else

            <button type="button" @click="stage = 2"
                class="rounded-2xl bg-violet-500 px-10 py-4 text-sm font-black text-white shadow-xl shadow-violet-900/40 transition hover:bg-violet-400">
                Continuar →
            </button>
        @endif

    </div>

</div>
