@php

    $statusClass = match ($universe->status) {
        'ACTIVE' => 'bg-emerald-100 text-emerald-700',

        'DRAFT' => 'bg-violet-100 text-violet-700',

        'ARCHIVED' => 'bg-slate-200 text-slate-600',

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
        hover:border-violet-300
        hover:shadow-xl
        hover:shadow-violet-950/5
    ">

    <a href="{{ route('universes.show', $universe) }}"
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
                via-indigo-950
                to-violet-950
            ">

            @if ($universe->image_url)
                <img src="{{ $universe->image_url }}" alt="{{ $universe->name }}"
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
                            bg-violet-400/10
                            text-5xl
                            ring-1
                            ring-violet-300/10
                        ">
                        🌌
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
                    {{ $universe->code }}
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
                    {{ $universe->name }}
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
                    {{ $universe->status_label }}
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
                {{ $universe->description ?: 'Universo sin descripción.' }}
            </p>


            <div
                class="
                    mt-5
                    grid
                    grid-cols-3
                    gap-2
                ">

                @foreach ([['Competidores', $universe->entities_count ?? 0], ['Temporadas', $universe->seasons_count ?? 0], ['Torneos', $universe->universe_tournaments_count ?? 0]] as [$label, $value])
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
                            {{ $label }}
                        </p>


                        <p
                            class="
                                mt-1
                                text-sm
                                font-black
                                text-slate-700
                            ">
                            {{ $value }}
                        </p>

                    </div>
                @endforeach

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
                    Creado {{ $universe->created_at->diffForHumans() }}
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

        </div>

    </a>

</article>
