@extends('layouts.hub')


@section('title', 'Perfil y cuenta')


@section('content')

    {{-- ========================================================= --}}
    {{-- CONTENEDOR --}}
    {{-- ========================================================= --}}

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

            <a href="{{ route('hub') }}"
                class="
                    inline-flex
                    items-center
                    gap-2
                    text-sm
                    font-bold
                    text-slate-500
                    transition
                    hover:text-white
                ">
                ← Centro OmniMerge
            </a>

        </div>


        {{-- ===================================================== --}}
        {{-- HERO DEL PERFIL --}}
        {{-- ===================================================== --}}

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
                shadow-indigo-950/40
                sm:p-10
            ">

            <div
                class="
                    absolute
                    -right-16
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
                    md:items-end
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


                        @if ($user->profile_visibility === 'PUBLIC')
                            <span
                                class="
                                    rounded-full
                                    border
                                    border-emerald-300/30
                                    bg-emerald-400/15
                                    px-3
                                    py-1
                                    text-[10px]
                                    font-black
                                    uppercase
                                    tracking-wider
                                    text-emerald-100
                                ">
                                Perfil público
                            </span>
                        @else
                            <span
                                class="
                                    rounded-full
                                    border
                                    border-white/20
                                    bg-white/10
                                    px-3
                                    py-1
                                    text-[10px]
                                    font-black
                                    uppercase
                                    tracking-wider
                                    text-white/70
                                ">
                                Perfil privado
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
                                max-w-2xl
                                text-base
                                font-semibold
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
                                class="
                                    transition
                                    hover:text-white
                                ">
                                🔗
                                {{ parse_url($user->website, PHP_URL_HOST) ?: $user->website }}
                            </a>
                        @endif


                        <span>
                            ◷ Miembro desde
                            {{ $user->created_at?->format('m/Y') }}
                        </span>

                    </div>

                </div>


                <a href="{{ route('community.creators.show', $user->username) }}"
                    class="
                        inline-flex
                        shrink-0
                        items-center
                        justify-center
                        rounded-xl
                        border
                        border-white/20
                        bg-white/10
                        px-5
                        py-3
                        text-sm
                        font-black
                        text-white
                        backdrop-blur
                        transition
                        hover:bg-white/20
                    ">
                    Ver mi perfil comunitario →
                </a>

            </div>

        </section>


        {{-- ===================================================== --}}
        {{-- ESTADÍSTICAS --}}
        {{-- ===================================================== --}}

        <section
            class="
                mt-6
                grid
                gap-4
                sm:grid-cols-2
                lg:grid-cols-4
            ">

            @foreach ([
            [
                'label' => 'Entidades',
                'value' => $user->entities_count,
                'icon' => '✦',
            ],
            [
                'label' => 'Atributos',
                'value' => $user->attributes_count,
                'icon' => '☷',
            ],
            [
                'label' => 'Colecciones',
                'value' => $user->collections_count,
                'icon' => '▤',
            ],
            [
                'label' => 'Último acceso',
                'value' => $user->last_login_at ? $user->last_login_at->diffForHumans() : 'Primera sesión',
                'icon' => '◷',
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
                            gap-4
                        ">

                        <div
                            class="
                                flex
                                h-11
                                w-11
                                shrink-0
                                items-center
                                justify-center
                                rounded-xl
                                bg-indigo-500/10
                                text-lg
                                text-indigo-300
                            ">
                            {{ $stat['icon'] }}
                        </div>


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
                                    mt-1
                                    text-lg
                                    font-black
                                    text-white
                                ">
                                {{ $stat['value'] }}
                            </p>

                        </div>

                    </div>

                </article>
            @endforeach

        </section>


        {{-- ===================================================== --}}
        {{-- MENSAJE GUARDADO --}}
        {{-- ===================================================== --}}

        @if (session('status') === 'profile-updated')
            <div x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(
                () => show = false,
                4000
            )"
                class="
                    mt-6
                    rounded-2xl
                    border
                    border-emerald-500/20
                    bg-emerald-500/10
                    px-5
                    py-4
                    text-sm
                    font-semibold
                    text-emerald-300
                ">
                ✓ Tu perfil fue actualizado correctamente.
            </div>
        @endif


        {{-- ===================================================== --}}
        {{-- CONFIGURACIÓN --}}
        {{-- ===================================================== --}}

        <div
            class="
                mt-8
                grid
                items-start
                gap-6
                xl:grid-cols-[minmax(0,1fr)_380px]
            ">

            {{-- PERFIL --}}
            <div>

                @include('profile.partials.update-profile-information-form')

            </div>


            {{-- SEGURIDAD --}}
            <div class="space-y-6">

                @include('profile.partials.update-password-form')


                @include('profile.partials.delete-user-form')

            </div>

        </div>

    </div>

@endsection
