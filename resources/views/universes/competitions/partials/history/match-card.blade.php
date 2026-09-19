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


<div class="rounded-xl border p-2.5 {{ $played ? 'border-slate-800 bg-slate-900/60' : 'border-dashed border-slate-800 bg-slate-950' }}">

    @if ($match->label || $match->group_label)
        <p class="mb-1.5 truncate text-[9px] font-black uppercase tracking-wider text-slate-600">
            {{ $match->group_label ?? $match->label }}
        </p>
    @endif


    <div class="grid grid-cols-[minmax(0,1fr)_auto_minmax(0,1fr)] items-center gap-2">

        {{-- LADO A --}}

        <div class="flex min-w-0 items-center justify-end gap-2 text-right">

            <div class="min-w-0">
                <p class="truncate text-[11px] {{ $winnerA ? 'font-black text-white' : 'font-bold text-slate-500' }}">
                    {{ $match->participant_a_name ?? 'BYE' }}
                </p>
            </div>

            <div
                class="flex h-8 w-8 shrink-0 items-center justify-center overflow-hidden rounded-lg border bg-slate-900 text-slate-700 {{ $winnerA ? 'border-violet-500' : 'border-slate-800' }}">

                @if ($match->participant_a_face_url)
                    <img src="{{ $match->participant_a_face_url }}" alt="{{ $match->participant_a_name }}"
                        class="h-full w-full object-cover">
                @else
                    <x-omni-icon name="usuario" size="h-3.5 w-3.5" />
                @endif

            </div>

        </div>


        {{-- MARCADOR --}}

        <div
            class="shrink-0 rounded-lg px-2.5 py-1.5 text-center text-xs font-black tabular-nums {{ $played ? 'bg-violet-500/20 text-violet-200' : 'bg-slate-900 text-slate-600' }}">
            {{ $played ? ($match->score_a ?? '—') . ' · ' . ($match->score_b ?? '—') : 'vs' }}
        </div>


        {{-- LADO B --}}

        <div class="flex min-w-0 items-center gap-2">

            <div
                class="flex h-8 w-8 shrink-0 items-center justify-center overflow-hidden rounded-lg border bg-slate-900 text-slate-700 {{ $winnerB ? 'border-violet-500' : 'border-slate-800' }}">

                @if ($match->participant_b_face_url)
                    <img src="{{ $match->participant_b_face_url }}" alt="{{ $match->participant_b_name }}"
                        class="h-full w-full object-cover">
                @else
                    <x-omni-icon name="usuario" size="h-3.5 w-3.5" />
                @endif

            </div>

            <div class="min-w-0">
                <p class="truncate text-[11px] {{ $winnerB ? 'font-black text-white' : 'font-bold text-slate-500' }}">
                    {{ $match->participant_b_name ?? 'BYE' }}
                </p>
            </div>

        </div>

    </div>

</div>
