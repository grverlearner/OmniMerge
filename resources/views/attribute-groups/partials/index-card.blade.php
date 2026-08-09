@php

    $previewAttributes = $group->attributes->take(4);

    $remainingAttributes = max(0, $group->attributes_count - 4);

@endphp


<article
    class="
        group
        overflow-hidden
        rounded-2xl
        border
        border-slate-200
        bg-white
        shadow-sm
        transition
        hover:-translate-y-0.5
        hover:border-indigo-200
        hover:shadow-lg
    ">

    {{-- ========================================================= --}}
    {{-- MOSAICO --}}
    {{-- ========================================================= --}}

    <a href="{{ route('attribute-groups.show', $group) }}"
        class="
            block
            bg-slate-100
            p-3
        "
        :class="{
            'h-28': density === 'compact',
        
            'h-36': density === 'medium',
        
            'h-48': density === 'large'
        }">

        @if ($previewAttributes->isNotEmpty())

            <div
                class="
                    grid
                    h-full
                    grid-cols-2
                    gap-1.5
                ">

                @foreach ($previewAttributes as $attribute)
                    <div
                        class="
                            relative
                            overflow-hidden
                            rounded-lg
                            bg-white
                        ">

                        @if ($attribute->image_url)
                            <img src="{{ $attribute->image_url }}" alt="{{ $attribute->name }}"
                                class="
                                    h-full
                                    w-full
                                    object-cover
                                ">
                        @else
                            <div class="
                                    flex
                                    h-full
                                    items-center
                                    justify-center
                                    text-xl
                                    font-black
                                "
                                style="
                                    background-color:
                                        {{ $attribute->color ?? '#6366F1' }}20;

                                    color:
                                        {{ $attribute->color ?? '#6366F1' }};
                                ">
                                {{ $attribute->icon ?: '☷' }}
                            </div>
                        @endif


                        <div
                            class="
                                absolute
                                inset-x-0
                                bottom-0
                                bg-gradient-to-t
                                from-black/70
                                to-transparent
                                px-2
                                pb-1.5
                                pt-4
                            ">

                            <p
                                class="
                                    truncate
                                    text-[9px]
                                    font-black
                                    text-white
                                ">
                                {{ $attribute->pivot->custom_label ?: $attribute->name }}
                            </p>

                        </div>

                    </div>
                @endforeach

            </div>
        @else
            <div class="
                    flex
                    h-full
                    items-center
                    justify-center
                    rounded-xl
                    text-4xl
                    font-black
                "
                style="
                    background-color:
                        {{ $group->color ?? '#6366F1' }}15;

                    color:
                        {{ $group->color ?? '#6366F1' }};
                ">
                {{ $group->icon ?: '▥' }}
            </div>

        @endif

    </a>


    {{-- ========================================================= --}}
    {{-- INFORMACIÓN --}}
    {{-- ========================================================= --}}

    <div
        :class="{
            'p-4': density === 'compact',
        
            'p-5': density === 'medium',
        
            'p-6': density === 'large'
        }">

        <div
            class="
                flex
                items-start
                justify-between
                gap-3
            ">

            <div
                class="
                    flex
                    min-w-0
                    items-center
                    gap-3
                ">

                <div class="
                        flex
                        h-10
                        w-10
                        shrink-0
                        items-center
                        justify-center
                        rounded-xl
                        text-lg
                        font-black
                    "
                    style="
                        background-color:
                            {{ $group->color ?? '#6366F1' }}20;

                        color:
                            {{ $group->color ?? '#6366F1' }};
                    ">
                    {{ $group->icon ?: '▥' }}
                </div>


                <div class="min-w-0">

                    <a href="{{ route('attribute-groups.show', $group) }}"
                        class="
                            block
                            truncate
                            font-black
                            text-slate-900
                            hover:text-indigo-700
                        ">
                        {{ $group->name }}
                    </a>


                    <p
                        class="
                            mt-1
                            font-mono
                            text-[9px]
                            font-black
                            text-slate-400
                        ">
                        {{ $group->code }}
                    </p>

                </div>

            </div>


            <x-status-badge :status="$group->status" />

        </div>


        <p x-show="
                density !== 'compact'
            "
            class="
                mt-4
                line-clamp-2
                text-sm
                leading-6
                text-slate-500
            ">
            {{ $group->description ?: 'Sin descripción.' }}
        </p>


        @if ($group->attributes->isNotEmpty())

            <div
                class="
                    mt-4
                    flex
                    flex-wrap
                    gap-1.5
                ">

                @foreach ($group->attributes->take(3) as $attribute)
                    <span
                        class="
                            max-w-[130px]
                            truncate
                            rounded-full
                            bg-slate-100
                            px-2.5
                            py-1
                            text-[9px]
                            font-bold
                            text-slate-600
                        ">
                        {{ $attribute->pivot->custom_label ?: $attribute->name }}
                    </span>
                @endforeach


                @if ($remainingAttributes > 0)
                    <span
                        class="
                            rounded-full
                            bg-indigo-50
                            px-2.5
                            py-1
                            text-[9px]
                            font-black
                            text-indigo-600
                        ">
                        +{{ $remainingAttributes }}
                    </span>
                @endif

            </div>

        @endif


        <div
            class="
                mt-4
                grid
                grid-cols-2
                gap-3
                border-t
                border-slate-100
                pt-4
            ">

            <div>

                <p
                    class="
                        text-[9px]
                        font-black
                        uppercase
                        tracking-wider
                        text-slate-400
                    ">
                    Atributos
                </p>


                <p
                    class="
                        mt-1
                        font-black
                        text-slate-700
                    ">
                    {{ $group->attributes_count }}
                </p>

            </div>


            <div>

                <p
                    class="
                        text-[9px]
                        font-black
                        uppercase
                        tracking-wider
                        text-slate-400
                    ">
                    Presentación
                </p>


                <p
                    class="
                        mt-1
                        text-xs
                        font-black
                        text-slate-700
                    ">
                    {{ $group->layout_label }}
                </p>

            </div>

        </div>

    </div>

</article>
