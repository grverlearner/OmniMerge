@php
    /*
     * Mi perfil.
     *
     * Lo que habia era el ajuste de cuenta que trae Breeze, ampliado: nombre,
     * correo, contraseña y borrar cuenta, en claro. Correcto y sin contestar
     * las dos preguntas que uno se hace de verdad aqui:
     *
     *   · ¿como me ven los demas?
     *   · ¿que se ve de lo mio?
     *
     * La primera se contesta enseñando la tarjeta tal y como sale en la
     * comunidad, y con el enlace a la pagina de verdad. La segunda, contando de
     * cada tipo cuantas cosas hay y cuantas son publicas — porque la visibilidad
     * se decide pieza a pieza en otra pantalla, y hasta ahora se podia poner el
     * perfil en publico sin tener ni idea de que quedaba a la vista.
     *
     * Ver docs/md/76-Perfil.md
     */

    $usuario = $user;

    $totalPublicas = collect($loQueSeVe)->sum('publicas');
    $totalCosas = collect($loQueSeVe)->sum('total');
@endphp

<x-app-layout surface="dark">

    <x-slot name="header">Mi perfil</x-slot>

    <div class="mx-auto max-w-5xl space-y-3">

        {{-- ===================================================== --}}
        {{-- CÓMO TE VEN --}}
        {{-- ===================================================== --}}

        <section class="overflow-hidden rounded-2xl border border-violet-500/25 bg-slate-900/50">

            <header class="flex flex-wrap items-center gap-2 border-b border-violet-500/15 px-4 py-2.5">

                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-violet-500/15 text-violet-300">
                    <x-omni-icon name="usuario" size="h-4 w-4" />
                </span>

                <div class="min-w-0 flex-1">
                    <h2 class="text-[13px] font-black text-white">Cómo te ven</h2>
                    <p class="text-[10px] text-slate-500">
                        Esto es lo que aparece de ti en la comunidad, junto a cada cosa que
                        publicas.
                    </p>
                </div>

                <a href="{{ route('profiles.show', $usuario->username) }}"
                    class="shrink-0 rounded-xl border border-violet-500/40 bg-violet-500/10 px-3 py-2 text-[11px] font-black text-violet-300 transition hover:bg-violet-500 hover:text-white">
                    Ver mi perfil completo
                </a>
            </header>

            <div class="flex flex-wrap items-center gap-4 p-4">

                <span class="h-20 w-20 shrink-0 overflow-hidden rounded-2xl border-2 border-violet-500/50 bg-slate-950">
                    @if ($usuario->avatar_url)
                        <img src="{{ $usuario->avatar_url }}" alt="" class="h-full w-full object-cover">
                    @else
                        <span class="flex h-full w-full items-center justify-center text-[22px] font-black text-violet-300">
                            {{ $usuario->initials }}
                        </span>
                    @endif
                </span>

                <div class="min-w-0 flex-1">
                    <p class="font-mono text-[11px] font-black text-violet-400">&#64;{{ $usuario->username }}</p>
                    <p class="text-[17px] font-black leading-tight text-white">{{ $usuario->name }}</p>

                    @if ($usuario->headline)
                        <p class="text-[12px] font-bold text-slate-300">{{ $usuario->headline }}</p>
                    @else
                        <p class="text-[11px] italic text-slate-600">Sin presentación corta</p>
                    @endif

                    @if ($usuario->bio)
                        <p class="mt-1 max-w-2xl text-[11px] leading-relaxed text-slate-400">{{ $usuario->bio }}</p>
                    @endif
                </div>

                {{-- El estado del perfil, con su consecuencia dicha --}}
                <div class="shrink-0 rounded-xl border px-3 py-2"
                    style="border-color: {{ $usuario->isPublicProfile() ? '#34d39944' : '#fbbf2444' }};
                           background-color: {{ $usuario->isPublicProfile() ? '#34d39912' : '#fbbf2412' }}">

                    <p class="text-[10px] font-black uppercase tracking-wider"
                        style="color: {{ $usuario->isPublicProfile() ? '#34d399' : '#fbbf24' }}">
                        {{ $usuario->isPublicProfile() ? 'Perfil público' : 'Perfil privado' }}
                    </p>

                    <p class="mt-0.5 max-w-[200px] text-[10px] leading-3 text-slate-500">
                        @if ($usuario->isPublicProfile())
                            Cualquiera con cuenta puede abrir tu página.
                        @else
                            Tu página no la puede abrir nadie más que tú.
                        @endif
                    </p>
                </div>
            </div>
        </section>


        {{-- ===================================================== --}}
        {{-- QUÉ SE VE DE TI --}}
        {{-- ===================================================== --}}

        @include('profile.partials.que-se-ve')


        {{-- ===================================================== --}}
        {{-- QUIÉN ERES --}}
        {{-- ===================================================== --}}

        @include('profile.partials.update-profile-information-form')


        {{-- ===================================================== --}}
        {{-- CONTRASEÑA --}}
        {{-- ===================================================== --}}

        @include('profile.partials.update-password-form')


        {{-- ===================================================== --}}
        {{-- LA CUENTA --}}
        {{-- ===================================================== --}}

        @include('profile.partials.la-cuenta')


        {{-- ===================================================== --}}
        {{-- BORRAR --}}
        {{-- ===================================================== --}}

        @include('profile.partials.delete-user-form')
    </div>

</x-app-layout>
