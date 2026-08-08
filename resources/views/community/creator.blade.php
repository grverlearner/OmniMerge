@extends('layouts.hub')


@section('title', $user->name)


@section('content')

    <div
        class="
        mx-auto
        max-w-7xl
        px-5
        py-10
        sm:px-6
        lg:px-8
        lg:py-14
    ">

        {{-- VOLVER --}}
        <div class="mb-6">

            <a href="{{ route('community.index') }}"
                class="
                text-sm
                font-bold
                text-slate-500
                transition
                hover:text-white
            ">
                ← Volver a Comunidad
            </a>

        </div>


        {{-- ========================================================= --}}
        {{-- PERFIL --}}
        {{-- ========================================================= --}}

        <section
            class="
            relative
            overflow-hidden
            rounded-[32px]
            border
            border-white/10
            bg-gradient-to-br
            from-indigo-600
            via-violet-600
            to-fuchsia-600
            p-7
            shadow-2xl
            shadow-indigo-950/30
            sm:p-10
        ">

            <div
                class="
                absolute
                -right-20
                -top-20
                h-72
                w-72
                rounded-full
                bg-white/10
                blur-3xl
            ">
            </div>


            <div
                class="
                relative
                flex
                flex-col
                gap-7
                md:flex-row
                md:items-center
            ">

                <x-user-avatar :user="$user" size="2xl" ring />


                <div class="
                    min-w-0
                    flex-1
                ">

                    <div
                        class="
                        flex
                        flex-wrap
                        items-center
                        gap-3
                    ">

                        <h1
                            class="
                            text-3xl
                            font-black
                            tracking-tight
                            text-white
                            sm:text-4xl
                        ">
                            {{ $user->name }}
                        </h1>


                        @if ($isOwner)
                            <span
                                class="
                                rounded-full
                                bg-white/10
                                px-3
                                py-1
                                text-[10px]
                                font-black
                                uppercase
                                tracking-wider
                                text-white
                            ">
                                Este eres tú
                            </span>
                        @endif

                    </div>


                    <p
                        class="
                        mt-2
                        font-semibold
                        text-indigo-100
                    ">
                        {{ '@' . $user->username }}
                    </p>


                    @if ($user->headline)
                        <p
                            class="
                            mt-4
                            text-lg
                            font-bold
                            text-white
                        ">
                            {{ $user->headline }}
                        </p>
                    @endif


                    @if ($user->bio)
                        <p
                            class="
                            mt-3
                            max-w-3xl
                            text-sm
                            leading-7
                            text-indigo-100/80
                        ">
                            {{ $user->bio }}
                        </p>
                    @endif


                    <div
                        class="
                        mt-5
                        flex
                        flex-wrap
                        gap-x-5
                        gap-y-2
                        text-xs
                        font-semibold
                        text-indigo-100/70
                    ">

                        @if ($user->location)
                            <span>
                                📍 {{ $user->location }}
                            </span>
                        @endif


                        @if ($user->website)
                            <a href="{{ $user->website }}" target="_blank" rel="noopener noreferrer nofollow"
                                class="hover:text-white">
                                🔗 Sitio web
                            </a>
                        @endif


                        <span>
                            Miembro desde
                            {{ $user->created_at?->format('m/Y') }}
                        </span>

                    </div>

                </div>


                @if ($isOwner)
                    <a href="{{ route('profile.edit') }}"
                        class="
                        shrink-0
                        rounded-xl
                        bg-white
                        px-5
                        py-3
                        text-sm
                        font-black
                        text-indigo-700
                    ">
                        Editar mi perfil
                    </a>
                @endif

            </div>

        </section>


        {{-- ========================================================= --}}
        {{-- ESTADÍSTICAS --}}
        {{-- ========================================================= --}}

        <section class="
            mt-6
            grid
            gap-4
            sm:grid-cols-3
        ">

            @foreach ([
            [
                'label' => 'Entidades públicas',
                'value' => $user->public_entities_count,
                'icon' => '✦',
            ],
            [
                'label' => 'Colecciones públicas',
                'value' => $user->public_collections_count,
                'icon' => '▤',
            ],
            [
                'label' => 'Atributos públicos',
                'value' => $user->public_attributes_count,
                'icon' => '☷',
            ],
        ] as $stat)
                <article
                    class="
                    rounded-2xl
                    border
                    border-white/10
                    bg-white/[0.03]
                    p-5
                ">

                    <div
                        class="
                        flex
                        items-center
                        justify-between
                    ">

                        <div>

                            <p
                                class="
                                text-xs
                                font-bold
                                uppercase
                                tracking-wider
                                text-slate-500
                            ">
                                {{ $stat['label'] }}
                            </p>

                            <p
                                class="
                                mt-2
                                text-3xl
                                font-black
                                text-white
                            ">
                                {{ $stat['value'] }}
                            </p>

                        </div>


                        <span
                            class="
                            text-2xl
                            text-indigo-300
                        ">
                            {{ $stat['icon'] }}
                        </span>

                    </div>

                </article>
            @endforeach

        </section>


        {{-- ========================================================= --}}
        {{-- ENTIDADES --}}
        {{-- ========================================================= --}}

        <section class="mt-12">

            <div
                class="
                flex
                items-end
                justify-between
                gap-4
            ">

                <div>

                    <p
                        class="
                        text-xs
                        font-black
                        uppercase
                        tracking-wider
                        text-indigo-400
                    ">
                        Creaciones
                    </p>

                    <h2
                        class="
                        mt-2
                        text-2xl
                        font-black
                        text-white
                    ">
                        Entidades públicas
                    </h2>

                </div>

            </div>


            @if ($entities->isNotEmpty())

                <div
                    class="
                    mt-6
                    grid
                    gap-6
                    md:grid-cols-2
                    xl:grid-cols-3
                ">

                    @foreach ($entities as $entity)
                        @include('community.partials.entity-card', [
                            'entity' => $entity,
                        ])
                    @endforeach

                </div>
            @else
                <div
                    class="
                    mt-6
                    rounded-2xl
                    border
                    border-dashed
                    border-white/10
                    p-10
                    text-center
                    text-sm
                    text-slate-500
                ">
                    Este creador todavía no tiene
                    entidades públicas.
                </div>

            @endif

        </section>


        {{-- ========================================================= --}}
        {{-- COLECCIONES --}}
        {{-- ========================================================= --}}

        <section class="mt-14">

            <h2 class="
                text-2xl
                font-black
                text-white
            ">
                Colecciones públicas
            </h2>


            @if ($collections->isNotEmpty())

                <div
                    class="
                    mt-6
                    grid
                    gap-6
                    md:grid-cols-2
                    xl:grid-cols-3
                ">

                    @foreach ($collections as $collection)
                        @include('community.partials.collection-card', [
                            'collection' => $collection,
                        ])
                    @endforeach

                </div>
            @else
                <div
                    class="
                    mt-6
                    rounded-2xl
                    border
                    border-dashed
                    border-white/10
                    p-10
                    text-center
                    text-sm
                    text-slate-500
                ">
                    Este creador todavía no tiene
                    colecciones públicas.
                </div>

            @endif

        </section>


        {{-- ========================================================= --}}
        {{-- ATRIBUTOS --}}
        {{-- ========================================================= --}}

        <section class="mt-14">

            <h2 class="
                text-2xl
                font-black
                text-white
            ">
                Atributos públicos
            </h2>


            @if ($attributes->isNotEmpty())

                <div
                    class="
                    mt-6
                    grid
                    gap-6
                    md:grid-cols-2
                    xl:grid-cols-3
                ">

                    @foreach ($attributes as $attribute)
                        @include('community.partials.attribute-card', [
                            'attribute' => $attribute,
                        ])
                    @endforeach

                </div>
            @else
                <div
                    class="
                    mt-6
                    rounded-2xl
                    border
                    border-dashed
                    border-white/10
                    p-10
                    text-center
                    text-sm
                    text-slate-500
                ">
                    Este creador todavía no tiene
                    atributos públicos.
                </div>

            @endif

        </section>


    </div>

@endsection
