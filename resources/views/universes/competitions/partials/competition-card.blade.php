@php

    /*
     * El color comunica de un vistazo si esto es algo que está pasando,
     * algo que ya pasó, o algo que aún no ha empezado.
     */
    $statusClass = match ($competition->status) {
        'RUNNING' => 'bg-emerald-100 text-emerald-700',
        'PAUSED' => 'bg-amber-100 text-amber-700',
        'COMPLETED' => 'bg-slate-900 text-white',
        'CANCELLED' => 'bg-red-100 text-red-700',
        default => 'bg-violet-100 text-violet-700',
    };

@endphp


<article
    class="
        group
        rounded-2xl
        border
        border-slate-200
        bg-white
        p-5
        transition
        hover:border-violet-300
        hover:shadow-lg
        hover:shadow-violet-950/5
    ">

    <a href="{{ route('universes.competitions.show', [$universe, $competition]) }}"
        class="block">

        <div
            class="
                flex
                flex-wrap
                items-center
                gap-2
            ">

            <span
                class="
                    rounded-full
                    bg-slate-100
                    px-2.5
                    py-1
                    font-mono
                    text-[9px]
                    font-black
                    text-slate-500
                ">
                {{ $competition->code }}
            </span>


            <span
                class="
                    {{ $statusClass }}

                    rounded-full
                    px-2.5
                    py-1
                    text-[9px]
                    font-black
                    uppercase
                    tracking-wider
                ">
                {{ $competition->status_label }}
            </span>


            @if ($competition->season)
                <span
                    class="
                        rounded-full
                        bg-violet-50
                        px-2.5
                        py-1
                        text-[9px]
                        font-black
                        uppercase
                        text-violet-700
                    ">
                    ◷ Temporada {{ $competition->season->number }}
                </span>
            @endif

        </div>


        <p
            class="
                mt-3
                text-lg
                font-black
                text-slate-900
            ">
            {{ $competition->name }}
        </p>


        <p
            class="
                mt-1
                text-xs
                text-slate-500
            ">
            {{ $competition->universeTournament?->name ?? 'Torneo no disponible' }}
        </p>


        <div
            class="
                mt-4
                grid
                grid-cols-3
                gap-2
            ">

            <div class="rounded-xl bg-slate-50 p-3">

                <p class="text-[9px] font-black uppercase text-slate-400">
                    Competidores
                </p>

                <p class="mt-1 text-sm font-black text-slate-700">
                    {{ $competition->participant_count }}
                </p>

            </div>


            <div class="rounded-xl bg-slate-50 p-3">

                <p class="text-[9px] font-black uppercase text-slate-400">
                    Motor
                </p>

                <p class="mt-1 truncate text-[11px] font-black text-slate-700">
                    {{ $competition->runtime_status_label ?? '—' }}
                </p>

            </div>


            <div class="rounded-xl bg-slate-50 p-3">

                <p class="text-[9px] font-black uppercase text-slate-400">
                    Inicio
                </p>

                <p class="mt-1 text-[11px] font-black text-slate-700">
                    {{ $competition->started_at?->format('d/m/Y') ?? 'Sin iniciar' }}
                </p>

            </div>

        </div>


        <div
            class="
                mt-4
                flex
                items-center
                justify-between
                border-t
                border-slate-100
                pt-3
            ">

            <span class="text-xs text-slate-400">
                {{ $competition->created_at->diffForHumans() }}
            </span>


            <span
                class="
                    text-xs
                    font-black
                    text-violet-600
                    transition
                    group-hover:translate-x-1
                ">
                Abrir →
            </span>

        </div>

    </a>

</article>
