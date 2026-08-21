@php
    /*
     * Tarjeta de enfrentamiento: las dos Entidades cara a cara.
     *
     * $match      TournamentInstanceMatch
     * $compact    bool (por defecto false)
     */

    $compact = $compact ?? false;

    $winnerA = $match->winner_key && $match->winner_key === $match->participant_a_key;
    $winnerB = $match->winner_key && $match->winner_key === $match->participant_b_key;

    $played = $match->status === 'COMPLETED';
@endphp


<div
    class="
        rounded-2xl
        border
        {{ $played ? 'border-slate-200 bg-white' : 'border-dashed border-slate-200 bg-slate-50/60' }}
        p-3
    ">

    @if ($match->label || $match->group_label)
        <p
            class="
                mb-2
                truncate
                text-[9px]
                font-black
                uppercase
                tracking-wider
                text-slate-400
            ">
            {{ $match->group_label ?? $match->label }}
        </p>
    @endif


    <div
        class="
            grid
            grid-cols-[minmax(0,1fr)_auto_minmax(0,1fr)]
            items-center
            gap-2
        ">

        {{-- LADO A --}}

        <div
            class="
                flex
                min-w-0
                items-center
                justify-end
                gap-2
                text-right
            ">

            <div class="min-w-0">
                <p
                    class="
                        truncate
                        text-[11px]
                        {{ $winnerA ? 'font-black text-slate-900' : 'font-bold text-slate-500' }}
                    ">
                    {{ $match->participant_a_name ?? 'BYE' }}
                </p>
            </div>

            <div
                class="
                    flex
                    h-8
                    w-8
                    shrink-0
                    items-center
                    justify-center
                    overflow-hidden
                    rounded-lg
                    {{ $winnerA ? 'ring-2 ring-violet-500' : '' }}
                    bg-violet-100
                    text-violet-500
                ">

                @if ($match->participantAEntity?->image_url)
                    <img src="{{ $match->participantAEntity->image_url }}"
                        alt="{{ $match->participant_a_name }}"
                        class="h-full w-full object-cover">
                @else
                    <span class="text-xs">✦</span>
                @endif

            </div>

        </div>


        {{-- MARCADOR --}}

        <div
            class="
                shrink-0
                rounded-lg
                {{ $played ? 'bg-slate-900 text-white' : 'bg-slate-200 text-slate-500' }}
                px-2.5
                py-1.5
                text-center
                text-xs
                font-black
                tabular-nums
            ">
            {{ $played ? ($match->score_a ?? '—') . ' · ' . ($match->score_b ?? '—') : 'vs' }}
        </div>


        {{-- LADO B --}}

        <div
            class="
                flex
                min-w-0
                items-center
                gap-2
            ">

            <div
                class="
                    flex
                    h-8
                    w-8
                    shrink-0
                    items-center
                    justify-center
                    overflow-hidden
                    rounded-lg
                    {{ $winnerB ? 'ring-2 ring-violet-500' : '' }}
                    bg-violet-100
                    text-violet-500
                ">

                @if ($match->participantBEntity?->image_url)
                    <img src="{{ $match->participantBEntity->image_url }}"
                        alt="{{ $match->participant_b_name }}"
                        class="h-full w-full object-cover">
                @else
                    <span class="text-xs">✦</span>
                @endif

            </div>

            <div class="min-w-0">
                <p
                    class="
                        truncate
                        text-[11px]
                        {{ $winnerB ? 'font-black text-slate-900' : 'font-bold text-slate-500' }}
                    ">
                    {{ $match->participant_b_name ?? 'BYE' }}
                </p>
            </div>

        </div>

    </div>

</div>
