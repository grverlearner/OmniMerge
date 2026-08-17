@php

    $statusClass = match ($template->status) {
        'ACTIVE' => 'bg-emerald-100 text-emerald-700',

        'DRAFT' => 'bg-amber-100 text-amber-700',

        'ARCHIVED' => 'bg-slate-200 text-slate-600',

        default => 'bg-slate-100 text-slate-600',
    };

    $visibilityClass = match ($template->visibility) {
        'PUBLIC' => 'bg-violet-100 text-violet-700',

        'PRIVATE' => 'bg-slate-100 text-slate-600',

        'UNLISTED' => 'bg-cyan-100 text-cyan-700',

        default => 'bg-slate-100 text-slate-600',
    };

@endphp


<article
    class="
        group
        overflow-hidden
        rounded-3xl
        border
        border-slate-200
        bg-white
        shadow-sm
        transition
        duration-200
        hover:-translate-y-1
        hover:border-amber-300
        hover:shadow-xl
        hover:shadow-amber-950/5
    ">

    <a href="{{ route('tournaments.templates.show', $template) }}"
        class="
            block
        ">

        <div
            class="
                relative
                h-44
                overflow-hidden
                bg-gradient-to-br
                from-slate-950
                via-slate-900
                to-amber-950
            ">

            @if ($template->image_url)
                <img src="{{ $template->image_url }}" alt="{{ $template->name }}"
                    class="
                        h-full
                        w-full
                        object-cover
                        transition
                        duration-500
                        group-hover:scale-105
                    ">


                <div
                    class="
                        absolute
                        inset-0
                        bg-gradient-to-t
                        from-slate-950/80
                        via-transparent
                        to-transparent
                    ">
                </div>
            @else
                <div
                    class="
                        absolute
                        inset-0
                        flex
                        items-center
                        justify-center
                    ">

                    <div
                        class="
                            flex
                            h-20
                            w-20
                            items-center
                            justify-center
                            rounded-3xl
                            bg-amber-400/10
                            text-5xl
                            ring-1
                            ring-amber-300/10
                        ">
                        🏆
                    </div>

                </div>
            @endif


            <div
                class="
                    absolute
                    left-4
                    top-4
                ">

                <span
                    class="
                        rounded-full
                        bg-slate-950/70
                        px-3
                        py-1.5
                        font-mono
                        text-[9px]
                        font-black
                        tracking-wider
                        text-white/70
                        backdrop-blur
                    ">
                    {{ $template->code }}
                </span>

            </div>


            <div
                class="
                    absolute
                    bottom-4
                    left-4
                    right-4
                ">

                <p
                    class="
                        truncate
                        text-lg
                        font-black
                        text-white
                    ">
                    {{ $template->name }}
                </p>

            </div>

        </div>


        <div class="
                p-5
            ">

            <div
                class="
                    flex
                    flex-wrap
                    gap-2
                ">

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
                    {{ $template->status_label }}
                </span>


                <span
                    class="
                        {{ $visibilityClass }}

                        rounded-full
                        px-2.5
                        py-1
                        text-[9px]
                        font-black
                        uppercase
                        tracking-wider
                    ">
                    {{ $template->visibility_label }}
                </span>

            </div>


            <p
                class="
                    mt-4
                    line-clamp-2
                    min-h-[40px]
                    text-sm
                    leading-5
                    text-slate-500
                ">
                {{ $template->description ?: 'Plantilla competitiva sin descripción.' }}
            </p>


            <div
                class="
                    mt-5
                    grid
                    grid-cols-2
                    gap-2
                ">

                <div
                    class="
                        rounded-xl
                        bg-slate-50
                        p-3
                    ">

                    <p
                        class="
                            text-[9px]
                            font-black
                            uppercase
                            text-slate-400
                        ">
                        Participantes
                    </p>


                    <p
                        class="
                            mt-1
                            text-xs
                            font-black
                            text-slate-700
                        ">
                        {{ $template->participant_range_label }}
                    </p>

                </div>


                <div
                    class="
                        rounded-xl
                        bg-slate-50
                        p-3
                    ">

                    <p
                        class="
                            text-[9px]
                            font-black
                            uppercase
                            text-slate-400
                        ">
                        Fases del grafo
                    </p>


                    <p
                        class="
                            mt-1
                            text-xs
                            font-black
                            text-slate-700
                        ">
                        {{ $template->graph_nodes_count ?? $template->graphNodes()->count() }}
                    </p>

                </div>

            </div>


            <div
                class="
                    mt-5
                    flex
                    items-center
                    justify-between
                    border-t
                    border-slate-100
                    pt-4
                ">

                <span
                    class="
                        text-xs
                        text-slate-400
                    ">
                    {{ $template->allow_byes ? 'BYE permitido' : 'Sin BYE' }}
                </span>


                <span
                    class="
                        text-xs
                        font-black
                        text-amber-600
                        transition
                        group-hover:translate-x-1
                    ">
                    Abrir →
                </span>

            </div>

        </div>

    </a>

</article>
