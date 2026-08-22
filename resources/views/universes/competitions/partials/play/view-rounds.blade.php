@php
    /*
     * Todos contra todos: jornadas navegables a la izquierda, clasificación
     * viva a la derecha. Nada de bracket: no hay eliminación que dibujar.
     */
    $rounds = $block['rounds']->sortKeys();
    $standings = $block['standings']->sortBy('position');
@endphp

<div class="grid gap-5 p-5 xl:grid-cols-[1fr_340px]">

    {{-- JORNADAS --}}

    <div x-data="{ round: {{ $rounds->keys()->first() ?? 0 }} }">

        @if ($rounds->count() > 1)
            <div class="mb-4 flex flex-wrap gap-1.5">
                @foreach ($rounds as $number => $matches)
                    <button type="button" @click="round = {{ $number }}"
                        :class="round === {{ $number }}
                            ? 'bg-violet-500 text-white'
                            : 'bg-slate-800 text-slate-400 hover:text-slate-200'"
                        class="rounded-lg px-3 py-1.5 text-[11px] font-black transition">
                        J{{ $number }}
                    </button>
                @endforeach
            </div>
        @endif

        @foreach ($rounds as $number => $matches)
            <div x-show="round === {{ $number }}" x-cloak>

                <p class="mb-3 text-[10px] font-black uppercase tracking-wider text-slate-400">
                    Jornada {{ $number }}
                </p>

                <div class="grid gap-2.5 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($matches as $match)
                        @include('universes.competitions.partials.play.match-chip', ['match' => $match])
                    @endforeach
                </div>

            </div>
        @endforeach

    </div>


    {{-- CLASIFICACIÓN --}}

    <div class="rounded-2xl border border-slate-800 bg-slate-950/50 p-4">

        <p class="mb-3 text-[10px] font-black uppercase tracking-wider text-slate-400">
            Clasificación
        </p>

        <div class="space-y-1">

            @foreach ($standings as $row)
                <div class="flex items-center gap-2.5 rounded-lg px-2 py-1.5 {{ $row->status === 'ADVANCED' ? 'bg-emerald-500/10' : '' }}">

                    <span class="w-5 shrink-0 text-center font-mono text-[11px] font-black text-slate-500">
                        {{ $row->position ?? '—' }}
                    </span>

                    <div class="h-6 w-6 shrink-0 overflow-hidden rounded bg-slate-800">
                        @if ($row->universeEntity?->image_url)
                            <img src="{{ $row->universeEntity->image_url }}" alt="" class="h-full w-full object-cover">
                        @endif
                    </div>

                    <span class="min-w-0 flex-1 truncate text-[11px] font-semibold text-slate-300">
                        {{ $row->participant_name }}
                    </span>

                    <span class="shrink-0 font-mono text-[10px] text-slate-500">
                        {{ $row->wins }}-{{ $row->draws }}-{{ $row->losses }}
                    </span>

                    <span class="w-7 shrink-0 text-right font-mono text-xs font-black text-violet-300">
                        {{ $row->points }}
                    </span>

                </div>
            @endforeach

        </div>

    </div>

</div>
